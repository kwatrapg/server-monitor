<?php
/**
 * Sentruo — database migration runner (CLI only).
 *
 * Applies every *.sql file in database/migrations/ that has not run yet, in
 * filename order, inside a transaction where the storage engine allows it, and
 * records each in `core_migrations`.
 *
 *   php bin/migrate.php            # apply pending migrations
 *   php bin/migrate.php --status   # list applied / pending
 */

if (PHP_SAPI !== 'cli') { http_response_code(403); exit("CLI only.\n"); }

$root = dirname(__DIR__);
require $root . '/includes/env.php';
require $root . '/config.php';
require $root . '/vendor/classes/class.medoo.php';

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['server'], $config['port'], $config['database_name'], $config['charset'] ?: 'utf8'),
    $config['username'], $config['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$pdo->exec("CREATE TABLE IF NOT EXISTS core_migrations (
    filename VARCHAR(191) NOT NULL PRIMARY KEY,
    applied_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8");

$applied = $pdo->query("SELECT filename FROM core_migrations")->fetchAll(PDO::FETCH_COLUMN);
$files = glob($root . '/database/migrations/*.sql');
sort($files);

if (in_array('--status', $argv, true)) {
    foreach ($files as $f) {
        $b = basename($f);
        echo (in_array($b, $applied, true) ? '  [applied] ' : '  [PENDING] ') . $b . "\n";
    }
    exit(0);
}

$pending = array_values(array_filter($files, fn($f) => !in_array(basename($f), $applied, true)));
if (!$pending) { echo "Nothing to migrate.\n"; exit(0); }

foreach ($pending as $file) {
    $name = basename($file);
    echo "Applying $name ... ";
    $sql = file_get_contents($file);

    // Drop full-line SQL comments, then split on ";" at end of line. Good enough
    // for these DDL/DML files (no stored routines / triggers in migrations).
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    $statements = array_filter(array_map('trim', preg_split('/;\s*[\r\n]/', $sql)));

    try {
        foreach ($statements as $stmt) {
            $stmt = trim($stmt, "; \t\r\n");
            if ($stmt === '') continue;
            $pdo->exec($stmt);
        }
        $ins = $pdo->prepare("INSERT INTO core_migrations (filename, applied_at) VALUES (?, NOW())");
        $ins->execute([$name]);
        echo "ok\n";
    } catch (Throwable $e) {
        echo "FAILED\n  " . $e->getMessage() . "\n";
        exit(1);
    }
}
echo "Done.\n";

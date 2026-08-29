<?php
/**
 * Sentruo — first-run installer (CLI only).
 *
 *   php bin/install.php
 *
 * - Refuses to run if the database already has users (no re-install / takeover).
 * - Never prints a generated password; the admin chooses one interactively and
 *   it must satisfy the password policy.
 * - Applies the schema + seed + migrations.
 * - Writes nothing to a web-served path.
 */

if (PHP_SAPI !== 'cli') { http_response_code(403); exit("CLI only.\n"); }

$root = dirname(__DIR__);
require $root . '/includes/env.php';

fwrite(STDOUT, "Sentruo installer\n=================\n\n");

// --- sanity ------------------------------------------------------------------
foreach (['pdo_mysql', 'openssl'] as $ext) {
    if (!extension_loaded($ext)) { fwrite(STDERR, "Missing PHP extension: $ext\n"); exit(1); }
}
if (!is_file($root . '/vendor/autoload.php')) {
    fwrite(STDERR, "Run 'composer install --no-dev' first.\n"); exit(1);
}
if (!is_file($root . '/.env')) {
    fwrite(STDERR, "Create .env from .env.example first (set APP_KEY + DB_*).\n"); exit(1);
}

require $root . '/config.php';
if (($config['encryption_key'] ?? '') === '') {
    fwrite(STDERR, "APP_KEY is not set in .env. Generate one:\n  php -r \"echo bin2hex(random_bytes(32)).PHP_EOL;\"\n");
    exit(1);
}

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['server'], $config['port'], $config['database_name'], $config['charset'] ?: 'utf8'),
        $config['username'], $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Throwable $e) {
    fwrite(STDERR, "Cannot connect to the database: " . $e->getMessage() . "\n"); exit(1);
}

$hasUsers = false;
try {
    $hasUsers = (int) $pdo->query("SELECT COUNT(*) FROM core_users")->fetchColumn() > 0;
} catch (Throwable $e) { /* table not created yet */ }

if ($hasUsers) {
    fwrite(STDERR, "This database already has users — Sentruo is already installed.\n");
    fwrite(STDERR, "Use 'php bin/migrate.php' to apply pending migrations instead.\n");
    exit(1);
}

// --- schema + seed + migrations -------------------------------------------
function run_sql_file(PDO $pdo, string $file): void {
    $sql = preg_replace('/^\s*--.*$/m', '', file_get_contents($file));
    foreach (array_filter(array_map('trim', preg_split('/;\s*[\r\n]/', $sql))) as $stmt) {
        if ($stmt !== '') $pdo->exec($stmt);
    }
}
fwrite(STDOUT, "Applying schema ... ");
run_sql_file($pdo, $root . '/database/schema.sql');
fwrite(STDOUT, "ok\nApplying seed data ... ");
if (is_file($root . '/database/seed.sql')) run_sql_file($pdo, $root . '/database/seed.sql');
fwrite(STDOUT, "ok\nApplying migrations ... \n");
passthru(PHP_BINARY . ' ' . escapeshellarg($root . '/bin/migrate.php'), $rc);
if ($rc !== 0) { fwrite(STDERR, "Migration failed.\n"); exit(1); }

// --- first admin ---------------------------------------------------------
require $root . '/includes/security.php';

function prompt(string $label, bool $hidden = false): string {
    fwrite(STDOUT, $label);
    if ($hidden) { system('stty -echo'); }
    $v = trim((string) fgets(STDIN));
    if ($hidden) { system('stty echo'); fwrite(STDOUT, "\n"); }
    return $v;
}

fwrite(STDOUT, "\nCreate the administrator account:\n");
$name  = prompt("  Name:  ");
$email = strtolower(prompt("  Email: "));
while (true) {
    $pw  = prompt("  Password (>=12 chars, mixed case + digit): ", true);
    $pw2 = prompt("  Confirm password: ", true);
    if ($pw !== $pw2) { fwrite(STDERR, "  Passwords do not match.\n"); continue; }
    if (($err = sm_password_policy_error($pw)) !== null) { fwrite(STDERR, "  $err\n"); continue; }
    break;
}

$adminRole = (int) $pdo->query("SELECT id FROM core_roles ORDER BY id ASC LIMIT 1")->fetchColumn();
$stmt = $pdo->prepare(
    "INSERT INTO core_users (roleid, name, email, password, groups, theme, sidebar, layout, notes, sessionid, resetkey, lang, autorefresh, must_change_password)
     VALUES (?, ?, ?, ?, 'a:1:{i:0;s:1:\"0\";}', 'skin-blue', 'opened', '', '', '', '', 'en', 0, 0)"
);
$stmt->execute([$adminRole ?: 1, $name, $email, sm_password_hash($pw)]);

fwrite(STDOUT, "\nDone. Sign in at your configured app URL with the account you just created.\n");
fwrite(STDOUT, "Remember to set app_url in Settings and to run the cron every minute (see docs/DEPLOYMENT.md).\n");

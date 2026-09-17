<?php
/**
 * One-time migration: grants the new server-log-monitoring permissions
 * (viewServerLogs, manageLogSources, editLogAlert) to existing roles,
 * mirroring each role's current Servers access level. Safe to re-run
 * (idempotent) - roles that already have a permission are left unchanged.
 *
 * core_roles.perms is a PHP-serialized array, so this has to be done in PHP,
 * never with raw SQL string surgery on the column.
 *
 * Needed only for a database that was seeded BEFORE this feature shipped -
 * database/seed.sql already grants these to the Super Administrator role on
 * a fresh install/database/migrate.php run.
 *
 *   php bin/migrate_log_permissions.php            # apply
 *   php bin/migrate_log_permissions.php --status    # show what would change, no writes
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

$dryRun = in_array('--status', $argv, true);

$roles = $pdo->query("SELECT id, name, perms FROM core_roles")->fetchAll(PDO::FETCH_ASSOC);
if (!$roles) { echo "No roles found - nothing to do.\n"; exit(0); }

$grantIfHasAny = [
    ['viewServers'],                    'viewServerLogs',
    ['addServer', 'editServer'],        'manageLogSources',
    ['editServer'],                     'editLogAlert',
];

foreach ($roles as $role) {
    $perms = @unserialize((string) $role['perms']);
    if (!is_array($perms)) $perms = [];

    $add = [];
    for ($i = 0; $i < count($grantIfHasAny); $i += 2) {
        $mirrorOf = $grantIfHasAny[$i];
        $grant = $grantIfHasAny[$i + 1];
        foreach ($mirrorOf as $needs) {
            if (in_array($needs, $perms, true)) { $add[] = $grant; break; }
        }
    }

    if (empty($add)) {
        echo "Role '{$role['name']}' (id {$role['id']}): no Server access found, skipped.\n";
        continue;
    }

    $before = $perms;
    $after = array_values(array_unique(array_merge($perms, $add)));

    if ($after == $before) {
        echo "Role '{$role['name']}' (id {$role['id']}): already up to date, no change.\n";
        continue;
    }

    $granted = array_diff($after, $before);

    if ($dryRun) {
        echo "Role '{$role['name']}' (id {$role['id']}): WOULD grant " . implode(", ", $granted) . "\n";
        continue;
    }

    $stmt = $pdo->prepare("UPDATE core_roles SET perms = ? WHERE id = ?");
    $stmt->execute([serialize($after), $role['id']]);
    echo "Role '{$role['name']}' (id {$role['id']}): granted " . implode(", ", $granted) . "\n";
}

echo "\n" . ($dryRun ? "Dry run - no changes written." : "Done. Refresh the app (no re-login needed) to see the Server Logs nav item.") . "\n";

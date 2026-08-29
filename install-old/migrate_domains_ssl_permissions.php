<?php
/**
 * One-time migration: grants the new Domains/SSL permissions to existing roles,
 * mirroring each role's current Websites/Checks access level. Safe to re-run
 * (idempotent) - roles that already have a permission are left unchanged.
 *
 * Usage (from the server-monitor root):
 *   php install-old/migrate_domains_ssl_permissions.php
 *
 * Or via browser (must be run once by an administrator, then removed/blocked):
 *   https://yourdomain.com/install-old/migrate_domains_ssl_permissions.php
 */

$scriptpath = dirname(__DIR__);

require($scriptpath . '/includes/functions.php');
require($scriptpath . '/config.php');
spl_autoload_register('vendorClassAutoload');
spl_autoload_register('appClassAutoload');
require $scriptpath . '/vendor/autoload.php';

$database = new medoo($config);

$isCli = (php_sapi_name() === 'cli');
function out($msg, $isCli) {
    echo $isCli ? $msg . "\n" : htmlspecialchars($msg) . "<br>\n";
}

$roles = $database->select("core_roles", "*");
if (empty($roles)) { out("No roles found - nothing to do.", $isCli); exit; }

foreach ($roles as $role) {
    $perms = @unserialize($role['perms']);
    if (!is_array($perms)) $perms = [];

    $add = [];

    // View: mirror if the role can already see Websites or Checks
    if (in_array("viewWebsites", $perms) || in_array("viewChecks", $perms)) {
        $add[] = "viewDomains";
        $add[] = "viewSsl";
    }

    // Add: mirror if the role can already add Websites or Checks
    if (in_array("addWebsite", $perms) || in_array("addCheck", $perms)) {
        $add[] = "addDomain";
        $add[] = "addSsl";
    }

    // Edit: mirror if the role can already edit Websites or Checks
    if (in_array("editWebsite", $perms) || in_array("editCheck", $perms)) {
        $add[] = "editDomain";
        $add[] = "editSsl";
    }

    // Delete: mirror if the role can already delete Websites or Checks
    if (in_array("deleteWebsite", $perms) || in_array("deleteCheck", $perms)) {
        $add[] = "deleteDomain";
        $add[] = "deleteSsl";
    }

    if (empty($add)) {
        out("Role '{$role['name']}' (id {$role['id']}): no Website/Check access found, skipped.", $isCli);
        continue;
    }

    $before = $perms;
    $after = array_values(array_unique(array_merge($perms, $add)));

    if ($after == $before) {
        out("Role '{$role['name']}' (id {$role['id']}): already up to date, no change.", $isCli);
        continue;
    }

    $database->update("core_roles", ["perms" => serialize($after)], ["id" => $role['id']]);
    $added = array_diff($after, $before);
    out("Role '{$role['name']}' (id {$role['id']}): granted " . implode(", ", $added), $isCli);
}

out("\nDone. Refresh the app (no re-login needed) to see the Domains/SSL nav items.", $isCli);

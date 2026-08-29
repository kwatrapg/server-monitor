<?php
/**
 * Passive "callback" check ingest.
 *
 * External systems POST/GET here to report a check result. Authentication is a
 * per-check CSPRNG secret (app_checks.callbackkey) — previously this used the
 * check's `host` string, which is guessable (VAPT A-4).
 */

$debug = false;
error_reporting($debug ? E_ALL & ~E_NOTICE : 0);
ini_set('display_errors', $debug ? '1' : '0');

$scriptpath = __DIR__;

require($scriptpath . '/includes/functions.php');
spl_autoload_register('vendorClassAutoload');
spl_autoload_register('appClassAutoload');
require($scriptpath . '/config.php');

$database = new medoo($config);
date_default_timezone_set(getConfigValue("timezone"));
$datetime = date("Y-m-d H:i:s");

// Accept the key + status from either query string or POST body.
$key    = (string) ($_REQUEST['key'] ?? '');
$status = (string) ($_REQUEST['status'] ?? '');

if ($key === '' || !preg_match('/^[a-f0-9]{16,64}$/i', $key)) {
    http_response_code(400);
    exit("Invalid request.");
}

$check = $database->get("app_checks", "*", ["callbackkey" => $key, "type" => "callback"]);
if (empty($check)) {
    http_response_code(404);
    @logSystem("Callback ingest: unknown key from " . ($_SERVER['REMOTE_ADDR'] ?? '?'));
    exit("Unknown check.");
}

$map = ["success" => "1", "failure" => "0", "up" => "1", "down" => "0"];
if (!isset($map[$status])) {
    http_response_code(400);
    exit("status must be success or failure.");
}

$database->insert("app_checks_history", [
    "checkid"    => $check['id'],
    "timestamp"  => $datetime,
    "latency"    => 0,
    "statuscode" => $map[$status],
]);

http_response_code(204);

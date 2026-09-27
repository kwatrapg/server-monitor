<?php
// Hands agent.sh the alert-action commands queued for its server (see
// ServerAction::trigger()), for it to run locally and report back to
// commandresult.php. Authenticated the same way as commandsconfig.php (the
// per-server key). Each run is returned once: fetching marks it dispatched.
//
// The command TEXT is admin-entered and runs with the same local privileges
// agent.sh already runs with - this endpoint adds no new remote-execution
// capability, it only tells an already-trusted local agent what an admin
// configured it to run on that same box.

$debug = false;
if ($debug) {
    error_reporting(E_ALL & ~E_NOTICE);
    ini_set('display_errors', '1');
} else {
    // see docs/incidents/2026-09-14.md - keep display off but keep logging on.
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

$scriptpath = __DIR__;

require($scriptpath . '/includes/functions.php');
spl_autoload_register('vendorClassAutoload');
spl_autoload_register('appClassAutoload');
require($scriptpath . '/config.php');

$database = new medoo($config);

$serverkey = $_GET['serverkey'] ?? '';
if ($serverkey === '') { http_response_code(400); die("No server key received."); }

$server = $database->get("app_servers", ["id"], [ "serverkey" => $serverkey ]);
if (empty($server)) { http_response_code(404); die("Unknown server."); }

header('Content-Type: application/json; charset=utf-8');
echo json_encode(ServerAction::claimPendingRuns($server['id']));

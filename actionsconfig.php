<?php
// Hands agent.sh the alert-action commands queued for its server (see
// ServerAction::trigger()), for it to run locally and report back to
// actionresult.php. Authenticated the same way as agent.php identifies a server
// (the per-server key). Each run is returned once: fetching marks it dispatched.
//
// Plain text, one run per line: "<run_id> <timeout_seconds> <base64 command>".
// Deliberately not JSON - the agent parses it with read + base64 -d, so it
// needs no jq (not a dependency of the agent on this branch), and base64 means
// the command text can contain any character without escaping.
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
    // error_reporting(0) would also stop PHP from logging errors - keep display
    // off but keep logging on.
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
if (!preg_match('/^[A-Za-z0-9]{16,64}$/', (string) $serverkey)) { http_response_code(400); die("Malformed server key."); }

$server = $database->get("app_servers", ["id"], [ "serverkey" => $serverkey ]);
if (empty($server)) { http_response_code(404); die("Unknown server."); }

header('Content-Type: text/plain; charset=utf-8');
foreach (ServerAction::claimPendingRuns($server['id']) as $run) {
    echo $run['run_id'] . " " . $run['timeout_seconds'] . " " . base64_encode($run['command']) . "\n";
}

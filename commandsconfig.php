<?php
// Serves the list of enabled custom commands for a server, for agent.sh to run
// locally and report back to commandresult.php. Authenticated the same way as
// agent.php/agentconfig.php (the per-server key), read via GET since this is a
// config fetch, not a data push.
//
// The command TEXT itself is admin-entered and runs with the same local
// privileges agent.sh already runs with (root, per install.sh) - this endpoint
// does not add new remote-execution capability, it only tells an already-
// trusted local agent what an admin configured it to run on that same box.

$debug = false;
if ($debug) {
    error_reporting(E_ALL & ~E_NOTICE);
    ini_set('display_errors', '1');
} else {
    // error_reporting(0) doesn't just hide errors from the response, it also stops
    // PHP from logging them anywhere (log_errors respects the reporting level) -
    // see docs/incidents/2026-09-14.md. Keep display off but keep logging on.
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

$commands = $database->select("app_servers_commands", ["id", "command", "timeout_seconds"], [
    "AND" => [ "serverid" => $server['id'], "status" => 1 ],
]);

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array_map(function ($c) {
    return [
        "id" => (int) $c['id'],
        "command" => (string) $c['command'],
        "timeout_seconds" => (int) $c['timeout_seconds'],
    ];
}, $commands));

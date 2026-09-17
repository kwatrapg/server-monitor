<?php
// Internal auth_request target for the Loki reverse proxy.
// Hit only as an nginx `auth_request` subrequest in front of /loki/api/v1/push -
// never called directly by agents or the app. No body, no side effects: status
// code is the entire contract nginx cares about.
//
// Validates the X-Logs-Token header against an active server's app_servers.logs_token,
// exactly the way agent.php validates serverkey for metrics.

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

function getHeaderCaseInsensitive($name) {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    foreach ($headers as $key => $value) {
        if (strcasecmp($key, $name) === 0) return $value;
    }
    $serverkey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
    return $_SERVER[$serverkey] ?? '';
}

$token = getHeaderCaseInsensitive('X-Logs-Token');

if ($token === '') {
    http_response_code(403);
    exit;
}

// $token is already confirmed non-empty above, so an equality match here can never
// hit a row with an empty logs_token - no separate exclusion condition needed
// (medoo 1.1.3 doesn't AND two conditions that share a base column key anyway).
$server = $database->get('app_servers', ['id'], [ 'logs_token' => $token ]);

if (empty($server)) {
    http_response_code(403);
    exit;
}

http_response_code(200);
header('X-Server-Id: ' . $server['id']);

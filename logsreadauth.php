<?php
// Internal auth_request target for the Loki reverse proxy.
// Hit only as an nginx `auth_request` subrequest in front of every Loki API path
// EXCEPT /loki/api/v1/push (that one uses logspushauth.php's per-server token).
// The app is the only caller of Loki reads, so this checks a single shared
// read credential (core_config.loki_read_token) rather than a per-server one.

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

$auth = getHeaderCaseInsensitive('Authorization');
$provided = (strncmp($auth, 'Bearer ', 7) === 0) ? substr($auth, 7) : '';
$expected = getConfigValue('loki_read_token');

// Fail closed: an unset expected token must never be treated as "no auth required".
if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
    http_response_code(403);
    exit;
}

http_response_code(200);

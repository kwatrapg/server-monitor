<?php
/**
 * Agent metrics ingest.
 *
 * Auth: the per-server CSPRNG `serverkey` embedded in the payload, PLUS an
 * HMAC-SHA256 signature over "timestamp.body" and a ±300s replay window
 * (VAPT F-13). Set AGENT_REQUIRE_SIGNATURE=0 only for a migration window.
 * Plaintext HTTP is rejected unless AGENT_ALLOW_HTTP=1.
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

function agent_reject($code, $msg) {
    http_response_code($code);
    @logSystem("Agent ingest rejected ($code): $msg from " . ($_SERVER['REMOTE_ADDR'] ?? '?'));
    exit($msg . "\n");
}

// --- transport ------------------------------------------------------------
$https = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
      || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
if (!$https && !sm_env_bool("AGENT_ALLOW_HTTP", false)) {
    agent_reject(400, "HTTPS required.");
}

if (!isset($_POST['data']) || $_POST['data'] === '') agent_reject(400, "No data received.");
$payload = (string) $_POST['data'];

// Payload is base64url. (Legacy agents sent a raw/url-encoded blob; if it does
// not look like base64url, fall back to the old decoding for one release.)
if (preg_match('~^[A-Za-z0-9_\-]+={0,2}$~', $payload)) {
    $data = base64_decode(strtr($payload, '-_', '+/'), true);
    if ($data === false) agent_reject(400, "Malformed payload.");
} else {
    $data = urldecode($payload);
}

// --- identify the server -------------------------------------------------
$serverkey = Server::extractData("serverkey", $data);
if (!preg_match('/^[A-Za-z0-9]{16,64}$/', (string) $serverkey)) agent_reject(400, "Malformed server key.");

$server = $database->get("app_servers", "*", ["serverkey" => $serverkey]);
if (empty($server)) agent_reject(404, "Unknown server.");

// --- signature + replay window -----------------------------------------
$requireSig = sm_env_bool('AGENT_REQUIRE_SIGNATURE', true);
if ($requireSig) {
    $ts  = $_SERVER['HTTP_X_AGENT_TIMESTAMP'] ?? '';
    $sig = $_SERVER['HTTP_X_AGENT_SIGNATURE'] ?? '';
    if (!ctype_digit((string) $ts) || $sig === '') agent_reject(401, "Missing signature.");
    if (abs(time() - (int) $ts) > 300) agent_reject(401, "Stale request.");

    // Signing key: deployment-wide secret, else the per-server key.
    $secret = (string) (sm_env('AGENT_HMAC_SECRET', '') ?: $serverkey);
    $expected = hash_hmac('sha256', $ts . '.' . $payload, $secret);
    if (!hash_equals($expected, strtolower((string) $sig))) agent_reject(401, "Bad signature.");

    // Replay: timestamp must strictly advance per server.
    $last = $server['last_agent_nonce_at'] ? strtotime($server['last_agent_nonce_at']) : 0;
    if ((int) $ts <= $last) agent_reject(409, "Replay detected.");
    $database->update("app_servers", ["last_agent_nonce_at" => date('Y-m-d H:i:s', (int) $ts)], ["id" => $server['id']]);
}

// --- store -------------------------------------------------------------
$lastHistory = $database->get("app_servers_history", "*", ["serverid" => $server['id'], "ORDER" => ["id" => "DESC"]]);
$database->insert("app_servers_history", [
    "serverid"  => $server['id'],
    "timestamp" => $datetime,
    "data"      => gzcompress($data, 9),
]);
Server::cleanHistory($lastHistory['id'] ?? 0);

http_response_code(204);

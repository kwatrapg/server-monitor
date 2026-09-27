<?php
/**
 * Alert-action command result ingest.
 *
 * Auth: identical scheme to agent.php - the per-server serverkey plus an
 * HMAC-SHA256 signature over "timestamp.body" and a replay window (VAPT F-13
 * pattern, reused deliberately for consistency). Uses its own nonce column
 * (last_action_nonce_at) rather than agent.php's, since the agent POSTs here
 * right after its metrics upload, usually within the same second.
 *
 * Body (base64url, then signed): one line per run the agent was handed by
 * actionsconfig.php - "<run_id> <exit_code> <base64 output>". Output is only
 * ever stored (length-capped), never passed to a shell.
 */

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
date_default_timezone_set(getConfigValue("timezone"));

function actionresult_reject($code, $msg) {
    http_response_code($code);
    @logSystem("Action result ingest rejected ($code): $msg from " . ($_SERVER['REMOTE_ADDR'] ?? '?'));
    exit($msg . "\n");
}

// --- transport ------------------------------------------------------------
$https = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
      || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
if (!$https && !sm_env_bool("AGENT_ALLOW_HTTP", false)) {
    actionresult_reject(400, "HTTPS required.");
}

$serverkey = $_POST['serverkey'] ?? '';
if (!preg_match('/^[A-Za-z0-9]{16,64}$/', (string) $serverkey)) actionresult_reject(400, "Malformed server key.");

$server = $database->get("app_servers", "*", ["serverkey" => $serverkey]);
if (empty($server)) actionresult_reject(404, "Unknown server.");

if (!isset($_POST['data']) || $_POST['data'] === '') actionresult_reject(400, "No data received.");
$payload = (string) $_POST['data'];

// --- signature + replay window -----------------------------------------
$requireSig = sm_env_bool('AGENT_REQUIRE_SIGNATURE', true);
if ($requireSig) {
    $ts  = $_SERVER['HTTP_X_AGENT_TIMESTAMP'] ?? '';
    $sig = $_SERVER['HTTP_X_AGENT_SIGNATURE'] ?? '';
    if (!ctype_digit((string) $ts) || $sig === '') actionresult_reject(401, "Missing signature.");
    if (abs(time() - (int) $ts) > 300) actionresult_reject(401, "Stale request.");

    $secret = (string) (sm_env('AGENT_HMAC_SECRET', '') ?: $serverkey);
    $expected = hash_hmac('sha256', $ts . '.' . $payload, $secret);
    if (!hash_equals($expected, strtolower((string) $sig))) actionresult_reject(401, "Bad signature.");

    $last = $server['last_action_nonce_at'] ? strtotime($server['last_action_nonce_at']) : 0;
    if ((int) $ts <= $last) actionresult_reject(409, "Replay detected.");
    $database->update("app_servers", ["last_action_nonce_at" => date('Y-m-d H:i:s', (int) $ts)], ["id" => $server['id']]);
}

// --- decode + apply --------------------------------------------------------
$body = base64_decode(strtr($payload, '-_', '+/'), true);
if ($body === false) actionresult_reject(400, "Malformed payload.");

$applied = 0;
foreach (explode("\n", $body) as $line) {
    $parts = explode(" ", trim($line), 3);
    if (count($parts) < 2 || !ctype_digit($parts[0]) || !preg_match('/^-?\d+$/', $parts[1])) continue;

    $output = base64_decode($parts[2] ?? '', true);
    // reportRun() only touches a run that belongs to this server and is still dispatched
    ServerAction::reportRun($server['id'], (int) $parts[0], (int) $parts[1], substr((string) $output, 0, 1000));
    $applied++;
}

http_response_code(200);
echo json_encode(["applied" => $applied]);

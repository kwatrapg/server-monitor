<?php
/**
 * Custom command result ingest.
 *
 * Auth: identical scheme to agent.php - the per-server serverkey plus an
 * HMAC-SHA256 signature over "timestamp.body" and a replay window (VAPT F-13
 * pattern, reused deliberately for consistency). Uses its own nonce column
 * (last_command_nonce_at) rather than agent.php's, since the agent may POST to
 * both endpoints within the same second.
 *
 * Body: JSON array of {id, exit_code, output} - one per command this server
 * was configured to run (from commandsconfig.php). Never raw shell output
 * trusted beyond a length cap; nothing here is ever passed to a shell.
 */

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

// Unlike agent.php, this endpoint can trigger an alert email synchronously
// (Command::reportResult() -> App::send_alert_notif() -> sendEmail(), inline,
// not via the cron sweep), so it needs PHPMailer available - the lightweight
// vendorClassAutoload() above only resolves vendor/classes/class.*.php, not
// Composer's namespaced packages. Matches includes/loader.php and
// crons/cron.php, the app's other two entry points that send mail.
require $scriptpath . '/vendor/autoload.php';

$database = new medoo($config);
date_default_timezone_set(getConfigValue("timezone"));

function commandresult_reject($code, $msg) {
    http_response_code($code);
    @logSystem("Command result ingest rejected ($code): $msg from " . ($_SERVER['REMOTE_ADDR'] ?? '?'));
    exit($msg . "\n");
}

// --- transport ------------------------------------------------------------
$https = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
      || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
if (!$https && !sm_env_bool("AGENT_ALLOW_HTTP", false)) {
    commandresult_reject(400, "HTTPS required.");
}

$serverkey = $_POST['serverkey'] ?? '';
if (!preg_match('/^[A-Za-z0-9]{16,64}$/', (string) $serverkey)) commandresult_reject(400, "Malformed server key.");

$server = $database->get("app_servers", "*", ["serverkey" => $serverkey]);
if (empty($server)) commandresult_reject(404, "Unknown server.");

if (!isset($_POST['data']) || $_POST['data'] === '') commandresult_reject(400, "No data received.");
$payload = (string) $_POST['data'];

// --- signature + replay window -----------------------------------------
$requireSig = sm_env_bool('AGENT_REQUIRE_SIGNATURE', true);
if ($requireSig) {
    $ts  = $_SERVER['HTTP_X_AGENT_TIMESTAMP'] ?? '';
    $sig = $_SERVER['HTTP_X_AGENT_SIGNATURE'] ?? '';
    if (!ctype_digit((string) $ts) || $sig === '') commandresult_reject(401, "Missing signature.");
    if (abs(time() - (int) $ts) > 300) commandresult_reject(401, "Stale request.");

    $secret = (string) (sm_env('AGENT_HMAC_SECRET', '') ?: $serverkey);
    $expected = hash_hmac('sha256', $ts . '.' . $payload, $secret);
    if (!hash_equals($expected, strtolower((string) $sig))) commandresult_reject(401, "Bad signature.");

    $last = $server['last_command_nonce_at'] ? strtotime($server['last_command_nonce_at']) : 0;
    if ((int) $ts <= $last) commandresult_reject(409, "Replay detected.");
    $database->update("app_servers", ["last_command_nonce_at" => date('Y-m-d H:i:s', (int) $ts)], ["id" => $server['id']]);
}

// --- decode + validate ---------------------------------------------------
$results = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
if (!is_array($results)) commandresult_reject(400, "Malformed payload.");

$applied = 0;
foreach ($results as $r) {
    if (!is_array($r) || !isset($r['id']) || !isset($r['exit_code'])) continue;

    $commandRow = $database->get("app_servers_commands", "*", [
        "AND" => [ "id" => (int) $r['id'], "serverid" => $server['id'] ],
    ]);
    if (empty($commandRow)) continue; // deleted/reassigned since the agent fetched its config - skip, not fatal

    $exitCode = (int) $r['exit_code'];
    $output = substr((string) ($r['output'] ?? ''), 0, 1000);

    Command::reportResult($commandRow, $exitCode, $output);
    $applied++;
}

http_response_code(200);
echo json_encode(["applied" => $applied]);

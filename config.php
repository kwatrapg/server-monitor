<?php
/**
 * Sentruo — runtime configuration shim.
 *
 * This file contains NO secrets. Credentials and keys come from the environment
 * (real env vars or a `.env` file kept outside the web root, chmod 600,
 * git-ignored). See .env.example and docs/DEPLOYMENT.md.
 *
 * Kept at the app root for backward compatibility with the existing bootstrap
 * (loader.php / agent.php / callback.php / crons/cron.php all `require` it).
 */

require_once __DIR__ . '/includes/env.php';

$config = [
    'database_type' => 'mysql',
    'database_name' => sm_env('DB_NAME', 'monitor'),
    'server'        => sm_env('DB_HOST', 'localhost'),
    'username'      => sm_env('DB_USER', 'datamine'),
    'password'      => sm_env('DB_PASSWORD', 'mypass'),
    'charset'       => sm_env('DB_CHARSET', 'utf8mb4'),
    'port'          => (int) sm_env('DB_PORT', 3306),

    // Application secret (HMACs, token hashing, at-rest secret encryption). 64 hex chars. Never commit it.
    'encryption_key' => (string) sm_env('APP_KEY', ''),
];

if ($config['encryption_key'] === '' && PHP_SAPI !== 'cli') {
    // Fail closed rather than run HMAC/signing code with an empty key.
    error_log('Sentruo: APP_KEY is not set in the environment.');
}

if (defined('SM_DB_SOCKET_OK')) { /* placeholder for future unix-socket support */ }

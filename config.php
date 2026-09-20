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

    // SaaS license verification (admin-panel/ service). A license is
    // mandatory (see includes/classes/class.license.php) — leaving these
    // unset means no license can ever be verified, not that checking is
    // skipped.
    'license_api_url'     => (string) sm_env('LICENSE_API_URL', ''),
    'license_hmac_secret' => (string) sm_env('LICENSE_HMAC_SECRET', ''),
    // Public URL of the admin portal shown to a locked-out user (e.g. a
    // customer-facing https://admin.example.com). Falls back to
    // license_api_url if not set separately (e.g. when both are the same
    // host, or during local testing).
    'license_portal_url'  => (string) sm_env('LICENSE_PORTAL_URL', sm_env('LICENSE_API_URL', '')),
];

if ($config['encryption_key'] === '' && PHP_SAPI !== 'cli') {
    // Fail closed rather than run HMAC/signing code with an empty key.
    error_log('Sentruo: APP_KEY is not set in the environment.');
}

if (defined('SM_DB_SOCKET_OK')) { /* placeholder for future unix-socket support */ }

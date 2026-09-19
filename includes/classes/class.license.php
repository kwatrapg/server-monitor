<?php

/**
 * SaaS license verification and plan-limit enforcement.
 *
 * Talks to the separate admin-panel service's POST /api/v1/license/verify
 * (see admin-panel/README.md) to find out which plan this install is on and
 * what its resource limits are, caching the result in core_config so normal
 * page loads never wait on a network call.
 *
 * Licensing is entirely opt-in: if LICENSE_API_URL is not set (the default),
 * isEnabled() is false and every limit is unlimited — existing self-hosted
 * installs are unaffected.
 */
class License extends App {

    const CACHE_TTL_SECONDS = 6 * 3600;         // re-verify with the license server at most this often
    const GRACE_PERIOD_SECONDS = 3 * 24 * 3600; // if unreachable, keep trusting the last-good result this long

    // Applied when licensing is enabled but there is no currently-valid
    // license (none entered yet, invalid, expired, suspended...) — lets a
    // SaaS install keep working in a limited trial mode instead of being
    // completely locked out.
    const TRIAL_LIMITS = [
        'max_servers'  => 2,
        'max_websites' => 2,
        'max_checks'   => 5,
    ];

    public static function isEnabled() {
        global $config;
        return trim((string) ($config['license_api_url'] ?? '')) !== '';
    }

    public static function getKey() {
        return trim((string) getConfigValue('license_key'));
    }

    public static function setKey($key) {
        Settings::update('license_key', trim((string) $key));
        // Force a fresh remote check on next status() read rather than trusting
        // whatever was cached for the previous key.
        Settings::update('license_last_checked_at', '0');
    }

    /** Stable per-install identifier sent to the license server for activation tracking. */
    public static function getInstallFingerprint() {
        global $config;
        $fp = getConfigValue('license_fingerprint');
        if ($fp === '' || $fp === null || $fp === false) {
            $fp = hash('sha256', ($config['encryption_key'] ?? '') . '|' . ($config['database_name'] ?? '') . '|' . php_uname('n'));
            Settings::update('license_fingerprint', $fp);
        }
        return $fp;
    }

    /**
     * Current license snapshot:
     * ['enabled','valid','reason','plan','plan_name','limits'=>['max_servers'=>?int,...],'expires_at','checked_at','source']
     * 'source' is one of: disabled | trial | cache | live.
     */
    public static function status($forceRefresh = false) {
        if (!self::isEnabled()) {
            return [
                'enabled' => false, 'valid' => true, 'reason' => 'disabled',
                'plan' => null, 'plan_name' => null,
                'limits' => ['max_servers' => null, 'max_websites' => null, 'max_checks' => null],
                'expires_at' => null, 'checked_at' => null, 'source' => 'disabled',
            ];
        }

        $key = self::getKey();
        if ($key === '') {
            return self::trialStatus('not_configured');
        }

        $lastChecked = (int) getConfigValue('license_last_checked_at');
        $cachedValid = getConfigValue('license_valid');
        $haveCache = $cachedValid !== '' && $cachedValid !== null && $cachedValid !== false;

        if (!$forceRefresh && $haveCache && (time() - $lastChecked) < self::CACHE_TTL_SECONDS) {
            return self::cachedStatus('cache');
        }

        $fresh = self::verifyRemote($key);
        if ($fresh === null) {
            if ($haveCache && (time() - $lastChecked) < self::GRACE_PERIOD_SECONDS) {
                return self::cachedStatus('cache');
            }
            return self::trialStatus('unreachable');
        }
        return $fresh;
    }

    private static function cachedStatus($source) {
        $valid = getConfigValue('license_valid') === '1';
        $limits = [
            'max_servers'  => self::nullableInt(getConfigValue('license_max_servers')),
            'max_websites' => self::nullableInt(getConfigValue('license_max_websites')),
            'max_checks'   => self::nullableInt(getConfigValue('license_max_checks')),
        ];
        $checkedAt = (int) getConfigValue('license_last_checked_at');
        return [
            'enabled' => true,
            'valid' => $valid,
            'reason' => (string) getConfigValue('license_reason'),
            'plan' => getConfigValue('license_plan') ?: null,
            'plan_name' => getConfigValue('license_plan_name') ?: null,
            'limits' => $valid ? $limits : self::TRIAL_LIMITS,
            'expires_at' => getConfigValue('license_expires_at') ?: null,
            'checked_at' => $checkedAt ? date('Y-m-d H:i:s', $checkedAt) : null,
            'source' => $source,
        ];
    }

    private static function trialStatus($reason) {
        $checkedAt = (int) getConfigValue('license_last_checked_at');
        return [
            'enabled' => true, 'valid' => false, 'reason' => $reason,
            'plan' => null, 'plan_name' => null,
            'limits' => self::TRIAL_LIMITS,
            'expires_at' => null,
            'checked_at' => $checkedAt ? date('Y-m-d H:i:s', $checkedAt) : null,
            'source' => 'trial',
        ];
    }

    private static function nullableInt($v) {
        if ($v === null || $v === false || $v === '') return null;
        return (int) $v;
    }

    /**
     * Calls the admin-panel's license verification API, checks the HMAC
     * signature on the response, and caches the result into core_config.
     * Returns the status array, or null if the call/signature couldn't be
     * trusted (network error, bad response, missing/invalid signature).
     */
    private static function verifyRemote($key) {
        global $config;

        $base = rtrim((string) ($config['license_api_url'] ?? ''), '/');
        $secret = (string) ($config['license_hmac_secret'] ?? '');
        if ($base === '') return null;

        $domain = parse_url((string) getConfigValue('app_url'), PHP_URL_HOST);
        if (!$domain) $domain = (string) ($_SERVER['SERVER_NAME'] ?? '');

        $payload = json_encode([
            'license_key' => $key,
            'domain' => $domain,
            'fingerprint' => self::getInstallFingerprint(),
        ]);

        $response = sm_trusted_http_post($base . '/api/v1/license/verify', $payload, 8);
        if ($response['error'] !== null || $response['status'] < 200 || $response['status'] >= 500) {
            logSystem('License verification unreachable: ' . ($response['error'] ?: ('HTTP ' . $response['status'])));
            return null;
        }

        $data = json_decode((string) $response['body'], true);
        if (!is_array($data) || !isset($data['signature'])) {
            logSystem('License verification returned an unparseable response.');
            return null;
        }

        if ($secret === '') {
            logSystem('License verification response received but LICENSE_HMAC_SECRET is not configured; refusing to trust it.');
            return null;
        }

        $signature = (string) $data['signature'];
        $unsigned = $data;
        unset($unsigned['signature']);
        $expected = hash_hmac('sha256', json_encode($unsigned), $secret);

        if (!sm_hash_equals($expected, $signature)) {
            logSystem('License verification response failed signature check.');
            return null;
        }

        $valid = !empty($data['valid']);
        $limits = is_array($data['limits'] ?? null) ? $data['limits'] : [];
        $now = time();

        Settings::update('license_valid', $valid ? '1' : '0');
        Settings::update('license_reason', (string) ($data['reason'] ?? ($valid ? 'active' : 'invalid')));
        Settings::update('license_plan', (string) ($data['plan'] ?? ''));
        Settings::update('license_plan_name', (string) ($data['plan_name'] ?? ''));
        Settings::update('license_max_servers', isset($limits['max_servers']) ? (string) $limits['max_servers'] : '');
        Settings::update('license_max_websites', isset($limits['max_websites']) ? (string) $limits['max_websites'] : '');
        Settings::update('license_max_checks', isset($limits['max_checks']) ? (string) $limits['max_checks'] : '');
        Settings::update('license_expires_at', (string) ($data['expires_at'] ?? ''));
        Settings::update('license_last_checked_at', (string) $now);

        logSystem('License verified: ' . ($valid ? 'valid (' . ($data['plan_name'] ?? $data['plan'] ?? '') . ')' : 'invalid (' . ($data['reason'] ?? '') . ')'));

        return [
            'enabled' => true,
            'valid' => $valid,
            'reason' => (string) ($data['reason'] ?? ($valid ? 'active' : 'invalid')),
            'plan' => $data['plan'] ?? null,
            'plan_name' => $data['plan_name'] ?? null,
            'limits' => $valid ? [
                'max_servers' => $limits['max_servers'] ?? null,
                'max_websites' => $limits['max_websites'] ?? null,
                'max_checks' => $limits['max_checks'] ?? null,
            ] : self::TRIAL_LIMITS,
            'expires_at' => $data['expires_at'] ?? null,
            'checked_at' => date('Y-m-d H:i:s', $now),
            'source' => 'live',
        ];
    }

    public static function getLimits() {
        return self::status()['limits'];
    }

    /** Current usage vs. limit for each resource type, for display in the UI. */
    public static function getUsage() {
        $limits = self::getLimits();
        return [
            'max_servers'  => ['used' => countTable('app_servers'),  'limit' => $limits['max_servers']],
            'max_websites' => ['used' => countTable('app_websites'), 'limit' => $limits['max_websites']],
            'max_checks'   => ['used' => countTable('app_checks'),   'limit' => $limits['max_checks']],
        ];
    }

    /** True if another resource of $type ('max_servers'|'max_websites'|'max_checks') can be added. */
    public static function canAdd($type) {
        $limits = self::getLimits();
        $max = $limits[$type] ?? null;
        if ($max === null) return true; // unlimited: licensing disabled, or plan has no cap on this resource

        $current = match ($type) {
            'max_servers' => countTable('app_servers'),
            'max_websites' => countTable('app_websites'),
            'max_checks' => countTable('app_checks'),
            default => 0,
        };
        return $current < (int) $max;
    }
}

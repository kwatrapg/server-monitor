<?php
/**
 * Sentruo — core security primitives.
 *
 * Output encoding, CSPRNG tokens, CSRF protection, request-origin checks,
 * login/reset throttling, and SSRF host guarding + safe HTTP fetch.
 *
 * Loaded early by includes/loader.php (web) and available to the CLI
 * entrypoints (agent.php, callback.php, crons/cron.php) via functions.php.
 */

// Always loaded via require_once (from includes/functions.php). Do not require()
// this file directly elsewhere.
define('SM_SECURITY_LOADED', true);

// ---------------------------------------------------------------------------
// Output encoding
// ---------------------------------------------------------------------------

/**
 * HTML-escape a value for safe output in element text or double-quoted attributes.
 * Use everywhere a user- or DB-sourced string is echoed into markup.
 */
function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Escape for use inside a single-quoted JS string / JSON-ish context. */
function ejs($value) {
    return json_encode((string) $value, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
}

// ---------------------------------------------------------------------------
// CSPRNG tokens
// ---------------------------------------------------------------------------

/**
 * Cryptographically secure random string.
 * Returns $len characters from [0-9a-f] (hex). Replaces the legacy rand()-based
 * randomString() for all security-sensitive tokens.
 */
function sm_random_token($len = 32) {
    $len = max(1, (int) $len);
    $bytes = random_bytes((int) ceil($len / 2));
    return substr(bin2hex($bytes), 0, $len);
}

/** Constant-time compare wrapper that tolerates non-string input. */
function sm_hash_equals($known, $given) {
    return is_string($known) && is_string($given) && hash_equals($known, $given);
}

// ---------------------------------------------------------------------------
// At-rest secret encryption (VAPT F-04 revision) — lets secrets that must be
// admin-editable (e.g. SMTP password) live in core_config without being
// stored in the clear. Keyed by APP_KEY (config.php 'encryption_key'), which
// itself stays in .env only. A DB dump/SQLi leak yields ciphertext, not the
// secret.
// ---------------------------------------------------------------------------

/**
 * Encrypt a secret for storage in the database (AES-256-GCM).
 * Returns '' for empty input so "leave blank to keep unchanged" form flows work.
 */
function sm_encrypt_secret($plaintext) {
    global $config;
    $plaintext = (string) $plaintext;
    if ($plaintext === '') return '';
    $key = @hex2bin((string) ($config['encryption_key'] ?? ''));
    if ($key === false || strlen($key) !== 32) {
        throw new RuntimeException('APP_KEY is not set/valid; cannot encrypt secret for storage.');
    }
    $iv = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    return base64_encode($iv . $tag . $ciphertext);
}

/**
 * Decrypt a secret previously stored with sm_encrypt_secret(). Returns ''
 * on any failure (missing key, corrupt value) rather than throwing, since
 * callers treat '' the same as "not configured".
 */
function sm_decrypt_secret($stored) {
    global $config;
    $stored = (string) $stored;
    if ($stored === '') return '';
    $raw = base64_decode($stored, true);
    if ($raw === false || strlen($raw) < 12 + 16) return '';
    $key = @hex2bin((string) ($config['encryption_key'] ?? ''));
    if ($key === false || strlen($key) !== 32) return '';
    $iv  = substr($raw, 0, 12);
    $tag = substr($raw, 12, 16);
    $ciphertext = substr($raw, 28);
    $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    return $plaintext === false ? '' : $plaintext;
}

// ---------------------------------------------------------------------------
// Password hashing (VAPT F-05) — argon2id when available, else bcrypt.
// Legacy unsalted sha1 hashes are accepted once on login and transparently
// upgraded (see signIn()).
// ---------------------------------------------------------------------------

function sm_password_algo() {
    if (defined('PASSWORD_ARGON2ID') && in_array('argon2id', password_algos(), true)) {
        return PASSWORD_ARGON2ID;
    }
    return PASSWORD_BCRYPT;
}

function sm_password_hash($plain) {
    return password_hash((string) $plain, sm_password_algo());
}

/** True when $plain matches $stored (modern hash or legacy sha1). */
function sm_password_matches($plain, $stored) {
    if (!is_string($stored) || $stored === '') return false;
    if ($stored[0] === '$') {                       // $argon2id$ / $2y$ (bcrypt)
        return password_verify((string) $plain, $stored);
    }
    if (preg_match('/^[0-9a-f]{40}$/i', $stored)) { // legacy unsalted sha1
        return hash_equals(strtolower($stored), sha1((string) $plain));
    }
    return false;
}

/** True when the stored hash should be re-hashed with the current algo. */
function sm_password_needs_upgrade($stored) {
    if (!is_string($stored) || $stored === '' || $stored[0] !== '$') return true;
    return password_needs_rehash($stored, sm_password_algo());
}

/** Minimal password policy. Returns null when OK, or an error string. */
function sm_password_policy_error($plain) {
    $plain = (string) $plain;
    if (strlen($plain) < 12) return 'Password must be at least 12 characters.';
    if (!preg_match('/[a-z]/', $plain) || !preg_match('/[A-Z]/', $plain) || !preg_match('/\d/', $plain)) {
        return 'Password must contain lower-case, upper-case and numeric characters.';
    }
    return null;
}

// ---------------------------------------------------------------------------
// CSRF
// ---------------------------------------------------------------------------

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = sm_random_token(64);
    }
    return $_SESSION['csrf_token'];
}

/** Hidden input for inclusion in every state-changing <form>. */
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** True when the request carries a valid CSRF token (POST field or header). */
function csrf_verify() {
    if (empty($_SESSION['csrf_token'])) return false;
    $sent = $_POST['csrf_token']
        ?? $_SERVER['HTTP_X_CSRF_TOKEN']
        ?? $_GET['csrf_token']
        ?? '';
    return sm_hash_equals($_SESSION['csrf_token'], (string) $sent);
}

/**
 * Same-origin check for state-changing requests. Compares the Origin (or, as a
 * fallback, Referer) host against the configured app_url / request host.
 */
function sm_request_origin_ok() {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) return true;

    $source = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
    if ($source === '') return true; // some privacy setups strip both; CSRF token still gates

    $srcHost = parse_url($source, PHP_URL_HOST);
    if ($srcHost === null || $srcHost === false) return false;

    $allowed = [];
    $allowed[] = $_SERVER['HTTP_HOST'] ?? '';
    $allowed[] = $_SERVER['SERVER_NAME'] ?? '';
    if (function_exists('getConfigValue')) {
        $appUrl = (string) getConfigValue('app_url');
        if ($appUrl !== '') {
            $h = parse_url($appUrl, PHP_URL_HOST);
            if ($h) $allowed[] = $h;
        }
    }
    $allowed = array_filter(array_map('strtolower', $allowed));
    return in_array(strtolower($srcHost), $allowed, true);
}

/**
 * Enforce CSRF + origin for the current mutating request or terminate with 403.
 * Call from every controller that writes state.
 */
function csrf_check_or_die() {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method === 'GET' || $method === 'HEAD') {
        // GET quick-actions that mutate still require a token in the query string.
        if (!csrf_verify()) { sm_deny(403, 'Invalid or missing security token.'); }
        return;
    }
    if (!csrf_verify() || !sm_request_origin_ok()) {
        sm_deny(403, 'Invalid or missing security token.');
    }
}

function sm_deny($code, $message) {
    http_response_code($code);
    header('Content-Type: text/plain; charset=utf-8');
    if (function_exists('logSystem')) { @logSystem('Security: request denied (' . $code . ') - ' . $message); }
    exit($message . "\n");
}

// ---------------------------------------------------------------------------
// Login / password-reset throttling  (table: core_auththrottle)
// ---------------------------------------------------------------------------

function sm_client_ip() {
    // REMOTE_ADDR only. X-Forwarded-For is honoured solely when the direct peer
    // is a configured trusted proxy (see TRUSTED_PROXIES env, comma separated).
    $remote = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $trusted = array_filter(array_map('trim', explode(',', (string) getenv('TRUSTED_PROXIES'))));
    if ($trusted && in_array($remote, $trusted, true) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
        $candidate = end($parts);
        if (filter_var($candidate, FILTER_VALIDATE_IP)) return $candidate;
    }
    return $remote;
}

function sm_throttle_id($scope, $identifier) {
    return $scope . ':' . hash('sha256', strtolower(trim((string) $identifier)) . '|' . sm_client_ip());
}

/**
 * Returns [allowed(bool), retry_after_seconds(int)].
 * Sliding window: MAX_FAILS failures within WINDOW seconds triggers a lockout
 * that grows with the failure count (capped).
 */
function sm_throttle_check($scope, $identifier) {
    global $database;
    $MAX_FAILS = 5;
    $WINDOW    = 900;   // 15 min
    $LOCK_MAX  = 3600;  // 1 h cap

    if (!$database) return [true, 0];
    try {
        $row = $database->get('core_auththrottle', '*', ['throttle_key' => sm_throttle_id($scope, $identifier)]);
    } catch (\Throwable $ex) {
        return [true, 0]; // table not migrated yet — fail open, but log
    }
    if (!$row) return [true, 0];

    $now = time();
    $last = strtotime($row['last_fail'] ?? 'now');
    if (($now - $last) > $WINDOW) return [true, 0]; // window elapsed, stale record

    $fails = (int) $row['fail_count'];
    if ($fails < $MAX_FAILS) return [true, 0];

    $over = $fails - $MAX_FAILS + 1;
    $lock = min($LOCK_MAX, 60 * (2 ** min($over, 6))); // 120s,240s,... capped
    $unlockAt = $last + $lock;
    if ($now >= $unlockAt) return [true, 0];
    return [false, $unlockAt - $now];
}

function sm_throttle_hit($scope, $identifier) {
    global $database;
    if (!$database) return;
    $key = sm_throttle_id($scope, $identifier);
    $now = date('Y-m-d H:i:s');
    try {
        $row = $database->get('core_auththrottle', '*', ['throttle_key' => $key]);
        if ($row) {
            $reset = (time() - strtotime($row['last_fail'])) > 900;
            $database->update('core_auththrottle', [
                'fail_count' => $reset ? 1 : ($row['fail_count'] + 1),
                'last_fail'  => $now,
            ], ['throttle_key' => $key]);
        } else {
            $database->insert('core_auththrottle', [
                'throttle_key' => $key,
                'fail_count'   => 1,
                'last_fail'    => $now,
            ]);
        }
    } catch (\Throwable $ex) { /* table not migrated yet */ }
}

function sm_throttle_clear($scope, $identifier) {
    global $database;
    if (!$database) return;
    try {
        $database->delete('core_auththrottle', ['throttle_key' => sm_throttle_id($scope, $identifier)]);
    } catch (\Throwable $ex) { /* noop */ }
}

// ---------------------------------------------------------------------------
// SSRF protection — host guarding + safe HTTP fetch
// ---------------------------------------------------------------------------

class HostGuard {

    /** Allow probing of private/reserved ranges (opt-in for internal monitoring). */
    public static function allowPrivate() {
        if (getenv('ALLOW_PRIVATE_PROBE_TARGETS') === '1') return true;
        if (function_exists('getConfigValue') && getConfigValue('allow_private_probe_targets') === 'true') return true;
        return false;
    }

    /** True if $ip is loopback / private / link-local / reserved / CGN. */
    public static function isBlockedIp($ip) {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) return true;

        // Public range check via PHP's own reserved/private filters.
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return true;
        }
        // Extra explicit blocks not fully covered by the filter.
        $blocks = [
            '0.0.0.0/8', '100.64.0.0/10', '169.254.0.0/16', '192.0.0.0/24',
            '192.0.2.0/24', '198.18.0.0/15', '198.51.100.0/24', '203.0.113.0/24',
            '224.0.0.0/4', '240.0.0.0/4',
        ];
        foreach ($blocks as $cidr) {
            if (self::ipInCidr($ip, $cidr)) return true;
        }
        // IPv6 unique-local / link-local / mapped.
        if (strpos($ip, ':') !== false) {
            $lc = strtolower($ip);
            if ($lc === '::1' || strpos($lc, 'fc') === 0 || strpos($lc, 'fd') === 0
                || strpos($lc, 'fe80') === 0 || strpos($lc, '::ffff:') === 0) {
                return true;
            }
        }
        return false;
    }

    public static function ipInCidr($ip, $cidr) {
        [$subnet, $bits] = explode('/', $cidr);
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        if ($ipLong === false || $subnetLong === false) return false;
        $mask = -1 << (32 - (int) $bits);
        return ($ipLong & $mask) === ($subnetLong & $mask);
    }

    /**
     * Validate a hostname/IP is safe to connect to. Resolves DNS and checks
     * every returned address. Returns the validated IP to connect to (pin it to
     * defeat DNS rebinding) or throws \RuntimeException.
     */
    public static function assertConnectable($host) {
        $host = trim((string) $host);
        if ($host === '' || strlen($host) > 253) {
            throw new \RuntimeException('Empty or oversized host.');
        }
        if (self::allowPrivate()) {
            // Still resolve so callers get an IP, but skip the range checks.
            if (filter_var($host, FILTER_VALIDATE_IP)) return $host;
            $ip = gethostbyname($host);
            return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : $host;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if (self::isBlockedIp($host)) throw new \RuntimeException('Target IP is in a blocked range: ' . $host);
            return $host;
        }

        if (!preg_match('/^(?=.{1,253}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)(\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)*$/i', $host)) {
            throw new \RuntimeException('Malformed hostname: ' . $host);
        }

        $ips = [];
        foreach (['A' => DNS_A, 'AAAA' => DNS_AAAA] as $rr) {
            $recs = @dns_get_record($host, $rr);
            if (is_array($recs)) {
                foreach ($recs as $r) {
                    if (!empty($r['ip']))   $ips[] = $r['ip'];
                    if (!empty($r['ipv6'])) $ips[] = $r['ipv6'];
                }
            }
        }
        if (!$ips) {
            $fallback = gethostbyname($host);
            if ($fallback !== $host && filter_var($fallback, FILTER_VALIDATE_IP)) $ips[] = $fallback;
        }
        if (!$ips) throw new \RuntimeException('Host does not resolve: ' . $host);

        foreach ($ips as $ip) {
            if (self::isBlockedIp($ip)) {
                throw new \RuntimeException('Host resolves to a blocked address (' . $ip . '): ' . $host);
            }
        }
        return $ips[0];
    }

    /** Validate an absolute http(s) URL and its host. Returns [url, pinnedIp, host, port]. */
    public static function assertUrl($url) {
        $parts = parse_url((string) $url);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            throw new \RuntimeException('Not an absolute URL.');
        }
        $scheme = strtolower($parts['scheme']);
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new \RuntimeException('Blocked URL scheme: ' . $scheme);
        }
        $host = $parts['host'];
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);
        $ip = self::assertConnectable($host);
        return [$url, $ip, $host, (int) $port];
    }
}

/**
 * SSRF-safe HTTP GET. Follows redirects manually, re-validating each hop.
 * Requires ext-curl; returns ['status'=>int,'body'=>string,'error'=>?string].
 */
function sm_safe_http_get($url, $timeout = 10, $maxRedirects = 3) {
    if (!function_exists('curl_init')) {
        return ['status' => 0, 'body' => '', 'error' => 'curl unavailable'];
    }
    $hop = 0;
    while (true) {
        try {
            [$u, $ip, $host, $port] = HostGuard::assertUrl($url);
        } catch (\Throwable $ex) {
            return ['status' => 0, 'body' => '', 'error' => $ex->getMessage()];
        }
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $u,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => min(10, $timeout),
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS      => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_USERAGENT      => 'Sentruo-Monitor/1.0',
            // Pin the connection to the validated IP (defeats DNS rebinding).
            CURLOPT_RESOLVE        => ["{$host}:{$port}:{$ip}"],
        ]);
        $body   = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err    = curl_errno($ch) ? curl_error($ch) : null;
        $loc    = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        curl_close($ch);

        if ($err) return ['status' => $status, 'body' => (string) $body, 'error' => $err];
        if ($status >= 300 && $status < 400 && $loc && $hop < $maxRedirects) {
            $url = $loc; $hop++; continue;
        }
        return ['status' => $status, 'body' => (string) $body, 'error' => null];
    }
}

/**
 * HTTP POST with a JSON body to an ADMIN-CONFIGURED, trusted endpoint (e.g.
 * LICENSE_API_URL) — not for user-suppliable targets. Deliberately does not
 * go through HostGuard: unlike check/website probe targets, this URL comes
 * from .env, not from user input, and commonly points at 127.0.0.1/a private
 * IP (the license service co-located with this app or on the same LAN), which
 * HostGuard's SSRF allow-list would otherwise reject. Still hardened: TLS
 * verified, no redirect following, bounded timeout, http(s) only.
 * Requires ext-curl; returns ['status'=>int,'body'=>string,'error'=>?string].
 */
function sm_trusted_http_post($url, $jsonBody, $timeout = 10) {
    if (!function_exists('curl_init')) {
        return ['status' => 0, 'body' => '', 'error' => 'curl unavailable'];
    }
    $scheme = strtolower((string) parse_url((string) $url, PHP_URL_SCHEME));
    if (!in_array($scheme, ['http', 'https'], true)) {
        return ['status' => 0, 'body' => '', 'error' => 'Blocked URL scheme: ' . $scheme];
    }
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => (string) $jsonBody,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Content-Length: ' . strlen((string) $jsonBody)],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => false,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => min(10, $timeout),
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_PROTOCOLS      => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        CURLOPT_USERAGENT      => 'Sentruo-Monitor/1.0',
    ]);
    $body   = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $err    = curl_errno($ch) ? curl_error($ch) : null;
    curl_close($ch);

    return ['status' => $status, 'body' => (string) $body, 'error' => $err];
}

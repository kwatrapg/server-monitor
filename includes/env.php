<?php
/**
 * Sentruo — minimal .env loader (no external dependency).
 *
 * Secrets (DB credentials, APP_KEY, SMTP, SMS, map keys) live in the process
 * environment or in a `.env` file kept OUTSIDE the web root / chmod 600 and
 * git-ignored (VAPT F-04 / F-15). Real environment variables always win over
 * the file.
 */

// Always loaded via require_once. (A function_exists() guard here would be
// defeated by PHP early-binding sm_env() before this line runs.)

(function () {
    // Look for .env next to the app root, then one level up (recommended: outside docroot).
    $candidates = [
        getenv('SENTRUO_ENV_FILE') ?: null,
        dirname(__DIR__) . '/.env',
        dirname(__DIR__, 2) . '/.env',
    ];
    foreach (array_filter($candidates) as $path) {
        if (!is_file($path) || !is_readable($path)) continue;
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = ltrim($line);
            if ($line === '' || $line[0] === '#') continue;
            if (!str_contains($line, '=')) continue;
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v);
            if ((strlen($v) >= 2) && ($v[0] === '"' || $v[0] === "'") && $v[-1] === $v[0]) {
                $v = substr($v, 1, -1);
            }
            if (getenv($k) === false && !isset($_ENV[$k]) && !isset($_SERVER[$k])) {
                putenv("$k=$v");
                $_ENV[$k] = $v;
            }
        }
        break;
    }
})();

/**
 * Read a configuration value from the environment.
 * @param string $key
 * @param mixed  $default returned when the key is unset or empty
 */
function sm_env(string $key, $default = null) {
    $v = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($v === false || $v === null || $v === '') return $default;
    $low = strtolower(trim((string) $v));
    if ($low === 'true')  return true;
    if ($low === 'false') return false;
    if ($low === 'null')  return null;
    return $v;
}

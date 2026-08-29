<?php
/**
 * Sentruo — security response headers (VAPT F-10).
 *
 * Emitted from PHP so protection holds even when the app is served without the
 * bundled vhost. The vhost templates in deploy/ set the same headers at the
 * edge. Adjust the CSP once inline handlers are removed from the templates.
 */

if (defined('SM_HEADERS_SENT') || headers_sent()) return;
define('SM_HEADERS_SENT', true);

$https = (function () {
    if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') return true;
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') return true;
    return false;
})();

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cross-Origin-Opener-Policy: same-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(), usb=()');
header_remove('X-Powered-By');

// AdminLTE 2 ships inline scripts/handlers and inline styles; 'unsafe-inline'
// stays until the front-end is refactored (tracked in docs/REMEDIATION.md).
$csp = "default-src 'self'; "
     . "script-src 'self' 'unsafe-inline' 'unsafe-eval'; "
     . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
     . "font-src 'self' data: https://fonts.gstatic.com; "
     . "img-src 'self' data: https://www.gravatar.com https://*.tile.openstreetmap.org; "
     . "connect-src 'self'; "
     . "frame-ancestors 'none'; "
     . "base-uri 'self'; "
     . "form-action 'self'; "
     . "object-src 'none'";
header('Content-Security-Policy: ' . $csp);

if ($https) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

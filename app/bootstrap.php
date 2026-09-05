<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');

require __DIR__ . '/config.php';
require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/helpers.php';
require __DIR__ . '/lib/flash.php';
require __DIR__ . '/lib/csrf.php';
require __DIR__ . '/lib/validate.php';
require __DIR__ . '/lib/ratelimit.php';
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/uploads.php';
require __DIR__ . '/lib/settings.php';
require __DIR__ . '/lib/csp_nonce.php';
require __DIR__ . '/lib/i18n.php';
require __DIR__ . '/lib/audit.php';

set_exception_handler(function (Throwable $e): void {
    error_log('[asso-stock] Uncaught: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo '<!doctype html><html lang="fr"><head><meta charset="utf-8"><title>Erreur</title></head>'
        . '<body><p>Une erreur est survenue. Merci de réessayer plus tard.</p></body></html>';
    exit;
});

// --- Sécurité des sessions ---
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
session_name(SESSION_COOKIE_NAME);
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => FORCE_SECURE_COOKIES,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// --- En-têtes de sécurité (redondants avec Nginx, appliqués aussi si Nginx est court-circuité) ---
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), camera=(), microphone=()');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'nonce-" . cspNonce() . "'; script-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
if (FORCE_SECURE_COOKIES) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

enforceIdleTimeout();

// Les pages authentifiées ne doivent jamais être servies depuis le cache du navigateur/proxy.
if (!empty($_SESSION['user_id'])) {
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
}

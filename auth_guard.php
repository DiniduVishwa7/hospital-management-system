
<?php
/**
 * MediCore central authentication guard (PHP 8.2+, educational demo).
 * Include this BEFORE database access and HTML output on protected pages.
 * Do not include this in login.php or logout.php.
 */

// This application currently treats every logged-in account as an admin.
// Add proper roles/permissions before any use with real healthcare data.
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);

session_start();

// Protected health-related pages must never be cached or framed.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');

if (
    !isset($_SESSION['user_id']) ||
    !is_int($_SESSION['user_id']) ||
    $_SESSION['user_id'] < 1
) {
    header('Location: login.php');
    exit;
}

// End sessions after 30 minutes without activity on protected pages.
$medicoreNow = time();
$medicoreLastActivity = $_SESSION['medicore_last_activity'] ?? null;

if (
    is_int($medicoreLastActivity) &&
    $medicoreNow - $medicoreLastActivity > 1800
) {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $cookie = session_get_cookie_params();

        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => $cookie['path'],
            'domain' => $cookie['domain'],
            'secure' => $cookie['secure'],
            'httponly' => $cookie['httponly'],
            'samesite' => $cookie['samesite'] ?? 'Lax',
        ]);
    }

    session_destroy();
    header('Location: login.php?expired=1');
    exit;
}

$_SESSION['medicore_last_activity'] = $medicoreNow;

// Create a CSRF token for the secure logout form.
// This prevents the "Undefined array key: logout_token" warning.
if (empty($_SESSION['logout_token'])) {
    $_SESSION['logout_token'] = bin2hex(random_bytes(32));
}

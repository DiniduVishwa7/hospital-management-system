<?php
declare(strict_types=1);

function medicore_route_error(int $status, string $message, array $allowedMethods = []): never
{
    http_response_code($status);
    header('Content-Type: text/plain; charset=UTF-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    if ($allowedMethods !== []) {
        header('Allow: ' . implode(', ', $allowedMethods));
    }
    echo $message . "\n";
    exit;
}

try {
    require_once dirname(__DIR__) . '/app/shared/bootstrap.php';
    $medicoreRoutes = require MEDICORE_APP_PATH . '/routes.php';
} catch (Throwable $error) {
    medicore_route_error(500, 'Application unavailable.');
}

$medicoreRoute = array_key_exists('route', $_GET) ? $_GET['route'] : 'dashboard';
if (
    !is_string($medicoreRoute)
    || strlen($medicoreRoute) > 64
    || preg_match('~\A[a-z][a-z0-9-]*(?:/[a-z][a-z0-9-]*)*\z~', $medicoreRoute) !== 1
) {
    medicore_route_error(400, 'Invalid route.');
}

if (!array_key_exists($medicoreRoute, $medicoreRoutes)) {
    medicore_route_error(404, 'Route not found.');
}

$medicoreDefinition = $medicoreRoutes[$medicoreRoute];
$medicoreMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($medicoreMethod, $medicoreDefinition['methods'], true)) {
    medicore_route_error(405, 'Method not allowed.', $medicoreDefinition['methods']);
}

// All handlers remain unavailable in Stage 1, even if a file appears later.
if (!$medicoreDefinition['available'] || !is_file($medicoreDefinition['file'])) {
    medicore_route_error(404, 'Route unavailable.');
}

if ($medicoreDefinition['auth']) {
    // Only the future migrated guard may initialize protected sessions.
    // There is deliberately no fallback to includes/ or root PHP handlers.
    $medicoreGuard = MEDICORE_APP_PATH . '/shared/auth_guard.php';
    if (!is_file($medicoreGuard)) {
        medicore_route_error(404, 'Route unavailable.');
    }

    require_once $medicoreGuard;
    if (
        session_status() !== PHP_SESSION_ACTIVE
        || !isset($_SESSION['user_id'])
        || !is_int($_SESSION['user_id'])
        || $_SESSION['user_id'] < 1
    ) {
        medicore_route_error(403, 'Authentication required.');
    }
}

// The filename comes exclusively from the literal, developer-owned allowlist.
require $medicoreDefinition['file'];

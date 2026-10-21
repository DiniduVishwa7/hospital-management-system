<?php
declare(strict_types=1);

// This bootstrap deliberately performs no session startup or database access.
define('MEDICORE_ROOT', dirname(__DIR__, 2));
define('MEDICORE_APP_PATH', MEDICORE_ROOT . '/app');
define('MEDICORE_PUBLIC_PATH', MEDICORE_ROOT . '/public');

// Trusted deployment setting only; never derive URLs from HTTP_HOST or input.
// Empty (or "/") serves public/ at the URL root. For the existing XAMPP mount,
// set MEDICORE_BASE_PATH to "/hospital-management-system/public".
$medicoreBasePathSetting = getenv('MEDICORE_BASE_PATH');
$medicoreBasePathSetting = $medicoreBasePathSetting === false
    ? ''
    : $medicoreBasePathSetting;

if ($medicoreBasePathSetting === '/') {
    $medicoreBasePathSetting = '';
}

if (
    $medicoreBasePathSetting !== ''
    && preg_match('~\A/(?:[A-Za-z0-9_-]+/)*[A-Za-z0-9_-]+/?\z~', $medicoreBasePathSetting) !== 1
) {
    throw new RuntimeException('Invalid MediCore base path configuration.');
}

define('MEDICORE_BASE_PATH', rtrim($medicoreBasePathSetting, '/'));
unset($medicoreBasePathSetting);

require_once __DIR__ . '/urls.php';

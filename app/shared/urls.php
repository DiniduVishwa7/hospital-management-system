<?php
declare(strict_types=1);

/**
 * Return an unescaped local URL; escape it when placing it in HTML.
 * Route names must come from the fixed route table, including planned routes.
 */
function medicore_url(string $route, array $query = []): string
{
    static $routes = null;
    if ($routes === null) {
        $routes = require MEDICORE_APP_PATH . '/routes.php';
    }

    if (!array_key_exists($route, $routes)) {
        throw new InvalidArgumentException('Unknown MediCore route.');
    }

    foreach ($query as $key => $value) {
        if (
            !is_string($key)
            || $key === 'route'
            || preg_match('/\A[A-Za-z_][A-Za-z0-9_]*\z/', $key) !== 1
            || (!is_scalar($value) && $value !== null)
        ) {
            throw new InvalidArgumentException('Invalid MediCore query parameter.');
        }
    }

    return MEDICORE_BASE_PATH . '/index.php?' . http_build_query(
        ['route' => $route] + $query,
        '',
        '&',
        PHP_QUERY_RFC3986
    );
}

function medicore_asset(string $path): string
{
    if (
        preg_match('~\A[A-Za-z0-9_-][A-Za-z0-9_.-]*(?:/[A-Za-z0-9_-][A-Za-z0-9_.-]*)*\z~', $path) !== 1
    ) {
        throw new InvalidArgumentException('Invalid MediCore asset path.');
    }

    return MEDICORE_BASE_PATH . '/assets/' . $path;
}

/** Redirect only to an allowlisted application route, never an arbitrary URL. */
function medicore_redirect(string $route, array $query = [], int $status = 302): never
{
    if (!in_array($status, [301, 302, 303, 307, 308], true)) {
        throw new InvalidArgumentException('Invalid redirect status.');
    }

    header('Location: ' . medicore_url($route, $query), true, $status);
    exit;
}

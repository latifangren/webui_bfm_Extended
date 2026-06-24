<?php
/**
 * BOX UI Extended — Front Controller (Router)
 * 
 * Simple router for the new pages structure.
 * Maps clean URLs to the correct includes/ or pages/ file.
 * 
 * For backward compat, old direct file paths still work
 * (PHP built-in server serves them directly).
 * 
 * USAGE: Set PHP built-in server to use this as router:
 *   php -t files/www files/www/router.php
 * 
 * Or include at the top of index.php for routing.
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// ── NEW ROUTES: /n/ or /network/ prefix ─────────────────
switch (true) {
    // Network pages
    case $uri === '/network/monitor' || $uri === '/network/monitor/':
        require __DIR__ . '/pages/network/monitor.php';
        return true;

    case $uri === '/network/tools' || $uri === '/network/tools/':
        require __DIR__ . '/pages/network/tools.php';
        return true;

    // API endpoints
    case str_starts_with($uri, '/api/'):
        $api_file = __DIR__ . $uri . '.php';
        if (file_exists($api_file)) {
            require $api_file;
            return true;
        }
        return false;

    // Default: let PHP built-in server handle (serve static files)
    default:
        $filePath = __DIR__ . $uri;
        $ext = pathinfo($uri, PATHINFO_EXTENSION);
        // If it's a real file, serve it directly
        if (file_exists($filePath) && is_file($filePath)) {
            return false;
        }
        // If it's a directory index, serve it
        if (is_dir($filePath) && file_exists($filePath . '/index.php')) {
            require $filePath . '/index.php';
            return true;
        }
        // 404 — show branded page
        http_response_code(404);
        require __DIR__ . '/pages/error/404.php';
        return true;
}

return false;

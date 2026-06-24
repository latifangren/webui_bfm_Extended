<?php
/**
 * BOX UI Extended — Bootstrap
 * 
 * Single entry point for autoloading, config, and shared setup.
 * Include this at the top of every page.
 */

// ── Paths ───────────────────────────────────────────────
define('BOXUI_ROOT', dirname(__DIR__));
define('BOXUI_INCLUDES', BOXUI_ROOT . '/includes');
define('BOXUI_PAGES', BOXUI_ROOT . '/pages');
define('BOXUI_AUTH', BOXUI_INCLUDES . '/auth');
define('BOXUI_COMMANDS', BOXUI_INCLUDES . '/commands');
define('BOXUI_FEATURES', BOXUI_INCLUDES . '/features');

// ── Session ─────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 31536000, // 1 year
    ]);
}

// ── Auth Config ─────────────────────────────────────────
$config_file = BOXUI_ROOT . '/auth/config.json';
if (file_exists($config_file)) {
    $boxui_config = json_decode(file_get_contents($config_file), true);
    define('BOXUI_LOGIN_ENABLED', $boxui_config['LOGIN_ENABLED'] ?? true);
} else {
    define('BOXUI_LOGIN_ENABLED', true);
}

// ── Simple PSR-4 style autoloader ───────────────────────
spl_autoload_register(function ($class) {
    // Convert: BoxUI\Auth\AuthService -> includes/auth/AuthService.php
    $prefix = 'BoxUI\\';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $relative_class = substr($class, strlen($prefix));
    $file = BOXUI_INCLUDES . '/' . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// ── Load functions (non-OOP helpers) ────────────────────
$helpers_file = BOXUI_INCLUDES . '/helpers.php';
if (file_exists($helpers_file)) {
    require $helpers_file;
}

// ── Auth guard helper ───────────────────────────────────
function boxui_require_auth(): void {
    if (BOXUI_LOGIN_ENABLED && !isset($_SESSION['user_id'])) {
        $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
        header('Location: /auth/login.php');
        exit;
    }
}

function boxui_is_authenticated(): bool {
    return !BOXUI_LOGIN_ENABLED || isset($_SESSION['user_id']);
}

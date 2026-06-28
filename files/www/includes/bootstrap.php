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
    // Security flags: HttpOnly prevents JS access, SameSite=Lax mitigates CSRF
    session_set_cookie_params([
        'lifetime' => 31536000, // 1 year
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => false, // HTTP-only environment (no HTTPS)
    ]);
    session_start();
}

// Idle timeout: 30 minutes without activity = force relogin
if (isset($_SESSION['user_id'])) {
    $idleMax = 1800; // 30 minutes
    $lastAct = $_SESSION['last_activity'] ?? 0;
    if ($lastAct > 0 && (time() - $lastAct) > $idleMax) {
        $_SESSION = [];
        session_destroy();
        header('Location: /auth/login.php?expired=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
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
    // Convert: BoxUI\Auth\AuthService -> includes/auth/AuthService.php (supports case-sensitive OS)
    $prefix = 'BoxUI\\';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $relative_class = substr($class, strlen($prefix));
    $parts = explode('\\', $relative_class);
    $className = array_pop($parts);
    $dirParts = array_map('strtolower', $parts);
    $dirPath = count($dirParts) > 0 ? implode('/', $dirParts) . '/' : '';
    $file = BOXUI_INCLUDES . '/' . $dirPath . $className . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// ── Load functions (non-OOP helpers) ────────────────────
$helpers_file = BOXUI_INCLUDES . '/helpers.php';
if (file_exists($helpers_file)) {
    require $helpers_file;
}

// ── Error Reporting ─────────────────────────────────────
define('BOXUI_DEBUG', true);
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', BOXUI_ROOT . '/../error.log');

// ── Shutdown handler for fatal errors ───────────────────
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $msg = date('Y-m-d H:i:s') . " [FATAL] {$e['message']} in {$e['file']}:{$e['line']}" . PHP_EOL;
        @file_put_contents(BOXUI_ROOT . '/../error.log', $msg, FILE_APPEND);
        // Attempt to show error page
        if (file_exists(BOXUI_PAGES . '/error/500.php')) {
            $errorMsg = $e['message'];
            $errorFile = $e['file'];
            $errorLine = $e['line'];
            http_response_code(500);
            require BOXUI_PAGES . '/error/500.php';
        }
    }
});

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

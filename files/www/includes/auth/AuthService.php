<?php
namespace BoxUI\Auth;

/**
 * BOX UI Extended — Authentication Service
 * 
 * Single source of truth for auth logic.
 * No file should call session_start() or check $_SESSION directly
 * for auth purposes — use this service instead.
 */
class AuthService
{
    private static $credentials = null;
    private static $config = null;

    /**
     * Load credentials from config.json and credentials.php.
     */
    public static function init(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_lifetime' => 31536000,
            ]);
        }
    }

    /**
     * Check if login is enabled globally.
     */
    public static function isLoginEnabled(): bool
    {
        if (defined('BOXUI_LOGIN_ENABLED')) {
            return BOXUI_LOGIN_ENABLED;
        }
        $config = self::loadConfig();
        return $config['LOGIN_ENABLED'] ?? true;
    }

    /**
     * Check if current user is authenticated.
     */
    public static function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * Require auth or redirect to login.
     */
    public static function requireAuth(string $redirect = null): void
    {
        if (!self::isLoginEnabled()) {
            return;
        }
        if (!self::isAuthenticated()) {
            $_SESSION['redirect_to'] = $redirect ?? $_SERVER['REQUEST_URI'];
            header('Location: /auth/login.php');
            exit;
        }
    }

    /**
     * Attempt login with username + password.
     */
    public static function login(string $username, string $password): bool
    {
        $creds = self::loadCredentials();
        if ($username === $creds['username'] && password_verify($password, $creds['hashed_password'])) {
            session_regenerate_id(true); // Prevent session fixation
            $_SESSION['user_id'] = session_id();
            $_SESSION['username'] = $username;
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time();
            return true;
        }
        return false;
    }

    /**
     * Logout current session.
     */
    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    /**
     * Get redirect URL after login and clear it from session.
     */
    public static function consumeRedirect(string $default = '/'): string
    {
        $url = $_SESSION['redirect_to'] ?? $default;
        unset($_SESSION['redirect_to']);
        return $url;
    }

    /**
     * Change password (hashes and saves).
     * Optionally update username — keeps existing if not provided.
     */
    public static function changePassword(string $newPassword, string $newUsername = null): bool
    {
        $creds = self::loadCredentials();
        $username = $newUsername ?? $creds['username'];

        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $configPath = dirname(__DIR__, 2) . '/auth/credentials.php';
        $username_esc = addslashes($username);
        $content = '<?php
if (basename(__FILE__) == basename($_SERVER[\'PHP_SELF\'])) {
    header(\'Location: /\');
    exit;
}
return [
    \'username\' => \'' . $username_esc . '\',
    \'hashed_password\' => \'' . $hash . '\',
];
';
        return file_put_contents($configPath, $content) !== false;
    }

    // ── Private ──────────────────────────────────────────

    private static function loadCredentials(): array
    {
        if (self::$credentials !== null) {
            return self::$credentials;
        }
        $path = dirname(__DIR__, 2) . '/auth/credentials.php';
        if (file_exists($path)) {
            self::$credentials = require $path;
        } else {
            self::$credentials = [
                'username' => 'admin',
                'hashed_password' => '$2y$10$vus0vO2fKBIxW9JqYreCIenXsN843CnnWef20PXgGkn6OGPNjM3Cq',
            ];
        }
        return self::$credentials;
    }

    private static function loadConfig(): array
    {
        if (self::$config !== null) {
            return self::$config;
        }
        $path = dirname(__DIR__, 2) . '/auth/config.json';
        if (file_exists($path)) {
            self::$config = json_decode(file_get_contents($path), true) ?? [];
        } else {
            self::$config = ['LOGIN_ENABLED' => true];
        }
        return self::$config;
    }
}

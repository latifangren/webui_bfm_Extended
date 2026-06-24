<?php
/**
 * BOX UI Extended — Entry Point
 * 
 * Loads bootstrap, checks auth, renders SPA shell.
 * Theme selection via select_theme/theme.json.
 */
require_once __DIR__ . '/includes/bootstrap.php';

use BoxUI\Auth\AuthService;
use BoxUI\Module\ModuleRegistry;

AuthService::init();
AuthService::requireAuth();

// Theme loader (backward compat for old custom themes)
$themeJson = boxui_theme_json_path();
if (file_exists($themeJson)) {
    $themeData = boxui_json_read($themeJson);
    $themePath = $themeData['path'] ?? '';

    // Old theme system (user has a custom theme file)
    if ($themePath !== '' && $themePath !== 'extended') {
        $themeFile = __DIR__ . '/' . $themePath . '.php';
        if (file_exists($themeFile)) {
            require $themeFile;
            exit;
        }
    }
}

// Default: use new SPA layout (no config needed)
$title = 'BOX UI Extended';
$content = '';
require __DIR__ . '/pages/layouts/default.php';

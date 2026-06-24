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

// Theme loader (backward compat)
$themeJson = boxui_theme_json_path();
if (file_exists($themeJson)) {
    $themeData = boxui_json_read($themeJson);
    $themePath = $themeData['path'] ?? 'default';

    // If theme is 'extended', use our new layout instead of extended.php
    if ($themePath === 'extended') {
        // Load via new layout system
        $title = 'BOX UI Extended';
        $content = ''; // Will be loaded via JS
        require __DIR__ . '/pages/layouts/default.php';
        exit;
    }

    // Fallback: old theme system
    $themeFile = __DIR__ . '/' . $themePath . '.php';
    if (file_exists($themeFile)) {
        require $themeFile;
        exit;
    }
}

// Ultimate fallback — old extended.php
require __DIR__ . '/extended.php';

<?php
/**
 * BOX UI Extended — Shared helper functions
 * Pure functions only — no side effects, no state.
 */

/**
 * Get the current host from HTTP_HOST (without port).
 */
function boxui_host(): string {
    $p = $_SERVER['HTTP_HOST'] ?? '127.0.0.1';
    $x = explode(':', $p);
    return $x[0];
}

/**
 * Get module directory (parent of this www dir).
 */
function boxui_module_dir(): string {
    return dirname(__DIR__, 3); // files/www -> files -> php7 -> /data/adb/php7
}

/**
 * Theme JSON path.
 */
function boxui_theme_json_path(): string {
    return __DIR__ . '/../select_theme/theme.json';
}

/**
 * Safe JSON read with fallback.
 */
function boxui_json_read(string $path, array $default = []): array {
    if (!file_exists($path)) {
        return $default;
    }
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : $default;
}

/**
 * HTML escape helper.
 */
function boxui_e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Format bytes to human-readable.
 */
function boxui_format_bytes(int $bytes, int $precision = 2): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
}

/**
 * Format uptime seconds to human-readable.
 */
function boxui_format_uptime(int $seconds): string {
    $days = floor($seconds / 86400);
    $hours = floor(($seconds % 86400) / 3600);
    $mins = floor(($seconds % 3600) / 60);
    $parts = [];
    if ($days > 0) $parts[] = "{$days}h";
    if ($hours > 0) $parts[] = "{$hours}j";
    $parts[] = "{$mins}m";
    return implode(' ', $parts);
}

<?php
/**
 * Clash Status API — used by sidebar to show Clash running/stopped.
 * 
 * Lightweight endpoint, no HTML.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

use BoxUI\Auth\AuthService;

AuthService::init();
if (!AuthService::isAuthenticated()) {
    http_response_code(403);
    echo 'unauthenticated';
    exit;
}

// Check multiple Clash/BFR indicators
$checks = [
    shell_exec('box.service status 2>&1'),
    shell_exec('pidof clash 2>/dev/null'),
    shell_exec('pidof bfr 2>/dev/null'),
];

$output = implode('', $checks);

if (stripos($output, 'running') !== false || stripos($output, 'active') !== false || !empty(trim($checks[1] ?? ''))) {
    echo 'running';
} else {
    echo 'stopped';
}

<?php
/**
 * Clash Status API — used by sidebar to show Clash running/stopped.
 * 
 * Lightweight endpoint, no HTML.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

use BoxUI\Auth\AuthService;
use BoxUI\Commands\CommandRunner;

AuthService::init();
if (!AuthService::isAuthenticated()) {
    http_response_code(403);
    echo 'unauthenticated';
    exit;
}

// Check multiple Clash/BFR indicators
$checks = [
    CommandRunner::box_status(),
    CommandRunner::sh('pidof clash 2>/dev/null'),
    CommandRunner::sh('pidof bfr 2>/dev/null'),
];

$output = implode('', $checks);

if (stripos($output, 'running') !== false || stripos($output, 'active') !== false || !empty(trim($checks[1] ?? ''))) {
    echo 'running';
} else {
    echo 'stopped';
}

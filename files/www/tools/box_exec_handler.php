<?php
/**
 * BOX Execution (BFR Service Control) — POST handler
 * 
 * AJAX handler for start/stop/restart actions.
 * Separated from pages/box/executed.php for clean view separation.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

use BoxUI\Auth\AuthService;
use BoxUI\Features\Box\BoxService;

AuthService::init();
AuthService::requireAuth();

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'start':
        BoxService::start();
        break;
    case 'stop':
        BoxService::stop();
        break;
    case 'restart':
        BoxService::restart();
        break;
    default:
        http_response_code(400);
        header('Content-Type: text/plain');
        echo "Unknown action: " . htmlspecialchars($action);
        exit;
}

header('Content-Type: text/plain');
echo "OK: {$action}";

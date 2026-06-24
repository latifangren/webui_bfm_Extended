<?php
/**
 * ADB Tools — POST handler
 * Extracted from adb_tools.php for clean separation.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

use BoxUI\Auth\AuthService;
use BoxUI\Features\Services\ServicesService;

AuthService::init();
AuthService::requireAuth();

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'enable_tcpip':
        echo ServicesService::adbEnableTcpIp();
        break;
    case 'disable_tcpip':
        echo ServicesService::adbDisableTcpIp();
        break;
    case 'restart_server':
        echo ServicesService::adbRestartServer();
        break;
    default:
        http_response_code(400);
        echo "Unknown action: " . htmlspecialchars($action);
}

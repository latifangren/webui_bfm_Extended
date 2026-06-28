<?php
/**
 * Ping Monitor — AJAX handler.
 * POST: start / stop / restart
 * GET:  get_status (JSON)
 */
require_once __DIR__ . '/../../includes/bootstrap.php';

use BoxUI\Features\Network\PingMonitorService;

// GET — status JSON
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_status') {
    header('Content-Type: application/json');
    echo json_encode(PingMonitorService::getStatusData());
    exit;
}

// POST — actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    switch ($action) {
        case 'start':
            echo PingMonitorService::start();
            break;
        case 'stop':
            echo PingMonitorService::stop();
            break;
        case 'restart':
            PingMonitorService::stop();
            sleep(1);
            echo PingMonitorService::start();
            break;
        default:
            http_response_code(400);
            echo 'Unknown action';
    }
    exit;
}

http_response_code(400);
echo 'Invalid request';

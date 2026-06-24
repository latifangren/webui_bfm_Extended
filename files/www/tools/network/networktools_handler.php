<?php
/**
 * Network Tools — POST handler
 * 
 * Extracted from networktools.php for clean separation.
 * Handles all form submissions, returns response for HTMX/fetch.
 */
require_once __DIR__ . '/../../includes/bootstrap.php';

use BoxUI\Auth\AuthService;
use BoxUI\Features\Network\NetworkService;

AuthService::init();
AuthService::requireAuth();

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'enable_airplane_mode':
        $radios = ['wifi' => 'off', 'bluetooth' => 'off'];
        if (!isset($_POST['cell'])) $radios['mobile_data'] = 'on';
        if (!isset($_POST['bluetooth'])) unset($radios['bluetooth']);
        NetworkService::setRadios($radios);
        NetworkService::setAirplaneMode(true);
        echo "<p class='green-text' style='color:#4CAF50;'>Mode pesawat diaktifkan.</p>";
        break;

    case 'disable_airplane_mode':
        NetworkService::setAirplaneMode(false);
        $enabled_radios = isset($_POST['enabled_radios']) ? json_decode($_POST['enabled_radios'], true) : [];
        if (!empty($enabled_radios)) {
            NetworkService::setRadios($enabled_radios);
        }
        echo "<p class='green-text' style='color:#4CAF50;'>Mode pesawat dinonaktifkan.</p>";
        break;

    default:
        http_response_code(400);
        echo "<p class='red-text' style='color:#ff4444;'>Unknown action: " . htmlspecialchars($action) . "</p>";
}

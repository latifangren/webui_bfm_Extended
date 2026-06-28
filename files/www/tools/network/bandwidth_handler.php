<?php
/**
 * Bandwidth Monitor (vnStat) — POST handler
 * 
 * Separated from pages/network/bandwidth.php for clean view separation.
 */
require_once __DIR__ . '/../../includes/bootstrap.php';

use BoxUI\Auth\AuthService;
use BoxUI\Features\Network\NetworkService;

AuthService::init();
AuthService::requireAuth();

if (isset($_POST['reset_vnstat'])) {
    NetworkService::vnstatReset();
} elseif (isset($_POST['start_vnstat'])) {
    NetworkService::vnstatStart();
}

if (isset($_SERVER['HTTP_HX_REQUEST'])) {
    header('HX-Location: /pages/network/bandwidth.php');
} else {
    header('Location: /pages/network/bandwidth.php');
}
exit;

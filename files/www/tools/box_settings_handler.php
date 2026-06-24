<?php
/**
 * BOX Settings (BFR Configuration) — POST handler
 *
 * Separated from pages/box/settings.php for clean view separation.
 * Processes form submission and redirects back with status.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

use BoxUI\Auth\AuthService;
use BoxUI\Features\Box\BoxService;

AuthService::init();
AuthService::requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
    $result = BoxService::saveSettings($_POST);
    header('Location: /pages/box/settings.php?msg=' . urlencode($result));
    exit;
}

header('Location: /pages/box/settings.php');
exit;

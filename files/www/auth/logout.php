<?php
/**
 * Logout — refactored using AuthService.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

use BoxUI\Auth\AuthService;

AuthService::init();
AuthService::logout();

header('Location: login.php?loggedout=1');
exit;

<?php
/**
 * Login page — refactored using AuthService.
 * 
 * Auth logic delegated to AuthService.
 * View template pulled from pages/auth/login.php.
 */

// Bootstrap
require_once __DIR__ . '/../includes/bootstrap.php';

use BoxUI\Auth\AuthService;

AuthService::init();

// If login disabled, redirect
if (!AuthService::isLoginEnabled()) {
    $_SESSION['login_disabled'] = true;
    header("Location: /");
    exit;
}

// If already logged in, redirect
if (AuthService::isAuthenticated()) {
    $redirect = AuthService::consumeRedirect('/');
    header("Location: {$redirect}");
    exit;
}

// Handle login POST
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (AuthService::login($username, $password)) {
        $redirect = AuthService::consumeRedirect('/');
        header("Location: {$redirect}");
        exit;
    } else {
        $error = 'Username atau password tidak valid.';
    }
}

// Render login view
require __DIR__ . '/../pages/auth/login.php';

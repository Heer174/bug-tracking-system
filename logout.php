<?php
/**
 * Bug Tracking System - Logout Endpoint
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

logout_user();

// Re-start a clean session just to carry the flash message
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
set_flash('info', 'You have been successfully logged out.');

header('Location: ' . BASE_URL . '/login.php');
exit;

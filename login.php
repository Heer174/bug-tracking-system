<?php
/**
 * Bug Tracking System - User Login
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$error = null;
$emailValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf($token)) {
        $error = 'Security session expired. Please refresh the page and try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $emailValue = $email;

        $authResult = authenticate_user($email, $password);
        if ($authResult['success']) {
            set_flash('success', 'Welcome back, ' . htmlspecialchars($authResult['user']['name']) . '!');
            header('Location: ' . BASE_URL . '/dashboard.php');
            exit;
        } else {
            $error = $authResult['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - <?= APP_NAME; ?></title>
    <link rel="stylesheet" href="<?= BASE_URL; ?>/assets/css/style.css">
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <a href="<?= BASE_URL; ?>/index.php" class="auth-logo">
                <div class="logo-badge" style="width: 34px; height: 34px;">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <rect x="6" y="9" width="12" height="11" rx="2"></rect>
                        <path d="M10 5a2 2 0 1 1 4 0"></path>
                    </svg>
                </div>
                <span><?= APP_NAME; ?></span>
            </a>
            <h2 style="font-size: 1.4rem; color: var(--navy-900); margin-top: 10px;">Sign in to your account</h2>
            <p style="font-size: 0.88rem; color: var(--slate-500); margin-top: 4px;">Enter your credentials to access the bug tracker</p>
        </div>

        <?= render_flash_messages(); ?>

        <?php if ($error): ?>
            <div class="alert alert-danger" style="margin-bottom: 20px;">
                <div class="alert-icon">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </div>
                <div class="alert-message"><?= e($error); ?></div>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL; ?>/login.php" method="POST" autocomplete="off">
            <?= csrf_field(); ?>

            <div class="form-group">
                <label for="email" class="form-label">Email Address <span class="required">*</span></label>
                <input type="email" id="email" name="email" class="form-control" placeholder="name@company.com" required value="<?= e($emailValue); ?>">
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <label for="password" class="form-label" style="margin-bottom: 0;">Password <span class="required">*</span></label>
                </div>
                <input type="password" id="password" name="password" class="form-control" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; margin-top: 8px;">
                Sign In
            </button>
        </form>

        <div style="margin-top: 20px; text-align: center; font-size: 0.88rem; color: var(--slate-600);">
            Don't have an account? <a href="<?= BASE_URL; ?>/register.php" style="font-weight: 600;">Create Tester Account</a>
        </div>

        <!-- Quick Evaluation / Demo autofill helper -->
        <div class="demo-pills">
            <div class="demo-pills-title">Academic Demo Quick Login:</div>
            <div class="demo-buttons">
                <button type="button" class="btn btn-secondary btn-sm" onclick="fillCredentials('admin@bugtrace.com', 'admin123')">
                    &bull; Admin
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="fillCredentials('alex.dev@bugtrace.com', 'dev123')">
                    &bull; Developer
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="fillCredentials('mike.tester@bugtrace.com', 'tester123')">
                    &bull; Tester
                </button>
            </div>
        </div>
    </div>
</div>

<script src="<?= BASE_URL; ?>/assets/js/main.js"></script>
</body>
</html>

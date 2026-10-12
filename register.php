<?php
/**
 * Bug Tracking System - User Registration (Tester / Reporter)
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
$nameValue = '';
$emailValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf($token)) {
        $error = 'Security session expired. Please refresh the page and try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        $nameValue = $name;
        $emailValue = $email;

        if (empty($name) || empty($email) || empty($password)) {
            $error = 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($password !== $passwordConfirm) {
            $error = 'Passwords do not match.';
        } else {
            $regResult = register_new_user($name, $email, $password, 'tester');
            if ($regResult['success']) {
                set_flash('success', 'Account registered successfully! You can now log in as a Tester.');
                header('Location: ' . BASE_URL . '/login.php');
                exit;
            } else {
                $error = $regResult['message'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Account - <?= APP_NAME; ?></title>
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
            <h2 style="font-size: 1.4rem; color: var(--navy-900); margin-top: 10px;">Create Tester Account</h2>
            <p style="font-size: 0.88rem; color: var(--slate-500); margin-top: 4px;">Join the QA team to report and verify bugs</p>
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

        <form action="<?= BASE_URL; ?>/register.php" method="POST" autocomplete="off">
            <?= csrf_field(); ?>

            <div class="form-group">
                <label for="name" class="form-label">Full Name <span class="required">*</span></label>
                <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Jane Doe" required value="<?= e($nameValue); ?>">
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email Address <span class="required">*</span></label>
                <input type="email" id="email" name="email" class="form-control" placeholder="name@company.com" required value="<?= e($emailValue); ?>">
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password <span class="required">*</span></label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Minimum 6 characters" required minlength="6">
            </div>

            <div class="form-group">
                <label for="password_confirm" class="form-label">Confirm Password <span class="required">*</span></label>
                <input type="password" id="password_confirm" name="password_confirm" class="form-control" placeholder="Re-enter password" required minlength="6">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; margin-top: 8px;">
                Register as Tester
            </button>
        </form>

        <div style="margin-top: 20px; text-align: center; font-size: 0.88rem; color: var(--slate-600);">
            Already have an account? <a href="<?= BASE_URL; ?>/login.php" style="font-weight: 600;">Sign In</a>
        </div>
    </div>
</div>

<script src="<?= BASE_URL; ?>/assets/js/main.js"></script>
</body>
</html>

<?php
/**
 * Bug Tracking System - User Profile & Security Settings
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$db = get_db();
$currentUser = current_user();

$errors = [];
$successMsg = null;

// Fetch full user record
$stmtUser = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmtUser->execute([':id' => $currentUser['id']]);
$userRecord = $stmtUser->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf($token)) {
        $errors[] = 'Security validation token expired. Please reload and retry.';
    } else {
        $action = $_POST['action'] ?? '';

        // 1. Update Profile Info
        if ($action === 'update_profile') {
            $name = trim($_POST['name'] ?? '');
            if (empty($name)) {
                $errors[] = 'Display name cannot be empty.';
            } else {
                $stmtUp = $db->prepare("UPDATE users SET name = :name, updated_at = NOW() WHERE id = :id");
                $stmtUp->execute([':name' => $name, ':id' => $currentUser['id']]);
                $_SESSION['user_name'] = $name;
                $userRecord['name'] = $name;
                set_flash('success', 'Profile information updated successfully.');
                header('Location: ' . BASE_URL . '/profile.php');
                exit;
            }
        }

        // 2. Change Password
        if ($action === 'change_password') {
            $currentPass = $_POST['current_password'] ?? '';
            $newPass     = $_POST['new_password'] ?? '';
            $confirmPass = $_POST['confirm_password'] ?? '';

            if (empty($currentPass) || empty($newPass)) {
                $errors[] = 'All password fields are required.';
            } elseif (!password_verify($currentPass, $userRecord['password'])) {
                $errors[] = 'The current password you entered is incorrect.';
            } elseif (strlen($newPass) < 6) {
                $errors[] = 'New password must be at least 6 characters.';
            } elseif ($newPass !== $confirmPass) {
                $errors[] = 'New passwords do not match.';
            } else {
                $newHash = password_hash($newPass, PASSWORD_BCRYPT);
                $stmtPwd = $db->prepare("UPDATE users SET password = :password, updated_at = NOW() WHERE id = :id");
                $stmtPwd->execute([':password' => $newHash, ':id' => $currentUser['id']]);
                set_flash('success', 'Your password has been changed successfully.');
                header('Location: ' . BASE_URL . '/profile.php');
                exit;
            }
        }
    }
}

$pageTitle = 'My Profile';
require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 800px; margin: 0 auto;">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" style="margin-bottom: 24px;">
            <div class="alert-icon">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            </div>
            <div class="alert-message">
                <ul style="margin: 0; padding-left: 18px;">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <!-- User Profile Details -->
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h2 class="card-title">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                Personal Profile Details
            </h2>
        </div>
        <div class="card-body">
            <form action="<?= BASE_URL; ?>/profile.php" method="POST">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="update_profile">

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name <span class="required">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?= e($userRecord['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Address (Read-only)</label>
                        <input type="email" class="form-control" value="<?= e($userRecord['email']); ?>" disabled style="background: var(--slate-100); color: var(--slate-600);">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Assigned Role</label>
                        <div><?= render_role_badge($userRecord['role']); ?></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Member Since</label>
                        <div style="font-size: 0.9rem; color: var(--navy-800); padding-top: 6px;">
                            <?= format_datetime($userRecord['created_at']); ?>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top: 10px;">
                    Update Profile
                </button>
            </form>
        </div>
    </div>

    <!-- Security & Password Change -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                Security &amp; Change Password
            </h2>
        </div>
        <div class="card-body">
            <form action="<?= BASE_URL; ?>/profile.php" method="POST">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="change_password">

                <div class="form-group">
                    <label class="form-label">Current Password <span class="required">*</span></label>
                    <input type="password" name="current_password" class="form-control" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">New Password <span class="required">*</span></label>
                        <input type="password" name="new_password" class="form-control" placeholder="Minimum 6 characters" required minlength="6">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Confirm New Password <span class="required">*</span></label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter new password" required minlength="6">
                    </div>
                </div>

                <button type="submit" class="btn btn-secondary" style="margin-top: 10px;">
                    Change Password
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

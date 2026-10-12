<?php
/**
 * Bug Tracking System - User Management (Admin Only)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Strictly restrict to administrator
require_role('admin');

$db = get_db();
$currentUser = current_user();

$errors = [];
$successMsg = null;

// Handle POST actions (Create User, Update Role/Status, Reset Password)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf($token)) {
        set_flash('danger', 'Security validation token expired. Please retry.');
        header('Location: ' . BASE_URL . '/users.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    // 1. Create a new user
    if ($action === 'create_user') {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = trim($_POST['role'] ?? 'tester');

        if (empty($name) || empty($email) || empty($password)) {
            $errors[] = 'All fields are required to create a user account.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please provide a valid email address.';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        } elseif (!in_array($role, ['admin', 'developer', 'tester'], true)) {
            $errors[] = 'Invalid user role selected.';
        } else {
            // Check uniqueness
            $chkStmt = $db->prepare("SELECT id FROM users WHERE email = :email");
            $chkStmt->execute([':email' => $email]);
            if ($chkStmt->fetch()) {
                $errors[] = 'A user with this email already exists.';
            } else {
                $pwdHash = password_hash($password, PASSWORD_BCRYPT);
                $stmtAdd = $db->prepare("
                    INSERT INTO users (name, email, password, role, status, created_at)
                    VALUES (:name, :email, :password, :role, 'active', NOW())
                ");
                $stmtAdd->execute([
                    ':name'     => $name,
                    ':email'    => $email,
                    ':password' => $pwdHash,
                    ':role'     => $role
                ]);
                set_flash('success', "User '{$name}' created successfully as {$role}.");
                header('Location: ' . BASE_URL . '/users.php');
                exit;
            }
        }
    }

    // 2. Update user role and active status
    if ($action === 'update_user') {
        $targetId = (int)($_POST['user_id'] ?? 0);
        $newRole  = trim($_POST['role'] ?? '');
        $newStatus= trim($_POST['status'] ?? '');

        if ($targetId === $currentUser['id'] && $newStatus === 'inactive') {
            $errors[] = 'You cannot deactivate your own administrative account.';
        } elseif (in_array($newRole, ['admin', 'developer', 'tester'], true) && in_array($newStatus, ['active', 'inactive'], true)) {
            $stmtUp = $db->prepare("
                UPDATE users 
                SET role = :role, status = :status, updated_at = NOW() 
                WHERE id = :id
            ");
            $stmtUp->execute([
                ':role'   => $newRole,
                ':status' => $newStatus,
                ':id'     => $targetId
            ]);
            set_flash('success', "User account #{$targetId} updated successfully.");
            header('Location: ' . BASE_URL . '/users.php');
            exit;
        } else {
            $errors[] = 'Invalid role or status parameters.';
        }
    }

    // 3. Reset user password
    if ($action === 'reset_password') {
        $targetId = (int)($_POST['user_id'] ?? 0);
        $newPass  = $_POST['new_password'] ?? '';

        if (strlen($newPass) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        } else {
            $pwdHash = password_hash($newPass, PASSWORD_BCRYPT);
            $stmtReset = $db->prepare("UPDATE users SET password = :password, updated_at = NOW() WHERE id = :id");
            $stmtReset->execute([
                ':password' => $pwdHash,
                ':id'       => $targetId
            ]);
            set_flash('success', "Password for user #{$targetId} has been reset successfully.");
            header('Location: ' . BASE_URL . '/users.php');
            exit;
        }
    }
}

// Fetch all users with defect count metrics
$users = $db->query("
    SELECT 
        u.*,
        (SELECT COUNT(*) FROM bugs WHERE reporter_id = u.id) AS reported_count,
        (SELECT COUNT(*) FROM bugs WHERE assigned_to = u.id) AS assigned_count
    FROM users u
    ORDER BY u.id ASC
")->fetchAll();

$pageTitle = 'User Management';
require_once __DIR__ . '/includes/header.php';
?>

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

<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">
        <h2 class="card-title">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            System Users &amp; Access Controls
        </h2>
        <button type="button" class="btn btn-primary btn-sm" onclick="openModal('create-user-modal')">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Add New User
        </button>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th>User Details</th>
                    <th>Role</th>
                    <th>Account Status</th>
                    <th>Reported Bugs</th>
                    <th>Assigned Work</th>
                    <th>Registered</th>
                    <th style="width: 140px; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td style="font-weight: 700; color: var(--slate-500);">#<?= (int)$u['id']; ?></td>
                        <td>
                            <div style="font-weight: 600; color: var(--navy-900);"><?= e($u['name']); ?></div>
                            <div style="font-size: 0.8rem; color: var(--slate-500);"><?= e($u['email']); ?></div>
                        </td>
                        <td><?= render_role_badge($u['role']); ?></td>
                        <td>
                            <?php if ($u['status'] === 'active'): ?>
                                <span class="badge badge-active">&bull; Active</span>
                            <?php else: ?>
                                <span class="badge badge-inactive">&bull; Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size: 0.88rem; color: var(--navy-700); font-weight: 500;">
                            <?= (int)$u['reported_count']; ?> tickets
                        </td>
                        <td style="font-size: 0.88rem; color: var(--navy-700); font-weight: 500;">
                            <?= (int)$u['assigned_count']; ?> tickets
                        </td>
                        <td style="font-size: 0.82rem; color: var(--slate-500);">
                            <?= time_ago($u['created_at']); ?>
                        </td>
                        <td style="text-align: right;">
                            <div style="display: inline-flex; gap: 6px;">
                                <button type="button" class="btn btn-secondary btn-sm" onclick="editUser(<?= (int)$u['id']; ?>, '<?= e($u['name']); ?>', '<?= e($u['role']); ?>', '<?= e($u['status']); ?>')">
                                    Edit
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="resetUserPassword(<?= (int)$u['id']; ?>, '<?= e($u['name']); ?>')">
                                    Key
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Create New User -->
<div id="create-user-modal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title">Create New System User</h3>
            <button type="button" class="modal-close" onclick="closeModal('create-user-modal')">&times;</button>
        </div>
        <form action="<?= BASE_URL; ?>/users.php" method="POST">
            <?= csrf_field(); ?>
            <input type="hidden" name="action" value="create_user">

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Full Name <span class="required">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. David Miller" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address <span class="required">*</span></label>
                    <input type="email" name="email" class="form-control" placeholder="david@company.com" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Initial Password <span class="required">*</span></label>
                    <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters" required minlength="6">
                </div>

                <div class="form-group">
                    <label class="form-label">System Role <span class="required">*</span></label>
                    <select name="role" class="form-control" required>
                        <option value="tester">Tester / QA Reporter</option>
                        <option value="developer">Developer</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('create-user-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit User Role & Status -->
<div id="edit-user-modal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title">Edit User Account: <span id="edit-user-name"></span></h3>
            <button type="button" class="modal-close" onclick="closeModal('edit-user-modal')">&times;</button>
        </div>
        <form action="<?= BASE_URL; ?>/users.php" method="POST">
            <?= csrf_field(); ?>
            <input type="hidden" name="action" value="update_user">
            <input type="hidden" name="user_id" id="edit-user-id" value="">

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">System Role <span class="required">*</span></label>
                    <select name="role" id="edit-user-role" class="form-control" required>
                        <option value="admin">Administrator</option>
                        <option value="developer">Developer</option>
                        <option value="tester">Tester</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Account Status <span class="required">*</span></label>
                    <select name="status" id="edit-user-status" class="form-control" required>
                        <option value="active">Active (Permitted to log in)</option>
                        <option value="inactive">Inactive (Access deactivated)</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('edit-user-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Reset User Password -->
<div id="reset-password-modal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title">Reset Password: <span id="reset-user-name"></span></h3>
            <button type="button" class="modal-close" onclick="closeModal('reset-password-modal')">&times;</button>
        </div>
        <form action="<?= BASE_URL; ?>/users.php" method="POST">
            <?= csrf_field(); ?>
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="user_id" id="reset-user-id" value="">

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">New Password <span class="required">*</span></label>
                    <input type="password" name="new_password" class="form-control" placeholder="Minimum 6 characters" required minlength="6">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('reset-password-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Password</button>
            </div>
        </form>
    </div>
</div>

<script>
function editUser(id, name, role, status) {
    document.getElementById('edit-user-id').value = id;
    document.getElementById('edit-user-name').textContent = name;
    document.getElementById('edit-user-role').value = role;
    document.getElementById('edit-user-status').value = status;
    openModal('edit-user-modal');
}

function resetUserPassword(id, name) {
    document.getElementById('reset-user-id').value = id;
    document.getElementById('reset-user-name').textContent = name;
    openModal('reset-password-modal');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

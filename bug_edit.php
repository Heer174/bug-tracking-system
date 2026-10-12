<?php
/**
 * Bug Tracking System - Edit Bug Report
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$db = get_db();
$currentUser = current_user();

$bugId = (int)($_GET['id'] ?? 0);
if ($bugId <= 0) {
    set_flash('danger', 'Invalid bug ID.');
    header('Location: ' . BASE_URL . '/bugs.php');
    exit;
}

// Fetch Bug Record
$stmt = $db->prepare("SELECT * FROM bugs WHERE id = :id");
$stmt->execute([':id' => $bugId]);
$bug = $stmt->fetch();

if (!$bug) {
    set_flash('danger', 'Bug record not found.');
    header('Location: ' . BASE_URL . '/bugs.php');
    exit;
}

// Check authorization (Admin or original Reporter)
if ($currentUser['role'] !== 'admin' && $currentUser['id'] !== (int)$bug['reporter_id']) {
    set_flash('danger', 'You do not have permission to edit this bug ticket.');
    header('Location: ' . BASE_URL . "/bug_view.php?id={$bugId}");
    exit;
}

// Dropdown data
$projects = $db->query("SELECT id, name FROM projects ORDER BY name ASC")->fetchAll();
$developers = [];
if ($currentUser['role'] === 'admin') {
    $developers = $db->query("SELECT id, name FROM users WHERE role = 'developer' AND status = 'active' ORDER BY name ASC")->fetchAll();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf($token)) {
        $errors[] = 'Security token expired. Please refresh the page.';
    } else {
        $title       = trim($_POST['title'] ?? '');
        $projectId   = (int)($_POST['project_id'] ?? $bug['project_id']);
        $category    = trim($_POST['category'] ?? $bug['category']);
        $priority    = trim($_POST['priority'] ?? $bug['priority']);
        $severity    = trim($_POST['severity'] ?? $bug['severity']);
        $description = trim($_POST['description'] ?? '');
        $steps       = trim($_POST['steps_to_reproduce'] ?? '');
        $expected    = trim($_POST['expected_result'] ?? '');
        $actual      = trim($_POST['actual_result'] ?? '');

        // Validation
        if (empty($title)) {
            $errors[] = 'Bug title cannot be empty.';
        }
        if (empty($description)) {
            $errors[] = 'Bug description cannot be empty.';
        }

        // Handle replacement screenshot upload if submitted
        $newScreenshot = $bug['screenshot'];
        if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadRes = handle_screenshot_upload($_FILES['screenshot']);
            if (!$uploadRes['success']) {
                $errors[] = $uploadRes['error'];
            } else {
                // Delete previous screenshot if exists
                if ($bug['screenshot'] && file_exists(UPLOAD_DIR . DIRECTORY_SEPARATOR . $bug['screenshot'])) {
                    @unlink(UPLOAD_DIR . DIRECTORY_SEPARATOR . $bug['screenshot']);
                }
                $newScreenshot = $uploadRes['filename'];
            }
        }

        // Admin-only fields
        $assignedTo = $bug['assigned_to'];
        $newStatus  = $bug['status'];
        if ($currentUser['role'] === 'admin') {
            $assignedTo = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
            $newStatus  = trim($_POST['status'] ?? $bug['status']);
        }

        if (empty($errors)) {
            try {
                $stmtUpdate = $db->prepare("
                    UPDATE bugs SET
                        title = :title,
                        project_id = :project_id,
                        category = :category,
                        priority = :priority,
                        severity = :severity,
                        description = :description,
                        steps_to_reproduce = :steps,
                        expected_result = :expected,
                        actual_result = :actual,
                        screenshot = :screenshot,
                        assigned_to = :assigned_to,
                        status = :status,
                        updated_at = NOW()
                    WHERE id = :id
                ");

                $stmtUpdate->execute([
                    ':title'       => $title,
                    ':project_id'  => $projectId,
                    ':category'    => $category,
                    ':priority'    => $priority,
                    ':severity'    => $severity,
                    ':description' => $description,
                    ':steps'       => $steps ?: null,
                    ':expected'    => $expected ?: null,
                    ':actual'      => $actual ?: null,
                    ':screenshot'  => $newScreenshot,
                    ':assigned_to' => $assignedTo,
                    ':status'      => $newStatus,
                    ':id'          => $bugId
                ]);

                // If status changed, log in history
                if ($newStatus !== $bug['status']) {
                    log_bug_history(
                        $bugId, 
                        $currentUser['id'], 
                        $bug['status'], 
                        $newStatus, 
                        "Status updated during edit by {$currentUser['name']}"
                    );
                } else {
                    log_bug_history(
                        $bugId, 
                        $currentUser['id'], 
                        $bug['status'], 
                        $bug['status'], 
                        "Bug details updated by {$currentUser['name']}"
                    );
                }

                set_flash('success', "Bug ticket #{$bugId} details updated successfully.");
                header('Location: ' . BASE_URL . "/bug_view.php?id={$bugId}");
                exit;

            } catch (PDOException $e) {
                error_log("Error updating bug: " . $e->getMessage());
                $errors[] = "Failed to save updates to the database.";
            }
        }
    }
}

$pageTitle = "Edit Bug #" . $bug['id'];
require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 900px; margin: 0 auto;">
    <div style="margin-bottom: 20px;">
        <a href="<?= BASE_URL; ?>/bug_view.php?id=<?= $bugId; ?>" style="color: var(--slate-500); font-size: 0.88rem; display: inline-flex; align-items: center; gap: 6px;">
            &larr; Back to Bug #<?= $bugId; ?>
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h1 class="card-title" style="font-size: 1.25rem;">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                Edit Bug Ticket #<?= (int)$bug['id']; ?>
            </h1>
        </div>

        <div class="card-body">
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

            <form action="<?= BASE_URL; ?>/bug_edit.php?id=<?= $bugId; ?>" method="POST" enctype="multipart/form-data">
                <?= csrf_field(); ?>

                <!-- Title -->
                <div class="form-group">
                    <label for="title" class="form-label">Bug Summary / Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title" class="form-control" required value="<?= e($bug['title']); ?>">
                </div>

                <!-- Project & Category Row -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="project_id" class="form-label">Project <span class="required">*</span></label>
                        <select id="project_id" name="project_id" class="form-control" required>
                            <?php foreach ($projects as $proj): ?>
                                <option value="<?= (int)$proj['id']; ?>" <?= ($bug['project_id'] == $proj['id']) ? 'selected' : ''; ?>>
                                    <?= e($proj['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="category" class="form-label">Category <span class="required">*</span></label>
                        <select id="category" name="category" class="form-control" required>
                            <?php foreach (BUG_CATEGORIES as $cat): ?>
                                <option value="<?= e($cat); ?>" <?= ($bug['category'] === $cat) ? 'selected' : ''; ?>>
                                    <?= e($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Priority & Severity Row -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="priority" class="form-label">Priority <span class="required">*</span></label>
                        <select id="priority" name="priority" class="form-control" required>
                            <?php foreach (array_keys(BUG_PRIORITIES) as $pr): ?>
                                <option value="<?= e($pr); ?>" <?= ($bug['priority'] === $pr) ? 'selected' : ''; ?>>
                                    <?= e($pr); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="severity" class="form-label">Severity <span class="required">*</span></label>
                        <select id="severity" name="severity" class="form-control" required>
                            <?php foreach (array_keys(BUG_SEVERITIES) as $sev): ?>
                                <option value="<?= e($sev); ?>" <?= ($bug['severity'] === $sev) ? 'selected' : ''; ?>>
                                    <?= e($sev); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Admin Only Fields: Assignee & Status Override -->
                <?php if ($currentUser['role'] === 'admin'): ?>
                    <div class="form-row" style="background: var(--blue-50); padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--blue-100); margin-bottom: 20px;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="assigned_to" class="form-label" style="color: var(--blue-600);">Assigned Developer (Admin Control)</label>
                            <select id="assigned_to" name="assigned_to" class="form-control">
                                <option value="">-- Unassigned --</option>
                                <?php foreach ($developers as $dev): ?>
                                    <option value="<?= (int)$dev['id']; ?>" <?= ($bug['assigned_to'] == $dev['id']) ? 'selected' : ''; ?>>
                                        <?= e($dev['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="status" class="form-label" style="color: var(--blue-600);">Status Override (Admin Control)</label>
                            <select id="status" name="status" class="form-control">
                                <?php foreach (array_keys(BUG_STATUSES) as $st): ?>
                                    <option value="<?= e($st); ?>" <?= ($bug['status'] === $st) ? 'selected' : ''; ?>>
                                        <?= e($st); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Description -->
                <div class="form-group">
                    <label for="description" class="form-label">Description <span class="required">*</span></label>
                    <textarea id="description" name="description" class="form-control" rows="4" required><?= e($bug['description']); ?></textarea>
                </div>

                <!-- Steps to Reproduce -->
                <div class="form-group">
                    <label for="steps_to_reproduce" class="form-label">Steps to Reproduce</label>
                    <textarea id="steps_to_reproduce" name="steps_to_reproduce" class="form-control" rows="4"><?= e($bug['steps_to_reproduce']); ?></textarea>
                </div>

                <!-- Expected & Actual Result -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="expected_result" class="form-label">Expected Result</label>
                        <textarea id="expected_result" name="expected_result" class="form-control" rows="3"><?= e($bug['expected_result']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="actual_result" class="form-label">Actual Result</label>
                        <textarea id="actual_result" name="actual_result" class="form-control" rows="3"><?= e($bug['actual_result']); ?></textarea>
                    </div>
                </div>

                <!-- Screenshot update -->
                <div class="form-group">
                    <label for="screenshot" class="form-label">Replace Screenshot Evidence (Optional)</label>
                    <?php if (!empty($bug['screenshot'])): ?>
                        <div style="font-size: 0.82rem; color: var(--slate-600); margin-bottom: 8px;">
                            Current file: <code><?= e($bug['screenshot']); ?></code>
                        </div>
                    <?php endif; ?>
                    <input type="file" id="screenshot" name="screenshot" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
                </div>

                <div style="display: flex; gap: 12px; margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--slate-200);">
                    <button type="submit" class="btn btn-primary btn-lg">
                        Save Changes
                    </button>
                    <a href="<?= BASE_URL; ?>/bug_view.php?id=<?= $bugId; ?>" class="btn btn-secondary btn-lg">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

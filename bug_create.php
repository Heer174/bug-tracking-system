<?php
/**
 * Bug Tracking System - Report New Bug
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$db = get_db();
$currentUser = current_user();

// Fetch available projects
$projects = $db->query("SELECT id, name FROM projects ORDER BY name ASC")->fetchAll();

$errors = [];
$title = '';
$projectId = 0;
$category = 'UI / Layout';
$priority = 'Medium';
$severity = 'Major';
$description = '';
$steps = '';
$expected = '';
$actual = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf($token)) {
        $errors[] = 'Security token expired. Please refresh the page and try again.';
    } else {
        $title       = trim($_POST['title'] ?? '');
        $projectId   = (int)($_POST['project_id'] ?? 0);
        $category    = trim($_POST['category'] ?? 'UI / Layout');
        $priority    = trim($_POST['priority'] ?? 'Medium');
        $severity    = trim($_POST['severity'] ?? 'Major');
        $description = trim($_POST['description'] ?? '');
        $steps       = trim($_POST['steps_to_reproduce'] ?? '');
        $expected    = trim($_POST['expected_result'] ?? '');
        $actual      = trim($_POST['actual_result'] ?? '');

        // Validation
        if (empty($title)) {
            $errors[] = 'Bug title is required.';
        } elseif (strlen($title) < 5) {
            $errors[] = 'Bug title must be at least 5 characters.';
        }

        if ($projectId <= 0) {
            $errors[] = 'Please select a valid project.';
        }

        if (empty($description)) {
            $errors[] = 'A detailed description is required.';
        }

        if (!array_key_exists($priority, BUG_PRIORITIES)) {
            $errors[] = 'Invalid priority selection.';
        }

        if (!array_key_exists($severity, BUG_SEVERITIES)) {
            $errors[] = 'Invalid severity selection.';
        }

        // Screenshot upload handling
        $screenshotFilename = null;
        if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = handle_screenshot_upload($_FILES['screenshot']);
            if (!$uploadResult['success']) {
                $errors[] = $uploadResult['error'];
            } else {
                $screenshotFilename = $uploadResult['filename'];
            }
        }

        // If no errors, persist to database
        if (empty($errors)) {
            try {
                $stmt = $db->prepare("
                    INSERT INTO bugs (
                        title, description, project_id, reporter_id, 
                        category, priority, severity, status, 
                        steps_to_reproduce, expected_result, actual_result, 
                        screenshot, created_at, updated_at
                    ) VALUES (
                        :title, :description, :project_id, :reporter_id,
                        :category, :priority, :severity, 'New',
                        :steps, :expected, :actual,
                        :screenshot, NOW(), NOW()
                    )
                ");

                $stmt->execute([
                    ':title'       => $title,
                    ':description' => $description,
                    ':project_id'  => $projectId,
                    ':reporter_id' => $currentUser['id'],
                    ':category'    => $category,
                    ':priority'    => $priority,
                    ':severity'    => $severity,
                    ':steps'       => $steps ?: null,
                    ':expected'    => $expected ?: null,
                    ':actual'      => $actual ?: null,
                    ':screenshot'  => $screenshotFilename
                ]);

                $newBugId = (int)$db->lastInsertId();

                // Log into bug history
                log_bug_history(
                    $newBugId, 
                    $currentUser['id'], 
                    'None', 
                    'New', 
                    "Bug reported by {$currentUser['name']}"
                );

                set_flash('success', "Bug ticket #{$newBugId} has been reported successfully.");
                header('Location: ' . BASE_URL . "/bug_view.php?id={$newBugId}");
                exit;

            } catch (PDOException $e) {
                error_log("Error creating bug: " . $e->getMessage());
                $errors[] = "Failed to submit bug report due to a database error.";
            }
        }
    }
}

$pageTitle = 'Report a New Bug';
require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 900px; margin: 0 auto;">
    <div style="margin-bottom: 20px;">
        <a href="<?= BASE_URL; ?>/bugs.php" style="color: var(--slate-500); font-size: 0.88rem; display: inline-flex; align-items: center; gap: 6px;">
            &larr; Back to all tickets
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h1 class="card-title" style="font-size: 1.25rem;">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                Report a New Defect / Bug
            </h1>
            <span style="font-size: 0.82rem; color: var(--slate-500);">All fields with <span style="color: #e11d48;">*</span> are mandatory</span>
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

            <form action="<?= BASE_URL; ?>/bug_create.php" method="POST" enctype="multipart/form-data">
                <?= csrf_field(); ?>

                <!-- Title -->
                <div class="form-group">
                    <label for="title" class="form-label">Bug Summary / Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title" class="form-control" placeholder="e.g. 504 Gateway Timeout during checkout 3DS verification" required value="<?= e($title); ?>">
                    <div class="form-hint">Provide a concise, descriptive summary of the problem.</div>
                </div>

                <!-- Project & Category Row -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="project_id" class="form-label">Target Project <span class="required">*</span></label>
                        <select id="project_id" name="project_id" class="form-control" required>
                            <option value="">-- Choose Project --</option>
                            <?php foreach ($projects as $proj): ?>
                                <option value="<?= (int)$proj['id']; ?>" <?= ($projectId === (int)$proj['id']) ? 'selected' : ''; ?>>
                                    <?= e($proj['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="category" class="form-label">Category <span class="required">*</span></label>
                        <select id="category" name="category" class="form-control" required>
                            <?php foreach (BUG_CATEGORIES as $cat): ?>
                                <option value="<?= e($cat); ?>" <?= ($category === $cat) ? 'selected' : ''; ?>>
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
                                <option value="<?= e($pr); ?>" <?= ($priority === $pr) ? 'selected' : ''; ?>>
                                    <?= e($pr); ?> (<?= match($pr) { 'Critical' => 'Fix immediately', 'High' => 'Next sprint / release', 'Medium' => 'Standard queue', 'Low' => 'Minor cosmetic' }; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="severity" class="form-label">Severity <span class="required">*</span></label>
                        <select id="severity" name="severity" class="form-control" required>
                            <?php foreach (array_keys(BUG_SEVERITIES) as $sev): ?>
                                <option value="<?= e($sev); ?>" <?= ($severity === $sev) ? 'selected' : ''; ?>>
                                    <?= e($sev); ?> (<?= match($sev) { 'Blocker' => 'Halts all testing/build', 'Critical' => 'Crash/data loss', 'Major' => 'Core feature failure', 'Minor' => 'Workaround exists', 'Low' => 'Trivial' }; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Description -->
                <div class="form-group">
                    <label for="description" class="form-label">Detailed Description <span class="required">*</span></label>
                    <textarea id="description" name="description" class="form-control" rows="4" placeholder="Explain the defect in detail, including affected environments, browsers, or devices..." required><?= e($description); ?></textarea>
                </div>

                <!-- Steps to Reproduce -->
                <div class="form-group">
                    <label for="steps_to_reproduce" class="form-label">Steps to Reproduce</label>
                    <textarea id="steps_to_reproduce" name="steps_to_reproduce" class="form-control" rows="4" placeholder="1. Go to page '...&#10;2. Click on '...&#10;3. Observe behavior..."><?= e($steps); ?></textarea>
                    <div class="form-hint">List numbered, reproducible steps to help the developer identify the root cause quickly.</div>
                </div>

                <!-- Expected & Actual Result Row -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="expected_result" class="form-label">Expected Result</label>
                        <textarea id="expected_result" name="expected_result" class="form-control" rows="3" placeholder="What should happen according to specification?"><?= e($expected); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="actual_result" class="form-label">Actual Result</label>
                        <textarea id="actual_result" name="actual_result" class="form-control" rows="3" placeholder="What actually happened instead? (Errors, incorrect display, etc.)"><?= e($actual); ?></textarea>
                    </div>
                </div>

                <!-- Screenshot Attachment -->
                <div class="form-group">
                    <label for="screenshot" class="form-label">Attach Screenshot / Evidence (Optional)</label>
                    <input type="file" id="screenshot" name="screenshot" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
                    <div class="form-hint">Accepted formats: JPG, PNG, GIF, WebP. Max size: 5 MB.</div>
                    
                    <div id="screenshot-preview-container" style="display: none; margin-top: 14px;">
                        <span style="font-size: 0.8rem; font-weight: 600; color: var(--slate-600); display: block; margin-bottom: 6px;">Image Preview:</span>
                        <img id="screenshot-preview-image" src="" alt="Preview" style="max-height: 180px; border-radius: var(--radius-md); border: 1px solid var(--slate-300);">
                    </div>
                </div>

                <div style="display: flex; gap: 12px; margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--slate-200);">
                    <button type="submit" class="btn btn-primary btn-lg">
                        Submit Bug Report
                    </button>
                    <a href="<?= BASE_URL; ?>/bugs.php" class="btn btn-secondary btn-lg">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

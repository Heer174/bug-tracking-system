<?php
/**
 * Bug Tracking System - Bug Details, Comments & Status History
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$db = get_db();
$currentUser = current_user();

$bugId = (int)($_GET['id'] ?? 0);
if ($bugId <= 0) {
    set_flash('danger', 'Invalid bug ticket identifier.');
    header('Location: ' . BASE_URL . '/bugs.php');
    exit;
}

// Fetch Bug Record
$stmt = $db->prepare("
    SELECT 
        b.*,
        p.name AS project_name,
        p.description AS project_desc,
        u.name AS reporter_name,
        u.email AS reporter_email,
        u.role AS reporter_role,
        d.name AS dev_name,
        d.email AS dev_email
    FROM bugs b
    LEFT JOIN projects p ON b.project_id = p.id
    LEFT JOIN users u ON b.reporter_id = u.id
    LEFT JOIN users d ON b.assigned_to = d.id
    WHERE b.id = :id
");
$stmt->execute([':id' => $bugId]);
$bug = $stmt->fetch();

if (!$bug) {
    set_flash('danger', 'The requested bug report could not be found.');
    header('Location: ' . BASE_URL . '/bugs.php');
    exit;
}

// Handle POST actions: Status Update, Assignment, or New Comment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf($token)) {
        set_flash('danger', 'Security validation failed. Please try again.');
        header("Location: " . BASE_URL . "/bug_view.php?id={$bugId}");
        exit;
    }

    $action = $_POST['action'] ?? '';

    // 1. Post a new comment
    if ($action === 'add_comment') {
        $commentText = trim($_POST['comment_text'] ?? '');
        if (!empty($commentText)) {
            $stmtC = $db->prepare("
                INSERT INTO comments (bug_id, user_id, comment_text, created_at)
                VALUES (:bug_id, :user_id, :comment_text, NOW())
            ");
            $stmtC->execute([
                ':bug_id'       => $bugId,
                ':user_id'      => $currentUser['id'],
                ':comment_text' => $commentText
            ]);
            set_flash('success', 'Your comment has been added to the discussion.');
        } else {
            set_flash('warning', 'Comment text cannot be empty.');
        }
        header("Location: " . BASE_URL . "/bug_view.php?id={$bugId}");
        exit;
    }

    // 2. Update Status
    if ($action === 'update_status') {
        $newStatus = trim($_POST['new_status'] ?? '');
        $note = trim($_POST['status_note'] ?? '');
        $currentStatus = $bug['status'];

        $isAssigned = ($bug['assigned_to'] === $currentUser['id']);
        $isReporter = ($bug['reporter_id'] === $currentUser['id']);
        $allowedTransitions = get_allowed_status_transitions($currentUser['role'], $currentStatus, $isAssigned, $isReporter);

        if (in_array($newStatus, $allowedTransitions, true) && $newStatus !== $currentStatus) {
            $stmtUp = $db->prepare("
                UPDATE bugs 
                SET status = :status, updated_at = NOW() 
                WHERE id = :id
            ");
            $stmtUp->execute([
                ':status' => $newStatus,
                ':id'     => $bugId
            ]);

            $desc = $note ? "Status changed to {$newStatus}: {$note}" : "Status changed to {$newStatus} by {$currentUser['name']}";
            log_bug_history($bugId, $currentUser['id'], $currentStatus, $newStatus, $desc);

            set_flash('success', "Bug status successfully updated to '{$newStatus}'.");
        } else {
            set_flash('danger', 'You do not have permission to perform this status transition.');
        }
        header("Location: " . BASE_URL . "/bug_view.php?id={$bugId}");
        exit;
    }

    // 3. Assign Developer (Admin Only)
    if ($action === 'assign_developer') {
        require_role('admin');
        $assigneeId = !empty($_POST['developer_id']) ? (int)$_POST['developer_id'] : null;

        // Fetch developer name
        $devName = 'Unassigned';
        if ($assigneeId) {
            $stmtD = $db->prepare("SELECT name FROM users WHERE id = :id AND role = 'developer'");
            $stmtD->execute([':id' => $assigneeId]);
            $devName = $stmtD->fetchColumn() ?: 'Developer';
        }

        $prevStatus = $bug['status'];
        $newStatus = ($assigneeId && $prevStatus === 'New') ? 'Assigned' : $prevStatus;

        $stmtAssign = $db->prepare("
            UPDATE bugs 
            SET assigned_to = :assigned_to, status = :status, updated_at = NOW() 
            WHERE id = :id
        ");
        $stmtAssign->execute([
            ':assigned_to' => $assigneeId,
            ':status'      => $newStatus,
            ':id'          => $bugId
        ]);

        $changeDesc = $assigneeId 
            ? "Ticket assigned to {$devName} by {$currentUser['name']}" 
            : "Ticket unassigned by {$currentUser['name']}";

        log_bug_history($bugId, $currentUser['id'], $prevStatus, $newStatus, $changeDesc);

        set_flash('success', "Developer assignment updated successfully.");
        header("Location: " . BASE_URL . "/bug_view.php?id={$bugId}");
        exit;
    }
}

// Fetch Comments
$stmtComments = $db->prepare("
    SELECT c.*, u.name AS user_name, u.role AS user_role
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.bug_id = :bug_id
    ORDER BY c.created_at ASC
");
$stmtComments->execute([':bug_id' => $bugId]);
$comments = $stmtComments->fetchAll();

// Fetch Status History / Audit Trail
$stmtHistory = $db->prepare("
    SELECT h.*, u.name AS user_name, u.role AS user_role
    FROM bug_history h
    JOIN users u ON h.changed_by = u.id
    WHERE h.bug_id = :bug_id
    ORDER BY h.created_at DESC
");
$stmtHistory->execute([':bug_id' => $bugId]);
$history = $stmtHistory->fetchAll();

// Fetch developers for assignment dropdown (if Admin)
$allDevelopers = [];
if ($currentUser['role'] === 'admin') {
    $allDevelopers = $db->query("SELECT id, name FROM users WHERE role = 'developer' AND status = 'active' ORDER BY name ASC")->fetchAll();
}

$pageTitle = "Bug #" . $bug['id'] . " - " . $bug['title'];
require_once __DIR__ . '/includes/header.php';

$isAssigned = ($bug['assigned_to'] === $currentUser['id']);
$isReporter = ($bug['reporter_id'] === $currentUser['id']);
$allowedTransitions = get_allowed_status_transitions($currentUser['role'], $bug['status'], $isAssigned, $isReporter);
?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <a href="<?= BASE_URL; ?>/bugs.php" style="color: var(--slate-500); font-size: 0.88rem; display: inline-flex; align-items: center; gap: 6px;">
        &larr; Back to all tickets
    </a>

    <div style="display: flex; gap: 10px;">
        <?php if ($currentUser['role'] === 'admin' || $currentUser['id'] === (int)$bug['reporter_id']): ?>
            <a href="<?= BASE_URL; ?>/bug_edit.php?id=<?= (int)$bug['id']; ?>" class="btn btn-secondary btn-sm">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                Edit Bug
            </a>
        <?php endif; ?>
        <?php if ($currentUser['role'] === 'admin'): ?>
            <a href="<?= BASE_URL; ?>/bugs.php?action=delete&id=<?= (int)$bug['id']; ?>&csrf_token=<?= csrf_token(); ?>" 
               class="btn btn-danger btn-sm" 
               data-confirm="Are you sure you want to delete this bug ticket permanently?">
                Delete Ticket
            </a>
        <?php endif; ?>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2.2fr 1fr; gap: 24px; align-items: start;" class="bug-detail-grid">
    <!-- Left Column: Primary Details, Reproduction, Evidence, Comments -->
    <div>
        <!-- Main Bug Header Card -->
        <div class="card">
            <div class="card-header" style="align-items: flex-start; gap: 16px;">
                <div style="flex: 1;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; flex-wrap: wrap;">
                        <span style="font-size: 0.9rem; font-weight: 700; color: var(--slate-500);">#<?= (int)$bug['id']; ?></span>
                        <?= render_status_badge($bug['status']); ?>
                        <?= render_priority_badge($bug['priority']); ?>
                        <?= render_severity_badge($bug['severity']); ?>
                        <span class="badge badge-default"><?= e($bug['category']); ?></span>
                    </div>
                    <h1 style="font-size: 1.4rem; font-weight: 800; color: var(--navy-900); margin: 0; line-height: 1.3;">
                        <?= e($bug['title']); ?>
                    </h1>
                    <div style="font-size: 0.82rem; color: var(--slate-500); margin-top: 8px;">
                        Reported by <strong><?= e($bug['reporter_name']); ?></strong> (<?= ucfirst(e($bug['reporter_role'])); ?>) &bull; <?= format_datetime($bug['created_at']); ?>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <!-- Description -->
                <div style="margin-bottom: 24px;">
                    <h3 style="font-size: 0.95rem; font-weight: 700; color: var(--navy-800); margin-bottom: 8px;">Description</h3>
                    <div style="font-size: 0.95rem; color: var(--navy-900); line-height: 1.6; white-space: pre-line; background: var(--slate-50); padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--slate-200);">
                        <?= e($bug['description']); ?>
                    </div>
                </div>

                <!-- Steps to Reproduce -->
                <?php if (!empty($bug['steps_to_reproduce'])): ?>
                    <div style="margin-bottom: 24px;">
                        <h3 style="font-size: 0.95rem; font-weight: 700; color: var(--navy-800); margin-bottom: 8px;">Steps to Reproduce</h3>
                        <div style="font-size: 0.92rem; color: var(--navy-800); line-height: 1.6; white-space: pre-line; background: var(--slate-50); padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--slate-200); font-family: inherit;">
                            <?= e($bug['steps_to_reproduce']); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Expected vs Actual Result -->
                <?php if (!empty($bug['expected_result']) || !empty($bug['actual_result'])): ?>
                    <div class="form-row" style="margin-bottom: 24px;">
                        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius-md); padding: 16px;">
                            <strong style="color: #1e40af; font-size: 0.88rem; display: block; margin-bottom: 6px;">Expected Result:</strong>
                            <div style="font-size: 0.9rem; color: #1e3a8a;"><?= e($bug['expected_result'] ?: 'None specified'); ?></div>
                        </div>

                        <div style="background: #fff1f2; border: 1px solid #fecdd3; border-radius: var(--radius-md); padding: 16px;">
                            <strong style="color: #9f1239; font-size: 0.88rem; display: block; margin-bottom: 6px;">Actual Result:</strong>
                            <div style="font-size: 0.9rem; color: #881337;"><?= e($bug['actual_result'] ?: 'None specified'); ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Screenshot Attachment -->
                <?php if (!empty($bug['screenshot'])): ?>
                    <div style="margin-bottom: 10px;">
                        <h3 style="font-size: 0.95rem; font-weight: 700; color: var(--navy-800); margin-bottom: 8px;">Attached Screenshot Evidence</h3>
                        <div style="background: var(--slate-50); padding: 14px; border-radius: var(--radius-md); border: 1px solid var(--slate-200); display: inline-block;">
                            <img src="<?= BASE_URL; ?>/uploads/<?= e($bug['screenshot']); ?>" 
                                 alt="Bug Screenshot" 
                                 class="lightbox-trigger"
                                 style="max-width: 100%; max-height: 240px; border-radius: var(--radius-sm); cursor: pointer; border: 1px solid var(--slate-300);"
                                 title="Click to view enlarged screenshot">
                            <div style="font-size: 0.78rem; color: var(--slate-500); margin-top: 6px; text-align: center;">
                                &boxbox; Click image to zoom
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Comments & Discussion Section -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    Discussion &amp; Comments (<?= count($comments); ?>)
                </h2>
            </div>

            <div class="card-body">
                <?php if (empty($comments)): ?>
                    <div style="text-align: center; padding: 24px; color: var(--slate-500); font-size: 0.9rem;">
                        No comments yet. Start the conversation below.
                    </div>
                <?php else: ?>
                    <div class="comments-list">
                        <?php foreach ($comments as $comment): ?>
                            <div class="comment-card">
                                <div class="comment-avatar">
                                    <?= strtoupper(substr($comment['user_name'], 0, 1)); ?>
                                </div>
                                <div class="comment-bubble">
                                    <div class="comment-meta">
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span class="comment-user"><?= e($comment['user_name']); ?></span>
                                            <?= render_role_badge($comment['user_role']); ?>
                                        </div>
                                        <span class="comment-time"><?= time_ago($comment['created_at']); ?></span>
                                    </div>
                                    <div class="comment-text"><?= e($comment['comment_text']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Post Comment Form -->
                <form action="<?= BASE_URL; ?>/bug_view.php?id=<?= $bugId; ?>" method="POST" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--slate-200);">
                    <?= csrf_field(); ?>
                    <input type="hidden" name="action" value="add_comment">

                    <div class="form-group" style="margin-bottom: 12px;">
                        <label for="comment_text" class="form-label">Add a Note or Comment</label>
                        <textarea id="comment_text" name="comment_text" class="form-control" rows="3" placeholder="Provide investigation findings, reproduction notes, or questions..." required></textarea>
                    </div>

                    <div style="text-align: right;">
                        <button type="submit" class="btn btn-primary btn-sm">
                            Post Comment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: Status Transitions, Assignment, Metadata, Timeline -->
    <div>
        <!-- Status Action Card -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title" style="font-size: 1rem;">Workflow Actions</h2>
            </div>
            <div class="card-body">
                <div style="margin-bottom: 16px;">
                    <div style="font-size: 0.8rem; color: var(--slate-500); text-transform: uppercase; font-weight: 700; margin-bottom: 4px;">Current Status</div>
                    <div><?= render_status_badge($bug['status']); ?></div>
                </div>

                <!-- Status Update Form -->
                <form action="<?= BASE_URL; ?>/bug_view.php?id=<?= $bugId; ?>" method="POST">
                    <?= csrf_field(); ?>
                    <input type="hidden" name="action" value="update_status">

                    <div class="form-group" style="margin-bottom: 12px;">
                        <label for="new_status" class="form-label">Change Status to:</label>
                        <select id="new_status" name="new_status" class="form-control" required>
                            <?php foreach ($allowedTransitions as $trans): ?>
                                <option value="<?= e($trans); ?>" <?= ($trans === $bug['status']) ? 'disabled selected' : ''; ?>>
                                    <?= ($trans === $bug['status']) ? "&bull; " . e($trans) . " (Current)" : "&rarr; " . e($trans); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 14px;">
                        <label for="status_note" class="form-label">Resolution / Change Note (Optional)</label>
                        <input type="text" id="status_note" name="status_note" class="form-control" placeholder="e.g. Verified on build 1.4 or Root cause fixed">
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm" style="width: 100%;">
                        Update Bug Status
                    </button>
                </form>

                <!-- Admin Developer Assignment -->
                <?php if ($currentUser['role'] === 'admin'): ?>
                    <form action="<?= BASE_URL; ?>/bug_view.php?id=<?= $bugId; ?>" method="POST" style="margin-top: 20px; padding-top: 18px; border-top: 1px dashed var(--slate-200);">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="action" value="assign_developer">

                        <div class="form-group" style="margin-bottom: 12px;">
                            <label for="developer_id" class="form-label">Assign Developer</label>
                            <select id="developer_id" name="developer_id" class="form-control">
                                <option value="">-- Unassigned --</option>
                                <?php foreach ($allDevelopers as $d): ?>
                                    <option value="<?= (int)$d['id']; ?>" <?= ($bug['assigned_to'] === (int)$d['id']) ? 'selected' : ''; ?>>
                                        <?= e($d['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-secondary btn-sm" style="width: 100%;">
                            Save Assignment
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Ticket Metadata Card -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title" style="font-size: 1rem;">Ticket Info</h2>
            </div>
            <div class="card-body" style="padding: 16px 20px; display: flex; flex-direction: column; gap: 14px; font-size: 0.88rem;">
                <div>
                    <span style="color: var(--slate-500); display: block; font-size: 0.76rem; text-transform: uppercase; font-weight: 700;">Project</span>
                    <strong style="color: var(--navy-900);"><?= e($bug['project_name'] ?? 'N/A'); ?></strong>
                </div>

                <div>
                    <span style="color: var(--slate-500); display: block; font-size: 0.76rem; text-transform: uppercase; font-weight: 700;">Assigned Developer</span>
                    <strong style="color: var(--navy-900);">
                        <?= $bug['dev_name'] ? e($bug['dev_name']) : '<span style="color: #e11d48; font-style: italic;">Unassigned</span>'; ?>
                    </strong>
                </div>

                <div>
                    <span style="color: var(--slate-500); display: block; font-size: 0.76rem; text-transform: uppercase; font-weight: 700;">Reported By</span>
                    <strong style="color: var(--navy-900);"><?= e($bug['reporter_name']); ?></strong>
                </div>

                <div>
                    <span style="color: var(--slate-500); display: block; font-size: 0.76rem; text-transform: uppercase; font-weight: 700;">Created</span>
                    <span style="color: var(--navy-800);"><?= format_datetime($bug['created_at']); ?></span>
                </div>

                <div>
                    <span style="color: var(--slate-500); display: block; font-size: 0.76rem; text-transform: uppercase; font-weight: 700;">Last Updated</span>
                    <span style="color: var(--navy-800);"><?= format_datetime($bug['updated_at']); ?> (<?= time_ago($bug['updated_at']); ?>)</span>
                </div>
            </div>
        </div>

        <!-- Status History / Audit Trail Timeline -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title" style="font-size: 1rem;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    Status History &amp; Audit Trail
                </h2>
            </div>
            <div class="card-body">
                <?php if (empty($history)): ?>
                    <div style="font-size: 0.85rem; color: var(--slate-500); text-align: center;">No history entries logged yet.</div>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($history as $h): ?>
                            <div class="timeline-item">
                                <div class="timeline-dot"></div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <span class="timeline-author"><?= e($h['user_name']); ?></span>
                                        <span class="timeline-time"><?= time_ago($h['created_at']); ?></span>
                                    </div>
                                    <div style="font-size: 0.8rem; margin-bottom: 4px;">
                                        Status: <span style="font-weight: 600; color: var(--slate-500);"><?= e($h['previous_status']); ?></span> &rarr; <span style="font-weight: 700; color: var(--blue-600);"><?= e($h['new_status']); ?></span>
                                    </div>
                                    <?php if (!empty($h['change_description'])): ?>
                                        <div class="timeline-body" style="font-size: 0.82rem; color: var(--slate-600);">
                                            <?= e($h['change_description']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
@media (max-width: 900px) {
    .bug-detail-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

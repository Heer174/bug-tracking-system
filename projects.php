<?php
/**
 * Bug Tracking System - Projects Management
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$db = get_db();
$currentUser = current_user();

$errors = [];

// Handle project creation & editing (Admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_role('admin');

    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf($token)) {
        set_flash('danger', 'Security validation token expired. Please retry.');
        header('Location: ' . BASE_URL . '/projects.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    // 1. Create Project
    if ($action === 'create_project') {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');

        if (empty($name)) {
            $errors[] = 'Project name is required.';
        } else {
            $stmt = $db->prepare("INSERT INTO projects (name, description, created_at) VALUES (:name, :desc, NOW())");
            $stmt->execute([':name' => $name, ':desc' => $desc ?: null]);
            set_flash('success', "Project '{$name}' created successfully.");
            header('Location: ' . BASE_URL . '/projects.php');
            exit;
        }
    }

    // 2. Edit Project
    if ($action === 'edit_project') {
        $id   = (int)($_POST['project_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');

        if (empty($name) || $id <= 0) {
            $errors[] = 'Invalid project name or identifier.';
        } else {
            $stmt = $db->prepare("UPDATE projects SET name = :name, description = :desc WHERE id = :id");
            $stmt->execute([':name' => $name, ':desc' => $desc ?: null, ':id' => $id]);
            set_flash('success', "Project #{$id} updated successfully.");
            header('Location: ' . BASE_URL . '/projects.php');
            exit;
        }
    }
}

// Fetch all projects with bug counts and open/resolved statistics
$projects = $db->query("
    SELECT 
        p.*,
        COUNT(b.id) AS total_bugs,
        SUM(CASE WHEN b.status IN ('New', 'Assigned', 'In Progress', 'Reopened') THEN 1 ELSE 0 END) AS open_bugs,
        SUM(CASE WHEN b.status IN ('Fixed', 'Verified', 'Closed') THEN 1 ELSE 0 END) AS resolved_bugs
    FROM projects p
    LEFT JOIN bugs b ON p.id = b.project_id
    GROUP BY p.id
    ORDER BY p.name ASC
")->fetchAll();

$pageTitle = 'Projects';
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
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
            Registered Software Projects
        </h2>
        <?php if ($currentUser['role'] === 'admin'): ?>
            <button type="button" class="btn btn-primary btn-sm" onclick="openModal('create-project-modal')">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Create Project
            </button>
        <?php endif; ?>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 70px;">ID</th>
                    <th>Project Name &amp; Description</th>
                    <th>Total Bugs</th>
                    <th>Open Defects</th>
                    <th>Resolved / Closed</th>
                    <th>Created</th>
                    <th style="width: 150px; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($projects)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 36px; color: var(--slate-500);">
                            No projects registered yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($projects as $proj): ?>
                        <tr>
                            <td style="font-weight: 700; color: var(--slate-500);">#<?= (int)$proj['id']; ?></td>
                            <td>
                                <a href="<?= BASE_URL; ?>/bugs.php?project=<?= (int)$proj['id']; ?>" style="font-weight: 700; font-size: 0.96rem; color: var(--navy-900);">
                                    <?= e($proj['name']); ?>
                                </a>
                                <p style="font-size: 0.82rem; color: var(--slate-500); margin-top: 2px; max-width: 500px;">
                                    <?= e($proj['description'] ?: 'No description provided.'); ?>
                                </p>
                            </td>
                            <td style="font-weight: 700; color: var(--navy-900);">
                                <?= (int)$proj['total_bugs']; ?>
                            </td>
                            <td>
                                <span class="badge badge-priority-high">
                                    <?= (int)$proj['open_bugs']; ?> open
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-status-fixed">
                                    <?= (int)$proj['resolved_bugs']; ?> fixed
                                </span>
                            </td>
                            <td style="font-size: 0.82rem; color: var(--slate-500);">
                                <?= time_ago($proj['created_at']); ?>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <a href="<?= BASE_URL; ?>/bugs.php?project=<?= (int)$proj['id']; ?>" class="btn btn-secondary btn-sm" title="View Bugs">
                                        View Bugs
                                    </a>
                                    <?php if ($currentUser['role'] === 'admin'): ?>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="editProject(<?= (int)$proj['id']; ?>, '<?= e(addslashes($proj['name'])); ?>', '<?= e(addslashes($proj['description'] ?? '')); ?>')">
                                            Edit
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Create Project (Admin Only) -->
<div id="create-project-modal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title">Create New Project</h3>
            <button type="button" class="modal-close" onclick="closeModal('create-project-modal')">&times;</button>
        </div>
        <form action="<?= BASE_URL; ?>/projects.php" method="POST">
            <?= csrf_field(); ?>
            <input type="hidden" name="action" value="create_project">

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Project Name <span class="required">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Healthcare Patient Portal" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Description (Optional)</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Overview of scope, repositories, or technical architecture..."></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('create-project-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Project</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Project (Admin Only) -->
<div id="edit-project-modal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title">Edit Project Details</h3>
            <button type="button" class="modal-close" onclick="closeModal('edit-project-modal')">&times;</button>
        </div>
        <form action="<?= BASE_URL; ?>/projects.php" method="POST">
            <?= csrf_field(); ?>
            <input type="hidden" name="action" value="edit_project">
            <input type="hidden" name="project_id" id="edit-project-id" value="">

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Project Name <span class="required">*</span></label>
                    <input type="text" name="name" id="edit-project-name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="edit-project-desc" class="form-control" rows="3"></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('edit-project-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function editProject(id, name, desc) {
    document.getElementById('edit-project-id').value = id;
    document.getElementById('edit-project-name').value = name;
    document.getElementById('edit-project-desc').value = desc;
    openModal('edit-project-modal');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

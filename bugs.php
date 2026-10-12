<?php
/**
 * Bug Tracking System - Bug Listing with Search, Filters & Pagination
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$db = get_db();
$currentUser = current_user();

// Handle deletion (Admin only)
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    require_role('admin');
    $deleteId = (int)($_GET['id'] ?? 0);
    $token = $_GET['csrf_token'] ?? '';

    if (validate_csrf($token) && $deleteId > 0) {
        // Fetch screenshot to clean up file if exists
        $stmtImg = $db->prepare("SELECT screenshot FROM bugs WHERE id = :id");
        $stmtImg->execute([':id' => $deleteId]);
        $bugImg = $stmtImg->fetchColumn();
        if ($bugImg && file_exists(UPLOAD_DIR . DIRECTORY_SEPARATOR . $bugImg)) {
            @unlink(UPLOAD_DIR . DIRECTORY_SEPARATOR . $bugImg);
        }

        $stmtDel = $db->prepare("DELETE FROM bugs WHERE id = :id");
        $stmtDel->execute([':id' => $deleteId]);
        set_flash('success', "Bug ticket #{$deleteId} has been successfully deleted.");
    } else {
        set_flash('danger', 'Invalid deletion token or bug identifier.');
    }
    header('Location: ' . BASE_URL . '/bugs.php');
    exit;
}

// Fetch Filter Dropdown Data
$projects = $db->query("SELECT id, name FROM projects ORDER BY name ASC")->fetchAll();
$developers = $db->query("SELECT id, name FROM users WHERE role = 'developer' AND status = 'active' ORDER BY name ASC")->fetchAll();

// Filter & Search Parameters
$search   = trim($_GET['q'] ?? '');
$project  = (int)($_GET['project'] ?? 0);
$status   = trim($_GET['status'] ?? '');
$priority = trim($_GET['priority'] ?? '');
$assigned = (int)($_GET['assigned'] ?? 0);
$sort     = trim($_GET['sort'] ?? 'id_desc');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 10;
$offset   = ($page - 1) * $perPage;

// Build Dynamic SQL Query with PDO parameters
$whereConditions = [];
$params = [];

if ($search !== '') {
    if (is_numeric($search)) {
        $whereConditions[] = "(b.id = :search_id OR b.title LIKE :search_txt OR b.description LIKE :search_txt2)";
        $params[':search_id'] = (int)$search;
        $params[':search_txt'] = "%{$search}%";
        $params[':search_txt2'] = "%{$search}%";
    } else {
        $whereConditions[] = "(b.title LIKE :search_txt OR b.description LIKE :search_txt2)";
        $params[':search_txt'] = "%{$search}%";
        $params[':search_txt2'] = "%{$search}%";
    }
}

if ($project > 0) {
    $whereConditions[] = "b.project_id = :project_id";
    $params[':project_id'] = $project;
}

if ($status !== '' && array_key_exists($status, BUG_STATUSES)) {
    $whereConditions[] = "b.status = :status";
    $params[':status'] = $status;
}

if ($priority !== '' && array_key_exists($priority, BUG_PRIORITIES)) {
    $whereConditions[] = "b.priority = :priority";
    $params[':priority'] = $priority;
}

if ($assigned > 0) {
    $whereConditions[] = "b.assigned_to = :assigned_to";
    $params[':assigned_to'] = $assigned;
} elseif (isset($_GET['assigned']) && $_GET['assigned'] === 'unassigned') {
    $whereConditions[] = "b.assigned_to IS NULL";
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Sorting logic
$orderBy = match($sort) {
    'id_asc'        => 'b.id ASC',
    'priority_desc' => "FIELD(b.priority, 'Critical', 'High', 'Medium', 'Low'), b.id DESC",
    'updated_desc'  => 'b.updated_at DESC',
    'created_asc'   => 'b.created_at ASC',
    default         => 'b.id DESC'
};

// Total count for pagination
$countSql = "SELECT COUNT(*) FROM bugs b {$whereClause}";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRecords / $perPage));

// Main Records Query
$querySql = "
    SELECT 
        b.*, 
        p.name AS project_name, 
        u.name AS reporter_name, 
        d.name AS developer_name
    FROM bugs b
    LEFT JOIN projects p ON b.project_id = p.id
    LEFT JOIN users u ON b.reporter_id = u.id
    LEFT JOIN users d ON b.assigned_to = d.id
    {$whereClause}
    ORDER BY {$orderBy}
    LIMIT :limit OFFSET :offset
";

$stmt = $db->prepare($querySql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$bugs = $stmt->fetchAll();

$pageTitle = 'Bug Tickets';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Action Bar & Filter Box -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-body" style="padding: 20px;">
        <form method="GET" action="<?= BASE_URL; ?>/bugs.php" style="display: flex; flex-direction: column; gap: 16px;">
            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <!-- Search bar -->
                <div style="flex: 2; min-width: 240px;">
                    <input type="text" name="q" class="form-control" placeholder="Search by bug title, description, or #ID..." value="<?= e($search); ?>">
                </div>

                <!-- Project filter -->
                <div style="flex: 1; min-width: 170px;">
                    <select name="project" class="form-control">
                        <option value="">All Projects</option>
                        <?php foreach ($projects as $proj): ?>
                            <option value="<?= (int)$proj['id']; ?>" <?= ($project === (int)$proj['id']) ? 'selected' : ''; ?>>
                                <?= e($proj['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Status filter -->
                <div style="flex: 1; min-width: 150px;">
                    <select name="status" class="form-control">
                        <option value="">All Statuses</option>
                        <?php foreach (array_keys(BUG_STATUSES) as $st): ?>
                            <option value="<?= e($st); ?>" <?= ($status === $st) ? 'selected' : ''; ?>>
                                <?= e($st); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Priority filter -->
                <div style="flex: 1; min-width: 150px;">
                    <select name="priority" class="form-control">
                        <option value="">All Priorities</option>
                        <?php foreach (array_keys(BUG_PRIORITIES) as $pr): ?>
                            <option value="<?= e($pr); ?>" <?= ($priority === $pr) ? 'selected' : ''; ?>>
                                <?= e($pr); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Developer filter -->
                <div style="flex: 1; min-width: 170px;">
                    <select name="assigned" class="form-control">
                        <option value="">All Assignees</option>
                        <option value="unassigned" <?= (isset($_GET['assigned']) && $_GET['assigned'] === 'unassigned') ? 'selected' : ''; ?>>-- Unassigned --</option>
                        <?php foreach ($developers as $dev): ?>
                            <option value="<?= (int)$dev['id']; ?>" <?= ($assigned === (int)$dev['id']) ? 'selected' : ''; ?>>
                                <?= e($dev['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Sort Order -->
                <div style="flex: 1; min-width: 150px;">
                    <select name="sort" class="form-control">
                        <option value="id_desc" <?= ($sort === 'id_desc') ? 'selected' : ''; ?>>Newest First</option>
                        <option value="id_asc" <?= ($sort === 'id_asc') ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="priority_desc" <?= ($sort === 'priority_desc') ? 'selected' : ''; ?>>Highest Priority</option>
                        <option value="updated_desc" <?= ($sort === 'updated_desc') ? 'selected' : ''; ?>>Recently Updated</option>
                    </select>
                </div>

                <!-- Action buttons -->
                <div style="display: flex; gap: 8px;">
                    <button type="submit" class="btn btn-primary">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        Filter
                    </button>
                    <a href="<?= BASE_URL; ?>/bugs.php" class="btn btn-secondary" title="Reset Filters">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Bugs Table Card -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <span>Bug Tickets (<?= $totalRecords; ?> found)</span>
        </h2>
        <div style="display: flex; gap: 10px;">
            <a href="<?= BASE_URL; ?>/bug_create.php" class="btn btn-primary btn-sm">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Report New Bug
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 70px;">ID</th>
                    <th>Title &amp; Category</th>
                    <th>Project</th>
                    <th>Priority</th>
                    <th>Severity</th>
                    <th>Status</th>
                    <th>Assigned To</th>
                    <th>Reporter</th>
                    <th>Updated</th>
                    <th style="width: 140px; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bugs)): ?>
                    <tr>
                        <td colspan="10">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <svg width="60" height="60" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <rect x="6" y="9" width="12" height="11" rx="2"></rect>
                                        <path d="M10 5a2 2 0 1 1 4 0"></path>
                                        <line x1="9" y1="15" x2="15" y2="15"></line>
                                    </svg>
                                </div>
                                <div class="empty-state-title">No Bug Reports Found</div>
                                <p class="empty-state-desc">
                                    No defects match your current search and filter criteria. Try adjusting filters or report a new issue.
                                </p>
                                <a href="<?= BASE_URL; ?>/bug_create.php" class="btn btn-primary btn-sm">Report a Bug</a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($bugs as $bug): ?>
                        <tr>
                            <td style="font-weight: 700; color: var(--slate-500);">
                                #<?= (int)$bug['id']; ?>
                            </td>
                            <td>
                                <a href="<?= BASE_URL; ?>/bug_view.php?id=<?= (int)$bug['id']; ?>" style="font-weight: 600; color: var(--navy-900); display: block;">
                                    <?= e($bug['title']); ?>
                                </a>
                                <span style="font-size: 0.76rem; color: var(--slate-500);"><?= e($bug['category']); ?></span>
                            </td>
                            <td>
                                <span style="font-size: 0.88rem; color: var(--navy-700); font-weight: 500;">
                                    <?= e($bug['project_name'] ?? 'Unassigned Project'); ?>
                                </span>
                            </td>
                            <td><?= render_priority_badge($bug['priority']); ?></td>
                            <td><?= render_severity_badge($bug['severity']); ?></td>
                            <td><?= render_status_badge($bug['status']); ?></td>
                            <td>
                                <?php if ($bug['developer_name']): ?>
                                    <span style="font-size: 0.88rem; color: var(--navy-800); font-weight: 500;"><?= e($bug['developer_name']); ?></span>
                                <?php else: ?>
                                    <span style="font-size: 0.82rem; color: #e11d48; font-style: italic; font-weight: 500;">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size: 0.85rem; color: var(--navy-700);">
                                <?= e($bug['reporter_name'] ?? 'System'); ?>
                            </td>
                            <td style="font-size: 0.82rem; color: var(--slate-500);">
                                <?= time_ago($bug['updated_at']); ?>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <a href="<?= BASE_URL; ?>/bug_view.php?id=<?= (int)$bug['id']; ?>" class="btn btn-secondary btn-sm" title="View details">
                                        View
                                    </a>
                                    <?php if ($currentUser['role'] === 'admin' || $currentUser['id'] === (int)$bug['reporter_id']): ?>
                                        <a href="<?= BASE_URL; ?>/bug_edit.php?id=<?= (int)$bug['id']; ?>" class="btn btn-secondary btn-sm" title="Edit bug">
                                            Edit
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($currentUser['role'] === 'admin'): ?>
                                        <a href="<?= BASE_URL; ?>/bugs.php?action=delete&id=<?= (int)$bug['id']; ?>&csrf_token=<?= csrf_token(); ?>" 
                                           class="btn btn-danger btn-sm" 
                                           title="Delete bug" 
                                           data-confirm="Are you sure you want to delete bug #<?= (int)$bug['id']; ?>? This action is permanent.">
                                            &times;
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Controls -->
    <?php if ($totalPages > 1): ?>
        <div class="card-footer">
            <div class="pagination">
                <div style="font-size: 0.85rem; color: var(--slate-500);">
                    Showing <?= ($offset + 1); ?> - <?= min($offset + $perPage, $totalRecords); ?> of <?= $totalRecords; ?> tickets
                </div>
                <div class="pagination-links">
                    <?php
                    $queryString = $_GET;
                    unset($queryString['page']);
                    $baseQuery = http_build_query($queryString);
                    $pagePrefix = $baseQuery ? '?' . $baseQuery . '&page=' : '?page=';
                    ?>
                    <?php if ($page > 1): ?>
                        <a href="<?= $pagePrefix . ($page - 1); ?>" class="page-btn">&larr; Prev</a>
                    <?php endif; ?>

                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <?php if ($p == 1 || $p == $totalPages || abs($p - $page) <= 2): ?>
                            <a href="<?= $pagePrefix . $p; ?>" class="page-btn <?= ($p === $page) ? 'active' : ''; ?>">
                                <?= $p; ?>
                            </a>
                        <?php elseif (abs($p - $page) == 3): ?>
                            <span style="padding: 6px 4px; color: var(--slate-400);">&hellip;</span>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="<?= $pagePrefix . ($page + 1); ?>" class="page-btn">Next &rarr;</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

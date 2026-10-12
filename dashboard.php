<?php
/**
 * Bug Tracking System - Role-Based Dashboard
 */

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';

$db = get_db();
$userRole = $currentUser['role'];
$userId = $currentUser['id'];

// Global Metric Queries
$stmtCounts = $db->query("
    SELECT 
        COUNT(*) AS total_bugs,
        SUM(CASE WHEN status = 'New' THEN 1 ELSE 0 END) AS new_bugs,
        SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress_bugs,
        SUM(CASE WHEN status = 'Fixed' THEN 1 ELSE 0 END) AS fixed_bugs,
        SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) AS closed_bugs,
        SUM(CASE WHEN status = 'Assigned' THEN 1 ELSE 0 END) AS assigned_bugs,
        SUM(CASE WHEN status = 'Verified' THEN 1 ELSE 0 END) AS verified_bugs,
        SUM(CASE WHEN status = 'Reopened' THEN 1 ELSE 0 END) AS reopened_bugs
    FROM bugs
");
$metrics = $stmtCounts->fetch() ?: [
    'total_bugs' => 0, 'new_bugs' => 0, 'in_progress_bugs' => 0, 
    'fixed_bugs' => 0, 'closed_bugs' => 0, 'assigned_bugs' => 0,
    'verified_bugs' => 0, 'reopened_bugs' => 0
];

// Role-specific metrics
$roleMetrics = [];
if ($userRole === 'admin') {
    $stmtAdmin = $db->query("
        SELECT 
            (SELECT COUNT(*) FROM users) AS total_users,
            (SELECT COUNT(*) FROM projects) AS total_projects,
            (SELECT COUNT(*) FROM bugs WHERE assigned_to IS NULL) AS unassigned_bugs
    ");
    $roleMetrics = $stmtAdmin->fetch() ?: ['total_users' => 0, 'total_projects' => 0, 'unassigned_bugs' => 0];
} elseif ($userRole === 'developer') {
    $stmtDev = $db->prepare("
        SELECT 
            COUNT(*) AS assigned_to_me,
            SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) AS my_in_progress,
            SUM(CASE WHEN status = 'Fixed' THEN 1 ELSE 0 END) AS my_fixed
        FROM bugs 
        WHERE assigned_to = :dev_id
    ");
    $stmtDev->execute([':dev_id' => $userId]);
    $roleMetrics = $stmtDev->fetch() ?: ['assigned_to_me' => 0, 'my_in_progress' => 0, 'my_fixed' => 0];
} elseif ($userRole === 'tester') {
    $stmtTester = $db->prepare("
        SELECT 
            COUNT(*) AS reported_by_me,
            SUM(CASE WHEN status = 'Fixed' THEN 1 ELSE 0 END) AS awaiting_my_verification,
            SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) AS my_closed
        FROM bugs 
        WHERE reporter_id = :tester_id
    ");
    $stmtTester->execute([':tester_id' => $userId]);
    $roleMetrics = $stmtTester->fetch() ?: ['reported_by_me' => 0, 'awaiting_my_verification' => 0, 'my_closed' => 0];
}

// Recent Bugs query tailored to role
if ($userRole === 'admin') {
    $stmtRecent = $db->query("
        SELECT b.*, p.name AS project_name, u.name AS reporter_name, d.name AS dev_name 
        FROM bugs b
        LEFT JOIN projects p ON b.project_id = p.id
        LEFT JOIN users u ON b.reporter_id = u.id
        LEFT JOIN users d ON b.assigned_to = d.id
        ORDER BY b.created_at DESC LIMIT 6
    ");
} elseif ($userRole === 'developer') {
    $stmtRecent = $db->prepare("
        SELECT b.*, p.name AS project_name, u.name AS reporter_name, d.name AS dev_name 
        FROM bugs b
        LEFT JOIN projects p ON b.project_id = p.id
        LEFT JOIN users u ON b.reporter_id = u.id
        LEFT JOIN users d ON b.assigned_to = d.id
        WHERE b.assigned_to = :dev_id
        ORDER BY b.updated_at DESC LIMIT 6
    ");
    $stmtRecent->execute([':dev_id' => $userId]);
} else {
    // Tester
    $stmtRecent = $db->prepare("
        SELECT b.*, p.name AS project_name, u.name AS reporter_name, d.name AS dev_name 
        FROM bugs b
        LEFT JOIN projects p ON b.project_id = p.id
        LEFT JOIN users u ON b.reporter_id = u.id
        LEFT JOIN users d ON b.assigned_to = d.id
        WHERE b.reporter_id = :tester_id
        ORDER BY b.created_at DESC LIMIT 6
    ");
    $stmtRecent->execute([':tester_id' => $userId]);
}
$recentBugs = $stmtRecent->fetchAll();

// Priority distribution for chart
$stmtPriority = $db->query("
    SELECT priority, COUNT(*) AS count 
    FROM bugs 
    GROUP BY priority
");
$priorityCounts = ['Critical' => 0, 'High' => 0, 'Medium' => 0, 'Low' => 0];
while ($row = $stmtPriority->fetch()) {
    $priorityCounts[$row['priority']] = (int)$row['count'];
}
?>

<!-- Role Welcome Banner -->
<div style="background: linear-gradient(135deg, #1e293b, #0f172a); border-radius: var(--radius-lg); padding: 26px 32px; color: #ffffff; margin-bottom: 28px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; border: 1px solid #334155; box-shadow: var(--shadow-sm);">
    <div>
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
            <h1 style="font-size: 1.5rem; font-weight: 800; color: #ffffff; margin: 0;">Welcome back, <?= e($currentUser['name']); ?></h1>
            <?= render_role_badge($userRole); ?>
        </div>
        <p style="color: #94a3b8; font-size: 0.92rem; margin: 0;">
            <?php if ($userRole === 'admin'): ?>
                You have full administrative privileges to oversee bug assignments, projects, and users.
            <?php elseif ($userRole === 'developer'): ?>
                Review your assigned defects, update debugging progress, and submit fixes.
            <?php else: ?>
                Report identified issues with reproduction steps, and verify completed fixes.
            <?php endif; ?>
        </p>
    </div>
    <div style="display: flex; gap: 12px;">
        <a href="<?= BASE_URL; ?>/bug_create.php" class="btn btn-primary">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            <span>Report Bug</span>
        </a>
        <a href="<?= BASE_URL; ?>/bugs.php" class="btn btn-secondary" style="background: rgba(255,255,255,0.1); color: #fff; border-color: rgba(255,255,255,0.2);">
            <span>View All Tickets</span>
        </a>
    </div>
</div>

<!-- Primary Summary Cards Required by Spec -->
<div class="metrics-grid">
    <div class="metric-card card-total">
        <div class="metric-info">
            <span class="metric-label">Total Bugs</span>
            <span class="metric-value"><?= (int)$metrics['total_bugs']; ?></span>
        </div>
        <div class="metric-icon-wrap">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="6" y="9" width="12" height="11" rx="2"></rect>
                <path d="M10 5a2 2 0 1 1 4 0"></path>
            </svg>
        </div>
    </div>

    <div class="metric-card card-new">
        <div class="metric-info">
            <span class="metric-label">New Bugs</span>
            <span class="metric-value"><?= (int)$metrics['new_bugs']; ?></span>
        </div>
        <div class="metric-icon-wrap">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="16"></line>
                <line x1="8" y1="12" x2="16" y2="12"></line>
            </svg>
        </div>
    </div>

    <div class="metric-card card-progress">
        <div class="metric-info">
            <span class="metric-label">In Progress</span>
            <span class="metric-value"><?= (int)$metrics['in_progress_bugs']; ?></span>
        </div>
        <div class="metric-icon-wrap">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"></path>
            </svg>
        </div>
    </div>

    <div class="metric-card card-fixed">
        <div class="metric-info">
            <span class="metric-label">Fixed</span>
            <span class="metric-value"><?= (int)$metrics['fixed_bugs']; ?></span>
        </div>
        <div class="metric-icon-wrap">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>
    </div>

    <div class="metric-card card-closed">
        <div class="metric-info">
            <span class="metric-label">Closed</span>
            <span class="metric-value"><?= (int)$metrics['closed_bugs']; ?></span>
        </div>
        <div class="metric-icon-wrap">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
            </svg>
        </div>
    </div>
</div>

<!-- Role Specific Secondary Counters -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 28px;">
    <?php if ($userRole === 'admin'): ?>
        <div style="background: #ffffff; padding: 14px 20px; border-radius: var(--radius-md); border: 1px solid var(--slate-200); display: flex; align-items: center; justify-content: space-between;">
            <span style="font-size: 0.85rem; color: var(--slate-600); font-weight: 500;">Active Projects:</span>
            <span style="font-weight: 700; color: var(--navy-900); font-size: 1.1rem;"><?= (int)$roleMetrics['total_projects']; ?></span>
        </div>
        <div style="background: #ffffff; padding: 14px 20px; border-radius: var(--radius-md); border: 1px solid var(--slate-200); display: flex; align-items: center; justify-content: space-between;">
            <span style="font-size: 0.85rem; color: var(--slate-600); font-weight: 500;">Registered Users:</span>
            <span style="font-weight: 700; color: var(--navy-900); font-size: 1.1rem;"><?= (int)$roleMetrics['total_users']; ?></span>
        </div>
        <div style="background: #ffffff; padding: 14px 20px; border-radius: var(--radius-md); border: 1px solid var(--slate-200); display: flex; align-items: center; justify-content: space-between;">
            <span style="font-size: 0.85rem; color: var(--slate-600); font-weight: 500;">Unassigned Tickets:</span>
            <span style="font-weight: 700; color: #e11d48; font-size: 1.1rem;"><?= (int)$roleMetrics['unassigned_bugs']; ?></span>
        </div>
    <?php elseif ($userRole === 'developer'): ?>
        <div style="background: #ffffff; padding: 14px 20px; border-radius: var(--radius-md); border: 1px solid var(--slate-200); display: flex; align-items: center; justify-content: space-between;">
            <span style="font-size: 0.85rem; color: var(--slate-600); font-weight: 500;">Assigned to Me:</span>
            <span style="font-weight: 700; color: var(--blue-600); font-size: 1.1rem;"><?= (int)$roleMetrics['assigned_to_me']; ?></span>
        </div>
        <div style="background: #ffffff; padding: 14px 20px; border-radius: var(--radius-md); border: 1px solid var(--slate-200); display: flex; align-items: center; justify-content: space-between;">
            <span style="font-size: 0.85rem; color: var(--slate-600); font-weight: 500;">My In-Progress:</span>
            <span style="font-weight: 700; color: #f59e0b; font-size: 1.1rem;"><?= (int)$roleMetrics['my_in_progress']; ?></span>
        </div>
        <div style="background: #ffffff; padding: 14px 20px; border-radius: var(--radius-md); border: 1px solid var(--slate-200); display: flex; align-items: center; justify-content: space-between;">
            <span style="font-size: 0.85rem; color: var(--slate-600); font-weight: 500;">Fixed by Me:</span>
            <span style="font-weight: 700; color: #10b981; font-size: 1.1rem;"><?= (int)$roleMetrics['my_fixed']; ?></span>
        </div>
    <?php else: ?>
        <div style="background: #ffffff; padding: 14px 20px; border-radius: var(--radius-md); border: 1px solid var(--slate-200); display: flex; align-items: center; justify-content: space-between;">
            <span style="font-size: 0.85rem; color: var(--slate-600); font-weight: 500;">Reported by Me:</span>
            <span style="font-weight: 700; color: var(--blue-600); font-size: 1.1rem;"><?= (int)$roleMetrics['reported_by_me']; ?></span>
        </div>
        <div style="background: #ffffff; padding: 14px 20px; border-radius: var(--radius-md); border: 1px solid var(--slate-200); display: flex; align-items: center; justify-content: space-between;">
            <span style="font-size: 0.85rem; color: var(--slate-600); font-weight: 500;">Awaiting My Verification:</span>
            <span style="font-weight: 700; color: #0d9488; font-size: 1.1rem;"><?= (int)$roleMetrics['awaiting_my_verification']; ?></span>
        </div>
        <div style="background: #ffffff; padding: 14px 20px; border-radius: var(--radius-md); border: 1px solid var(--slate-200); display: flex; align-items: center; justify-content: space-between;">
            <span style="font-size: 0.85rem; color: var(--slate-600); font-weight: 500;">Closed by Me:</span>
            <span style="font-weight: 700; color: var(--slate-500); font-size: 1.1rem;"><?= (int)$roleMetrics['my_closed']; ?></span>
        </div>
    <?php endif; ?>
</div>

<!-- Visual Analytics Row -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 24px; margin-bottom: 28px;">
    <!-- Bug Status Donut Chart -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                Bug Status Distribution
            </h2>
        </div>
        <div class="card-body" style="display: flex; flex-direction: column; align-items: center;">
            <div style="width: 100%; height: 220px; position: relative;">
                <canvas id="statusChart"></canvas>
            </div>
            <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 12px; margin-top: 14px; font-size: 0.8rem;">
                <span style="display: flex; align-items: center; gap: 5px;"><span style="width: 10px; height: 10px; background: #3b82f6; border-radius: 2px;"></span> New</span>
                <span style="display: flex; align-items: center; gap: 5px;"><span style="width: 10px; height: 10px; background: #6366f1; border-radius: 2px;"></span> Assigned</span>
                <span style="display: flex; align-items: center; gap: 5px;"><span style="width: 10px; height: 10px; background: #f59e0b; border-radius: 2px;"></span> In Progress</span>
                <span style="display: flex; align-items: center; gap: 5px;"><span style="width: 10px; height: 10px; background: #10b981; border-radius: 2px;"></span> Fixed</span>
                <span style="display: flex; align-items: center; gap: 5px;"><span style="width: 10px; height: 10px; background: #06b6d4; border-radius: 2px;"></span> Verified</span>
                <span style="display: flex; align-items: center; gap: 5px;"><span style="width: 10px; height: 10px; background: #64748b; border-radius: 2px;"></span> Closed</span>
            </div>
        </div>
    </div>

    <!-- Bug Priority Bar Chart -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                Bugs by Priority Level
            </h2>
        </div>
        <div class="card-body">
            <div style="width: 100%; height: 220px; position: relative;">
                <canvas id="priorityChart"></canvas>
            </div>
            <div style="text-align: center; margin-top: 14px; font-size: 0.8rem; color: var(--slate-500);">
                Defect volume categorized by severity impact
            </div>
        </div>
    </div>
</div>

<!-- Recent Bugs Table -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            <?= ($userRole === 'developer') ? 'My Active Workload' : (($userRole === 'tester') ? 'My Recently Reported Bugs' : 'Recent System Defects'); ?>
        </h2>
        <a href="<?= BASE_URL; ?>/bugs.php" class="btn btn-secondary btn-sm">View All Tickets &rarr;</a>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 70px;">ID</th>
                    <th>Bug Title</th>
                    <th>Project</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Assigned To</th>
                    <th>Reported</th>
                    <th style="width: 100px; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentBugs)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 36px; color: var(--slate-500);">
                            No bug records found for this view.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentBugs as $bug): ?>
                        <tr>
                            <td style="font-weight: 700; color: var(--slate-500);">#<?= (int)$bug['id']; ?></td>
                            <td>
                                <a href="<?= BASE_URL; ?>/bug_view.php?id=<?= (int)$bug['id']; ?>" style="font-weight: 600; color: var(--navy-900); display: block;">
                                    <?= e($bug['title']); ?>
                                </a>
                                <span style="font-size: 0.78rem; color: var(--slate-500);"><?= e($bug['category']); ?></span>
                            </td>
                            <td>
                                <span style="font-size: 0.88rem; color: var(--navy-700);"><?= e($bug['project_name'] ?? 'N/A'); ?></span>
                            </td>
                            <td><?= render_priority_badge($bug['priority']); ?></td>
                            <td><?= render_status_badge($bug['status']); ?></td>
                            <td>
                                <?php if ($bug['dev_name']): ?>
                                    <span style="font-size: 0.88rem; color: var(--navy-800); font-weight: 500;"><?= e($bug['dev_name']); ?></span>
                                <?php else: ?>
                                    <span style="font-size: 0.82rem; color: var(--slate-400); font-style: italic;">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size: 0.82rem; color: var(--slate-500);"><?= time_ago($bug['created_at']); ?></td>
                            <td style="text-align: right;">
                                <a href="<?= BASE_URL; ?>/bug_view.php?id=<?= (int)$bug['id']; ?>" class="btn btn-secondary btn-sm">Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Render Donut Chart for Bug Statuses
    ChartEngine.renderDonut('statusChart', [
        { label: 'New',         value: <?= (int)$metrics['new_bugs']; ?>,         color: '#3b82f6' },
        { label: 'Assigned',    value: <?= (int)$metrics['assigned_bugs']; ?>,    color: '#6366f1' },
        { label: 'In Progress', value: <?= (int)$metrics['in_progress_bugs']; ?>, color: '#f59e0b' },
        { label: 'Fixed',       value: <?= (int)$metrics['fixed_bugs']; ?>,       color: '#10b981' },
        { label: 'Verified',    value: <?= (int)$metrics['verified_bugs']; ?>,    color: '#06b6d4' },
        { label: 'Reopened',    value: <?= (int)$metrics['reopened_bugs']; ?>,    color: '#f43f5e' },
        { label: 'Closed',      value: <?= (int)$metrics['closed_bugs']; ?>,      color: '#64748b' }
    ]);

    // Render Bar Chart for Priorities
    ChartEngine.renderBar('priorityChart', [
        { label: 'Critical', value: <?= (int)$priorityCounts['Critical']; ?>, color: '#e11d48' },
        { label: 'High',     value: <?= (int)$priorityCounts['High']; ?>,     color: '#f97316' },
        { label: 'Medium',   value: <?= (int)$priorityCounts['Medium']; ?>,   color: '#3b82f6' },
        { label: 'Low',      value: <?= (int)$priorityCounts['Low']; ?>,      color: '#64748b' }
    ]);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

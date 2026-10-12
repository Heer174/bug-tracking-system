<?php
/**
 * Bug Tracking System - Analytics & Metrics Reports
 */

$pageTitle = 'Analytics & Reports';
require_once __DIR__ . '/includes/header.php';

$db = get_db();

// Optional project filter
$selectedProject = (int)($_GET['project'] ?? 0);

$whereClause = "";
$params = [];
if ($selectedProject > 0) {
    $whereClause = "WHERE b.project_id = :project_id";
    $params[':project_id'] = $selectedProject;
}

// 1. Bug counts by status
$stmtStatus = $db->prepare("
    SELECT 
        status, 
        COUNT(*) AS count 
    FROM bugs b
    {$whereClause}
    GROUP BY status
");
$stmtStatus->execute($params);
$statusData = [];
foreach (array_keys(BUG_STATUSES) as $st) {
    $statusData[$st] = 0;
}
while ($row = $stmtStatus->fetch()) {
    $statusData[$row['status']] = (int)$row['count'];
}

// 2. Bug counts by priority
$stmtPriority = $db->prepare("
    SELECT 
        priority, 
        COUNT(*) AS count 
    FROM bugs b
    {$whereClause}
    GROUP BY priority
");
$stmtPriority->execute($params);
$priorityData = ['Critical' => 0, 'High' => 0, 'Medium' => 0, 'Low' => 0];
while ($row = $stmtPriority->fetch()) {
    $priorityData[$row['priority']] = (int)$row['count'];
}

// 3. Bug counts by category
$stmtCat = $db->prepare("
    SELECT 
        category, 
        COUNT(*) AS count 
    FROM bugs b
    {$whereClause}
    GROUP BY category
    ORDER BY count DESC
");
$stmtCat->execute($params);
$categoryData = $stmtCat->fetchAll();

// 4. Developer Workload Metrics
$stmtDevWork = $db->prepare("
    SELECT 
        u.id, 
        u.name, 
        COUNT(b.id) AS total_assigned,
        SUM(CASE WHEN b.status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress,
        SUM(CASE WHEN b.status = 'Fixed' THEN 1 ELSE 0 END) AS fixed,
        SUM(CASE WHEN b.status = 'Closed' THEN 1 ELSE 0 END) AS closed
    FROM users u
    LEFT JOIN bugs b ON u.id = b.assigned_to " . ($selectedProject > 0 ? "AND b.project_id = :proj_dev" : "") . "
    WHERE u.role = 'developer' AND u.status = 'active'
    GROUP BY u.id
    ORDER BY total_assigned DESC
");
$devParams = $selectedProject > 0 ? [':proj_dev' => $selectedProject] : [];
$stmtDevWork->execute($devParams);
$developerWorkloads = $stmtDevWork->fetchAll();

// Fetch projects for filter
$projects = $db->query("SELECT id, name FROM projects ORDER BY name ASC")->fetchAll();
?>

<!-- Action Bar & Filter -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-body" style="padding: 18px 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <form method="GET" action="<?= BASE_URL; ?>/reports.php" style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <label for="project" style="font-weight: 600; font-size: 0.88rem; color: var(--navy-800);">Filter Report By Project:</label>
            <select name="project" id="project" class="form-control" style="width: auto; min-width: 200px;" onchange="this.form.submit()">
                <option value="0">All Projects Combined</option>
                <?php foreach ($projects as $p): ?>
                    <option value="<?= (int)$p['id']; ?>" <?= ($selectedProject === (int)$p['id']) ? 'selected' : ''; ?>>
                        <?= e($p['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            Print / Export Report
        </button>
    </div>
</div>

<!-- Visual Charts Row -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 24px; margin-bottom: 28px;">
    <!-- Status Distribution Chart -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                Bug Lifecycle Status Breakdown
            </h2>
        </div>
        <div class="card-body" style="display: flex; flex-direction: column; align-items: center;">
            <div style="width: 100%; height: 240px; position: relative;">
                <canvas id="reportStatusChart"></canvas>
            </div>
            <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin-top: 14px; font-size: 0.78rem;">
                <span style="display: flex; align-items: center; gap: 4px;"><span style="width: 10px; height: 10px; background: #3b82f6; border-radius: 2px;"></span> New (<?= $statusData['New']; ?>)</span>
                <span style="display: flex; align-items: center; gap: 4px;"><span style="width: 10px; height: 10px; background: #6366f1; border-radius: 2px;"></span> Assigned (<?= $statusData['Assigned']; ?>)</span>
                <span style="display: flex; align-items: center; gap: 4px;"><span style="width: 10px; height: 10px; background: #f59e0b; border-radius: 2px;"></span> In Progress (<?= $statusData['In Progress']; ?>)</span>
                <span style="display: flex; align-items: center; gap: 4px;"><span style="width: 10px; height: 10px; background: #10b981; border-radius: 2px;"></span> Fixed (<?= $statusData['Fixed']; ?>)</span>
                <span style="display: flex; align-items: center; gap: 4px;"><span style="width: 10px; height: 10px; background: #06b6d4; border-radius: 2px;"></span> Verified (<?= $statusData['Verified']; ?>)</span>
                <span style="display: flex; align-items: center; gap: 4px;"><span style="width: 10px; height: 10px; background: #f43f5e; border-radius: 2px;"></span> Reopened (<?= $statusData['Reopened']; ?>)</span>
                <span style="display: flex; align-items: center; gap: 4px;"><span style="width: 10px; height: 10px; background: #64748b; border-radius: 2px;"></span> Closed (<?= $statusData['Closed']; ?>)</span>
            </div>
        </div>
    </div>

    <!-- Priority Distribution Chart -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                Bug Severity &amp; Priority Spread
            </h2>
        </div>
        <div class="card-body">
            <div style="width: 100%; height: 240px; position: relative;">
                <canvas id="reportPriorityChart"></canvas>
            </div>
            <div style="text-align: center; margin-top: 14px; font-size: 0.8rem; color: var(--slate-500);">
                Critical: <?= $priorityData['Critical']; ?> &bull; High: <?= $priorityData['High']; ?> &bull; Medium: <?= $priorityData['Medium']; ?> &bull; Low: <?= $priorityData['Low']; ?>
            </div>
        </div>
    </div>
</div>

<!-- Developer Workload Ranking Table -->
<div class="card" style="margin-bottom: 28px;">
    <div class="card-header">
        <h2 class="card-title">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><polyline points="16 11 18 13 22 9"></polyline></svg>
            Developer Workload &amp; Resolution Performance
        </h2>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Developer Name</th>
                    <th>Total Assigned</th>
                    <th>In Progress</th>
                    <th>Implemented Fixes</th>
                    <th>Closed Tickets</th>
                    <th>Resolution Rate</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($developerWorkloads)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 24px; color: var(--slate-500);">
                            No active developer accounts found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($developerWorkloads as $dev): ?>
                        <?php 
                            $total = (int)$dev['total_assigned'];
                            $resolved = (int)$dev['fixed'] + (int)$dev['closed'];
                            $rate = $total > 0 ? round(($resolved / $total) * 100) : 0;
                        ?>
                        <tr>
                            <td style="font-weight: 600; color: var(--navy-900);">
                                <?= e($dev['name']); ?>
                            </td>
                            <td style="font-weight: 700;">
                                <?= $total; ?>
                            </td>
                            <td>
                                <span class="badge badge-status-progress"><?= (int)$dev['in_progress']; ?> active</span>
                            </td>
                            <td>
                                <span class="badge badge-status-fixed"><?= (int)$dev['fixed']; ?> fixed</span>
                            </td>
                            <td>
                                <span class="badge badge-status-closed"><?= (int)$dev['closed']; ?> closed</span>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="flex: 1; max-width: 100px; height: 8px; background: var(--slate-200); border-radius: 4px; overflow: hidden;">
                                        <div style="width: <?= $rate; ?>%; height: 100%; background: #10b981;"></div>
                                    </div>
                                    <span style="font-size: 0.82rem; font-weight: 700; color: var(--navy-800);"><?= $rate; ?>%</span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Defect Breakdown by Category -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h7"></path></svg>
            Defects Categorization Summary
        </h2>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Ticket Count</th>
                    <th>Percentage of Reported Defects</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                    $totalDefects = array_sum(array_column($categoryData, 'count'));
                ?>
                <?php if (empty($categoryData)): ?>
                    <tr>
                        <td colspan="3" style="text-align: center; padding: 24px; color: var(--slate-500);">
                            No defects recorded yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categoryData as $cat): ?>
                        <?php 
                            $cCount = (int)$cat['count'];
                            $pct = $totalDefects > 0 ? round(($cCount / $totalDefects) * 100, 1) : 0;
                        ?>
                        <tr>
                            <td style="font-weight: 600; color: var(--navy-900);"><?= e($cat['category']); ?></td>
                            <td style="font-weight: 700;"><?= $cCount; ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="flex: 1; max-width: 140px; height: 8px; background: var(--slate-200); border-radius: 4px; overflow: hidden;">
                                        <div style="width: <?= $pct; ?>%; height: 100%; background: var(--blue-600);"></div>
                                    </div>
                                    <span style="font-size: 0.82rem; font-weight: 600; color: var(--navy-700);"><?= $pct; ?>%</span>
                                </div>
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
    ChartEngine.renderDonut('reportStatusChart', [
        { label: 'New',         value: <?= (int)$statusData['New']; ?>,         color: '#3b82f6' },
        { label: 'Assigned',    value: <?= (int)$statusData['Assigned']; ?>,    color: '#6366f1' },
        { label: 'In Progress', value: <?= (int)$statusData['In Progress']; ?>, color: '#f59e0b' },
        { label: 'Fixed',       value: <?= (int)$statusData['Fixed']; ?>,       color: '#10b981' },
        { label: 'Verified',    value: <?= (int)$statusData['Verified']; ?>,    color: '#06b6d4' },
        { label: 'Reopened',    value: <?= (int)$statusData['Reopened']; ?>,    color: '#f43f5e' },
        { label: 'Closed',      value: <?= (int)$statusData['Closed']; ?>,      color: '#64748b' }
    ]);

    // Render Bar Chart for Priorities
    ChartEngine.renderBar('reportPriorityChart', [
        { label: 'Critical', value: <?= (int)$priorityData['Critical']; ?>, color: '#e11d48' },
        { label: 'High',     value: <?= (int)$priorityData['High']; ?>,     color: '#f97316' },
        { label: 'Medium',   value: <?= (int)$priorityData['Medium']; ?>,   color: '#3b82f6' },
        { label: 'Low',      value: <?= (int)$priorityData['Low']; ?>,      color: '#64748b' }
    ]);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

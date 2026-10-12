<?php
/**
 * Bug Tracking System - Sidebar Navigation
 */

$user = current_user();
$active = basename($_SERVER['PHP_SELF'], '.php');
$userInitials = strtoupper(substr($user['name'] ?? 'U', 0, 1));
?>
<aside class="app-sidebar">
    <div class="sidebar-header">
        <a href="<?= BASE_URL; ?>/dashboard.php" class="sidebar-logo">
            <div class="logo-badge">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <rect x="6" y="9" width="12" height="11" rx="2"></rect>
                    <path d="M10 5a2 2 0 1 1 4 0"></path>
                    <path d="M4 11h2"></path>
                    <path d="M18 11h2"></path>
                    <path d="M4 16h2"></path>
                    <path d="M18 16h2"></path>
                </svg>
            </div>
            <span><?= APP_NAME; ?></span>
        </a>
    </div>

    <div class="sidebar-user">
        <div class="user-avatar-circle"><?= e($userInitials); ?></div>
        <div class="sidebar-user-info">
            <div class="sidebar-user-name"><?= e($user['name']); ?></div>
            <div class="sidebar-user-role"><?= render_role_badge($user['role']); ?></div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-title">Core Navigation</div>

        <a href="<?= BASE_URL; ?>/dashboard.php" class="nav-link <?= ($active === 'dashboard') ? 'active' : ''; ?>">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="3" y="3" width="7" height="7"></rect>
                <rect x="14" y="3" width="7" height="7"></rect>
                <rect x="14" y="14" width="7" height="7"></rect>
                <rect x="3" y="14" width="7" height="7"></rect>
            </svg>
            <span>Dashboard</span>
        </a>

        <a href="<?= BASE_URL; ?>/bugs.php" class="nav-link <?= ($active === 'bugs' || $active === 'bug_view' || $active === 'bug_edit') ? 'active' : ''; ?>">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                <path d="M9 14l2 2 4-4"></path>
            </svg>
            <span>Bug Tickets</span>
        </a>

        <a href="<?= BASE_URL; ?>/bug_create.php" class="nav-link <?= ($active === 'bug_create') ? 'active' : ''; ?>">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="16"></line>
                <line x1="8" y1="12" x2="16" y2="12"></line>
            </svg>
            <span>Report Bug</span>
        </a>

        <a href="<?= BASE_URL; ?>/projects.php" class="nav-link <?= ($active === 'projects') ? 'active' : ''; ?>">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
            </svg>
            <span>Projects</span>
        </a>

        <a href="<?= BASE_URL; ?>/reports.php" class="nav-link <?= ($active === 'reports') ? 'active' : ''; ?>">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <line x1="18" y1="20" x2="18" y2="10"></line>
                <line x1="12" y1="20" x2="12" y2="4"></line>
                <line x1="6" y1="20" x2="6" y2="14"></line>
            </svg>
            <span>Analytics & Reports</span>
        </a>

        <?php if ($user['role'] === 'admin'): ?>
            <div class="nav-section-title">Administration</div>
            <a href="<?= BASE_URL; ?>/users.php" class="nav-link <?= ($active === 'users') ? 'active' : ''; ?>">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <span>User Management</span>
            </a>
        <?php endif; ?>

        <div class="nav-section-title">Documentation</div>
        <a href="<?= BASE_URL; ?>/docs.php" class="nav-link <?= ($active === 'docs') ? 'active' : ''; ?>">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
            </svg>
            <span>SE Documentation</span>
        </a>

        <div class="nav-section-title">Account</div>
        <a href="<?= BASE_URL; ?>/profile.php" class="nav-link <?= ($active === 'profile') ? 'active' : ''; ?>">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            <span>My Profile</span>
        </a>

        <a href="<?= BASE_URL; ?>/logout.php" class="nav-link" data-confirm="Are you sure you want to sign out?">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <span>Sign Out</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div style="font-size: 0.72rem; color: var(--slate-400); text-align: center;">
            BugTrace v<?= APP_VERSION; ?> &bull; SE Mini-Project
        </div>
    </div>
</aside>

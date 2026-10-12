<?php
/**
 * Bug Tracking System - Layout Header
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

// Ensure user is logged in for protected header layout
require_login();
$currentUser = current_user();
$pageTitle = $pageTitle ?? APP_TITLE;
$activePage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="A comprehensive web-based Bug Tracking System for software development teams to report, prioritize, track, and resolve bugs efficiently.">
    <title><?= e($pageTitle); ?> - <?= APP_NAME; ?></title>
    <link rel="stylesheet" href="<?= BASE_URL; ?>/assets/css/style.css">
</head>
<body>
<div class="app-container">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="app-main">
        <!-- Topbar Header -->
        <header class="app-topbar">
            <div class="topbar-title">
                <button type="button" class="mobile-toggle" aria-label="Toggle Navigation">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
                <span><?= e($pageTitle); ?></span>
            </div>

            <div class="topbar-actions">
                <a href="<?= BASE_URL; ?>/bug_create.php" class="btn btn-primary btn-sm">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Report Bug</span>
                </a>

                <div class="user-pill" style="display: flex; align-items: center; gap: 8px; border-left: 1px solid var(--slate-200); padding-left: 14px;">
                    <span style="font-weight: 600; font-size: 0.88rem; color: var(--navy-800);"><?= e($currentUser['name']); ?></span>
                    <?= render_role_badge($currentUser['role']); ?>
                </div>

                <a href="<?= BASE_URL; ?>/logout.php" class="btn btn-secondary btn-sm" title="Log Out" data-confirm="Are you sure you want to sign out?">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                </a>
            </div>
        </header>

        <!-- Main Content Wrapper -->
        <main class="main-content">
            <?= render_flash_messages(); ?>

<?php
/**
 * Bug Tracking System - Landing Page
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

// If user already logged in, redirect directly to dashboard
if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME; ?> - Software Engineering Bug Tracking System</title>
    <meta name="description" content="A complete web-based Bug Tracking System for software teams to report, prioritize, track, and resolve bugs efficiently.">
    <link rel="stylesheet" href="<?= BASE_URL; ?>/assets/css/style.css">
    <style>
        .landing-hero {
            background: radial-gradient(circle at 80% 20%, #1e3a8a, #0f172a 75%);
            color: #ffffff;
            padding: 80px 24px 100px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            background: rgba(37, 99, 235, 0.25);
            border: 1px solid rgba(96, 165, 250, 0.4);
            border-radius: var(--radius-full);
            font-size: 0.85rem;
            color: #93c5fd;
            margin-bottom: 24px;
            backdrop-filter: blur(8px);
        }
        .hero-title {
            font-size: clamp(2.2rem, 5vw, 3.5rem);
            font-weight: 800;
            line-height: 1.15;
            max-width: 850px;
            margin: 0 auto 20px;
            letter-spacing: -0.03em;
        }
        .hero-title span {
            background: linear-gradient(135deg, #60a5fa, #38bdf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero-desc {
            font-size: 1.15rem;
            color: #cbd5e1;
            max-width: 680px;
            margin: 0 auto 36px;
            line-height: 1.6;
        }
        .hero-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-top: -50px;
            position: relative;
            z-index: 10;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
            padding: 0 24px;
        }
        .feature-card {
            background: #ffffff;
            border-radius: var(--radius-lg);
            padding: 30px;
            border: 1px solid var(--slate-200);
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08);
            transition: var(--transition);
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.12);
        }
        .feature-icon {
            width: 52px;
            height: 52px;
            border-radius: var(--radius-md);
            background: var(--blue-50);
            color: var(--blue-600);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
        .workflow-section {
            padding: 80px 24px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .section-header {
            text-align: center;
            margin-bottom: 50px;
        }
        .section-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--navy-900);
            margin-bottom: 12px;
        }
        .section-subtitle {
            font-size: 1rem;
            color: var(--slate-500);
            max-width: 600px;
            margin: 0 auto;
        }
        .workflow-steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            position: relative;
        }
        .workflow-step {
            background: #ffffff;
            border: 1px solid var(--slate-200);
            border-radius: var(--radius-md);
            padding: 20px 14px;
            text-align: center;
            box-shadow: var(--shadow-sm);
        }
        .workflow-num {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--blue-600);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin: 0 auto 10px;
            font-size: 0.85rem;
        }
        .role-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
        }
        .role-card {
            background: #ffffff;
            border-radius: var(--radius-lg);
            border: 1px solid var(--slate-200);
            padding: 30px;
            display: flex;
            flex-direction: column;
        }
        .role-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 18px;
        }
        .role-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .role-list li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 0.9rem;
            color: var(--navy-700);
        }
        .role-list svg {
            color: #059669;
            flex-shrink: 0;
            margin-top: 2px;
        }
    </style>
</head>
<body>

<!-- Landing Hero -->
<header class="landing-hero">
    <div class="hero-badge">
        <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
        </svg>
        <span>Software Engineering Mini-Project</span>
    </div>

    <h1 class="hero-title">Track, Resolve, and Deliver Quality Code with <span><?= APP_NAME; ?></span></h1>
    <p class="hero-desc">
        A professional, responsive Bug Tracking System built with PHP, MySQL, and modern Vanilla CSS.
        Streamline reporting, assignment, triage, and verification with seamless role-based workflows.
    </p>

    <div class="hero-actions">
        <a href="<?= BASE_URL; ?>/login.php" class="btn btn-primary btn-lg">
            <span>Sign In to System</span>
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <line x1="5" y1="12" x2="19" y2="12"></line>
                <polyline points="12 5 19 12 12 19"></polyline>
            </svg>
        </a>
        <a href="<?= BASE_URL; ?>/register.php" class="btn btn-secondary btn-lg" style="background: rgba(255,255,255,0.1); color: #fff; border-color: rgba(255,255,255,0.25);">
            <span>Create Tester Account</span>
        </a>
    </div>
</header>

<!-- Highlight Features Grid -->
<section class="feature-grid">
    <div class="feature-card">
        <div class="feature-icon">
            <svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
            </svg>
        </div>
        <h3 style="font-size: 1.15rem; margin-bottom: 8px; color: var(--navy-900);">Role-Based Security</h3>
        <p style="color: var(--slate-500); font-size: 0.9rem;">
            Dedicated dashboards for Administrators, Developers, and QA Testers with strict session authorization and prepared statements.
        </p>
    </div>

    <div class="feature-card">
        <div class="feature-icon">
            <svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
        </div>
        <h3 style="font-size: 1.15rem; margin-bottom: 8px; color: var(--navy-900);">Full Lifecycle Workflow</h3>
        <p style="color: var(--slate-500); font-size: 0.9rem;">
            Track issues through New, Assigned, In Progress, Fixed, Verified, Reopened, and Closed states with complete audit history.
        </p>
    </div>

    <div class="feature-card">
        <div class="feature-icon">
            <svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <line x1="18" y1="20" x2="18" y2="10"></line>
                <line x1="12" y1="20" x2="12" y2="4"></line>
                <line x1="6" y1="20" x2="6" y2="14"></line>
            </svg>
        </div>
        <h3 style="font-size: 1.15rem; margin-bottom: 8px; color: var(--navy-900);">Interactive Analytics</h3>
        <p style="color: var(--slate-500); font-size: 0.9rem;">
            Zero-dependency canvas charts display bug volume by priority, status breakdown, and developer assignment metrics.
        </p>
    </div>
</section>

<!-- Bug Lifecycle Pipeline Section -->
<section class="workflow-section">
    <div class="section-header">
        <h2 class="section-title">Standard Bug Resolution Pipeline</h2>
        <p class="section-subtitle">
            Every reported defect traverses a regulated state machine to guarantee quality assurance and accountability.
        </p>
    </div>

    <div class="workflow-steps">
        <div class="workflow-step">
            <div class="workflow-num">1</div>
            <strong style="color: var(--status-new);">New</strong>
            <p style="font-size: 0.78rem; color: var(--slate-500); margin-top: 4px;">Reported by QA / Tester</p>
        </div>
        <div class="workflow-step">
            <div class="workflow-num">2</div>
            <strong style="color: var(--status-assigned);">Assigned</strong>
            <p style="font-size: 0.78rem; color: var(--slate-500); margin-top: 4px;">Delegated to Dev by Admin</p>
        </div>
        <div class="workflow-step">
            <div class="workflow-num">3</div>
            <strong style="color: var(--status-progress);">In Progress</strong>
            <p style="font-size: 0.78rem; color: var(--slate-500); margin-top: 4px;">Under active development</p>
        </div>
        <div class="workflow-step">
            <div class="workflow-num">4</div>
            <strong style="color: var(--status-fixed);">Fixed</strong>
            <p style="font-size: 0.78rem; color: var(--slate-500); margin-top: 4px;">Fix implemented by Dev</p>
        </div>
        <div class="workflow-step">
            <div class="workflow-num">5</div>
            <strong style="color: var(--status-verified);">Verified</strong>
            <p style="font-size: 0.78rem; color: var(--slate-500); margin-top: 4px;">QA confirms bug resolved</p>
        </div>
        <div class="workflow-step">
            <div class="workflow-num">6</div>
            <strong style="color: var(--status-closed);">Closed</strong>
            <p style="font-size: 0.78rem; color: var(--slate-500); margin-top: 4px;">Archived as resolved</p>
        </div>
    </div>
</section>

<!-- Role Breakdown Section -->
<section class="workflow-section" style="padding-top: 0;">
    <div class="section-header">
        <h2 class="section-title">Tailored Role Experiences</h2>
        <p class="section-subtitle">
            Each stakeholder accesses a specialized interface matching their operational duties.
        </p>
    </div>

    <div class="role-cards-grid">
        <!-- Admin -->
        <div class="role-card">
            <div class="role-header">
                <div class="logo-badge" style="background: linear-gradient(135deg, #7c3aed, #4f46e5);">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="8" r="4"></circle>
                        <path d="M6 20v-2a6 6 0 0 1 12 0v2"></path>
                    </svg>
                </div>
                <div>
                    <h3 style="font-size: 1.15rem; color: var(--navy-900);">Administrator</h3>
                    <span style="font-size: 0.8rem; color: var(--slate-500);">System Governance &amp; Triage</span>
                </div>
            </div>
            <ul class="role-list">
                <li>
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Manage all user accounts &amp; roles</span>
                </li>
                <li>
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Assign tickets to developer team</span>
                </li>
                <li>
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Delete duplicates and inappropriate bugs</span>
                </li>
                <li>
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>View comprehensive project reports</span>
                </li>
            </ul>
        </div>

        <!-- Developer -->
        <div class="role-card">
            <div class="role-header">
                <div class="logo-badge" style="background: linear-gradient(135deg, #2563eb, #1d4ed8);">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <polyline points="16 18 22 12 16 6"></polyline>
                        <polyline points="8 6 2 12 8 18"></polyline>
                    </svg>
                </div>
                <div>
                    <h3 style="font-size: 1.15rem; color: var(--navy-900);">Developer</h3>
                    <span style="font-size: 0.8rem; color: var(--slate-500);">Investigation &amp; Resolution</span>
                </div>
            </div>
            <ul class="role-list">
                <li>
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Inspect assigned bugs and reproduction steps</span>
                </li>
                <li>
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Update ticket status to "In Progress"</span>
                </li>
                <li>
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Mark bugs as "Fixed" with resolution notes</span>
                </li>
                <li>
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Engage in collaborative comment threads</span>
                </li>
            </ul>
        </div>

        <!-- Tester -->
        <div class="role-card">
            <div class="role-header">
                <div class="logo-badge" style="background: linear-gradient(135deg, #0d9488, #0f766e);">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 14 14"></polyline>
                    </svg>
                </div>
                <div>
                    <h3 style="font-size: 1.15rem; color: var(--navy-900);">Tester / Reporter</h3>
                    <span style="font-size: 0.8rem; color: var(--slate-500);">Quality Assurance &amp; Verification</span>
                </div>
            </div>
            <ul class="role-list">
                <li>
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Submit new bugs with screenshot evidence</span>
                </li>
                <li>
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Track reported bugs through lifecycle</span>
                </li>
                <li>
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Verify developer fixes or Reopen issues</span>
                </li>
                <li>
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Filter, search, and sort tickets</span>
                </li>
            </ul>
        </div>
    </div>
</section>

<!-- Footer -->
<footer style="background: #ffffff; border-top: 1px solid var(--slate-200); padding: 40px 24px; text-align: center;">
    <div style="max-width: 1200px; margin: 0 auto; display: flex; flex-direction: column; align-items: center; gap: 16px;">
        <div style="font-weight: 700; font-size: 1.2rem; color: var(--navy-900);">
            <?= APP_NAME; ?> &bull; <?= APP_TITLE; ?>
        </div>
        <p style="color: var(--slate-500); font-size: 0.88rem; max-width: 500px;">
            A Software Engineering Mini-Project built with clean architecture, strict security best practices, and responsive design.
        </p>
        <div style="display: flex; gap: 16px;">
            <a href="<?= BASE_URL; ?>/login.php" class="btn btn-primary btn-sm">Sign In</a>
            <a href="<?= BASE_URL; ?>/register.php" class="btn btn-secondary btn-sm">Register</a>
        </div>
    </div>
</footer>

<script src="<?= BASE_URL; ?>/assets/js/main.js"></script>
</body>
</html>

<?php
/**
 * Bug Tracking System - Academic Documentation Viewer
 */

$pageTitle = 'SE Documentation';
require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 1100px; margin: 0 auto;">
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h1 class="card-title" style="font-size: 1.3rem;">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                Software Engineering Mini-Project Documentation
            </h1>
            <a href="<?= BASE_URL; ?>/DOCUMENTATION.md" target="_blank" class="btn btn-secondary btn-sm">
                View Raw Markdown
            </a>
        </div>

        <div class="card-body" style="font-size: 0.95rem; line-height: 1.7; color: var(--navy-800);">
            <div style="background: var(--blue-50); border-left: 4px solid var(--blue-600); padding: 16px 20px; border-radius: 0 var(--radius-md) var(--radius-md) 0; margin-bottom: 30px;">
                <h3 style="color: var(--blue-600); margin: 0 0 6px; font-size: 1.05rem;">Project Overview &amp; Abstract</h3>
                <p style="margin: 0; color: var(--navy-900);">
                    The <strong>Bug Tracking System (BugTrace)</strong> is an academic mini-project developed in accordance with Software Engineering principles. It provides an end-to-end framework enabling QA testers, developers, and project administrators to record, triage, assign, resolve, and audit software defects. Built using native PHP with PDO prepared statements, a relational MySQL database, and responsive Vanilla CSS/JS.
                </p>
            </div>

            <!-- Quick Navigation -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; margin-bottom: 36px;">
                <a href="#workflow" class="card" style="padding: 16px; margin: 0; text-decoration: none; border-color: var(--slate-300);">
                    <strong style="color: var(--navy-900); display: block; font-size: 0.95rem;">1. Bug Lifecycle Workflow</strong>
                    <span style="font-size: 0.82rem; color: var(--slate-500);">7-stage state machine</span>
                </a>
                <a href="#srs" class="card" style="padding: 16px; margin: 0; text-decoration: none; border-color: var(--slate-300);">
                    <strong style="color: var(--navy-900); display: block; font-size: 0.95rem;">2. SRS Requirements</strong>
                    <span style="font-size: 0.82rem; color: var(--slate-500);">Functional &amp; Non-Functional</span>
                </a>
                <a href="#schema" class="card" style="padding: 16px; margin: 0; text-decoration: none; border-color: var(--slate-300);">
                    <strong style="color: var(--navy-900); display: block; font-size: 0.95rem;">3. Database Schema</strong>
                    <span style="font-size: 0.82rem; color: var(--slate-500);">Data dictionary for 5 tables</span>
                </a>
                <a href="#testcases" class="card" style="padding: 16px; margin: 0; text-decoration: none; border-color: var(--slate-300);">
                    <strong style="color: var(--navy-900); display: block; font-size: 0.95rem;">4. Test Cases &amp; Results</strong>
                    <span style="font-size: 0.82rem; color: var(--slate-500);">18 verification test cases</span>
                </a>
            </div>

            <!-- Section: Workflow -->
            <section id="workflow" style="margin-bottom: 40px;">
                <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--navy-900); border-bottom: 2px solid var(--slate-200); padding-bottom: 8px; margin-bottom: 16px;">
                    1. Regulated Bug Lifecycle State Machine
                </h2>
                <p>
                    Every defect is governed by strict state transitions to preserve accountability across team roles:
                </p>
                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin: 16px 0;">
                    <span class="badge badge-status-new" style="padding: 8px 14px; font-size: 0.85rem;">1. New (Reported by QA)</span>
                    <span style="align-self: center;">&rarr;</span>
                    <span class="badge badge-status-assigned" style="padding: 8px 14px; font-size: 0.85rem;">2. Assigned (Admin delegated)</span>
                    <span style="align-self: center;">&rarr;</span>
                    <span class="badge badge-status-progress" style="padding: 8px 14px; font-size: 0.85rem;">3. In Progress (Dev fixing)</span>
                    <span style="align-self: center;">&rarr;</span>
                    <span class="badge badge-status-fixed" style="padding: 8px 14px; font-size: 0.85rem;">4. Fixed (Deployed)</span>
                    <span style="align-self: center;">&rarr;</span>
                    <span class="badge badge-status-verified" style="padding: 8px 14px; font-size: 0.85rem;">5. Verified (QA tested)</span>
                    <span style="align-self: center;">&rarr;</span>
                    <span class="badge badge-status-closed" style="padding: 8px 14px; font-size: 0.85rem;">6. Closed (Resolved)</span>
                </div>
                <div style="background: var(--slate-100); border-radius: var(--radius-md); padding: 14px; font-size: 0.88rem; color: var(--slate-600);">
                    * Note: If verification fails, QA transitions the status to <span class="badge badge-status-reopened">Reopened</span>, cycling the defect back to the developer queue.
                </div>
            </section>

            <!-- Section: SRS -->
            <section id="srs" style="margin-bottom: 40px;">
                <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--navy-900); border-bottom: 2px solid var(--slate-200); padding-bottom: 8px; margin-bottom: 16px;">
                    2. Software Requirements Specification (SRS)
                </h2>
                <h3 style="font-size: 1.05rem; color: var(--navy-900); margin: 14px 0 8px;">2.1 Functional Requirements</h3>
                <ul style="padding-left: 20px; margin-bottom: 20px;">
                    <li><strong>Authentication &amp; RBAC:</strong> Secure login with role enforcement (Admin, Developer, Tester).</li>
                    <li><strong>Defect Submission:</strong> Detailed bug logging with title, description, project, category, priority, severity, reproduction steps, expected/actual results, and screenshot upload.</li>
                    <li><strong>Assignment &amp; Triage:</strong> Administrators assign defects to specific active developers.</li>
                    <li><strong>Status Auditing:</strong> Every change is captured in <code>bug_history</code> with actor, timestamp, previous status, and notes.</li>
                    <li><strong>Threaded Discussions:</strong> Collaborative comments on bug detail pages.</li>
                    <li><strong>Search, Filter &amp; Sort:</strong> Multi-parameter query engine with pagination.</li>
                    <li><strong>Visual Analytics:</strong> Real-time zero-dependency Canvas charts for defect density.</li>
                </ul>

                <h3 style="font-size: 1.05rem; color: var(--navy-900); margin: 14px 0 8px;">2.2 Non-Functional Requirements &amp; Security</h3>
                <ul style="padding-left: 20px;">
                    <li><strong>Zero SQL Injection:</strong> 100% PDO prepared statements with bounded parameters.</li>
                    <li><strong>XSS Prevention:</strong> Output escaping via <code>htmlspecialchars()</code> on all user content.</li>
                    <li><strong>CSRF Hardening:</strong> Cryptographic token verification on all POST endpoints.</li>
                    <li><strong>Password Security:</strong> Bcrypt hashing via PHP <code>password_hash()</code>.</li>
                    <li><strong>Responsive Layout:</strong> Usable across mobile, tablet, and desktop screens.</li>
                </ul>
            </section>

            <!-- Section: Schema -->
            <section id="schema" style="margin-bottom: 40px;">
                <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--navy-900); border-bottom: 2px solid var(--slate-200); padding-bottom: 8px; margin-bottom: 16px;">
                    3. Database Schema Overview
                </h2>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Table Name</th>
                                <th>Primary Key</th>
                                <th>Foreign Keys</th>
                                <th>Purpose &amp; Stored Entity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>users</code></td>
                                <td><code>id</code></td>
                                <td>None</td>
                                <td>Stores user accounts, bcrypt password hashes, roles, and active states.</td>
                            </tr>
                            <tr>
                                <td><code>projects</code></td>
                                <td><code>id</code></td>
                                <td>None</td>
                                <td>Stores software projects and repositories under defect tracking.</td>
                            </tr>
                            <tr>
                                <td><code>bugs</code></td>
                                <td><code>id</code></td>
                                <td><code>project_id</code>, <code>reporter_id</code>, <code>assigned_to</code></td>
                                <td>Stores defect details, steps to reproduce, priority, severity, status, and screenshot filename.</td>
                            </tr>
                            <tr>
                                <td><code>comments</code></td>
                                <td><code>id</code></td>
                                <td><code>bug_id</code>, <code>user_id</code></td>
                                <td>Stores threaded discussions and developer resolution notes.</td>
                            </tr>
                            <tr>
                                <td><code>bug_history</code></td>
                                <td><code>id</code></td>
                                <td><code>bug_id</code>, <code>changed_by</code></td>
                                <td>Immutable audit log capturing all lifecycle status shifts with timestamps.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Section: Test Cases -->
            <section id="testcases" style="margin-bottom: 40px;">
                <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--navy-900); border-bottom: 2px solid var(--slate-200); padding-bottom: 8px; margin-bottom: 16px;">
                    4. Test Cases &amp; Verification Results
                </h2>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Test Scenario</th>
                                <th>Expected Result</th>
                                <th>Actual Result</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>TC01</strong></td>
                                <td>Tester Registration</td>
                                <td>Account created with role 'tester'</td>
                                <td>Account created, flash message shown</td>
                                <td><span class="badge badge-status-fixed">PASS</span></td>
                            </tr>
                            <tr>
                                <td><strong>TC02</strong></td>
                                <td>Duplicate Email Check</td>
                                <td>Registration rejected</td>
                                <td>Error "Email already exists" displayed</td>
                                <td><span class="badge badge-status-fixed">PASS</span></td>
                            </tr>
                            <tr>
                                <td><strong>TC03</strong></td>
                                <td>Admin Login</td>
                                <td>Admin dashboard loaded</td>
                                <td>Admin welcome view rendered</td>
                                <td><span class="badge badge-status-fixed">PASS</span></td>
                            </tr>
                            <tr>
                                <td><strong>TC04</strong></td>
                                <td>Invalid Credentials</td>
                                <td>Login denied</td>
                                <td>"Invalid email or password" displayed</td>
                                <td><span class="badge badge-status-fixed">PASS</span></td>
                            </tr>
                            <tr>
                                <td><strong>TC05</strong></td>
                                <td>Role Access Protection</td>
                                <td>Blocked from admin pages</td>
                                <td>Redirected to dashboard with danger alert</td>
                                <td><span class="badge badge-status-fixed">PASS</span></td>
                            </tr>
                            <tr>
                                <td><strong>TC06</strong></td>
                                <td>Report Bug with Screenshot</td>
                                <td>Bug stored with status 'New', image saved</td>
                                <td>Saved to DB; image displayed in lightbox</td>
                                <td><span class="badge badge-status-fixed">PASS</span></td>
                            </tr>
                            <tr>
                                <td><strong>TC07</strong></td>
                                <td>Developer Status Shift</td>
                                <td>Status updated to 'In Progress'</td>
                                <td>Status updated and logged in history</td>
                                <td><span class="badge badge-status-fixed">PASS</span></td>
                            </tr>
                            <tr>
                                <td><strong>TC08</strong></td>
                                <td>Developer Marks Fixed</td>
                                <td>Status updated to 'Fixed'</td>
                                <td>Status updated; ready for QA verification</td>
                                <td><span class="badge badge-status-fixed">PASS</span></td>
                            </tr>
                            <tr>
                                <td><strong>TC09</strong></td>
                                <td>Tester Reopens Fix</td>
                                <td>Status updated to 'Reopened'</td>
                                <td>Status updated; moved back to Dev queue</td>
                                <td><span class="badge badge-status-fixed">PASS</span></td>
                            </tr>
                            <tr>
                                <td><strong>TC10</strong></td>
                                <td>CSRF Defense</td>
                                <td>Unauthenticated POST blocked</td>
                                <td>Request halted with security error</td>
                                <td><span class="badge badge-status-fixed">PASS</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

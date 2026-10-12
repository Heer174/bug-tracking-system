<?php
/**
 * Bug Tracking System - Utility & Security Functions
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Escape output for safe HTML display (XSS Prevention)
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Generate or retrieve CSRF token
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render a hidden CSRF input field
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Validate submitted CSRF token
 */
function validate_csrf(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Set flash alert message for next page view
 */
function set_flash(string $type, string $message): void {
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][] = [
        'type'    => in_array($type, ['success', 'danger', 'warning', 'info']) ? $type : 'info',
        'message' => $message
    ];
}

/**
 * Check if flash messages exist
 */
function has_flash(): bool {
    return !empty($_SESSION['flash']);
}

/**
 * Retrieve and clear flash messages
 */
function get_flash(): array {
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/**
 * Render all flash messages as HTML alerts
 */
function render_flash_messages(): string {
    $flashes = get_flash();
    if (empty($flashes)) {
        return '';
    }

    $html = '<div class="flash-container">';
    foreach ($flashes as $flash) {
        $type = e($flash['type']);
        $msg = e($flash['message']);
        $icon = match($type) {
            'success' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
            'danger'  => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
            'warning' => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
            default   => '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>'
        };
        $html .= <<<HTML
        <div class="alert alert-{$type} animate-fade-in" role="alert">
            <div class="alert-icon">{$icon}</div>
            <div class="alert-message">{$msg}</div>
            <button type="button" class="alert-close" onclick="this.parentElement.remove();" aria-label="Close">&times;</button>
        </div>
HTML;
    }
    $html .= '</div>';
    return $html;
}

/**
 * Format relative date/time (e.g. "2 hours ago")
 */
function time_ago(?string $datetime): string {
    if (!$datetime) return 'Never';
    $time = strtotime($datetime);
    if (!$time) return 'Invalid date';

    $diff = time() - $time;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M d, Y', $time);
}

/**
 * Format full date/time
 */
function format_datetime(?string $datetime): string {
    if (!$datetime) return 'N/A';
    $time = strtotime($datetime);
    return date('M d, Y h:i A', $time);
}

/**
 * Render visual badge for bug status
 */
function render_status_badge(string $status): string {
    $class = match($status) {
        'New'         => 'badge-status-new',
        'Assigned'    => 'badge-status-assigned',
        'In Progress' => 'badge-status-progress',
        'Fixed'       => 'badge-status-fixed',
        'Verified'    => 'badge-status-verified',
        'Reopened'    => 'badge-status-reopened',
        'Closed'      => 'badge-status-closed',
        default       => 'badge-default'
    };
    return '<span class="badge ' . $class . '">' . e($status) . '</span>';
}

/**
 * Render visual badge for bug priority
 */
function render_priority_badge(string $priority): string {
    $class = match($priority) {
        'Critical' => 'badge-priority-critical',
        'High'     => 'badge-priority-high',
        'Medium'   => 'badge-priority-medium',
        'Low'      => 'badge-priority-low',
        default    => 'badge-default'
    };
    return '<span class="badge ' . $class . '">' . e($priority) . '</span>';
}

/**
 * Render visual badge for bug severity
 */
function render_severity_badge(string $severity): string {
    $class = match($severity) {
        'Blocker'  => 'badge-severity-blocker',
        'Critical' => 'badge-severity-critical',
        'Major'    => 'badge-severity-major',
        'Minor'    => 'badge-severity-minor',
        'Low'      => 'badge-severity-low',
        default    => 'badge-default'
    };
    return '<span class="badge ' . $class . '">' . e($severity) . '</span>';
}

/**
 * Render visual badge for user role
 */
function render_role_badge(string $role): string {
    $class = match(strtolower($role)) {
        'admin'     => 'badge-role-admin',
        'developer' => 'badge-role-dev',
        'tester'    => 'badge-role-tester',
        default     => 'badge-default'
    };
    return '<span class="badge ' . $class . '">' . ucfirst(e($role)) . '</span>';
}

/**
 * Log status change into bug_history
 */
function log_bug_history(int $bugId, int $userId, string $prevStatus, string $newStatus, string $description = ''): bool {
    try {
        $db = get_db();
        $stmt = $db->prepare("
            INSERT INTO bug_history (bug_id, changed_by, previous_status, new_status, change_description)
            VALUES (:bug_id, :changed_by, :previous_status, :new_status, :change_description)
        ");
        return $stmt->execute([
            ':bug_id'             => $bugId,
            ':changed_by'         => $userId,
            ':previous_status'    => $prevStatus,
            ':new_status'         => $newStatus,
            ':change_description' => $description
        ]);
    } catch (PDOException $e) {
        error_log("Error logging bug history: " . $e->getMessage());
        return false;
    }
}

/**
 * Validate permitted status transitions based on user role and bug state
 * Workflow rules:
 * - Admin: Can transition to any valid status
 * - Developer: Can change Assigned -> In Progress, In Progress -> Fixed, Reopened -> In Progress
 * - Tester: Can change Fixed -> Verified, Fixed -> Reopened, Verified -> Closed, or New -> Closed (duplicate/invalid)
 */
function get_allowed_status_transitions(string $role, string $currentStatus, bool $isAssignedToUser = false, bool $isReportedByUser = false): array {
    if ($role === 'admin') {
        // Admin has full control
        return ['New', 'Assigned', 'In Progress', 'Fixed', 'Verified', 'Reopened', 'Closed'];
    }

    if ($role === 'developer') {
        $transitions = [$currentStatus];
        if ($currentStatus === 'Assigned' || $currentStatus === 'Reopened') {
            $transitions[] = 'In Progress';
        } elseif ($currentStatus === 'In Progress') {
            $transitions[] = 'Fixed';
        }
        return array_unique($transitions);
    }

    if ($role === 'tester') {
        $transitions = [$currentStatus];
        if ($currentStatus === 'Fixed') {
            $transitions[] = 'Verified';
            $transitions[] = 'Reopened';
        } elseif ($currentStatus === 'Verified') {
            $transitions[] = 'Closed';
            $transitions[] = 'Reopened';
        } elseif ($currentStatus === 'New' && $isReportedByUser) {
            $transitions[] = 'Closed'; // Reporter can close self-reported bug if invalid
        }
        return array_unique($transitions);
    }

    return [$currentStatus];
}

/**
 * Validate and handle uploaded bug screenshot
 * @param array $file $_FILES['screenshot']
 * @return array ['success' => bool, 'filename' => ?string, 'error' => ?string]
 */
function handle_screenshot_upload(array $file): array {
    // If no file was uploaded
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'filename' => null, 'error' => null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'filename' => null, 'error' => 'File upload error code: ' . $file['error']];
    }

    // Size limit check
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        return ['success' => false, 'filename' => null, 'error' => 'Screenshot file size exceeds 5 MB limit.'];
    }

    // Ensure uploads directory exists
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    // Verify MIME type using finfo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!array_key_exists($mime, ALLOWED_MIME_TYPES)) {
        return ['success' => false, 'filename' => null, 'error' => 'Invalid file type. Allowed formats: JPG, PNG, GIF, WebP.'];
    }

    $extension = ALLOWED_MIME_TYPES[$mime];
    // Generate secure random filename
    $uniqueName = 'bug_' . bin2hex(random_bytes(12)) . '.' . $extension;
    $targetPath = UPLOAD_DIR . DIRECTORY_SEPARATOR . $uniqueName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'filename' => null, 'error' => 'Failed to save uploaded screenshot to disk.'];
    }

    return ['success' => true, 'filename' => $uniqueName, 'error' => null];
}

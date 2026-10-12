<?php
/**
 * Bug Tracking System - Configuration & Global Constants
 */

// Start output buffering to prevent "headers already sent" errors
if (!ob_get_level()) {
    ob_start();
}

// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    // Session security configurations
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Application settings
define('APP_NAME', 'BugTrace');
define('APP_TITLE', 'Bug Tracking System');
define('APP_VERSION', '1.0.0');

// Base URL calculation (works for both XAMPP subfolders and root / built-in server)
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
$protocol = $isHttps ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$baseUrl = rtrim($protocol . $host . $scriptDir, '/');

// If we are deep inside includes/ or subfolders, normalize to root
if (basename($scriptDir) === 'includes' || basename($scriptDir) === 'config') {
    $baseUrl = dirname($baseUrl);
}
define('BASE_URL', $baseUrl);

// Directory paths
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_DIR', ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads');
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5 MB

// Allowed image MIME types for screenshots
define('ALLOWED_MIME_TYPES', [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp'
]);

// Bug workflow constants
define('BUG_STATUSES', [
    'New'         => ['color' => 'blue',   'badge' => 'badge-blue'],
    'Assigned'    => ['color' => 'indigo', 'badge' => 'badge-indigo'],
    'In Progress' => ['color' => 'amber',  'badge' => 'badge-amber'],
    'Fixed'       => ['color' => 'emerald','badge' => 'badge-emerald'],
    'Verified'    => ['color' => 'teal',   'badge' => 'badge-teal'],
    'Reopened'    => ['color' => 'rose',   'badge' => 'badge-rose'],
    'Closed'      => ['color' => 'slate',  'badge' => 'badge-slate'],
]);

define('BUG_PRIORITIES', [
    'Low'      => ['badge' => 'badge-slate'],
    'Medium'   => ['badge' => 'badge-blue'],
    'High'     => ['badge' => 'badge-amber'],
    'Critical' => ['badge' => 'badge-rose']
]);

define('BUG_SEVERITIES', [
    'Low'      => ['badge' => 'badge-slate'],
    'Minor'    => ['badge' => 'badge-blue'],
    'Major'    => ['badge' => 'badge-amber'],
    'Critical' => ['badge' => 'badge-rose'],
    'Blocker'  => ['badge' => 'badge-purple']
]);

define('BUG_CATEGORIES', [
    'UI / Layout',
    'Backend / API',
    'Authentication',
    'Database',
    'Security',
    'Performance',
    'Feature Request',
    'Other'
]);

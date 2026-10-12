<?php
/**
 * Bug Tracking System - Authentication & Authorization Management
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Check if a user is currently authenticated
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['user_role']);
}

/**
 * Get current authenticated user details
 */
function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'     => (int)$_SESSION['user_id'],
        'name'   => $_SESSION['user_name'] ?? 'User',
        'email'  => $_SESSION['user_email'] ?? '',
        'role'   => $_SESSION['user_role'] ?? 'tester',
        'status' => $_SESSION['user_status'] ?? 'active'
    ];
}

/**
 * Check if the authenticated user has any of the specified roles
 */
function has_role(string ...$roles): bool {
    $user = current_user();
    if (!$user) {
        return false;
    }
    return in_array($user['role'], $roles, true);
}

/**
 * Enforce authentication requirement on protected pages
 */
function require_login(): void {
    if (!is_logged_in()) {
        set_flash('warning', 'Please sign in to access this page.');
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }

    // Verify account is still active
    if (isset($_SESSION['user_status']) && $_SESSION['user_status'] === 'inactive') {
        logout_user();
        set_flash('danger', 'Your account has been deactivated. Please contact an administrator.');
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/**
 * Enforce role authorization requirement on restricted pages
 */
function require_role(string ...$roles): void {
    require_login();
    if (!has_role(...$roles)) {
        set_flash('danger', 'Access denied. You do not have permission to view that resource.');
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit;
    }
}

/**
 * Authenticate user with email and password
 * @return array ['success' => bool, 'message' => string, 'user' => ?array]
 */
function authenticate_user(string $email, string $password): array {
    $email = trim(strtolower($email));
    
    if (empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Please enter both email and password.', 'user' => null];
    }

    try {
        $db = get_db();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid email address or password.', 'user' => null];
        }

        if ($user['status'] === 'inactive') {
            return ['success' => false, 'message' => 'This account has been deactivated. Please contact an admin.', 'user' => null];
        }

        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid email address or password.', 'user' => null];
        }

        // Re-generate session ID to prevent session fixation attacks
        session_regenerate_id(true);

        $_SESSION['user_id']     = (int)$user['id'];
        $_SESSION['user_name']   = $user['name'];
        $_SESSION['user_email']  = $user['email'];
        $_SESSION['user_role']   = $user['role'];
        $_SESSION['user_status'] = $user['status'];

        return ['success' => true, 'message' => 'Login successful.', 'user' => $user];

    } catch (PDOException $e) {
        error_log("Database error during authentication: " . $e->getMessage());
        return ['success' => false, 'message' => 'A database error occurred. Please try again.', 'user' => null];
    }
}

/**
 * Register a new user account
 * @return array ['success' => bool, 'message' => string, 'user_id' => ?int]
 */
function register_new_user(string $name, string $email, string $password, string $role = 'tester'): array {
    $name = trim($name);
    $email = trim(strtolower($email));

    if (empty($name) || empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'All fields are required.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Please enter a valid email address.'];
    }

    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters long.'];
    }

    // Role restrictions for public registration (only tester allowed publicly unless created by admin)
    $allowedPublicRoles = ['tester'];
    if (!in_array($role, $allowedPublicRoles, true) && !has_role('admin')) {
        $role = 'tester';
    }

    try {
        $db = get_db();

        // Check if email already taken
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'An account with this email address already exists.'];
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $insertStmt = $db->prepare("
            INSERT INTO users (name, email, password, role, status)
            VALUES (:name, :email, :password, :role, 'active')
        ");
        $insertStmt->execute([
            ':name'     => $name,
            ':email'    => $email,
            ':password' => $passwordHash,
            ':role'     => $role
        ]);

        $newId = (int)$db->lastInsertId();
        return ['success' => true, 'message' => 'Registration successful! You may now sign in.', 'user_id' => $newId];

    } catch (PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to create user account.'];
    }
}

/**
 * Safely log out the user and destroy the active session
 */
function logout_user(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

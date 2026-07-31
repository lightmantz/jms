<?php
// includes/auth.php - Authentication functions ONLY
// All core functions should be in functions.php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Get current logged in user
 */
function getCurrentUser() {
    if (!function_exists('isLoggedIn') || !isLoggedIn()) {
        return null;
    }
    
    // Get user from database
    $userId = $_SESSION['user_id'];
    $db = getDB();
    
    // Use is_active instead of status
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if ($user) {
        return $user;
    }
    
    return null;
}

/**
 * Authenticate user with email and password
 */
function authenticateUser($email, $password) {
    $db = getDB();
    
    // Use is_active instead of status, and password_hash instead of password
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        return $user;
    }
    
    return false;
}

/**
 * Login user - set session
 */
function loginUser($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'] ?? 'reader';
    $_SESSION['user_name'] = $user['full_name'] ?? 'User';
    
    // Update last login
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
}

/**
 * Logout user
 */
function logoutUser() {
    // Clear session
    $_SESSION = array();
    
    // Clear remember me cookie
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    session_destroy();
    // Start new session for messages
    session_start();
}

/**
 * Get dashboard URL based on user role
 */
function getDashboardUrl($user) {
    $role = $user['role'] ?? 'reader';
    $baseUrl = defined('SITE_URL') ? SITE_URL : '/jms/';
    
    switch ($role) {
        case 'admin':
            return $baseUrl . 'admin';
        case 'editor':
            return $baseUrl . 'editor';
        case 'reviewer':
            return $baseUrl . 'reviewer';
        case 'author':
            return $baseUrl . 'author';
        case 'publisher':
            return $baseUrl . 'publisher';
        case 'reader':
        default:
            return $baseUrl . '?page=home';
    }
}

/**
 * Check if user has a specific role
 */
function hasRole($user, $role) {
    if (!$user) return false;
    $userRole = $user['role'] ?? 'reader';
    return $userRole === $role;
}

/**
 * Check if user has any of the specified roles
 */
function hasAnyRole($user, $roles) {
    if (!$user) return false;
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    $userRole = $user['role'] ?? 'reader';
    return in_array($userRole, $roles);
}

/**
 * Check if user is admin
 */
function isAdmin($user = null) {
    if ($user === null) {
        $user = getCurrentUser();
    }
    return hasRole($user, 'admin');
}

/**
 * Check if user is editor
 */
function isEditor($user = null) {
    if ($user === null) {
        $user = getCurrentUser();
    }
    return hasRole($user, 'editor');
}

/**
 * Check if user is reviewer
 */
function isReviewer($user = null) {
    if ($user === null) {
        $user = getCurrentUser();
    }
    return hasRole($user, 'reviewer');
}

/**
 * Check if user is author
 */
function isAuthor($user = null) {
    if ($user === null) {
        $user = getCurrentUser();
    }
    return hasRole($user, 'author');
}

/**
 * Check if user is publisher
 */
function isPublisher($user = null) {
    if ($user === null) {
        $user = getCurrentUser();
    }
    return hasRole($user, 'publisher');
}

/**
 * Check if user is reader
 */
function isReader($user = null) {
    if ($user === null) {
        $user = getCurrentUser();
    }
    return hasRole($user, 'reader');
}

/**
 * Require a specific role - redirect if user doesn't have it
 */
function requireRole($roles) {
    if (!isLoggedIn()) {
        header('Location: ' . (defined('SITE_URL') ? SITE_URL : '/jms/') . '?page=login');
        exit;
    }
    
    $user = getCurrentUser();
    if (!$user) {
        header('Location: ' . (defined('SITE_URL') ? SITE_URL : '/jms/') . '?page=login');
        exit;
    }
    
    // Convert to array if string
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    
    // Check if user has any of the required roles
    if (!hasAnyRole($user, $roles)) {
        header('Location: ' . (defined('SITE_URL') ? SITE_URL : '/jms/') . '?page=403');
        exit;
    }
    
    return $user;
}

/**
 * Require admin role
 */
function requireAdmin() {
    return requireRole('admin');
}

/**
 * Require editor role (or admin)
 */
function requireEditor() {
    return requireRole(['admin', 'editor']);
}

/**
 * Require reviewer role (or admin, editor)
 */
function requireReviewer() {
    return requireRole(['admin', 'editor', 'reviewer']);
}

/**
 * Require author role
 */
function requireAuthor() {
    return requireRole(['admin', 'author']);
}

/**
 * Require publisher role
 */
function requirePublisher() {
    return requireRole(['admin', 'publisher']);
}

/**
 * Check if user has permission to access a resource
 */
function hasPermission($user, $permission) {
    if (!$user) return false;
    
    $role = $user['role'] ?? 'reader';
    
    // Define role permissions
    $permissions = [
        'admin' => ['*'], // Admin has all permissions
        'editor' => ['view_manuscripts', 'edit_manuscripts', 'assign_reviewers', 'make_decisions'],
        'reviewer' => ['view_assigned_manuscripts', 'submit_reviews'],
        'author' => ['submit_manuscripts', 'view_own_manuscripts', 'edit_own_manuscripts'],
        'publisher' => ['view_articles', 'manage_issues', 'publish_articles'],
        'reader' => ['view_articles']
    ];
    
    $userPermissions = $permissions[$role] ?? [];
    
    // Admin has all permissions
    if (in_array('*', $userPermissions)) {
        return true;
    }
    
    return in_array($permission, $userPermissions);
}
?>
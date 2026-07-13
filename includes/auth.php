<?php
// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isAuthenticated() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_username']);
}

// Redirect to login if not authenticated
function requireAuth() {
    if (!isAuthenticated()) {
        $_SESSION['login_redirect'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php');
        exit;
    }
}

// Redirect to dashboard if already authenticated
function requireGuest() {
    if (isAuthenticated()) {
        header('Location: dashboard.php');
        exit;
    }
}

// Get current admin details
function getCurrentAdmin() {
    if (isAuthenticated()) {
        return [
            'id' => $_SESSION['admin_id'],
            'username' => $_SESSION['admin_username'],
            'name' => $_SESSION['admin_name'],
            'email' => $_SESSION['admin_email'],
            'role' => $_SESSION['admin_role'] ?? 'admin'
        ];
    }
    return null;
}

// Check if current user is super admin
function isSuperAdmin() {
    if (isAuthenticated()) {
        return ($_SESSION['admin_role'] ?? 'admin') === 'super_admin';
    }
    return false;
}

// Require super admin role
function requireSuperAdmin() {
    requireAuth();
    if (!isSuperAdmin()) {
        header('Location: dashboard.php');
        exit;
    }
}

// Login function
function login($username, $password, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE (username = ? OR email = ?) AND is_active = 1 AND deleted_at IS NULL");
        $stmt->execute([$username, $username]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_role'] = $admin['role'] ?? 'admin'; // <-- FIXED: Store role
            $_SESSION['login_time'] = time();
            
            // Update last login
            $stmt = $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$admin['id']]);
            
            return true;
        }
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

// Logout function
function logout() {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header('Location: login.php');
    exit;
}

// Regenerate session ID periodically
function regenerateSession() {
    if (!isset($_SESSION['last_regeneration'])) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    } else if (time() - $_SESSION['last_regeneration'] > 300) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// CSRF Protection
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
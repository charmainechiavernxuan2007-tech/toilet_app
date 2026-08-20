<?php
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Attempt login. Returns true on success. */
function attemptLogin(string $username, string $password): bool {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user_id']    = (int)$user['id'];
    $_SESSION['username']   = $user['username'];
    $_SESSION['full_name']  = $user['full_name'];
    $_SESSION['role']       = $user['role'];
    $_SESSION['must_change_password'] = (bool)$user['must_change_password'];
    return true;
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

function currentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . basePathToRoot() . 'index.php');
        exit;
    }
    // Force password change before accessing anything else
    if (!empty($_SESSION['must_change_password']) &&
        basename($_SERVER['SCRIPT_NAME']) !== 'change_password.php' &&
        basename($_SERVER['SCRIPT_NAME']) !== 'logout.php') {
        header('Location: ' . basePathToRoot() . 'change_password.php?forced=1');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        http_response_code(403);
        die('Access denied: admin only.');
    }
}

function requireUser(): void {
    requireLogin();
    if (isAdmin()) {
        header('Location: ' . basePathToRoot() . 'admin/dashboard.php');
        exit;
    }
}

/** Works out relative path back to project root depending on current folder depth. */
function basePathToRoot(): string {
    $script = $_SERVER['SCRIPT_NAME'];
    if (strpos($script, '/admin/') !== false || strpos($script, '/user/') !== false) {
        return '../';
    }
    return '';
}

function logout(): void {
    $_SESSION = [];
    session_destroy();
}

/** Simple CSRF token helpers */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(400);
        die('Invalid or expired form submission (CSRF check failed). Please go back and try again.');
    }
}

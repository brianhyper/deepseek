<?php
require __DIR__ . '/includes/db_config.php';
require __DIR__ . '/includes/auth.php';

// Destroy all session data
$_SESSION = array();

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Log the logout
logActivity('Admin logout', ['admin_id' => $_SESSION['admin_id'] ?? null]);

// Redirect to login page
header("Location: login.php");
exit;
?>
<?php
function isLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function authenticateAdmin($email, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND is_admin = TRUE');
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    
    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        $_SESSION['admin_email'] = $admin['email'];
        return true;
    }
    
    return false;
}

function requireAdmin() {
    if (!isLoggedIn()) {
        $_SESSION['login_redirect'] = $_SERVER['REQUEST_URI'];
        header("Location: ../login.php");
        exit;
    }
}
?>
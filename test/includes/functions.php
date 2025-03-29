<?php
/**
 * HyperBrains Admin - Common Functions
 * Includes utility functions used across the admin panel
 */

/**
 * Escape HTML output to prevent XSS
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect with optional status code
 */
function redirect($url, $statusCode = 303) {
    header('Location: ' . $url, true, $statusCode);
    exit;
}

/**
 * Generate CSRF token and store in session
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Format date for display
 */
function formatDate($dateString, $format = 'M j, Y g:i A') {
    $date = new DateTime($dateString);
    return $date->format($format);
}

/**
 * Get pagination parameters
 */
function getPaginationParams($currentPage, $perPage = 10) {
    $currentPage = max(1, (int)$currentPage);
    $offset = ($currentPage - 1) * $perPage;
    return [
        'page' => $currentPage,
        'per_page' => $perPage,
        'offset' => $offset,
        'limit' => $perPage
    ];
}

/**
 * Get pagination links HTML
 */
function getPaginationLinks($totalItems, $currentPage, $perPage = 10, $baseUrl = '') {
    $totalPages = ceil($totalItems / $perPage);
    $html = '<div class="pagination">';
    
    // Previous link
    if ($currentPage > 1) {
        $html .= '<a href="' . $baseUrl . '?page=' . ($currentPage - 1) . '" class="page-link">&laquo; Prev</a>';
    }
    
    // Page links
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $active = $i == $currentPage ? 'active' : '';
        $html .= '<a href="' . $baseUrl . '?page=' . $i . '" class="page-link ' . $active . '">' . $i . '</a>';
    }
    
    // Next link
    if ($currentPage < $totalPages) {
        $html .= '<a href="' . $baseUrl . '?page=' . ($currentPage + 1) . '" class="page-link">Next &raquo;</a>';
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * Get user by ID
 */
function getUserById($userId) {
    global $pdo;
    
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

/**
 * Get recent chat messages
 */
function getRecentChats($limit = 10) {
    global $pdo;
    
    $stmt = $pdo->prepare('
        SELECT c.*, u.name as user_name, u.email as user_email 
        FROM chats c
        LEFT JOIN users u ON c.user_id = u.id
        ORDER BY c.timestamp DESC
        LIMIT ?
    ');
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * Get user count statistics
 */
function getUserStats() {
    global $pdo;
    
    return [
        'total' => $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'active' => $pdo->query('SELECT COUNT(*) FROM users WHERE is_banned = FALSE')->fetchColumn(),
        'banned' => $pdo->query('SELECT COUNT(*) FROM users WHERE is_banned = TRUE')->fetchColumn(),
        'admins' => $pdo->query('SELECT COUNT(*) FROM users WHERE is_admin = TRUE')->fetchColumn()
    ];
}

/**
 * Log admin activity
 */
function logActivity($action, $details = null) {
    global $pdo;
    
    $stmt = $pdo->prepare('
        INSERT INTO admin_logs 
        (admin_id, action, details, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?)
    ');
    
    $stmt->execute([
        $_SESSION['admin_id'],
        $action,
        $details ? json_encode($details) : null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    ]);
}

/**
 * Get system alerts/notifications
 */
function getSystemAlerts() {
    global $pdo;
    
    // Example: Check for failed login attempts
    $failedLogins = $pdo->query('
        SELECT COUNT(*) FROM login_attempts 
        WHERE success = FALSE AND created_at > NOW() - INTERVAL 1 HOUR
    ')->fetchColumn();
    
    $alerts = [];
    
    if ($failedLogins > 5) {
        $alerts[] = [
            'type' => 'warning',
            'message' => "$failedLogins failed login attempts in the last hour"
        ];
    }
    
    // Add more alert checks as needed
    
    return $alerts;
}

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate email address
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Generate random password
 */
function generatePassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    
    return $password;
}

/**
 * Get client IP address
 */
function getClientIp() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Format bytes to human-readable size
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Send email with template
 */
function sendEmail($to, $subject, $template, $data = []) {
    $headers = "From: HyperBrains Admin <noreply@hyperbrains.io>\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    // Render template with data
    ob_start();
    extract($data);
    include __DIR__ . "/../emails/$template.php";
    $message = ob_get_clean();
    
    return mail($to, $subject, $message, $headers);
}
<?php
// Security check - prevent direct access
defined('ABSPATH') or exit('Direct access denied');

// Start output buffering
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'HyperBrains Admin'; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" href="../../assets/images/favicon.ico">
    
    <!-- CSS -->
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- JavaScript -->
    <script src="../../assets/js/admin.js" defer></script>
</head>
<body>
    <header class="admin-header">
        <div class="header-container">
            <!-- Logo/Brand -->
            <div class="brand">
                <a href="dashboard.php">
                    <i class="fas fa-brain"></i>
                    <span>HyperBrains</span>
                </a>
            </div>
            
            <!-- User Menu -->
            <div class="user-menu">
                <div class="dropdown">
                    <button class="dropdown-toggle">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></span>
                        <i class="fas fa-caret-down"></i>
                    </button>
                    <div class="dropdown-content">
                        <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                        <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="admin-container">
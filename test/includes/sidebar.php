<?php
// Security check
defined('ABSPATH') or exit('Direct access denied');

// Get current page
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar">
    <nav class="sidebar-nav">
        <ul>
            <li class="<?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                <a href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li class="<?php echo $currentPage === 'users.php' ? 'active' : ''; ?>">
                <a href="users.php">
                    <i class="fas fa-users"></i>
                    <span>User Management</span>
                </a>
            </li>
            
            <li class="<?php echo $currentPage === 'chats.php' ? 'active' : ''; ?>">
                <a href="chats.php">
                    <i class="fas fa-comments"></i>
                    <span>Chat Moderation</span>
                </a>
            </li>
            
            <li class="<?php echo $currentPage === 'contacts.php' ? 'active' : ''; ?>">
                <a href="contacts.php">
                    <i class="fas fa-envelope"></i>
                    <span>Contact Forms</span>
                </a>
            </li>
            
            <li class="<?php echo $currentPage === 'settings.php' ? 'active' : ''; ?>">
                <a href="settings.php">
                    <i class="fas fa-cog"></i>
                    <span>System Settings</span>
                </a>
            </li>
            
            <li class="divider"></li>
            
            <li>
                <a href="documentation.php" target="_blank">
                    <i class="fas fa-book"></i>
                    <span>Documentation</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <!-- Collapse button for mobile -->
    <div class="sidebar-collapse">
        <button id="sidebarToggle">
            <i class="fas fa-chevron-left"></i>
        </button>
    </div>
</aside>

<main class="admin-content">
<?php
// Prevent direct access

define('ABSPATH', dirname(__FILE__));
if (!defined('ABSPATH')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Direct access denied');
}

require __DIR__ . '/../includes/db_config.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/functions.php';
requireAdmin();

// Get statistics
$stats = [
    'users' => $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = FALSE")->fetchColumn(),
    'active_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE is_banned = FALSE AND is_admin = FALSE")->fetchColumn(),
    'new_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE created_at > NOW() - INTERVAL 7 DAY")->fetchColumn(),
    'chats' => $pdo->query("SELECT COUNT(*) FROM chats")->fetchColumn(),
    'recent_chats' => $pdo->query("SELECT COUNT(*) FROM chats WHERE timestamp > NOW() - INTERVAL 24 HOUR")->fetchColumn(),
    'contacts' => $pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn(),
    'new_contacts' => $pdo->query("SELECT COUNT(*) FROM contacts WHERE created_at > NOW() - INTERVAL 7 DAY")->fetchColumn(),
    'projects' => $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn(),
    'active_projects' => $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'active'")->fetchColumn()
];

// Get recent activities
$recentActivities = $pdo->query("
    SELECT * FROM admin_logs 
    ORDER BY created_at DESC 
    LIMIT 10
")->fetchAll();

// Get recent chats
$recentChats = $pdo->query("
    SELECT c.*, u.name as user_name 
    FROM chats c
    LEFT JOIN users u ON c.user_id = u.id
    ORDER BY c.timestamp DESC
    LIMIT 5
")->fetchAll();

// Get recent contacts
$recentContacts = $pdo->query("
    SELECT * FROM contacts 
    ORDER BY created_at DESC 
    LIMIT 5
")->fetchAll();

// Get upcoming project deadlines
$upcomingDeadlines = $pdo->query("
    SELECT p.*, u.name as user_name 
    FROM projects p
    LEFT JOIN users u ON p.user_id = u.id
    WHERE p.deadline BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
    ORDER BY p.deadline ASC
    LIMIT 5
")->fetchAll();

// Get system alerts
$systemAlerts = getSystemAlerts();

// Handle CSV export
if (isset($_GET['export'])) {
    $type = $_GET['export'];
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="'.$type.'_export_'.date('Y-m-d').'.csv"');
    
    $output = fopen('php://output', 'w');
    
    switch($type) {
        case 'users':
            $data = $pdo->query("SELECT id, name, email, created_at FROM users")->fetchAll();
            fputcsv($output, ['ID', 'Name', 'Email', 'Joined']);
            break;
        case 'chats':
            $data = $pdo->query("SELECT c.id, u.name, c.message, c.timestamp FROM chats c LEFT JOIN users u ON c.user_id = u.id")->fetchAll();
            fputcsv($output, ['ID', 'User', 'Message', 'Timestamp']);
            break;
        case 'projects':
            $data = $pdo->query("SELECT p.id, p.title, u.name as owner, p.deadline, p.status FROM projects p LEFT JOIN users u ON p.user_id = u.id")->fetchAll();
            fputcsv($output, ['ID', 'Title', 'Owner', 'Deadline', 'Status']);
            break;
    }
    
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | HyperBrains</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="page-header">
                <h1 class="page-title"><i class="fas fa-tachometer-alt"></i> Dashboard Overview</h1>
                <div class="theme-controls">
                    <button class="theme-toggle" id="themeToggle">
                        <i class="fas fa-moon"></i> Toggle Dark Mode
                    </button>
                </div>
            </div>
            
            <?php if (!empty($systemAlerts)): ?>
                <div class="alerts-container">
                    <?php foreach ($systemAlerts as $alert): ?>
                        <div class="alert alert-<?php echo e($alert['type']); ?>">
                            <i class="fas fa-<?php echo $alert['type'] === 'warning' ? 'exclamation-triangle' : 'info-circle'; ?>"></i>
                            <?php echo e($alert['message']); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(0, 204, 255, 0.1);">
                        <i class="fas fa-users" style="color: #0cf;"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Users</h3>
                        <span class="stat-value"><?php echo e($stats['users']); ?></span>
                        <div class="stat-actions">
                            <a href="?export=users" class="export-btn">
                                <i class="fas fa-download"></i> Export
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(106, 0, 255, 0.1);">
                        <i class="fas fa-comments" style="color: #6a00ff;"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Chats</h3>
                        <span class="stat-value"><?php echo e($stats['chats']); ?></span>
                        <div class="stat-actions">
                            <a href="?export=chats" class="export-btn">
                                <i class="fas fa-download"></i> Export
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(255, 0, 170, 0.1);">
                        <i class="fas fa-envelope" style="color: #ff00aa;"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Contact Forms</h3>
                        <span class="stat-value"><?php echo e($stats['contacts']); ?></span>
                        <span class="stat-change <?php echo $stats['new_contacts'] > 0 ? 'positive' : ''; ?>">
                            <i class="fas fa-arrow-<?php echo $stats['new_contacts'] > 0 ? 'up' : 'down'; ?>"></i>
                            <?php echo e($stats['new_contacts']); ?> new this week
                        </span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(0, 200, 83, 0.1);">
                        <i class="fas fa-project-diagram" style="color: #00c853;"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Active Projects</h3>
                        <span class="stat-value"><?php echo e($stats['active_projects']); ?></span>
                        <div class="stat-actions">
                            <a href="?export=projects" class="export-btn">
                                <i class="fas fa-download"></i> Export
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Project Calendar Section -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-calendar-alt"></i> Project Deadlines</h3>
                    <div id="projectCalendar"></div>
                </div>
                <div class="card-body">
                    <?php foreach ($upcomingDeadlines as $project): ?>
                    <div class="deadline-item">
                        <h4><?php echo e($project['title']); ?></h4>
                        <p>
                            <i class="fas fa-user"></i> <?php echo e($project['user_name']); ?>
                            | <i class="fas fa-clock"></i> Due: <?php echo formatDate($project['deadline'], 'M j, Y'); ?>
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Recent Activity Sections -->
            <div class="activity-row">
                <!-- Recent Chats -->
                <div class="activity-card">
                    <div class="activity-header">
                        <h3><i class="fas fa-comments"></i> Recent Chats</h3>
                        <a href="chats.php" class="btn btn-sm">View All</a>
                    </div>
                    <div class="activity-list">
                        <?php foreach ($recentChats as $chat): ?>
                        <div class="activity-item">
                            <div class="activity-avatar">
                                <?php if ($chat['user_name']): ?>
                                    <?php echo strtoupper(substr($chat['user_name'], 0, 1)); ?>
                                <?php else: ?>
                                    <i class="fas fa-user-secret"></i>
                                <?php endif; ?>
                            </div>
                            <div class="activity-content">
                                <p class="activity-message"><?php echo e(mb_strimwidth($chat['message'], 0, 60, '...')); ?></p>
                                <small class="activity-meta">
                                    <?php if ($chat['user_name']): ?>
                                        <?php echo e($chat['user_name']); ?> • 
                                    <?php endif; ?>
                                    <?php echo formatDate($chat['timestamp'], 'M j, H:i'); ?>
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Recent Activities -->
                <div class="activity-card">
                    <div class="activity-header">
                        <h3><i class="fas fa-history"></i> Recent Activity</h3>
                    </div>
                    <div class="activity-list">
                        <?php foreach ($recentActivities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-avatar" style="background: rgba(0, 204, 255, 0.1); color: #0cf;">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <div class="activity-content">
                                <p class="activity-message">
                                    <strong><?php echo e($activity['action']); ?></strong>
                                    <?php if ($activity['details']): ?>
                                        <br><small><?php echo e(json_decode($activity['details'])); ?></small>
                                    <?php endif; ?>
                                </p>
                                <small class="activity-meta">
                                    <?php echo formatDate($activity['created_at'], 'M j, H:i'); ?>
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
    
    <script>
        // Theme Toggle
        const themeToggle = document.getElementById('themeToggle');
        themeToggle.addEventListener('click', () => {
            const html = document.documentElement;
            const isDark = html.getAttribute('data-theme') === 'dark';
            html.setAttribute('data-theme', isDark ? 'light' : 'dark');
            themeToggle.innerHTML = isDark 
                ? '<i class="fas fa-moon"></i> Dark Mode' 
                : '<i class="fas fa-sun"></i> Light Mode';
            localStorage.setItem('theme', isDark ? 'light' : 'dark');
        });

        // Initialize with saved theme
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
            themeToggle.innerHTML = '<i class="fas fa-sun"></i> Light Mode';
        }

        // Initialize Project Calendar
        flatpickr("#projectCalendar", {
            inline: true,
            mode: "multiple",
            dateFormat: "Y-m-d",
            defaultDate: [
                <?php 
                $dates = array_map(function($project) {
                    return "'" . date('Y-m-d', strtotime($project['deadline'])) . "'";
                }, $upcomingDeadlines);
                echo implode(',', $dates);
                ?>
            ]
        });

        // Initialize Charts
        new Chart(document.getElementById('userGrowthChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'New Users',
                    data: [15, 22, 18, 30, 42, 38],
                    borderColor: '#6a00ff',
                    backgroundColor: 'rgba(106, 0, 255, 0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });

        new Chart(document.getElementById('chatActivityChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Chats',
                    data: [45, 78, 56, 89, 76, 42, 31],
                    backgroundColor: 'rgba(255, 0, 170, 0.7)',
                    borderColor: 'rgba(255, 0, 170, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    </script>
</body>
</html>
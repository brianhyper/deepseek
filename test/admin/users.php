<?php
require __DIR__ . '/../../includes/db_config.php';
require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/functions.php';
requireAdmin();

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_ban'])) {
        if (!validateCsrfToken($_POST['csrf_token'])) {
            die('Invalid CSRF token');
        }
        
        $userId = (int)$_POST['user_id'];
        $isBanned = $_POST['is_banned'] === '1';
        
        $stmt = $pdo->prepare('UPDATE users SET is_banned = ? WHERE id = ?');
        $stmt->execute([!$isBanned, $userId]);
        
        logActivity('User ' . ($isBanned ? 'unbanned' : 'banned'), ['user_id' => $userId]);
        $_SESSION['message'] = 'User ' . ($isBanned ? 'unbanned' : 'banned') . ' successfully';
        redirect('users.php');
    }
}

// Get pagination parameters
$currentPage = $_GET['page'] ?? 1;
$perPage = 10;
$pagination = getPaginationParams($currentPage, $perPage);

// Search and filter
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';

// Build query
$query = 'SELECT * FROM users WHERE is_admin = FALSE';
$params = [];

if (!empty($search)) {
    $query .= ' AND (name LIKE ? OR email LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status === 'active') {
    $query .= ' AND is_banned = FALSE';
} elseif ($status === 'banned') {
    $query .= ' AND is_banned = TRUE';
}

// Get total count
$totalQuery = "SELECT COUNT(*) FROM ($query) AS total";
$totalStmt = $pdo->prepare($totalQuery);
$totalStmt->execute($params);
$totalUsers = $totalStmt->fetchColumn();

// Apply pagination
$query .= " LIMIT {$pagination['offset']}, {$pagination['per_page']}";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | HyperBrains Admin</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="admin-card">
                <div class="card-header">
                    <h2><i class="fas fa-users"></i> User Management</h2>
                    
                    <form method="GET" class="search-box">
                        <input type="text" name="search" class="search-input" placeholder="Search users..." 
                               value="<?php echo e($search); ?>">
                        <select name="status" class="form-control">
                            <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Users</option>
                            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active Only</option>
                            <option value="banned" <?php echo $status === 'banned' ? 'selected' : ''; ?>>Banned Only</option>
                        </select>
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </form>
                </div>
                
                <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success">
                    <?php echo e($_SESSION['message']); unset($_SESSION['message']); ?>
                </div>
                <?php endif; ?>
                
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Joined</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo e($user['id']); ?></td>
                                <td><?php echo e($user['name']); ?></td>
                                <td><?php echo e($user['email']); ?></td>
                                <td><?php echo formatDate($user['created_at']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $user['is_banned'] ? 'banned' : 'active'; ?>">
                                        <?php echo $user['is_banned'] ? 'Banned' : 'Active'; ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Are you sure?')">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                        <input type="hidden" name="user_id" value="<?php echo e($user['id']); ?>">
                                        <input type="hidden" name="is_banned" value="<?php echo e($user['is_banned']); ?>">
                                        <button type="submit" name="toggle_ban" class="btn btn-sm <?php echo $user['is_banned'] ? 'btn-success' : 'btn-danger'; ?>">
                                            <?php echo $user['is_banned'] ? 'Unban' : 'Ban'; ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php echo getPaginationLinks($totalUsers, $currentPage, $perPage, 'users.php?search=' . urlencode($search) . '&status=' . $status); ?>
            </div>
        </main>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
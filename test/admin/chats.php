<?php
require __DIR__ . '/../../includes/db_config.php';
require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/functions.php';
requireAdmin();

// Handle chat actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_chat'])) {
        if (!validateCsrfToken($_POST['csrf_token'])) {
            die('Invalid CSRF token');
        }
        
        $chatId = (int)$_POST['chat_id'];
        $stmt = $pdo->prepare('DELETE FROM chats WHERE id = ?');
        $stmt->execute([$chatId]);
        
        logActivity('Chat deleted', ['chat_id' => $chatId]);
        $_SESSION['message'] = 'Chat deleted successfully';
        redirect('chats.php');
    }
}

// Get pagination parameters
$currentPage = $_GET['page'] ?? 1;
$perPage = 15;
$pagination = getPaginationParams($currentPage, $perPage);

// Search filter
$search = $_GET['search'] ?? '';

// Build query
$query = '
    SELECT c.*, u.name as user_name, u.email as user_email 
    FROM chats c
    LEFT JOIN users u ON c.user_id = u.id
';

$params = [];

if (!empty($search)) {
    $query .= ' WHERE c.message LIKE ? OR c.response LIKE ? OR u.name LIKE ? OR u.email LIKE ?';
    $params = array_fill(0, 4, "%$search%");
}

// Get total count
$totalQuery = "SELECT COUNT(*) FROM ($query) AS total";
$totalStmt = $pdo->prepare($totalQuery);
$totalStmt->execute($params);
$totalChats = $totalStmt->fetchColumn();

// Apply pagination and ordering
$query .= " ORDER BY c.timestamp DESC LIMIT {$pagination['offset']}, {$pagination['per_page']}";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$chats = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Moderation | HyperBrains Admin</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="admin-card">
                <div class="card-header">
                    <h2><i class="fas fa-comments"></i> Chat Moderation</h2>
                    
                    <form method="GET" class="search-box">
                        <input type="text" name="search" class="search-input" placeholder="Search chats..." 
                               value="<?php echo e($search); ?>">
                        <button type="submit" class="btn btn-primary">Search</button>
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
                                <th>User</th>
                                <th>Message</th>
                                <th>Response</th>
                                <th>Timestamp</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($chats as $chat): ?>
                            <tr>
                                <td><?php echo e($chat['id']); ?></td>
                                <td>
                                    <?php if ($chat['user_email']): ?>
                                        <?php echo e($chat['user_name'] ?? 'Guest'); ?>
                                        <small><?php echo e($chat['user_email']); ?></small>
                                    <?php else: ?>
                                        Guest
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e(mb_strimwidth($chat['message'], 0, 50, '...')); ?></td>
                                <td><?php echo e(mb_strimwidth($chat['response'] ?? '', 0, 50, '...')); ?></td>
                                <td><?php echo formatDate($chat['timestamp']); ?></td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Delete this chat?')">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                        <input type="hidden" name="chat_id" value="<?php echo e($chat['id']); ?>">
                                        <button type="submit" name="delete_chat" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php echo getPaginationLinks($totalChats, $currentPage, $perPage, 'chats.php?search=' . urlencode($search)); ?>
                
                <div class="card-footer">
                    <form method="POST" action="export.php">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="export_type" value="chats">
                        <button type="submit" name="export_csv" class="btn btn-primary">
                            <i class="fas fa-download"></i> Export to CSV
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
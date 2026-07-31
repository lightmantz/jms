<?php
// modules/pages/notifications.php - Notifications Page
require_once __DIR__ . '/../../includes/init.php';

if (!isLoggedIn()) {
    header('Location: ' . SITE_URL . '?page=login');
    exit;
}

$currentUser = getCurrentUser();
$db = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'mark_all_read') {
            markAllNotificationsAsRead($currentUser['id']);
            header('Location: ' . SITE_URL . '?page=notifications');
            exit;
        } elseif ($_POST['action'] === 'mark_read' && isset($_POST['notification_id'])) {
            markNotificationAsRead($_POST['notification_id'], $currentUser['id']);
            header('Location: ' . SITE_URL . '?page=notifications');
            exit;
        } elseif ($_POST['action'] === 'delete' && isset($_POST['notification_id'])) {
            deleteNotification($_POST['notification_id'], $currentUser['id']);
            header('Location: ' . SITE_URL . '?page=notifications');
            exit;
        }
    }
}

// Get notifications
$page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$notifications = getNotifications($currentUser['id'], $perPage, $offset);
$totalUnread = getUnreadNotifications($currentUser['id']);

// Get total count for pagination
$stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ?");
$stmt->execute([$currentUser['id']]);
$total = $stmt->fetch()['count'];
$totalPages = ceil($total / $perPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>css/style.css">
</head>
<body class="antialiased text-gray-700 font-['Inter']" style="background: #f6f9fc;">
    <?php include INCLUDES_PATH . 'header.php'; ?>
    
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-8 md:p-10">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-[#0b2b3f] flex items-center gap-3">
                        <i class="fas fa-bell text-indigo-500"></i> Notifications
                    </h1>
                    <p class="text-gray-500 text-sm mt-1">Stay updated with your latest notifications</p>
                </div>
                <div class="flex gap-3">
                    <?php if ($totalUnread > 0): ?>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="mark_all_read">
                            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition text-sm">
                                <i class="fas fa-check-double mr-1"></i> Mark all read
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <div class="h-1 w-20 bg-indigo-200 rounded-full mb-6"></div>
            
            <!-- Stats -->
            <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="bg-gray-50 rounded-xl p-4 text-center border border-gray-200">
                    <p class="text-2xl font-bold text-gray-700"><?= $total ?></p>
                    <p class="text-xs text-gray-600">Total</p>
                </div>
                <div class="bg-indigo-50 rounded-xl p-4 text-center border border-indigo-200">
                    <p class="text-2xl font-bold text-indigo-700"><?= $totalUnread ?></p>
                    <p class="text-xs text-indigo-600">Unread</p>
                </div>
                <div class="bg-green-50 rounded-xl p-4 text-center border border-green-200">
                    <p class="text-2xl font-bold text-green-700"><?= $total - $totalUnread ?></p>
                    <p class="text-xs text-green-600">Read</p>
                </div>
            </div>
            
            <?php if (empty($notifications)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-bell-slash text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-600">No notifications</h3>
                    <p class="text-gray-500 mt-1">You're all caught up!</p>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($notifications as $notif): ?>
                        <div class="flex items-start gap-4 p-4 rounded-xl transition <?= $notif['is_read'] ? 'bg-white hover:bg-gray-50' : 'bg-indigo-50 border border-indigo-100' ?>">
                            <div class="flex-shrink-0 mt-1">
                                <?php 
                                $iconMap = [
                                    'new_user_registration' => 'fa-user-plus',
                                    'new_submission' => 'fa-file-upload',
                                    'manuscript_status_update' => 'fa-edit',
                                    'manuscript_published' => 'fa-check-double',
                                    'manuscript_accepted' => 'fa-check-circle',
                                    'reviewer_assignment' => 'fa-user-tie',
                                    'reviewer_assigned' => 'fa-user-check',
                                    'review_completed' => 'fa-star',
                                    'review_submitted' => 'fa-file-alt',
                                    'new_issue' => 'fa-book',
                                    'article_published' => 'fa-newspaper',
                                    'manuscript_accepted_admin' => 'fa-check-circle'
                                ];
                                $icon = $iconMap[$notif['type']] ?? 'fa-bell';
                                ?>
                                <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">
                                    <i class="fas <?= $icon ?>"></i>
                                </div>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="font-semibold text-[#0b2b3f]"><?= htmlspecialchars($notif['title']) ?></p>
                                        <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($notif['message']) ?></p>
                                        <p class="text-xs text-gray-400 mt-1"><?= timeAgo($notif['created_at']) ?></p>
                                    </div>
                                    <div class="flex gap-1 flex-shrink-0 ml-4">
                                        <?php if (!$notif['is_read']): ?>
                                            <form method="POST" action="">
                                                <input type="hidden" name="action" value="mark_read">
                                                <input type="hidden" name="notification_id" value="<?= $notif['id'] ?>">
                                                <button type="submit" class="text-indigo-600 hover:text-indigo-800 text-sm" title="Mark as read">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($notif['link']): ?>
                                            <a href="<?= $notif['link'] ?>" class="text-blue-600 hover:text-blue-800 text-sm" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        <?php endif; ?>
                                        <form method="POST" action="">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="notification_id" value="<?= $notif['id'] ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm" title="Delete" onclick="return confirm('Delete this notification?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="flex justify-center gap-2 mt-8">
                        <?php if ($page > 1): ?>
                            <a href="<?= SITE_URL ?>?page=notifications&page_num=<?= $page - 1 ?>" 
                               class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition text-sm">
                                Previous
                            </a>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="<?= SITE_URL ?>?page=notifications&page_num=<?= $i ?>" 
                               class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition text-sm <?= $i == $page ? 'bg-[#0b2b3f] text-white border-[#0b2b3f]' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <a href="<?= SITE_URL ?>?page=notifications&page_num=<?= $page + 1 ?>" 
                               class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition text-sm">
                                Next
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include INCLUDES_PATH . 'footer.php'; ?>
</body>
</html>
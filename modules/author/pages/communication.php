<?php
// modules/author/pages/communication.php - Communication History
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

$db = getDB();
$currentUser = getCurrentUser();

// Get user's manuscripts
$stmt = $db->prepare("
    SELECT id, title FROM manuscripts 
    WHERE corresponding_author_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$currentUser['id']]);
$manuscripts = $stmt->fetchAll();

// Get notifications for the user
$notifications = getNotifications($currentUser['id'], 50);

// Mark all as read
if (isset($_GET['mark_read']) && $_GET['mark_read'] == 'all') {
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$currentUser['id']]);
    header('Location: /jms/author?action=communication');
    exit;
}
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Communication History</h2>
            <p class="text-gray-500 text-sm mt-1">View all communications and notifications</p>
        </div>
        <div class="flex gap-3">
            <a href="/jms/author?action=communication&mark_read=all" class="text-indigo-600 hover:text-indigo-800 text-sm">
                <i class="fas fa-check-double mr-1"></i> Mark All Read
            </a>
            <a href="/jms/author" class="text-gray-500 hover:text-gray-700 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>
    </div>
    <div class="h-1 w-20 bg-indigo-200 rounded-full"></div>

    <?php if (empty($notifications)): ?>
        <div class="text-center py-12">
            <i class="fas fa-envelope-open text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No communications found.</p>
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($notifications as $notification): ?>
            <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition <?= $notification['is_read'] ? '' : 'border-indigo-300 bg-indigo-50/30' ?>">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 mt-1">
                        <?php if (!$notification['is_read']): ?>
                            <span class="w-2 h-2 bg-indigo-500 rounded-full inline-block"></span>
                        <?php else: ?>
                            <span class="w-2 h-2 bg-gray-300 rounded-full inline-block"></span>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <h4 class="font-semibold text-[#0b2b3f]"><?= htmlspecialchars($notification['title']) ?></h4>
                            <span class="text-xs text-gray-400"><?= timeAgo($notification['created_at']) ?></span>
                        </div>
                        <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($notification['message']) ?></p>
                        <?php if ($notification['link']): ?>
                            <a href="<?= $notification['link'] ?>" class="text-sm text-indigo-600 hover:text-indigo-800 mt-2 inline-block">
                                <i class="fas fa-arrow-right mr-1"></i> View Details
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-4 text-sm text-gray-400">
            Showing <?= count($notifications) ?> communications
        </div>
    <?php endif; ?>
</div>
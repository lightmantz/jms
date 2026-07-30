<?php
// modules/reviewer/pages/reminders.php - Deadline Reminders
if (!defined('SITE_URL')) {
    require_once dirname(dirname(dirname(__DIR__))) . '/includes/init.php';
}

$db = getDB();
$currentUser = getCurrentUser();

// Get upcoming deadlines
$stmt = $db->prepare("
    SELECT 
        r.*,
        m.title as manuscript_title
    FROM reviewer_assignments r
    JOIN manuscripts m ON r.manuscript_id = m.id
    WHERE r.reviewer_id = ? 
    AND r.status IN ('invited', 'accepted')
    AND r.due_date IS NOT NULL
    ORDER BY r.due_date ASC
");
$stmt->execute([$currentUser['id']]);
$reminders = $stmt->fetchAll();
?>
<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
    <h2 class="text-2xl font-bold text-[#0b2b3f] mb-6">Deadline Reminders</h2>
    
    <?php if (empty($reminders)): ?>
        <div class="text-center py-12">
            <i class="fas fa-bell-slash text-6xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No upcoming deadlines.</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($reminders as $reminder): 
                $daysUntil = floor((strtotime($reminder['due_date']) - time()) / 86400);
                $isOverdue = $daysUntil < 0;
                $isUrgent = $daysUntil >= 0 && $daysUntil <= 7;
            ?>
            <div class="flex items-center justify-between p-4 rounded-lg 
                <?= $isOverdue ? 'bg-red-50 border border-red-200' : 
                   ($isUrgent ? 'bg-yellow-50 border border-yellow-200' : 'bg-gray-50 border border-gray-200') ?>">
                <div class="flex-1">
                    <p class="font-medium text-[#0b2b3f]"><?= htmlspecialchars(substr($reminder['manuscript_title'], 0, 60)) ?>...</p>
                    <div class="flex items-center gap-3 text-sm mt-1">
                        <span class="text-gray-500">Due: <?= formatDate($reminder['due_date'], 'M d, Y') ?></span>
                        <?php if ($isOverdue): ?>
                            <span class="text-red-600 font-semibold">OVERDUE</span>
                        <?php elseif ($isUrgent): ?>
                            <span class="text-yellow-600 font-semibold"><?= $daysUntil ?> days remaining</span>
                        <?php else: ?>
                            <span class="text-gray-500"><?= $daysUntil ?> days remaining</span>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="/jms/reviewer?action=review-forms&id=<?= $reminder['manuscript_id'] ?>" 
                   class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm">
                    Start Review
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
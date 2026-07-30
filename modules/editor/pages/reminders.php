<?php
// modules/reviewer/pages/reminders.php - Deadline Reminders
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

$db = getDB();
$currentUser = getCurrentUser();

// Get reviewer's accepted reviews
$stmt = $db->prepare("
    SELECT r.*, m.title as manuscript_title,
           u.full_name as author_name,
           DATEDIFF(r.due_date, CURDATE()) as days_remaining
    FROM reviews r
    JOIN manuscripts m ON r.manuscript_id = m.id
    LEFT JOIN users u ON m.corresponding_author_id = u.id
    WHERE r.reviewer_id = ? AND r.status = 'accepted'
    ORDER BY r.due_date ASC
");
$stmt->execute([$currentUser['id']]);
$activeReviews = $stmt->fetchAll();

// Get completed reviews
$stmt = $db->prepare("
    SELECT r.*, m.title as manuscript_title,
           u.full_name as author_name
    FROM reviews r
    JOIN manuscripts m ON r.manuscript_id = m.id
    LEFT JOIN users u ON m.corresponding_author_id = u.id
    WHERE r.reviewer_id = ? AND r.status = 'completed'
    ORDER BY r.completed_date DESC
    LIMIT 5
");
$stmt->execute([$currentUser['id']]);
$completedReviews = $stmt->fetchAll();

// Count overdue reviews
$overdueCount = count(array_filter($activeReviews, function($r) {
    return $r['due_date'] && $r['due_date'] < date('Y-m-d');
}));
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Deadline Reminders</h2>
            <p class="text-gray-500 text-sm mt-1">Track your review deadlines</p>
        </div>
        <a href="/jms/reviewer" class="text-indigo-600 hover:text-indigo-800 text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back
        </a>
    </div>
    <div class="h-1 w-20 bg-indigo-200 rounded-full"></div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-blue-50 rounded-xl p-4 text-center border border-blue-200">
            <p class="text-2xl font-bold text-blue-700"><?= count($activeReviews) ?></p>
            <p class="text-xs text-blue-600">Active Reviews</p>
        </div>
        <div class="bg-red-50 rounded-xl p-4 text-center border border-red-200">
            <p class="text-2xl font-bold text-red-700"><?= $overdueCount ?></p>
            <p class="text-xs text-red-600">Overdue</p>
        </div>
        <div class="bg-green-50 rounded-xl p-4 text-center border border-green-200">
            <p class="text-2xl font-bold text-green-700"><?= count($completedReviews) ?></p>
            <p class="text-xs text-green-600">Completed (Recent)</p>
        </div>
        <div class="bg-yellow-50 rounded-xl p-4 text-center border border-yellow-200">
            <p class="text-2xl font-bold text-yellow-700">
                <?php 
                $avgDays = 0;
                if (!empty($activeReviews)) {
                    $totalDays = array_sum(array_filter(array_column($activeReviews, 'days_remaining'), function($d) { return $d !== null; }));
                    $avgDays = round($totalDays / count($activeReviews));
                }
                echo $avgDays;
                ?>
            </p>
            <p class="text-xs text-yellow-600">Avg Days Remaining</p>
        </div>
    </div>

    <?php if (empty($activeReviews) && empty($completedReviews)): ?>
        <div class="text-center py-12">
            <i class="fas fa-clock text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No review reminders found.</p>
        </div>
    <?php else: ?>
        <!-- Active Reviews -->
        <?php if (!empty($activeReviews)): ?>
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">Active Reviews</h3>
            <div class="space-y-3">
                <?php foreach ($activeReviews as $review): ?>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                    <div>
                        <p class="font-medium text-[#0b2b3f]"><?= htmlspecialchars(substr($review['manuscript_title'], 0, 40)) ?>...</p>
                        <p class="text-xs text-gray-500">Author: <?= htmlspecialchars($review['author_name'] ?? 'Unknown') ?></p>
                    </div>
                    <div class="text-right">
                        <?php if ($review['due_date']): ?>
                            <?php if ($review['days_remaining'] < 0): ?>
                                <p class="text-sm font-bold text-red-600">Overdue by <?= abs($review['days_remaining']) ?> days</p>
                            <?php elseif ($review['days_remaining'] <= 3): ?>
                                <p class="text-sm font-bold text-orange-600">Due in <?= $review['days_remaining'] ?> days</p>
                            <?php else: ?>
                                <p class="text-sm text-gray-600">Due in <?= $review['days_remaining'] ?> days</p>
                            <?php endif; ?>
                        <?php endif; ?>
                        <a href="/jms/reviewer?action=review-forms&id=<?= $review['manuscript_id'] ?>" 
                           class="text-indigo-600 hover:text-indigo-800 text-sm">
                            <i class="fas fa-arrow-right"></i> Review
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Completed Reviews -->
        <?php if (!empty($completedReviews)): ?>
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">Recent Completed Reviews</h3>
            <div class="space-y-2">
                <?php foreach ($completedReviews as $review): ?>
                <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                    <span class="text-sm"><?= htmlspecialchars(substr($review['manuscript_title'], 0, 40)) ?>...</span>
                    <span class="text-xs text-green-600">Completed <?= timeAgo($review['completed_date']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
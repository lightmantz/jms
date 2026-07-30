<?php
// modules/reviewer/pages/recommendation.php - Recommendation
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

$db = getDB();
$currentUser = getCurrentUser();

// Get completed reviews with recommendations
$stmt = $db->prepare("
    SELECT r.*, m.title as manuscript_title,
           u.full_name as author_name,
           r.recommendation,
           r.completed_date
    FROM reviews r
    JOIN manuscripts m ON r.manuscript_id = m.id
    LEFT JOIN users u ON m.corresponding_author_id = u.id
    WHERE r.reviewer_id = ? AND r.status = 'completed' AND r.recommendation IS NOT NULL
    ORDER BY r.completed_date DESC
");
$stmt->execute([$currentUser['id']]);
$completedReviews = $stmt->fetchAll();

// Get stats
$recommendationStats = [
    'accept' => 0,
    'minor_revision' => 0,
    'major_revision' => 0,
    'revise_resubmit' => 0,
    'reject' => 0
];

foreach ($completedReviews as $review) {
    if (isset($recommendationStats[$review['recommendation']])) {
        $recommendationStats[$review['recommendation']]++;
    }
}

$totalRecommendations = array_sum($recommendationStats);
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Recommendations</h2>
            <p class="text-gray-500 text-sm mt-1">View your review recommendations history</p>
        </div>
        <a href="/jms/reviewer" class="text-indigo-600 hover:text-indigo-800 text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back
        </a>
    </div>
    <div class="h-1 w-20 bg-indigo-200 rounded-full"></div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-green-50 rounded-xl p-4 text-center border border-green-200">
            <p class="text-2xl font-bold text-green-700"><?= $recommendationStats['accept'] ?></p>
            <p class="text-xs text-green-600">Accept</p>
        </div>
        <div class="bg-blue-50 rounded-xl p-4 text-center border border-blue-200">
            <p class="text-2xl font-bold text-blue-700"><?= $recommendationStats['minor_revision'] ?></p>
            <p class="text-xs text-blue-600">Minor Revision</p>
        </div>
        <div class="bg-orange-50 rounded-xl p-4 text-center border border-orange-200">
            <p class="text-2xl font-bold text-orange-700"><?= $recommendationStats['major_revision'] ?></p>
            <p class="text-xs text-orange-600">Major Revision</p>
        </div>
        <div class="bg-purple-50 rounded-xl p-4 text-center border border-purple-200">
            <p class="text-2xl font-bold text-purple-700"><?= $recommendationStats['revise_resubmit'] ?></p>
            <p class="text-xs text-purple-600">Revise & Resubmit</p>
        </div>
        <div class="bg-red-50 rounded-xl p-4 text-center border border-red-200">
            <p class="text-2xl font-bold text-red-700"><?= $recommendationStats['reject'] ?></p>
            <p class="text-xs text-red-600">Reject</p>
        </div>
    </div>

    <?php if (empty($completedReviews)): ?>
        <div class="text-center py-12">
            <i class="fas fa-thumbs-up text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No recommendations made yet.</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">
                Recommendation History (<?= $totalRecommendations ?>)
            </h3>
            <div class="space-y-3">
                <?php foreach ($completedReviews as $review): ?>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                    <div>
                        <p class="font-medium text-[#0b2b3f]"><?= htmlspecialchars(substr($review['manuscript_title'], 0, 40)) ?>...</p>
                        <p class="text-xs text-gray-500">Author: <?= htmlspecialchars($review['author_name'] ?? 'Unknown') ?></p>
                    </div>
                    <div class="text-right">
                        <span class="px-2 py-1 rounded-full text-xs font-medium 
                            <?= $review['recommendation'] == 'accept' ? 'bg-green-100 text-green-700' : 
                               ($review['recommendation'] == 'reject' ? 'bg-red-100 text-red-700' : 
                               ($review['recommendation'] == 'minor_revision' ? 'bg-blue-100 text-blue-700' : 
                               ($review['recommendation'] == 'major_revision' ? 'bg-orange-100 text-orange-700' : 'bg-purple-100 text-purple-700'))) ?>">
                            <?= ucfirst(str_replace('_', ' ', $review['recommendation'])) ?>
                        </span>
                        <p class="text-xs text-gray-400 mt-1"><?= formatDate($review['completed_date']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php
// modules/admin/pages/reviewer-view.php - View Reviewer Details
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

requireRole(['admin']);

$db = getDB();
$reviewerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($reviewerId <= 0) {
    echo '<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">Invalid reviewer ID.</div>';
    return;
}

// Get reviewer details
$stmt = $db->prepare("
    SELECT u.*, 
           (SELECT COUNT(*) FROM reviews WHERE reviewer_id = u.id AND status IN ('invited', 'accepted')) as pending_reviews,
           (SELECT COUNT(*) FROM reviews WHERE reviewer_id = u.id AND status = 'completed') as completed_reviews,
           (SELECT COUNT(*) FROM reviews WHERE reviewer_id = u.id) as total_reviews,
           (SELECT AVG(rating) FROM reviews WHERE reviewer_id = u.id AND rating IS NOT NULL) as avg_rating
    FROM users u
    WHERE u.id = ? AND u.role = 'reviewer'
");
$stmt->execute([$reviewerId]);
$reviewer = $stmt->fetch();

if (!$reviewer) {
    echo '<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">Reviewer not found.</div>';
    return;
}

// Get reviewer's recent reviews
$stmt = $db->prepare("
    SELECT r.*, m.title as manuscript_title, m.doi,
           u.full_name as editor_name
    FROM reviews r
    JOIN manuscripts m ON r.manuscript_id = m.id
    LEFT JOIN users u ON r.editor_id = u.id
    WHERE r.reviewer_id = ?
    ORDER BY r.created_at DESC
    LIMIT 10
");
$stmt->execute([$reviewerId]);
$recentReviews = $stmt->fetchAll();

// Get reviewer's assignment history
$stmt = $db->prepare("
    SELECT r.*, m.title as manuscript_title,
           u.full_name as editor_name
    FROM reviewer_assignments r
    JOIN manuscripts m ON r.manuscript_id = m.id
    LEFT JOIN users u ON r.editor_id = u.id
    WHERE r.reviewer_id = ?
    ORDER BY r.created_at DESC
    LIMIT 10
");
$stmt->execute([$reviewerId]);
$recentAssignments = $stmt->fetchAll();

function getReviewStatusBadge($status) {
    $classes = [
        'invited' => 'bg-yellow-100 text-yellow-700',
        'accepted' => 'bg-blue-100 text-blue-700',
        'declined' => 'bg-red-100 text-red-700',
        'completed' => 'bg-green-100 text-green-700',
        'overdue' => 'bg-red-100 text-red-700'
    ];
    return $classes[$status] ?? 'bg-gray-100 text-gray-700';
}
?>
<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Reviewer Details</h2>
            <p class="text-gray-500 text-sm mt-1">View reviewer information and activity</p>
        </div>
        <div class="flex gap-3">
            <button onclick="window.location.href='/jms/admin?action=reviewers'" class="text-indigo-600 hover:text-indigo-800 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back to Reviewers
            </button>
            <button onclick="window.location.href='/jms/admin?action=reviewers&edit=<?= $reviewer['id'] ?>'" 
                    class="bg-[#0b2b3f] text-white px-4 py-2 rounded-lg hover:bg-[#123a4f] transition text-sm">
                <i class="fas fa-edit mr-1"></i> Edit Reviewer
            </button>
        </div>
    </div>
    <div class="h-1 w-20 bg-indigo-200 rounded-full mb-6"></div>

    <!-- Profile Header -->
    <div class="flex items-start gap-6 p-6 bg-gray-50 rounded-xl border border-gray-200 mb-6">
        <div class="w-20 h-20 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-3xl flex-shrink-0">
            <?php 
            $initials = '';
            $nameParts = explode(' ', $reviewer['full_name']);
            foreach ($nameParts as $part) {
                if (!empty($part)) {
                    $initials .= strtoupper(substr($part, 0, 1));
                }
            }
            echo htmlspecialchars(substr($initials, 0, 2));
            ?>
        </div>
        <div class="flex-1">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="text-2xl font-bold text-[#0b2b3f]"><?= htmlspecialchars($reviewer['full_name']) ?></h3>
                    <p class="text-indigo-600 font-medium">Reviewer</p>
                    <p class="text-sm text-gray-500"><?= htmlspecialchars($reviewer['email']) ?></p>
                </div>
                <span class="px-3 py-1 rounded-full text-sm font-medium <?= $reviewer['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' ?>">
                    <?= $reviewer['is_active'] ? 'Active' : 'Inactive' ?>
                </span>
            </div>
            <?php if ($reviewer['institution']): ?>
                <p class="text-sm text-gray-600 mt-2"><i class="fas fa-university mr-2"></i> <?= htmlspecialchars($reviewer['institution']) ?></p>
            <?php endif; ?>
            <?php if ($reviewer['bio']): ?>
                <p class="text-sm text-gray-600 mt-2"><i class="fas fa-tag mr-2"></i> Expertise: <?= htmlspecialchars($reviewer['bio']) ?></p>
            <?php endif; ?>
            <div class="flex flex-wrap gap-3 mt-3">
                <span class="text-xs text-gray-500"><i class="far fa-calendar-alt mr-1"></i> Joined: <?= formatDate($reviewer['created_at']) ?></span>
                <?php if ($reviewer['last_login']): ?>
                    <span class="text-xs text-gray-500"><i class="far fa-clock mr-1"></i> Last Login: <?= formatDate($reviewer['last_login']) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-blue-700"><?= $reviewer['total_reviews'] ?? 0 ?></p>
            <p class="text-xs text-blue-600">Total Reviews</p>
        </div>
        <div class="bg-yellow-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-yellow-700"><?= $reviewer['pending_reviews'] ?? 0 ?></p>
            <p class="text-xs text-yellow-600">Pending</p>
        </div>
        <div class="bg-green-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-green-700"><?= $reviewer['completed_reviews'] ?? 0 ?></p>
            <p class="text-xs text-green-600">Completed</p>
        </div>
        <div class="bg-purple-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-purple-700"><?= number_format($reviewer['avg_rating'] ?? 0, 1) ?></p>
            <p class="text-xs text-purple-600">Avg. Rating</p>
        </div>
    </div>

    <!-- Recent Reviews -->
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-[#0b2b3f] mb-3 flex items-center gap-2">
            <i class="fas fa-star text-yellow-500"></i> Recent Reviews
        </h3>
        <?php if (empty($recentReviews)): ?>
            <p class="text-gray-500 text-sm">No reviews found.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Manuscript</th>
                            <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                            <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Recommendation</th>
                            <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
                            <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentReviews as $review): ?>
                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                            <td class="py-2 px-3 text-sm text-gray-700">
                                <?= htmlspecialchars(substr($review['manuscript_title'], 0, 40)) ?>...
                            </td>
                            <td class="py-2 px-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= getReviewStatusBadge($review['status']) ?>">
                                    <?= ucfirst($review['status']) ?>
                                </span>
                            </td>
                            <td class="py-2 px-3 text-sm text-gray-600">
                                <?= $review['recommendation'] ? ucfirst(str_replace('_', ' ', $review['recommendation'])) : '-' ?>
                            </td>
                            <td class="py-2 px-3 text-sm text-gray-500">
                                <?= formatDate($review['created_at']) ?>
                            </td>
                            <td class="py-2 px-3">
                                <a href="/jms/admin?action=manuscript&id=<?= $review['manuscript_id'] ?>" 
                                   class="text-indigo-600 hover:text-indigo-800 text-sm">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recent Assignments -->
    <div>
        <h3 class="text-lg font-semibold text-[#0b2b3f] mb-3 flex items-center gap-2">
            <i class="fas fa-tasks text-indigo-500"></i> Assignment History
        </h3>
        <?php if (empty($recentAssignments)): ?>
            <p class="text-gray-500 text-sm">No assignments found.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Manuscript</th>
                            <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                            <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Due Date</th>
                            <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentAssignments as $assignment): ?>
                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                            <td class="py-2 px-3 text-sm text-gray-700">
                                <?= htmlspecialchars(substr($assignment['manuscript_title'], 0, 40)) ?>...
                            </td>
                            <td class="py-2 px-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= getReviewStatusBadge($assignment['status']) ?>">
                                    <?= ucfirst($assignment['status']) ?>
                                </span>
                            </td>
                            <td class="py-2 px-3 text-sm text-gray-500">
                                <?= $assignment['due_date'] ? formatDate($assignment['due_date']) : '-' ?>
                            </td>
                            <td class="py-2 px-3">
                                <a href="/jms/admin?action=manuscript&id=<?= $assignment['manuscript_id'] ?>" 
                                   class="text-indigo-600 hover:text-indigo-800 text-sm">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
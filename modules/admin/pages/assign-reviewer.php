<?php
// modules/admin/pages/assign-reviewer.php - Assign Reviewer to Manuscript
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

requireRole(['admin']);

$db = getDB();
$message = '';
$error = '';

$manuscriptId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$manuscriptId) {
    echo '<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
            <div class="text-center py-12">
                <p class="text-gray-500">No manuscript specified.</p>
                <a href="/jms/admin?action=submissions" class="mt-3 inline-block bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                    <i class="fas fa-arrow-left mr-2"></i> Back
                </a>
            </div>
        </div>';
    exit;
}

$manuscript = getManuscript($manuscriptId);
if (!$manuscript) {
    echo '<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
            <div class="text-center py-12">
                <p class="text-gray-500">Manuscript not found.</p>
                <a href="/jms/admin?action=submissions" class="mt-3 inline-block bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                    <i class="fas fa-arrow-left mr-2"></i> Back
                </a>
            </div>
        </div>';
    exit;
}

// Get existing reviews
$existingReviews = getManuscriptReviews($manuscriptId);
$existingReviewerIds = array_column($existingReviews, 'reviewer_id');

// Get available reviewers (not already assigned to this manuscript)
$reviewers = getReviewers();
$availableReviewers = array_filter($reviewers, function($r) use ($existingReviewerIds) {
    return !in_array($r['id'], $existingReviewerIds);
});

// Handle assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_reviewer'])) {
    $reviewerId = (int)$_POST['reviewer_id'];
    $dueDate = $_POST['due_date'] ?? null;
    
    if ($reviewerId > 0) {
        if (inviteReviewer($manuscriptId, $reviewerId, $currentUser['id'], $dueDate)) {
            $message = 'Reviewer invited successfully!';
            // Refresh data
            $existingReviews = getManuscriptReviews($manuscriptId);
            $existingReviewerIds = array_column($existingReviews, 'reviewer_id');
            $availableReviewers = array_filter($reviewers, function($r) use ($existingReviewerIds) {
                return !in_array($r['id'], $existingReviewerIds);
            });
        } else {
            $error = 'Failed to invite reviewer.';
        }
    } else {
        $error = 'Please select a reviewer.';
    }
}
?>
<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Assign Reviewer</h2>
            <p class="text-gray-500 text-sm mt-1"><?= htmlspecialchars(substr($manuscript['title'], 0, 60)) ?>...</p>
        </div>
        <a href="/jms/admin?action=manuscript&id=<?= $manuscriptId ?>" class="text-indigo-600 hover:text-indigo-800 text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back to Manuscript
        </a>
    </div>
    <div class="h-1 w-20 bg-indigo-200 rounded-full mb-6"></div>

    <?php if ($message): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm mb-6">
            <i class="fas fa-check-circle mr-2"></i> <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm mb-6">
            <i class="fas fa-exclamation-circle mr-2"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Assign New Reviewer -->
        <div>
            <h3 class="font-semibold text-[#0b2b3f] mb-4">Invite New Reviewer</h3>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Select Reviewer *</label>
                    <select name="reviewer_id" required class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                        <option value="">Select a reviewer...</option>
                        <?php foreach ($availableReviewers as $reviewer): ?>
                        <option value="<?= $reviewer['id'] ?>">
                            <?= htmlspecialchars($reviewer['full_name']) ?> 
                            (<?= $reviewer['pending_reviews'] ?? 0 ?> pending, <?= $reviewer['completed_reviews'] ?? 0 ?> completed)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                    <input type="date" name="due_date" 
                           value="<?= date('Y-m-d', strtotime('+2 weeks')) ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                </div>
                <button type="submit" name="assign_reviewer" class="bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                    <i class="fas fa-envelope mr-2"></i> Send Invitation
                </button>
            </form>
            
            <?php if (empty($availableReviewers)): ?>
                <div class="mt-4 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                    <p class="text-sm text-yellow-700">
                        <i class="fas fa-info-circle mr-2"></i> 
                        All reviewers have been assigned to this manuscript. 
                        <a href="/jms/admin?action=reviewers" class="underline">Manage reviewers</a>
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Current Reviewers -->
        <div>
            <h3 class="font-semibold text-[#0b2b3f] mb-4">Current Reviewers (<?= count($existingReviews) ?>)</h3>
            <?php if (empty($existingReviews)): ?>
                <p class="text-sm text-gray-500">No reviewers assigned yet.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($existingReviews as $review): ?>
                    <div class="border border-gray-200 rounded-lg p-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-sm"><?= htmlspecialchars($review['reviewer_name']) ?></p>
                                <p class="text-xs text-gray-500"><?= htmlspecialchars($review['reviewer_institution'] ?? '') ?></p>
                            </div>
                            <div class="text-right">
                                <span class="px-2 py-1 rounded-full text-xs font-medium <?= getStatusBadge($review['status']) ?>">
                                    <?= ucfirst($review['status']) ?>
                                </span>
                                <?php if ($review['due_date']): ?>
                                    <p class="text-xs text-gray-400 mt-1">Due: <?= formatDate($review['due_date']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
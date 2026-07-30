<?php
// modules/editor/pages/reviewer-assignment.php - Reviewer Assignment
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

$db = getDB();
$currentUser = getCurrentUser();
$message = '';
$error = '';

$manuscriptId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Initialize variables
$manuscript = null;
$existingReviews = [];
$existingReviewerIds = [];
$availableReviewers = [];

if ($manuscriptId > 0) {
    $manuscript = getManuscript($manuscriptId);
    $existingReviews = getManuscriptReviews($manuscriptId);
    $existingReviewerIds = array_column($existingReviews, 'reviewer_id');
}

// Get all reviewers
$reviewers = getReviewers();

// Filter available reviewers (not already assigned to this manuscript)
$availableReviewers = array_filter($reviewers, function($r) use ($existingReviewerIds) {
    return !in_array($r['id'], $existingReviewerIds);
});

// Get manuscripts needing reviewers (that are under review)
$stmt = $db->query("
    SELECT m.id, m.title, u.full_name as author_name,
           (SELECT COUNT(*) FROM reviews WHERE manuscript_id = m.id) as review_count,
           (SELECT COUNT(*) FROM reviews WHERE manuscript_id = m.id AND status = 'completed') as completed_reviews
    FROM manuscripts m
    LEFT JOIN users u ON m.corresponding_author_id = u.id
    WHERE m.status = 'under_review'
    ORDER BY m.submission_date DESC
");
$manuscriptsNeedingReviewers = $stmt->fetchAll();

// Handle reviewer invitation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['invite_reviewer'])) {
    $manuscript_id = (int)$_POST['manuscript_id'];
    $reviewer_id = (int)$_POST['reviewer_id'];
    $due_date = $_POST['due_date'] ?? null;
    
    if ($reviewer_id <= 0) {
        $error = 'Please select a reviewer.';
    } else {
        if (inviteReviewer($manuscript_id, $reviewer_id, $currentUser['id'], $due_date)) {
            $message = 'Reviewer invited successfully!';
            // Refresh data
            $existingReviews = getManuscriptReviews($manuscript_id);
            $existingReviewerIds = array_column($existingReviews, 'reviewer_id');
            $availableReviewers = array_filter($reviewers, function($r) use ($existingReviewerIds) {
                return !in_array($r['id'], $existingReviewerIds);
            });
            if ($manuscriptId > 0) {
                $manuscript = getManuscript($manuscriptId);
            }
        } else {
            $error = 'Failed to invite reviewer.';
        }
    }
}
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Reviewer Assignment</h2>
            <p class="text-gray-500 text-sm mt-1">Invite and manage reviewers</p>
        </div>
        <a href="/jms/editor" class="text-indigo-600 hover:text-indigo-800 text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back
        </a>
    </div>
    <div class="h-1 w-20 bg-indigo-200 rounded-full"></div>

    <?php if ($message): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
            <i class="fas fa-check-circle mr-2"></i> <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
            <i class="fas fa-exclamation-circle mr-2"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($manuscriptId > 0 && $manuscript): ?>
        <!-- Assign Reviewers to Specific Manuscript -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">
                Invite Reviewers for: <?= htmlspecialchars(substr($manuscript['title'], 0, 50)) ?>...
            </h3>
            
            <div class="grid md:grid-cols-2 gap-4 mb-4">
                <div>
                    <p class="text-sm text-gray-500">Author</p>
                    <p class="font-medium"><?= htmlspecialchars($manuscript['author_name'] ?? 'Unknown') ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Current Reviewers</p>
                    <p class="font-medium"><?= count($existingReviews) ?></p>
                </div>
            </div>
            
            <?php if (!empty($availableReviewers)): ?>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="manuscript_id" value="<?= $manuscriptId ?>">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Select Reviewer *</label>
                    <select name="reviewer_id" required class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none transition">
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
                    <input type="date" name="due_date" value="<?= date('Y-m-d', strtotime('+2 weeks')) ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none transition">
                </div>
                <button type="submit" name="invite_reviewer" class="bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                    <i class="fas fa-envelope mr-2"></i> Send Invitation
                </button>
            </form>
            <?php else: ?>
                <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
                    <p class="text-sm text-yellow-700">
                        <i class="fas fa-info-circle mr-2"></i>
                        All available reviewers have been assigned to this manuscript.
                    </p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($existingReviews)): ?>
            <div class="mt-4 border-t border-gray-200 pt-4">
                <h4 class="font-semibold text-[#0b2b3f] mb-2">Current Reviewers</h4>
                <div class="space-y-2">
                    <?php foreach ($existingReviews as $review): ?>
                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                        <span class="text-sm"><?= htmlspecialchars($review['reviewer_name']) ?></span>
                        <span class="text-xs px-2 py-0.5 rounded-full <?= getStatusBadge($review['status']) ?>">
                            <?= ucfirst($review['status']) ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Manuscripts Needing Reviewers -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">Manuscripts Needing Reviewers</h3>
        <?php if (empty($manuscriptsNeedingReviewers)): ?>
            <p class="text-sm text-gray-500">All manuscripts have assigned reviewers.</p>
        <?php else: ?>
            <div class="space-y-2">
                <?php foreach ($manuscriptsNeedingReviewers as $manuscript): ?>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                    <div>
                        <p class="font-medium text-[#0b2b3f]"><?= htmlspecialchars(substr($manuscript['title'], 0, 40)) ?>...</p>
                        <div class="flex items-center gap-2 text-xs text-gray-500">
                            <span>Author: <?= htmlspecialchars($manuscript['author_name'] ?? 'Unknown') ?></span>
                            <span>· Reviews: <?= $manuscript['completed_reviews'] ?? 0 ?>/<?= $manuscript['review_count'] ?? 0 ?></span>
                        </div>
                    </div>
                    <a href="/jms/editor?action=reviewer-assignment&id=<?= $manuscript['id'] ?>" class="bg-[#0b2b3f] text-white px-4 py-2 rounded-lg hover:bg-[#123a4f] transition text-sm">
                        <i class="fas fa-user-plus mr-1"></i> Assign
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
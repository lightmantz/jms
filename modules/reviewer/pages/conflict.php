<?php
// modules/reviewer/pages/conflict.php - Conflict of Interest
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

$db = getDB();
$currentUser = getCurrentUser();
$message = '';
$error = '';

// Get reviewer's active reviews
$stmt = $db->prepare("
    SELECT r.*, m.title as manuscript_title,
           u.full_name as author_name
    FROM reviews r
    JOIN manuscripts m ON r.manuscript_id = m.id
    LEFT JOIN users u ON m.corresponding_author_id = u.id
    WHERE r.reviewer_id = ? AND r.status IN ('invited', 'accepted')
    ORDER BY r.invitation_date DESC
");
$stmt->execute([$currentUser['id']]);
$activeReviews = $stmt->fetchAll();

// Handle conflict declaration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['declare_conflict'])) {
        $review_id = (int)$_POST['review_id'];
        $conflict_type = $_POST['conflict_type'] ?? '';
        $conflict_details = trim($_POST['conflict_details'] ?? '');
        
        if (empty($conflict_type)) {
            $error = 'Please select a conflict type.';
        } elseif (empty($conflict_details)) {
            $error = 'Please provide details about the conflict.';
        } else {
            // Update review with conflict info
            $stmt = $db->prepare("
                UPDATE reviews 
                SET status = 'declined', 
                    confidential_comments = CONCAT('Conflict of Interest: ', ?, ' - ', ?)
                WHERE id = ? AND reviewer_id = ?
            ");
            if ($stmt->execute([$conflict_type, $conflict_details, $review_id, $currentUser['id']])) {
                $message = 'Conflict of interest declared. Review invitation declined.';
                logAction($currentUser['id'], 'declare_conflict', 'reviews', $review_id);
                // Refresh data
                $stmt = $db->prepare("
                    SELECT r.*, m.title as manuscript_title,
                           u.full_name as author_name
                    FROM reviews r
                    JOIN manuscripts m ON r.manuscript_id = m.id
                    LEFT JOIN users u ON m.corresponding_author_id = u.id
                    WHERE r.reviewer_id = ? AND r.status IN ('invited', 'accepted')
                    ORDER BY r.invitation_date DESC
                ");
                $stmt->execute([$currentUser['id']]);
                $activeReviews = $stmt->fetchAll();
            } else {
                $error = 'Failed to declare conflict.';
            }
        }
    } elseif (isset($_POST['no_conflict'])) {
        $review_id = (int)$_POST['review_id'];
        
        // Accept the review if no conflict
        if (acceptReview($review_id, $currentUser['id'])) {
            $message = 'Review accepted. No conflict of interest.';
            // Refresh data
            $stmt = $db->prepare("
                SELECT r.*, m.title as manuscript_title,
                       u.full_name as author_name
                FROM reviews r
                JOIN manuscripts m ON r.manuscript_id = m.id
                LEFT JOIN users u ON m.corresponding_author_id = u.id
                WHERE r.reviewer_id = ? AND r.status IN ('invited', 'accepted')
                ORDER BY r.invitation_date DESC
            ");
            $stmt->execute([$currentUser['id']]);
            $activeReviews = $stmt->fetchAll();
        } else {
            $error = 'Failed to accept review.';
        }
    }
}
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Conflict of Interest</h2>
            <p class="text-gray-500 text-sm mt-1">Declare any conflicts of interest for your reviews</p>
        </div>
        <a href="/jms/reviewer" class="text-indigo-600 hover:text-indigo-800 text-sm">
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

    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-4">
        <p class="text-sm text-yellow-700">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <strong>Important:</strong> You must declare any potential conflicts of interest before accepting a review. 
            Conflicts may include personal, financial, or professional relationships with the authors.
        </p>
    </div>

    <?php if (empty($activeReviews)): ?>
        <div class="text-center py-12">
            <i class="fas fa-check-circle text-5xl text-green-300 mb-4"></i>
            <p class="text-gray-500">No active reviews requiring conflict of interest declaration.</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($activeReviews as $review): ?>
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h4 class="font-semibold text-[#0b2b3f]"><?= htmlspecialchars($review['manuscript_title']) ?></h4>
                        <p class="text-sm text-gray-500">Author: <?= htmlspecialchars($review['author_name'] ?? 'Unknown') ?></p>
                        <?php if ($review['due_date']): ?>
                            <p class="text-sm text-gray-500">Due: <?= formatDate($review['due_date']) ?></p>
                        <?php endif; ?>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= getStatusBadge($review['status']) ?>">
                        <?= ucfirst($review['status']) ?>
                    </span>
                </div>

                <?php if ($review['status'] == 'invited'): ?>
                <div class="border-t border-gray-200 pt-4">
                    <p class="text-sm font-medium text-gray-700 mb-3">Do you have any conflict of interest with this manuscript?</p>
                    
                    <!-- No Conflict -->
                    <form method="POST" class="inline">
                        <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                        <button type="submit" name="no_conflict" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition">
                            <i class="fas fa-check mr-2"></i> No Conflict - Accept Review
                        </button>
                    </form>

                    <!-- Conflict -->
                    <button onclick="openConflictModal(<?= $review['id'] ?>)" 
                            class="ml-3 bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition">
                        <i class="fas fa-exclamation-triangle mr-2"></i> Declare Conflict
                    </button>
                </div>
                <?php elseif ($review['status'] == 'accepted'): ?>
                    <div class="bg-green-50 rounded-lg p-3 border border-green-200">
                        <p class="text-sm text-green-700">
                            <i class="fas fa-check-circle mr-2"></i>
                            Review accepted - No conflicts declared.
                        </p>
                        <a href="/jms/reviewer?action=review-forms&id=<?= $review['manuscript_id'] ?>" 
                           class="inline-block mt-2 text-sm text-indigo-600 hover:text-indigo-800">
                            <i class="fas fa-arrow-right mr-1"></i> Start Review
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Conflict Modal -->
<div id="conflictModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-[#0b2b3f]">Declare Conflict of Interest</h3>
            <button onclick="closeConflictModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="review_id" id="conflictReviewId">
            <input type="hidden" name="declare_conflict" value="1">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Conflict Type *</label>
                    <select name="conflict_type" required class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                        <option value="">Select conflict type...</option>
                        <option value="personal">Personal Relationship</option>
                        <option value="professional">Professional Relationship</option>
                        <option value="financial">Financial Interest</option>
                        <option value="institutional">Institutional Affiliation</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Conflict Details *</label>
                    <textarea name="conflict_details" rows="4" required
                              placeholder="Please describe the conflict of interest in detail..."
                              class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none"></textarea>
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-red-700 transition flex-1">
                    <i class="fas fa-exclamation-triangle mr-2"></i> Declare Conflict
                </button>
                <button type="button" onclick="closeConflictModal()" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg font-semibold hover:bg-gray-200 transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openConflictModal(reviewId) {
    document.getElementById('conflictReviewId').value = reviewId;
    document.getElementById('conflictModal').classList.remove('hidden');
}

function closeConflictModal() {
    document.getElementById('conflictModal').classList.add('hidden');
}
</script>
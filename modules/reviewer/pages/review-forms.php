<?php
// modules/reviewer/pages/review-forms.php - Review Forms
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

$db = getDB();
$currentUser = getCurrentUser();
$message = '';
$error = '';

$manuscriptId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($manuscriptId > 0) {
    $manuscript = getManuscript($manuscriptId);
    $review = null;
    
    // Get the review record for this manuscript and reviewer
    $stmt = $db->prepare("SELECT * FROM reviews WHERE manuscript_id = ? AND reviewer_id = ?");
    $stmt->execute([$manuscriptId, $currentUser['id']]);
    $review = $stmt->fetch();
}

// Get all reviews assigned to this reviewer
$stmt = $db->prepare("
    SELECT r.*, m.title as manuscript_title, m.abstract,
           u.full_name as author_name,
           r.status as review_status
    FROM reviews r
    JOIN manuscripts m ON r.manuscript_id = m.id
    LEFT JOIN users u ON m.corresponding_author_id = u.id
    WHERE r.reviewer_id = ? AND r.status IN ('accepted', 'completed')
    ORDER BY r.due_date ASC
");
$stmt->execute([$currentUser['id']]);
$myReviews = $stmt->fetchAll();

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $review_id = (int)$_POST['review_id'];
    $recommendation = $_POST['recommendation'] ?? '';
    $comments_to_author = trim($_POST['comments_to_author'] ?? '');
    $comments_to_editor = trim($_POST['comments_to_editor'] ?? '');
    $confidential_comments = trim($_POST['confidential_comments'] ?? '');
    
    if (empty($recommendation)) {
        $error = 'Please select a recommendation.';
    } elseif (empty($comments_to_author)) {
        $error = 'Please provide comments to the author.';
    } else {
        $data = [
            'recommendation' => $recommendation,
            'comments_to_author' => $comments_to_author,
            'comments_to_editor' => $comments_to_editor,
            'confidential_comments' => $confidential_comments
        ];
        
        if (submitReview($review_id, $currentUser['id'], $data)) {
            $message = 'Review submitted successfully!';
            // Refresh data
            $stmt = $db->prepare("SELECT * FROM reviews WHERE manuscript_id = ? AND reviewer_id = ?");
            $stmt->execute([$manuscriptId, $currentUser['id']]);
            $review = $stmt->fetch();
        } else {
            $error = 'Failed to submit review.';
        }
    }
}
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Review Forms</h2>
            <p class="text-gray-500 text-sm mt-1">Complete review forms for assigned manuscripts</p>
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

    <?php if ($manuscriptId > 0 && $manuscript && $review): ?>
        <!-- Review Form -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-[#0b2b3f]">Review Form</h3>
                <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= getStatusBadge($review['status']) ?>">
                    <?= ucfirst($review['status']) ?>
                </span>
            </div>
            
            <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                <p class="font-medium text-[#0b2b3f]"><?= htmlspecialchars($manuscript['title']) ?></p>
                <p class="text-sm text-gray-500">Author: <?= htmlspecialchars($manuscript['author_name'] ?? 'Unknown') ?></p>
                <?php if ($manuscript['abstract']): ?>
                    <div class="mt-2">
                        <p class="text-sm font-medium text-gray-700">Abstract</p>
                        <p class="text-sm text-gray-600"><?= nl2br(htmlspecialchars($manuscript['abstract'])) ?></p>
                    </div>
                <?php endif; ?>
                <?php if ($review['due_date']): ?>
                    <p class="text-sm text-gray-500 mt-2">Due: <?= formatDate($review['due_date']) ?></p>
                <?php endif; ?>
            </div>

            <?php if ($review['status'] == 'completed'): ?>
                <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                    <p class="text-sm text-green-700">
                        <i class="fas fa-check-circle mr-2"></i>
                        Review completed on <?= formatDate($review['completed_date']) ?>
                    </p>
                    <?php if ($review['recommendation']): ?>
                        <p class="text-sm font-medium mt-2">Recommendation: <?= ucfirst(str_replace('_', ' ', $review['recommendation'])) ?></p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                    
                    <!-- Recommendation -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Recommendation *</label>
                        <div class="grid md:grid-cols-3 gap-2">
                            <label class="flex items-center gap-2 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                <input type="radio" name="recommendation" value="accept" required>
                                <span class="text-sm text-green-600">Accept</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                <input type="radio" name="recommendation" value="minor_revision">
                                <span class="text-sm text-blue-600">Minor Revision</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                <input type="radio" name="recommendation" value="major_revision">
                                <span class="text-sm text-orange-600">Major Revision</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                <input type="radio" name="recommendation" value="revise_resubmit">
                                <span class="text-sm text-purple-600">Revise & Resubmit</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                <input type="radio" name="recommendation" value="reject">
                                <span class="text-sm text-red-600">Reject</span>
                            </label>
                        </div>
                    </div>

                    <!-- Comments to Author -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Comments to Author *</label>
                        <textarea name="comments_to_author" rows="5" required
                                  placeholder="Provide detailed feedback to the author..."
                                  class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition"></textarea>
                        <p class="text-xs text-gray-400 mt-1">These comments will be shared with the author</p>
                    </div>

                    <!-- Comments to Editor -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Comments to Editor</label>
                        <textarea name="comments_to_editor" rows="3"
                                  placeholder="Additional comments for the editor only..."
                                  class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition"></textarea>
                        <p class="text-xs text-gray-400 mt-1">These comments will only be seen by the editor</p>
                    </div>

                    <!-- Confidential Comments -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confidential Comments</label>
                        <textarea name="confidential_comments" rows="3"
                                  placeholder="Any confidential remarks for the editor..."
                                  class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition"></textarea>
                    </div>

                    <div class="flex gap-3">
                        <button type="submit" name="submit_review" class="bg-green-600 text-white px-8 py-2.5 rounded-lg font-semibold hover:bg-green-700 transition shadow-sm">
                            <i class="fas fa-check-circle mr-2"></i> Submit Review
                        </button>
                        <a href="/jms/reviewer?action=review-forms" class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg font-semibold hover:bg-gray-200 transition">
                            Cancel
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    <?php elseif ($manuscriptId > 0): ?>
        <div class="bg-white rounded-xl border border-gray-200 p-8 text-center">
            <i class="fas fa-exclamation-triangle text-5xl text-yellow-400 mb-4"></i>
            <p class="text-gray-500">You don't have permission to review this manuscript.</p>
            <a href="/jms/reviewer?action=review-forms" class="mt-3 inline-block bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                <i class="fas fa-arrow-left mr-2"></i> Back to My Reviews
            </a>
        </div>
    <?php else: ?>
        <!-- List of assigned reviews -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">My Assigned Reviews</h3>
            <?php if (empty($myReviews)): ?>
                <div class="text-center py-8">
                    <i class="fas fa-file-alt text-4xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500">No reviews assigned to you.</p>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($myReviews as $review): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                        <div>
                            <p class="font-medium text-[#0b2b3f]"><?= htmlspecialchars(substr($review['manuscript_title'], 0, 50)) ?>...</p>
                            <div class="flex items-center gap-2 text-xs text-gray-500 mt-1">
                                <span class="px-2 py-0.5 rounded-full <?= getStatusBadge($review['review_status']) ?>">
                                    <?= ucfirst($review['review_status']) ?>
                                </span>
                                <?php if ($review['due_date']): ?>
                                    <span>Due: <?= formatDate($review['due_date']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <?php if ($review['review_status'] == 'accepted'): ?>
                                <a href="/jms/reviewer?action=review-forms&id=<?= $review['manuscript_id'] ?>" 
                                   class="bg-[#0b2b3f] text-white px-4 py-2 rounded-lg hover:bg-[#123a4f] transition text-sm">
                                    <i class="fas fa-edit mr-1"></i> Review
                                </a>
                            <?php elseif ($review['review_status'] == 'completed'): ?>
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">Completed</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
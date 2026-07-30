<?php
// modules/editor/pages/decision.php - Decision Management
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
    $reviews = getManuscriptReviews($manuscriptId);
}

// Get all manuscripts for decision
$stmt = $db->query("
    SELECT m.*, u.full_name as author_name,
           (SELECT COUNT(*) FROM reviews WHERE manuscript_id = m.id AND status = 'completed') as completed_reviews,
           (SELECT COUNT(*) FROM reviews WHERE manuscript_id = m.id AND recommendation IS NOT NULL) as has_recommendations
    FROM manuscripts m
    LEFT JOIN users u ON m.corresponding_author_id = u.id
    WHERE m.status IN ('under_review', 'revision_required')
    ORDER BY m.submission_date DESC
");
$pendingDecisions = $stmt->fetchAll();

// Handle decision
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['make_decision'])) {
    $manuscript_id = (int)$_POST['manuscript_id'];
    $decision = $_POST['decision'] ?? '';
    $comments = trim($_POST['comments'] ?? '');
    
    if (empty($decision)) {
        $error = 'Please select a decision.';
    } else {
        $newStatus = $decision;
        if ($decision == 'accept') {
            $newStatus = 'accepted';
        } elseif ($decision == 'reject') {
            $newStatus = 'rejected';
        } elseif ($decision == 'revision') {
            $newStatus = 'revision_required';
        }
        
        $stmt = $db->prepare("UPDATE manuscripts SET status = ?, updated_at = NOW() WHERE id = ?");
        if ($stmt->execute([$newStatus, $manuscript_id])) {
            $message = 'Decision made successfully!';
            logAction($currentUser['id'], 'make_decision', 'manuscripts', $manuscript_id);
            
            // Notify author
            $manuscript = getManuscript($manuscript_id);
            if ($manuscript && $manuscript['corresponding_author_id']) {
                createNotification(
                    $manuscript['corresponding_author_id'],
                    'decision',
                    'Editorial Decision',
                    'A decision has been made on your manuscript: ' . $manuscript['title'],
                    SITE_URL . '/author?action=track&id=' . $manuscript_id
                );
            }
            
            // Refresh data
            if ($manuscriptId > 0) {
                $manuscript = getManuscript($manuscriptId);
                $reviews = getManuscriptReviews($manuscriptId);
            }
            $stmt = $db->query("
                SELECT m.*, u.full_name as author_name,
                       (SELECT COUNT(*) FROM reviews WHERE manuscript_id = m.id AND status = 'completed') as completed_reviews,
                       (SELECT COUNT(*) FROM reviews WHERE manuscript_id = m.id AND recommendation IS NOT NULL) as has_recommendations
                FROM manuscripts m
                LEFT JOIN users u ON m.corresponding_author_id = u.id
                WHERE m.status IN ('under_review', 'revision_required')
                ORDER BY m.submission_date DESC
            ");
            $pendingDecisions = $stmt->fetchAll();
        } else {
            $error = 'Failed to make decision.';
        }
    }
}
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Decision Management</h2>
            <p class="text-gray-500 text-sm mt-1">Make editorial decisions on manuscripts</p>
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

    <?php if ($manuscriptId > 0 && isset($manuscript)): ?>
        <!-- Make Decision on Specific Manuscript -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">
                <?= htmlspecialchars(substr($manuscript['title'], 0, 60)) ?>...
            </h3>
            
            <div class="grid md:grid-cols-3 gap-4 mb-4">
                <div>
                    <p class="text-sm text-gray-500">Author</p>
                    <p class="font-medium"><?= htmlspecialchars($manuscript['author_name'] ?? 'Unknown') ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Status</p>
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= getStatusBadge($manuscript['status']) ?>">
                        <?= ucfirst(str_replace('_', ' ', $manuscript['status'])) ?>
                    </span>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Reviews</p>
                    <p class="font-medium"><?= count($reviews ?? []) ?> completed</p>
                </div>
            </div>

            <?php if (!empty($reviews)): ?>
            <div class="mb-4 border-t border-gray-200 pt-4">
                <h4 class="font-semibold text-[#0b2b3f] mb-2">Reviewer Recommendations</h4>
                <div class="space-y-2">
                    <?php foreach ($reviews as $review): ?>
                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                        <span class="text-sm"><?= htmlspecialchars($review['reviewer_name']) ?></span>
                        <span class="text-sm font-medium <?= $review['recommendation'] == 'accept' ? 'text-green-600' : ($review['recommendation'] == 'reject' ? 'text-red-600' : 'text-yellow-600') ?>">
                            <?= ucfirst(str_replace('_', ' ', $review['recommendation'] ?? 'No recommendation')) ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="manuscript_id" value="<?= $manuscriptId ?>">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Decision *</label>
                    <select name="decision" required class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none transition">
                        <option value="">Select decision...</option>
                        <option value="accept">Accept</option>
                        <option value="revision">Revision Required</option>
                        <option value="reject">Reject</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Comments to Author</label>
                    <textarea name="comments" rows="4"
                              placeholder="Provide feedback to the author..."
                              class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition"></textarea>
                </div>
                <button type="submit" name="make_decision" class="bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                    <i class="fas fa-gavel mr-2"></i> Make Decision
                </button>
            </form>
        </div>
    <?php endif; ?>

    <!-- Pending Decisions -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">Pending Decisions</h3>
        <?php if (empty($pendingDecisions)): ?>
            <p class="text-sm text-gray-500">No pending decisions.</p>
        <?php else: ?>
            <div class="space-y-2">
                <?php foreach ($pendingDecisions as $manuscript): ?>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                    <div>
                        <p class="font-medium text-[#0b2b3f]"><?= htmlspecialchars(substr($manuscript['title'], 0, 40)) ?>...</p>
                        <div class="flex items-center gap-2 text-xs text-gray-500">
                            <span>Author: <?= htmlspecialchars($manuscript['author_name'] ?? 'Unknown') ?></span>
                            <span>· Reviews: <?= $manuscript['completed_reviews'] ?? 0 ?></span>
                        </div>
                    </div>
                    <a href="/jms/editor?action=decision&id=<?= $manuscript['id'] ?>" class="bg-[#0b2b3f] text-white px-4 py-2 rounded-lg hover:bg-[#123a4f] transition text-sm">
                        <i class="fas fa-gavel mr-1"></i> Decide
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
// modules/reviewer/pages/invitations.php - Review Invitations
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

$db = getDB();
$currentUser = getCurrentUser();
$message = '';
$error = '';

// Get reviewer's invitations
$stmt = $db->prepare("
    SELECT r.*, m.title as manuscript_title, m.abstract,
           u.full_name as author_name
    FROM reviews r
    JOIN manuscripts m ON r.manuscript_id = m.id
    LEFT JOIN users u ON m.corresponding_author_id = u.id
    WHERE r.reviewer_id = ?
    ORDER BY r.invitation_date DESC
");
$stmt->execute([$currentUser['id']]);
$invitations = $stmt->fetchAll();

// Handle invitation response
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $review_id = (int)$_POST['review_id'];
    $action = $_POST['action'] ?? '';
    
    if ($action == 'accept') {
        if (acceptReview($review_id, $currentUser['id'])) {
            $message = 'Review invitation accepted successfully!';
            // Refresh data
            $stmt = $db->prepare("
                SELECT r.*, m.title as manuscript_title, m.abstract,
                       u.full_name as author_name
                FROM reviews r
                JOIN manuscripts m ON r.manuscript_id = m.id
                LEFT JOIN users u ON m.corresponding_author_id = u.id
                WHERE r.reviewer_id = ?
                ORDER BY r.invitation_date DESC
            ");
            $stmt->execute([$currentUser['id']]);
            $invitations = $stmt->fetchAll();
        } else {
            $error = 'Failed to accept invitation.';
        }
    } elseif ($action == 'decline') {
        $reason = trim($_POST['reason'] ?? '');
        if (declineReview($review_id, $currentUser['id'], $reason)) {
            $message = 'Review invitation declined.';
            // Refresh data
            $stmt = $db->prepare("
                SELECT r.*, m.title as manuscript_title, m.abstract,
                       u.full_name as author_name
                FROM reviews r
                JOIN manuscripts m ON r.manuscript_id = m.id
                LEFT JOIN users u ON m.corresponding_author_id = u.id
                WHERE r.reviewer_id = ?
                ORDER BY r.invitation_date DESC
            ");
            $stmt->execute([$currentUser['id']]);
            $invitations = $stmt->fetchAll();
        } else {
            $error = 'Failed to decline invitation.';
        }
    }
}
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Review Invitations</h2>
            <p class="text-gray-500 text-sm mt-1">Manage your review invitations</p>
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

    <?php if (empty($invitations)): ?>
        <div class="text-center py-12">
            <i class="fas fa-envelope-open text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No review invitations found.</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($invitations as $invitation): ?>
            <div class="bg-white rounded-xl border border-gray-200 p-6 <?= $invitation['status'] == 'invited' ? 'border-indigo-300 bg-indigo-50/30' : '' ?>">
                <div class="flex items-start justify-between">
                    <div>
                        <h4 class="font-semibold text-[#0b2b3f]"><?= htmlspecialchars($invitation['manuscript_title']) ?></h4>
                        <p class="text-sm text-gray-600 mt-1">Author: <?= htmlspecialchars($invitation['author_name'] ?? 'Unknown') ?></p>
                        <?php if ($invitation['abstract']): ?>
                            <p class="text-sm text-gray-500 mt-2"><?= htmlspecialchars(substr($invitation['abstract'], 0, 150)) ?>...</p>
                        <?php endif; ?>
                        <div class="flex items-center gap-3 text-xs text-gray-400 mt-2">
                            <span>Invited: <?= formatDate($invitation['invitation_date']) ?></span>
                            <?php if ($invitation['due_date']): ?>
                                <span>Due: <?= formatDate($invitation['due_date']) ?></span>
                            <?php endif; ?>
                            <span class="px-2 py-0.5 rounded-full <?= getStatusBadge($invitation['status']) ?>">
                                <?= ucfirst($invitation['status']) ?>
                            </span>
                        </div>
                    </div>
                    <?php if ($invitation['status'] == 'invited'): ?>
                    <div class="flex gap-2">
                        <form method="POST">
                            <input type="hidden" name="review_id" value="<?= $invitation['id'] ?>">
                            <button type="submit" name="action" value="accept" 
                                    class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition text-sm">
                                <i class="fas fa-check mr-1"></i> Accept
                            </button>
                        </form>
                        <button onclick="openDeclineModal(<?= $invitation['id'] ?>)" 
                                class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition text-sm">
                            <i class="fas fa-times mr-1"></i> Decline
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Decline Modal -->
<div id="declineModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-[#0b2b3f]">Decline Review</h3>
            <button onclick="closeDeclineModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="review_id" id="declineReviewId">
            <input type="hidden" name="action" value="decline">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Declining</label>
                    <textarea name="reason" rows="3"
                              placeholder="Please provide a reason for declining this review..."
                              class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none"></textarea>
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-red-700 transition flex-1">
                    <i class="fas fa-times mr-2"></i> Decline
                </button>
                <button type="button" onclick="closeDeclineModal()" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg font-semibold hover:bg-gray-200 transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openDeclineModal(reviewId) {
    document.getElementById('declineReviewId').value = reviewId;
    document.getElementById('declineModal').classList.remove('hidden');
}

function closeDeclineModal() {
    document.getElementById('declineModal').classList.add('hidden');
}
</script>
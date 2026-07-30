<?php
// modules/editor/pages/acceptance.php - Final Acceptance
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

$db = getDB();
$currentUser = getCurrentUser();
$message = '';
$error = '';

// Handle acceptance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_manuscript'])) {
    $manuscript_id = (int)$_POST['manuscript_id'];
    $comments = trim($_POST['comments'] ?? '');
    
    $stmt = $db->prepare("UPDATE manuscripts SET status = 'accepted', accepted_at = NOW(), updated_at = NOW() WHERE id = ?");
    if ($stmt->execute([$manuscript_id])) {
        $message = 'Manuscript accepted successfully!';
        logAction($currentUser['id'], 'accept_manuscript', 'manuscripts', $manuscript_id);
        
        // Notify author
        $manuscript = getManuscript($manuscript_id);
        if ($manuscript && $manuscript['corresponding_author_id']) {
            createNotification(
                $manuscript['corresponding_author_id'],
                'accepted',
                'Manuscript Accepted',
                'Your manuscript "' . $manuscript['title'] . '" has been accepted!',
                SITE_URL . '/author?action=track&id=' . $manuscript_id
            );
        }
    } else {
        $error = 'Failed to accept manuscript.';
    }
}

// Get manuscripts ready for acceptance (under review with completed reviews)
$stmt = $db->query("
    SELECT m.*, u.full_name as author_name,
           (SELECT COUNT(*) FROM reviews WHERE manuscript_id = m.id AND status = 'completed') as completed_reviews,
           (SELECT COUNT(*) FROM reviews WHERE manuscript_id = m.id AND recommendation = 'accept') as accept_recommendations
    FROM manuscripts m
    LEFT JOIN users u ON m.corresponding_author_id = u.id
    WHERE m.status IN ('under_review', 'revision_required')
    ORDER BY m.submission_date DESC
");
$manuscripts = $stmt->fetchAll();

// Get already accepted manuscripts
$stmt = $db->query("
    SELECT m.*, u.full_name as author_name
    FROM manuscripts m
    LEFT JOIN users u ON m.corresponding_author_id = u.id
    WHERE m.status = 'accepted'
    ORDER BY m.accepted_at DESC
");
$acceptedManuscripts = $stmt->fetchAll();
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Final Acceptance</h2>
            <p class="text-gray-500 text-sm mt-1">Finalize manuscript acceptances</p>
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

    <div class="grid md:grid-cols-2 gap-6">
        <!-- Ready for Acceptance -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">Ready for Acceptance</h3>
            <?php if (empty($manuscripts)): ?>
                <p class="text-sm text-gray-500">No manuscripts ready for acceptance.</p>
            <?php else: ?>
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    <?php foreach ($manuscripts as $manuscript): ?>
                    <div class="border border-gray-200 rounded-lg p-3 hover:shadow-md transition">
                        <p class="font-medium text-[#0b2b3f]"><?= htmlspecialchars(substr($manuscript['title'], 0, 40)) ?>...</p>
                        <div class="flex items-center gap-2 text-xs text-gray-500 mt-1">
                            <span>Author: <?= htmlspecialchars($manuscript['author_name'] ?? 'Unknown') ?></span>
                            <span>· Reviews: <?= $manuscript['completed_reviews'] ?? 0 ?></span>
                            <?php if ($manuscript['accept_recommendations'] > 0): ?>
                                <span class="text-green-600"><?= $manuscript['accept_recommendations'] ?> accept recommendations</span>
                            <?php endif; ?>
                        </div>
                        <form method="POST" class="mt-2">
                            <input type="hidden" name="manuscript_id" value="<?= $manuscript['id'] ?>">
                            <div class="flex gap-2">
                                <button type="submit" name="accept_manuscript" class="bg-green-600 text-white px-4 py-1 rounded-lg hover:bg-green-700 transition text-sm"
                                        onclick="return confirm('Are you sure you want to accept this manuscript?')">
                                    <i class="fas fa-check mr-1"></i> Accept
                                </button>
                                <a href="/jms/editor?action=decision&id=<?= $manuscript['id'] ?>" 
                                   class="bg-gray-100 text-gray-700 px-4 py-1 rounded-lg hover:bg-gray-200 transition text-sm">
                                    <i class="fas fa-edit mr-1"></i> Review
                                </a>
                            </div>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Accepted Manuscripts -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">Accepted Manuscripts</h3>
            <?php if (empty($acceptedManuscripts)): ?>
                <p class="text-sm text-gray-500">No accepted manuscripts yet.</p>
            <?php else: ?>
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    <?php foreach ($acceptedManuscripts as $manuscript): ?>
                    <div class="border border-green-200 bg-green-50 rounded-lg p-3">
                        <p class="font-medium text-[#0b2b3f]"><?= htmlspecialchars(substr($manuscript['title'], 0, 40)) ?>...</p>
                        <div class="flex items-center gap-2 text-xs text-gray-500 mt-1">
                            <span>Author: <?= htmlspecialchars($manuscript['author_name'] ?? 'Unknown') ?></span>
                            <span>· Accepted: <?= formatDate($manuscript['accepted_at']) ?></span>
                        </div>
                        <div class="mt-2 flex gap-2">
                            <a href="/jms/editor?action=decision&id=<?= $manuscript['id'] ?>" 
                               class="text-indigo-600 hover:text-indigo-800 text-sm">
                                <i class="fas fa-eye mr-1"></i> View
                            </a>
                            <a href="/jms/publisher?action=doi&id=<?= $manuscript['id'] ?>" 
                               class="text-blue-600 hover:text-blue-800 text-sm">
                                <i class="fas fa-link mr-1"></i> Assign DOI
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
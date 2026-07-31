<?php
// modules/author/pages/track.php - Track Status
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

$db = getDB();
$currentUser = getCurrentUser();

$manuscriptId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get all user's manuscripts or specific one
if ($manuscriptId > 0) {
    $stmt = $db->prepare("
        SELECT m.*, 
               (SELECT COUNT(*) FROM reviews WHERE manuscript_id = m.id) as review_count,
               (SELECT COUNT(*) FROM reviews WHERE manuscript_id = m.id AND status = 'completed') as completed_reviews,
               (SELECT COUNT(*) FROM reviews WHERE manuscript_id = m.id AND recommendation IS NOT NULL) as recommendations
        FROM manuscripts m
        WHERE m.id = ? AND m.corresponding_author_id = ?
    ");
    $stmt->execute([$manuscriptId, $currentUser['id']]);
    $manuscript = $stmt->fetch();
    
    if ($manuscript) {
        // Get reviews
        $stmt = $db->prepare("
            SELECT r.*, u.full_name as reviewer_name
            FROM reviews r
            JOIN users u ON r.reviewer_id = u.id
            WHERE r.manuscript_id = ?
        ");
        $stmt->execute([$manuscriptId]);
        $reviews = $stmt->fetchAll();
    } else {
        $manuscript = null;
        $reviews = [];
    }
} else {
    // Get all manuscripts
    $stmt = $db->prepare("
        SELECT m.*, 
               (SELECT COUNT(*) FROM reviews WHERE manuscript_id = m.id) as review_count,
               (SELECT COUNT(*) FROM reviews WHERE manuscript_id = m.id AND status = 'completed') as completed_reviews
        FROM manuscripts m
        WHERE m.corresponding_author_id = ?
        ORDER BY m.created_at DESC
    ");
    $stmt->execute([$currentUser['id']]);
    $manuscripts = $stmt->fetchAll();
}

$statusSteps = [
    'draft' => ['label' => 'Draft', 'icon' => 'fa-pencil-alt', 'color' => 'gray'],
    'submitted' => ['label' => 'Submitted', 'icon' => 'fa-paper-plane', 'color' => 'blue'],
    'under_review' => ['label' => 'Under Review', 'icon' => 'fa-spinner', 'color' => 'yellow'],
    'revision_required' => ['label' => 'Revision Required', 'icon' => 'fa-edit', 'color' => 'orange'],
    'accepted' => ['label' => 'Accepted', 'icon' => 'fa-check-circle', 'color' => 'green'],
    'rejected' => ['label' => 'Rejected', 'icon' => 'fa-times-circle', 'color' => 'red'],
    'published' => ['label' => 'Published', 'icon' => 'fa-check-double', 'color' => 'purple'],
    'withdrawn' => ['label' => 'Withdrawn', 'icon' => 'fa-times', 'color' => 'gray']
];

$statusOrder = ['draft', 'submitted', 'under_review', 'revision_required', 'accepted', 'published'];
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Track Status</h2>
            <p class="text-gray-500 text-sm mt-1">Monitor the progress of your submissions</p>
        </div>
        <a href="/jms/author" class="text-indigo-600 hover:text-indigo-800 text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back
        </a>
    </div>
    <div class="h-1 w-20 bg-indigo-200 rounded-full"></div>

    <?php if ($manuscriptId > 0 && isset($manuscript)): ?>
        <!-- Single Manuscript View -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-[#0b2b3f]"><?= htmlspecialchars($manuscript['title']) ?></h3>
                <span class="px-3 py-1 rounded-full text-sm font-medium <?= getStatusBadge($manuscript['status']) ?>">
                    <?= ucfirst(str_replace('_', ' ', $manuscript['status'])) ?>
                </span>
            </div>
            
            <!-- Status Timeline -->
            <div class="relative">
                <div class="flex items-center justify-between mb-8">
                    <?php 
                    $currentStatusIndex = array_search($manuscript['status'], $statusOrder);
                    if ($currentStatusIndex === false) $currentStatusIndex = 0;
                    ?>
                    <?php foreach ($statusOrder as $index => $status): ?>
                        <?php if ($status != 'published' || $manuscript['status'] == 'published'): ?>
                        <div class="flex flex-col items-center flex-1">
                            <div class="relative">
                                <div class="w-12 h-12 rounded-full flex items-center justify-center 
                                    <?= $index <= $currentStatusIndex ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-400' ?>">
                                    <i class="fas <?= $statusSteps[$status]['icon'] ?>"></i>
                                </div>
                                <?php if ($index < count($statusOrder) - 1): ?>
                                    <div class="absolute top-1/2 left-full w-full h-1 -translate-y-1/2 
                                        <?= $index < $currentStatusIndex ? 'bg-green-500' : 'bg-gray-200' ?>">
                                    </div>
                                <?php endif; ?>
                            </div>
                            <span class="text-xs mt-2 text-center font-medium <?= $index <= $currentStatusIndex ? 'text-[#0b2b3f]' : 'text-gray-400' ?>">
                                <?= $statusSteps[$status]['label'] ?>
                            </span>
                            <?php if ($index == $currentStatusIndex): ?>
                                <span class="text-xs text-green-600 font-medium mt-1">Current</span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Manuscript Details -->
            <div class="grid md:grid-cols-2 gap-4 mt-6 border-t border-gray-200 pt-4">
                <div>
                    <p class="text-sm text-gray-500">DOI</p>
                    <p class="font-medium"><?= htmlspecialchars($manuscript['doi'] ?? 'Not assigned') ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Submitted</p>
                    <p class="font-medium"><?= formatDate($manuscript['submission_date']) ?></p>
                </div>
                <?php if ($manuscript['accepted_at']): ?>
                <div>
                    <p class="text-sm text-gray-500">Accepted</p>
                    <p class="font-medium"><?= formatDate($manuscript['accepted_at']) ?></p>
                </div>
                <?php endif; ?>
                <?php if ($manuscript['publication_date']): ?>
                <div>
                    <p class="text-sm text-gray-500">Published</p>
                    <p class="font-medium"><?= formatDate($manuscript['publication_date']) ?></p>
                </div>
                <?php endif; ?>
                <div>
                    <p class="text-sm text-gray-500">Reviews</p>
                    <p class="font-medium"><?= $manuscript['completed_reviews'] ?? 0 ?> / <?= $manuscript['review_count'] ?? 0 ?></p>
                </div>
            </div>

            <!-- Reviews -->
            <?php if (!empty($reviews)): ?>
            <div class="mt-4 border-t border-gray-200 pt-4">
                <h4 class="font-semibold text-[#0b2b3f] mb-3">Review History</h4>
                <div class="space-y-2">
                    <?php foreach ($reviews as $review): ?>
                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                        <div>
                            <p class="text-sm font-medium"><?= htmlspecialchars($review['reviewer_name']) ?></p>
                            <p class="text-xs text-gray-500">Status: <?= ucfirst($review['status']) ?></p>
                        </div>
                        <div class="text-right">
                            <?php if ($review['recommendation']): ?>
                                <span class="text-xs font-medium text-indigo-600"><?= ucfirst(str_replace('_', ' ', $review['recommendation'])) ?></span>
                            <?php endif; ?>
                            <?php if ($review['completed_date']): ?>
                                <p class="text-xs text-gray-400"><?= formatDate($review['completed_date']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="mt-4 flex gap-3">
                <a href="/jms/author?action=new-submission&edit=<?= $manuscript['id'] ?>" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition text-sm">
                    <i class="fas fa-edit mr-1"></i> Edit
                </a>
                <?php if ($manuscript['status'] == 'submitted' || $manuscript['status'] == 'under_review'): ?>
                    <form method="POST" action="/jms/author?action=withdraw" class="inline" 
                          onsubmit="return confirm('Are you sure you want to withdraw this manuscript?')">
                        <input type="hidden" name="manuscript_id" value="<?= $manuscript['id'] ?>">
                        <button type="submit" name="withdraw_manuscript" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition text-sm">
                            <i class="fas fa-times mr-1"></i> Withdraw
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-4">
            <a href="/jms/author?action=track" class="text-indigo-600 hover:text-indigo-800 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back to all submissions
            </a>
        </div>

    <?php else: ?>
        <!-- All Manuscripts List -->
        <?php if (empty($manuscripts)): ?>
            <div class="text-center py-12">
                <i class="fas fa-inbox text-5xl text-gray-300 mb-4"></i>
                <p class="text-gray-500">You haven't submitted any manuscripts yet.</p>
                <a href="/jms/author?action=new-submission" class="mt-3 inline-block bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                    <i class="fas fa-plus mr-2"></i> Start New Submission
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($manuscripts as $manuscript): ?>
                <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h4 class="font-semibold text-[#0b2b3f]"><?= htmlspecialchars($manuscript['title']) ?></h4>
                            <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500 mt-1">
                                <span class="px-2 py-0.5 rounded-full <?= getStatusBadge($manuscript['status']) ?>">
                                    <?= ucfirst(str_replace('_', ' ', $manuscript['status'])) ?>
                                </span>
                                <span>Submitted: <?= formatDate($manuscript['submission_date']) ?></span>
                                <?php if ($manuscript['review_count'] > 0): ?>
                                    <span>Reviews: <?= $manuscript['completed_reviews'] ?? 0 ?>/<?= $manuscript['review_count'] ?></span>
                                <?php endif; ?>
                                <?php if ($manuscript['doi']): ?>
                                    <span class="text-indigo-600">DOI: <?= htmlspecialchars($manuscript['doi']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <a href="/jms/author?action=track&id=<?= $manuscript['id'] ?>" class="text-indigo-600 hover:text-indigo-800 text-sm px-3 py-1 rounded hover:bg-indigo-50 transition">
                            <i class="fas fa-chevron-right"></i> View Details
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-4 text-sm text-gray-400">
                Showing <?= count($manuscripts) ?> manuscripts
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
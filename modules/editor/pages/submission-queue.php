<?php
// modules/editor/pages/submission-queue.php - Submission Queue
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

$db = getDB();
$currentUser = getCurrentUser();

// Get filter
$filter = $_GET['filter'] ?? 'all';

// Build query
$sql = "SELECT m.*, u.full_name as author_name, u.email as author_email,
        (SELECT COUNT(*) FROM reviews WHERE manuscript_id = m.id) as review_count,
        (SELECT COUNT(*) FROM reviews WHERE manuscript_id = m.id AND status = 'completed') as completed_reviews
        FROM manuscripts m
        LEFT JOIN users u ON m.corresponding_author_id = u.id
        WHERE m.status IN ('submitted', 'under_review', 'revision_required', 'accepted')";

if ($filter == 'submitted') {
    $sql .= " AND m.status = 'submitted'";
} elseif ($filter == 'under_review') {
    $sql .= " AND m.status = 'under_review'";
} elseif ($filter == 'revision_required') {
    $sql .= " AND m.status = 'revision_required'";
} elseif ($filter == 'accepted') {
    $sql .= " AND m.status = 'accepted'";
}

$sql .= " ORDER BY m.submission_date DESC";

$stmt = $db->prepare($sql);
$stmt->execute();
$manuscripts = $stmt->fetchAll();

// Get counts
$statusCounts = [];
$stmt = $db->query("
    SELECT status, COUNT(*) as count 
    FROM manuscripts 
    WHERE status IN ('submitted', 'under_review', 'revision_required', 'accepted')
    GROUP BY status
");
while ($row = $stmt->fetch()) {
    $statusCounts[$row['status']] = $row['count'];
}
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Submission Queue</h2>
            <p class="text-gray-500 text-sm mt-1">Manage all incoming submissions</p>
        </div>
        <a href="/jms/editor" class="text-indigo-600 hover:text-indigo-800 text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
        </a>
    </div>
    <div class="h-1 w-20 bg-indigo-200 rounded-full"></div>

    <!-- Filter Tabs -->
    <div class="flex flex-wrap gap-2 border-b border-gray-200 pb-4">
        <a href="/jms/editor?action=submission-queue&filter=all" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $filter == 'all' ? 'bg-[#0b2b3f] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">
            All (<?= array_sum($statusCounts) ?>)
        </a>
        <a href="/jms/editor?action=submission-queue&filter=submitted" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $filter == 'submitted' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-600 hover:bg-blue-100' ?>">
            New (<?= $statusCounts['submitted'] ?? 0 ?>)
        </a>
        <a href="/jms/editor?action=submission-queue&filter=under_review" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $filter == 'under_review' ? 'bg-yellow-600 text-white' : 'bg-yellow-50 text-yellow-600 hover:bg-yellow-100' ?>">
            Under Review (<?= $statusCounts['under_review'] ?? 0 ?>)
        </a>
        <a href="/jms/editor?action=submission-queue&filter=revision_required" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $filter == 'revision_required' ? 'bg-orange-600 text-white' : 'bg-orange-50 text-orange-600 hover:bg-orange-100' ?>">
            Revisions (<?= $statusCounts['revision_required'] ?? 0 ?>)
        </a>
        <a href="/jms/editor?action=submission-queue&filter=accepted" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $filter == 'accepted' ? 'bg-green-600 text-white' : 'bg-green-50 text-green-600 hover:bg-green-100' ?>">
            Accepted (<?= $statusCounts['accepted'] ?? 0 ?>)
        </a>
    </div>

    <?php if (empty($manuscripts)): ?>
        <div class="text-center py-12">
            <i class="fas fa-inbox text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No submissions in queue.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Manuscript</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Author</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Reviews</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Submitted</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($manuscripts as $manuscript): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                        <td class="py-2 px-3">
                            <p class="font-medium text-[#0b2b3f]"><?= htmlspecialchars(substr($manuscript['title'], 0, 40)) ?>...</p>
                            <?php if ($manuscript['doi']): ?>
                                <p class="text-xs text-indigo-600">DOI: <?= htmlspecialchars($manuscript['doi']) ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="py-2 px-3">
                            <p class="text-sm"><?= htmlspecialchars($manuscript['author_name'] ?? 'Unknown') ?></p>
                            <p class="text-xs text-gray-400"><?= htmlspecialchars($manuscript['author_email'] ?? '') ?></p>
                        </td>
                        <td class="py-2 px-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= getStatusBadge($manuscript['status']) ?>">
                                <?= ucfirst(str_replace('_', ' ', $manuscript['status'])) ?>
                            </span>
                        </td>
                        <td class="py-2 px-3 text-gray-600">
                            <?= $manuscript['completed_reviews'] ?? 0 ?>/<?= $manuscript['review_count'] ?? 0 ?>
                        </td>
                        <td class="py-2 px-3 text-gray-600">
                            <?= formatDate($manuscript['submission_date']) ?>
                            <br>
                            <span class="text-xs text-gray-400"><?= timeAgo($manuscript['submission_date']) ?></span>
                        </td>
                        <td class="py-2 px-3">
                            <div class="flex gap-2">
                                <a href="/jms/editor?action=decision&id=<?= $manuscript['id'] ?>" 
                                   class="text-indigo-600 hover:text-indigo-800 text-sm" title="Review">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($manuscript['status'] == 'submitted'): ?>
                                    <a href="/jms/editor?action=editor-assignment&id=<?= $manuscript['id'] ?>" 
                                       class="text-blue-600 hover:text-blue-800 text-sm" title="Assign Editor">
                                        <i class="fas fa-user-plus"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-4 text-sm text-gray-400">
            Showing <?= count($manuscripts) ?> submissions
        </div>
    <?php endif; ?>
</div>
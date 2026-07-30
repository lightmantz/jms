<?php
// modules/admin/pages/assignments.php - Manage Assignments
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

requireRole(['admin']);

$db = getDB();
$message = '';
$error = '';

// Get filter parameters
$filterType = $_GET['filter'] ?? 'all'; // all, editor, reviewer
$filterStatus = $_GET['status'] ?? 'all'; // all, pending, completed, overdue

// Build query for assignments
$sql = "SELECT 
            m.id as manuscript_id,
            m.title as manuscript_title,
            m.status as manuscript_status,
            m.submission_date,
            m.doi,
            u.full_name as author_name,
            u.email as author_email,
            e.full_name as editor_name,
            r.id as review_id,
            r.reviewer_id,
            rv.full_name as reviewer_name,
            rv.email as reviewer_email,
            r.status as review_status,
            r.invitation_date,
            r.accepted_date,
            r.due_date,
            r.completed_date,
            r.recommendation
        FROM manuscripts m
        LEFT JOIN users u ON m.corresponding_author_id = u.id
        LEFT JOIN users e ON m.editor_assigned_id = e.id
        LEFT JOIN reviews r ON m.id = r.manuscript_id
        LEFT JOIN users rv ON r.reviewer_id = rv.id
        WHERE 1=1";

$params = [];

// Apply filters
if ($filterType == 'editor') {
    $sql .= " AND m.editor_assigned_id IS NOT NULL";
} elseif ($filterType == 'reviewer') {
    $sql .= " AND r.reviewer_id IS NOT NULL";
}

if ($filterStatus == 'pending') {
    $sql .= " AND r.status IN ('invited', 'accepted')";
} elseif ($filterStatus == 'completed') {
    $sql .= " AND r.status = 'completed'";
} elseif ($filterStatus == 'overdue') {
    $sql .= " AND r.due_date < CURDATE() AND r.status IN ('invited', 'accepted')";
}

$sql .= " ORDER BY m.submission_date DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$assignments = $stmt->fetchAll();

// Get statistics
$stats = [
    'total' => count($assignments),
    'with_editor' => count(array_filter($assignments, function($a) { return !empty($a['editor_name']); })),
    'with_reviewers' => count(array_filter($assignments, function($a) { return !empty($a['reviewer_name']); })),
    'pending_reviews' => count(array_filter($assignments, function($a) { return in_array($a['review_status'], ['invited', 'accepted']); })),
    'completed_reviews' => count(array_filter($assignments, function($a) { return $a['review_status'] == 'completed'; })),
    'overdue' => count(array_filter($assignments, function($a) { 
        return $a['due_date'] && $a['due_date'] < date('Y-m-d') && in_array($a['review_status'], ['invited', 'accepted']); 
    }))
];
?>
<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Assignments</h2>
            <p class="text-gray-500 text-sm mt-1">Track editor and reviewer assignments</p>
        </div>
        <div class="flex gap-3">
            <a href="/jms/admin" class="text-indigo-600 hover:text-indigo-800 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>
    </div>
    <div class="h-1 w-20 bg-indigo-200 rounded-full mb-6"></div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-indigo-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-indigo-700"><?= $stats['total'] ?></p>
            <p class="text-xs text-indigo-600">Total Assignments</p>
        </div>
        <div class="bg-blue-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-blue-700"><?= $stats['with_editor'] ?></p>
            <p class="text-xs text-blue-600">With Editor</p>
        </div>
        <div class="bg-yellow-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-yellow-700"><?= $stats['pending_reviews'] ?></p>
            <p class="text-xs text-yellow-600">Pending Reviews</p>
        </div>
        <div class="bg-red-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-red-700"><?= $stats['overdue'] ?></p>
            <p class="text-xs text-red-600">Overdue</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-3 mb-6 border-b border-gray-200 pb-4">
        <a href="/jms/admin?action=assignments&filter=all&status=all" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $filterType == 'all' && $filterStatus == 'all' ? 'bg-[#0b2b3f] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">
            All
        </a>
        <a href="/jms/admin?action=assignments&filter=editor&status=all" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $filterType == 'editor' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-600 hover:bg-blue-100' ?>">
            Editor Assignments
        </a>
        <a href="/jms/admin?action=assignments&filter=reviewer&status=all" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $filterType == 'reviewer' ? 'bg-yellow-600 text-white' : 'bg-yellow-50 text-yellow-600 hover:bg-yellow-100' ?>">
            Reviewer Assignments
        </a>
        <span class="w-px bg-gray-200 mx-2"></span>
        <a href="/jms/admin?action=assignments&filter=<?= $filterType ?>&status=pending" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $filterStatus == 'pending' ? 'bg-orange-600 text-white' : 'bg-orange-50 text-orange-600 hover:bg-orange-100' ?>">
            Pending
        </a>
        <a href="/jms/admin?action=assignments&filter=<?= $filterType ?>&status=completed" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $filterStatus == 'completed' ? 'bg-green-600 text-white' : 'bg-green-50 text-green-600 hover:bg-green-100' ?>">
            Completed
        </a>
        <a href="/jms/admin?action=assignments&filter=<?= $filterType ?>&status=overdue" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $filterStatus == 'overdue' ? 'bg-red-600 text-white' : 'bg-red-50 text-red-600 hover:bg-red-100' ?>">
            Overdue
        </a>
    </div>

    <?php if ($message): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm mb-6">
            <i class="fas fa-check-circle mr-2"></i> <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($assignments)): ?>
        <div class="text-center py-12">
            <i class="fas fa-tasks text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No assignments found matching the current filters.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Manuscript</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Author</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Editor</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Reviewer</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Due Date</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assignments as $assignment): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                        <td class="py-3 px-4">
                            <p class="font-medium text-[#0b2b3f] text-sm"><?= htmlspecialchars(substr($assignment['manuscript_title'] ?? '', 0, 40)) ?>...</p>
                            <?php if ($assignment['doi']): ?>
                                <p class="text-xs text-indigo-600">DOI: <?= htmlspecialchars($assignment['doi']) ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4">
                            <p class="text-sm text-gray-600"><?= htmlspecialchars($assignment['author_name'] ?? 'Unknown') ?></p>
                            <p class="text-xs text-gray-400"><?= htmlspecialchars($assignment['author_email'] ?? '') ?></p>
                        </td>
                        <td class="py-3 px-4">
                            <?php if ($assignment['editor_name']): ?>
                                <p class="text-sm text-blue-600"><?= htmlspecialchars($assignment['editor_name']) ?></p>
                            <?php else: ?>
                                <span class="text-xs text-gray-400">Not assigned</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4">
                            <?php if ($assignment['reviewer_name']): ?>
                                <p class="text-sm text-yellow-600"><?= htmlspecialchars($assignment['reviewer_name']) ?></p>
                            <?php else: ?>
                                <span class="text-xs text-gray-400">Not assigned</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4">
                            <?php if ($assignment['review_status']): ?>
                                <span class="px-2 py-1 rounded-full text-xs font-medium <?= getStatusBadge($assignment['review_status']) ?>">
                                    <?= ucfirst($assignment['review_status']) ?>
                                </span>
                                <?php if ($assignment['recommendation']): ?>
                                    <p class="text-xs text-gray-400 mt-1">Rec: <?= ucfirst(str_replace('_', ' ', $assignment['recommendation'])) ?></p>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-xs text-gray-400">No review</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4">
                            <?php if ($assignment['due_date']): ?>
                                <p class="text-sm <?= $assignment['due_date'] < date('Y-m-d') && in_array($assignment['review_status'], ['invited', 'accepted']) ? 'text-red-600 font-medium' : 'text-gray-600' ?>">
                                    <?= formatDate($assignment['due_date']) ?>
                                    <?php if ($assignment['due_date'] < date('Y-m-d') && in_array($assignment['review_status'], ['invited', 'accepted'])): ?>
                                        <span class="block text-xs text-red-500">Overdue!</span>
                                    <?php endif; ?>
                                </p>
                            <?php else: ?>
                                <span class="text-xs text-gray-400">Not set</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex gap-2">
                                <a href="/jms/admin?action=manuscript&id=<?= $assignment['manuscript_id'] ?>" 
                                   class="text-indigo-600 hover:text-indigo-800 text-sm" title="View Manuscript">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($assignment['review_id']): ?>
                                    <a href="/jms/admin?action=review&id=<?= $assignment['review_id'] ?>" 
                                       class="text-green-600 hover:text-green-800 text-sm" title="View Review">
                                        <i class="fas fa-star"></i>
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
            Showing <?= count($assignments) ?> assignments
        </div>
    <?php endif; ?>
</div>
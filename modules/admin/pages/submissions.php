<?php
// modules/admin/pages/submissions.php - Manage All Submissions
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

requireRole(['admin']);

$db = getDB();
$message = '';
$error = '';

// Get the subaction (status filter)
$subaction = $_GET['subaction'] ?? 'all';

// Map subaction to status
$statusMap = [
    'new' => 'submitted',
    'under_review' => 'under_review',
    'revisions' => 'revision_required',
    'accepted' => 'accepted',
    'rejected' => 'rejected',
    'published' => 'published',
    'all' => null
];

$status = $statusMap[$subaction] ?? null;

// Build query
$sql = "SELECT m.*, u.full_name as author_name, u.email as author_email,
        (SELECT COUNT(*) FROM reviews WHERE manuscript_id = m.id) as review_count,
        (SELECT COUNT(*) FROM reviews WHERE manuscript_id = m.id AND status = 'completed') as completed_reviews,
        (SELECT full_name FROM users WHERE id = m.editor_assigned_id) as editor_name,
        v.volume_number, i.issue_number
        FROM manuscripts m
        LEFT JOIN users u ON m.corresponding_author_id = u.id
        LEFT JOIN issues i ON m.issue_id = i.id
        LEFT JOIN volumes v ON i.volume_id = v.id";

$params = [];

if ($status) {
    $sql .= " WHERE m.status = ?";
    $params[] = $status;
} else {
    $sql .= " WHERE m.status IN ('submitted', 'under_review', 'revision_required', 'accepted', 'rejected', 'published')";
}

$sql .= " ORDER BY m.submission_date DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$submissions = $stmt->fetchAll();

// Get counts for each status
$statusCounts = [];
$stmt = $db->query("SELECT status, COUNT(*) as count FROM manuscripts GROUP BY status");
while ($row = $stmt->fetch()) {
    $statusCounts[$row['status']] = $row['count'];
}

// Define status labels
$statusLabels = [
    'submitted' => 'New Submissions',
    'under_review' => 'Under Review',
    'revision_required' => 'Revisions Required',
    'accepted' => 'Accepted',
    'rejected' => 'Rejected',
    'published' => 'Published'
];

$statusColors = [
    'submitted' => 'blue',
    'under_review' => 'yellow',
    'revision_required' => 'orange',
    'accepted' => 'green',
    'rejected' => 'red',
    'published' => 'purple'
];

$currentLabel = $subaction == 'all' ? 'All Submissions' : ($statusLabels[$status] ?? 'Submissions');
?>
<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]"><?= htmlspecialchars($currentLabel) ?></h2>
            <p class="text-gray-500 text-sm mt-1">Manage and track all manuscript submissions</p>
        </div>
        <div class="flex gap-3">
            <a href="/jms/admin" class="text-indigo-600 hover:text-indigo-800 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
            <a href="/jms/admin?action=submissions&subaction=new" class="bg-[#0b2b3f] text-white px-4 py-2 rounded-lg hover:bg-[#123a4f] transition text-sm">
                <i class="fas fa-plus mr-1"></i> New Submission
            </a>
        </div>
    </div>
    <div class="h-1 w-20 bg-indigo-200 rounded-full mb-6"></div>

    <!-- Status Filter Tabs -->
    <div class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 pb-4">
        <a href="/jms/admin?action=submissions&subaction=all" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $subaction == 'all' ? 'bg-[#0b2b3f] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">
            All (<?= array_sum($statusCounts) ?>)
        </a>
        <a href="/jms/admin?action=submissions&subaction=new" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $subaction == 'new' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-600 hover:bg-blue-100' ?>">
            New (<?= $statusCounts['submitted'] ?? 0 ?>)
        </a>
        <a href="/jms/admin?action=submissions&subaction=under_review" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $subaction == 'under_review' ? 'bg-yellow-600 text-white' : 'bg-yellow-50 text-yellow-600 hover:bg-yellow-100' ?>">
            Under Review (<?= $statusCounts['under_review'] ?? 0 ?>)
        </a>
        <a href="/jms/admin?action=submissions&subaction=revisions" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $subaction == 'revisions' ? 'bg-orange-600 text-white' : 'bg-orange-50 text-orange-600 hover:bg-orange-100' ?>">
            Revisions (<?= $statusCounts['revision_required'] ?? 0 ?>)
        </a>
        <a href="/jms/admin?action=submissions&subaction=accepted" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $subaction == 'accepted' ? 'bg-green-600 text-white' : 'bg-green-50 text-green-600 hover:bg-green-100' ?>">
            Accepted (<?= $statusCounts['accepted'] ?? 0 ?>)
        </a>
        <a href="/jms/admin?action=submissions&subaction=rejected" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $subaction == 'rejected' ? 'bg-red-600 text-white' : 'bg-red-50 text-red-600 hover:bg-red-100' ?>">
            Rejected (<?= $statusCounts['rejected'] ?? 0 ?>)
        </a>
        <a href="/jms/admin?action=submissions&subaction=published" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $subaction == 'published' ? 'bg-purple-600 text-white' : 'bg-purple-50 text-purple-600 hover:bg-purple-100' ?>">
            Published (<?= $statusCounts['published'] ?? 0 ?>)
        </a>
    </div>

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

    <!-- Search and Filter -->
    <div class="mb-6">
        <form method="GET" action="" class="flex flex-wrap gap-3">
            <input type="hidden" name="action" value="submissions">
            <input type="hidden" name="subaction" value="<?= htmlspecialchars($subaction) ?>">
            <div class="flex-1 min-w-[200px]">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input type="text" name="search" placeholder="Search by title, author, or DOI..." 
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                           class="w-full pl-9 pr-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none text-sm">
                </div>
            </div>
            <button type="submit" class="bg-[#0b2b3f] text-white px-4 py-2 rounded-lg hover:bg-[#123a4f] transition text-sm">
                <i class="fas fa-search mr-1"></i> Search
            </button>
            <?php if (!empty($_GET['search'])): ?>
                <a href="/jms/admin?action=submissions&subaction=<?= $subaction ?>" class="text-sm text-gray-500 hover:text-[#0b2b3f]">
                    <i class="fas fa-times mr-1"></i> Clear
                </a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (empty($submissions)): ?>
        <div class="text-center py-12">
            <i class="fas fa-inbox text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No <?= strtolower($currentLabel) ?> found.</p>
            <?php if ($subaction == 'new'): ?>
                <a href="/jms/?page=submit" class="mt-3 inline-block bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                    <i class="fas fa-plus mr-2"></i> Create New Submission
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Title / Author</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Reviews</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Submitted</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Editor</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $submission): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                        <td class="py-3 px-4">
                            <div>
                                <p class="font-medium text-[#0b2b3f] text-sm"><?= htmlspecialchars(substr($submission['title'], 0, 60)) ?>...</p>
                                <p class="text-xs text-gray-500 mt-1">
                                    <i class="fas fa-user mr-1"></i> <?= htmlspecialchars($submission['author_name'] ?? 'Unknown') ?>
                                    <?php if ($submission['doi']): ?>
                                        <span class="ml-2 text-indigo-600">DOI: <?= htmlspecialchars($submission['doi']) ?></span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 rounded-full text-xs font-medium <?= getStatusBadge($submission['status']) ?>">
                                <?= ucfirst(str_replace('_', ' ', $submission['status'])) ?>
                            </span>
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-600">
                            <?php if ($submission['review_count'] > 0): ?>
                                <?= $submission['completed_reviews'] ?? 0 ?> / <?= $submission['review_count'] ?>
                                <span class="text-xs text-gray-400">completed</span>
                            <?php else: ?>
                                <span class="text-gray-400 text-xs">No reviews</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-600">
                            <?= formatDate($submission['submission_date']) ?>
                            <br>
                            <span class="text-xs text-gray-400"><?= timeAgo($submission['submission_date']) ?></span>
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-600">
                            <?= htmlspecialchars($submission['editor_name'] ?? 'Unassigned') ?>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex gap-2">
                                <a href="/jms/admin?action=manuscript&id=<?= $submission['id'] ?>" 
                                   class="text-indigo-600 hover:text-indigo-800 text-sm" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="/jms/admin?action=manuscript&id=<?= $submission['id'] ?>&edit=1" 
                                   class="text-blue-600 hover:text-blue-800 text-sm" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($submission['status'] == 'submitted'): ?>
                                    <a href="/jms/admin?action=assign&id=<?= $submission['id'] ?>" 
                                       class="text-yellow-600 hover:text-yellow-800 text-sm" title="Assign Editor">
                                        <i class="fas fa-user-plus"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if ($submission['status'] == 'accepted'): ?>
                                    <a href="/jms/admin?action=publish&id=<?= $submission['id'] ?>" 
                                       class="text-green-600 hover:text-green-800 text-sm" title="Publish">
                                        <i class="fas fa-check-circle"></i>
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
            Showing <?= count($submissions) ?> submissions
        </div>
    <?php endif; ?>
</div>
<?php
// modules/admin/pages/manuscript.php - View/Edit Manuscript Details
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

requireRole(['admin']);

$db = getDB();
$message = '';
$error = '';

$manuscriptId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = isset($_GET['edit']) ? true : false;

if (!$manuscriptId) {
    echo '<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
            <div class="text-center py-12">
                <i class="fas fa-exclamation-triangle text-5xl text-yellow-400 mb-4"></i>
                <p class="text-gray-500">No manuscript specified.</p>
                <a href="/jms/admin?action=submissions" class="mt-3 inline-block bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Submissions
                </a>
            </div>
        </div>';
    exit;
}

// Get manuscript details
$manuscript = getManuscript($manuscriptId);
if (!$manuscript) {
    echo '<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
            <div class="text-center py-12">
                <i class="fas fa-file-alt text-5xl text-gray-300 mb-4"></i>
                <p class="text-gray-500">Manuscript not found.</p>
                <a href="/jms/admin?action=submissions" class="mt-3 inline-block bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Submissions
                </a>
            </div>
        </div>';
    exit;
}

// Get reviews
$reviews = getManuscriptReviews($manuscriptId);

// Get editors for assignment
$editors = getEditors();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = $_POST['status'] ?? '';
    $editorId = isset($_POST['editor_id']) ? (int)$_POST['editor_id'] : 0;
    
    if ($newStatus && updateManuscriptStatus($manuscriptId, $newStatus, $currentUser['id'])) {
        $message = 'Status updated successfully!';
        
        // If editor assigned
        if ($editorId > 0 && $newStatus == 'under_review') {
            assignEditor($manuscriptId, $editorId, $currentUser['id']);
            $message .= ' Editor assigned.';
        }
        
        // Refresh manuscript data
        $manuscript = getManuscript($manuscriptId);
    } else {
        $error = 'Failed to update status.';
    }
}

// Get all statuses for dropdown
$statusOptions = [
    'submitted' => 'New Submission',
    'under_review' => 'Under Review',
    'revision_required' => 'Revisions Required',
    'accepted' => 'Accepted',
    'rejected' => 'Rejected',
    'published' => 'Published'
];
?>
<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]"><?= $isEdit ? 'Edit' : 'View' ?> Manuscript</h2>
            <p class="text-gray-500 text-sm mt-1"><?= htmlspecialchars(substr($manuscript['title'], 0, 80)) ?>...</p>
        </div>
        <div class="flex gap-3">
            <a href="/jms/admin?action=submissions&subaction=<?= $manuscript['status'] ?>" class="text-indigo-600 hover:text-indigo-800 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
            <?php if ($manuscript['status'] == 'accepted'): ?>
                <a href="/jms/admin?action=publish&id=<?= $manuscriptId ?>" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition text-sm">
                    <i class="fas fa-check-circle mr-1"></i> Publish
                </a>
            <?php endif; ?>
        </div>
    </div>
    <div class="h-1 w-20 bg-indigo-200 rounded-full mb-6"></div>

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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column - Manuscript Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Status Update -->
            <div class="bg-gray-50 rounded-xl p-4">
                <form method="POST" class="flex flex-wrap gap-4 items-end">
                    <div class="flex-1 min-w-[150px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Update Status</label>
                        <select name="status" class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none text-sm">
                            <?php foreach ($statusOptions as $key => $label): ?>
                            <option value="<?= $key ?>" <?= $manuscript['status'] == $key ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex-1 min-w-[150px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Assign Editor</label>
                        <select name="editor_id" class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none text-sm">
                            <option value="0">Unassigned</option>
                            <?php foreach ($editors as $editor): ?>
                            <option value="<?= $editor['id'] ?>" <?= $manuscript['editor_assigned_id'] == $editor['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($editor['full_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="update_status" class="bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition text-sm">
                        <i class="fas fa-save mr-1"></i> Update
                    </button>
                </form>
            </div>

            <!-- Manuscript Information -->
            <div class="border border-gray-200 rounded-xl p-4">
                <h3 class="font-semibold text-[#0b2b3f] mb-3">Manuscript Information</h3>
                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase">Title</label>
                        <p class="text-sm font-medium text-[#0b2b3f]"><?= htmlspecialchars($manuscript['title']) ?></p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase">Abstract</label>
                        <p class="text-sm text-gray-600"><?= nl2br(htmlspecialchars($manuscript['abstract'] ?? 'No abstract provided')) ?></p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-medium text-gray-500 uppercase">Article Type</label>
                            <p class="text-sm text-gray-600"><?= ucfirst(str_replace('_', ' ', $manuscript['article_type'] ?? 'N/A')) ?></p>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-500 uppercase">Submission Type</label>
                            <p class="text-sm text-gray-600"><?= ucfirst(str_replace('_', ' ', $manuscript['submission_type'] ?? 'N/A')) ?></p>
                        </div>
                    </div>
                    <?php if ($manuscript['doi']): ?>
                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase">DOI</label>
                        <p class="text-sm text-indigo-600"><?= htmlspecialchars($manuscript['doi']) ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($manuscript['funding_source']): ?>
                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase">Funding Source</label>
                        <p class="text-sm text-gray-600"><?= htmlspecialchars($manuscript['funding_source']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Author Information -->
            <div class="border border-gray-200 rounded-xl p-4">
                <h3 class="font-semibold text-[#0b2b3f] mb-3">Author Information</h3>
                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase">Corresponding Author</label>
                        <p class="text-sm font-medium text-[#0b2b3f]"><?= htmlspecialchars($manuscript['author_name'] ?? 'Unknown') ?></p>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars($manuscript['author_email'] ?? '') ?></p>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars($manuscript['author_institution'] ?? '') ?></p>
                    </div>
                </div>
            </div>

            <!-- Reviews -->
            <div class="border border-gray-200 rounded-xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-[#0b2b3f]">Reviews</h3>
                    <a href="/jms/admin?action=assign-reviewer&id=<?= $manuscriptId ?>" class="text-sm text-indigo-600 hover:underline">
                        <i class="fas fa-user-plus mr-1"></i> Assign Reviewer
                    </a>
                </div>
                <?php if (empty($reviews)): ?>
                    <p class="text-sm text-gray-500">No reviews assigned yet.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($reviews as $review): ?>
                        <div class="border-b border-gray-100 pb-3 last:border-0">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium"><?= htmlspecialchars($review['reviewer_name']) ?></p>
                                    <p class="text-xs text-gray-500"><?= htmlspecialchars($review['reviewer_institution'] ?? '') ?></p>
                                </div>
                                <div class="text-right">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium <?= getStatusBadge($review['status']) ?>">
                                        <?= ucfirst($review['status']) ?>
                                    </span>
                                    <?php if ($review['recommendation']): ?>
                                        <p class="text-xs text-gray-500 mt-1">Recommendation: <?= ucfirst(str_replace('_', ' ', $review['recommendation'])) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column - Meta Information -->
        <div class="space-y-6">
            <!-- Status Card -->
            <div class="border border-gray-200 rounded-xl p-4">
                <h3 class="font-semibold text-[#0b2b3f] mb-3">Status Information</h3>
                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase">Current Status</label>
                        <p class="mt-1">
                            <span class="px-3 py-1 rounded-full text-sm font-medium <?= getStatusBadge($manuscript['status']) ?>">
                                <?= ucfirst(str_replace('_', ' ', $manuscript['status'])) ?>
                            </span>
                        </p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase">Submitted</label>
                        <p class="text-sm text-gray-600"><?= formatDate($manuscript['submitted_at'] ?? $manuscript['submission_date']) ?></p>
                    </div>
                    <?php if ($manuscript['accepted_at']): ?>
                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase">Accepted</label>
                        <p class="text-sm text-gray-600"><?= formatDate($manuscript['accepted_at']) ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($manuscript['published_at']): ?>
                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase">Published</label>
                        <p class="text-sm text-gray-600"><?= formatDate($manuscript['published_at']) ?></p>
                    </div>
                    <?php endif; ?>
                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase">Assigned Editor</label>
                        <p class="text-sm text-gray-600"><?= htmlspecialchars($manuscript['editor_name'] ?? 'Unassigned') ?></p>
                    </div>
                </div>
            </div>

            <!-- Publication Info -->
            <?php if ($manuscript['status'] == 'published'): ?>
            <div class="border border-gray-200 rounded-xl p-4">
                <h3 class="font-semibold text-[#0b2b3f] mb-3">Publication Details</h3>
                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase">Volume</label>
                        <p class="text-sm text-gray-600"><?= $manuscript['volume_number'] ?? 'N/A' ?></p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase">Issue</label>
                        <p class="text-sm text-gray-600"><?= $manuscript['issue_number'] ?? 'N/A' ?></p>
                    </div>
                    <?php if ($manuscript['page_start'] && $manuscript['page_end']): ?>
                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase">Pages</label>
                        <p class="text-sm text-gray-600"><?= $manuscript['page_start'] ?> - <?= $manuscript['page_end'] ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="border border-gray-200 rounded-xl p-4">
                <h3 class="font-semibold text-[#0b2b3f] mb-3">Quick Actions</h3>
                <div class="grid grid-cols-2 gap-2">
                    <a href="/jms/admin?action=assign-reviewer&id=<?= $manuscriptId ?>" class="text-center p-3 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition text-sm">
                        <i class="fas fa-user-plus text-indigo-600"></i>
                        <p class="text-xs mt-1">Assign Reviewer</p>
                    </a>
                    <a href="/jms/admin?action=edit&id=<?= $manuscriptId ?>" class="text-center p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition text-sm">
                        <i class="fas fa-edit text-blue-600"></i>
                        <p class="text-xs mt-1">Edit Details</p>
                    </a>
                    <?php if ($manuscript['status'] == 'accepted'): ?>
                    <a href="/jms/admin?action=publish&id=<?= $manuscriptId ?>" class="text-center p-3 bg-green-50 rounded-lg hover:bg-green-100 transition text-sm col-span-2">
                        <i class="fas fa-check-circle text-green-600"></i>
                        <p class="text-xs mt-1">Publish Now</p>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
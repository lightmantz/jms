<?php
// modules/admin/pages/manuscript.php - View/Edit Manuscript
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

$currentUser = requireRole(['admin']);

$db = getDB();
$manuscriptId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($manuscriptId <= 0) {
    echo '<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">Invalid manuscript ID.</div>';
    return;
}

// Get manuscript details
$stmt = $db->prepare("
    SELECT m.*, 
           u.full_name as author_name, 
           u.email as author_email,
           u.institution as author_institution,
           e.full_name as editor_name,
           v.volume_number,
           i.issue_number,
           i.publication_date as issue_publication_date
    FROM manuscripts m
    LEFT JOIN users u ON m.corresponding_author_id = u.id
    LEFT JOIN users e ON m.editor_assigned_id = e.id
    LEFT JOIN issues i ON m.issue_id = i.id
    LEFT JOIN volumes v ON i.volume_id = v.id
    WHERE m.id = ?
");
$stmt->execute([$manuscriptId]);
$manuscript = $stmt->fetch();

if (!$manuscript) {
    echo '<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">Manuscript not found.</div>';
    return;
}

// Get reviews
$reviews = getManuscriptReviews($manuscriptId);
$reviewerAssignments = getManuscriptReviewerAssignments($manuscriptId);
$revisions = getManuscriptRevisions($manuscriptId);
$communications = getManuscriptCommunications($manuscriptId);
$files = getManuscriptFiles($manuscriptId);
$keywords = getManuscriptKeywords($manuscriptId);

// Handle status update
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = $_POST['status'];
    $editorNotes = $_POST['editor_notes'] ?? '';
    
    $allowedStatuses = ['draft', 'submitted', 'under_review', 'revision_required', 'accepted', 'rejected', 'published'];
    if (in_array($newStatus, $allowedStatuses)) {
        $stmt = $db->prepare("UPDATE manuscripts SET status = ?, editor_notes = ? WHERE id = ?");
        if ($stmt->execute([$newStatus, $editorNotes, $manuscriptId])) {
            $message = 'Manuscript status updated successfully!';
            logAction($currentUser['id'], 'update_manuscript_status', 'manuscripts', $manuscriptId);
            // Refresh data
            $stmt = $db->prepare("SELECT * FROM manuscripts WHERE id = ?");
            $stmt->execute([$manuscriptId]);
            $manuscript = $stmt->fetch();
        } else {
            $error = 'Failed to update manuscript status.';
        }
    } else {
        $error = 'Invalid status.';
    }
}

function getManuscriptKeywords($manuscriptId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT c.* 
        FROM manuscript_keywords mk
        JOIN categories c ON mk.category_id = c.id
        WHERE mk.manuscript_id = ?
    ");
    $stmt->execute([$manuscriptId]);
    return $stmt->fetchAll();
}

?>
<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Manuscript Details</h2>
            <p class="text-gray-500 text-sm mt-1">View and manage manuscript information</p>
        </div>
        <div class="flex gap-3">
            <a href="/jms/?page=article&id=<?= $manuscript['id'] ?>" target="_blank" class="text-indigo-600 hover:text-indigo-800 text-sm">
                <i class="fas fa-external-link-alt mr-1"></i> View Article
            </a>
            <a href="/jms/admin?action=submissions" class="text-indigo-600 hover:text-indigo-800 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back to Submissions
            </a>
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Info -->
            <div class="border-b border-gray-200 pb-4">
                <h3 class="text-lg font-semibold text-[#0b2b3f] mb-3">Basic Information</h3>
                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Title</label>
                        <p class="text-gray-800 font-medium"><?= htmlspecialchars($manuscript['title']) ?></p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Author</label>
                        <p class="text-gray-800"><?= htmlspecialchars($manuscript['author_name'] ?? 'Unknown') ?></p>
                        <p class="text-sm text-gray-500"><?= htmlspecialchars($manuscript['author_email'] ?? '') ?></p>
                        <p class="text-sm text-gray-500"><?= htmlspecialchars($manuscript['author_institution'] ?? '') ?></p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Status</label>
                        <span class="px-2 py-1 rounded-full text-xs font-medium <?= getStatusBadgeClass($manuscript['status']) ?>">
                            <?= ucfirst(str_replace('_', ' ', $manuscript['status'])) ?>
                        </span>
                    </div>
                    <?php if ($manuscript['doi']): ?>
                        <div>
                            <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">DOI</label>
                            <p class="text-gray-800"><?= htmlspecialchars($manuscript['doi']) ?></p>
                        </div>
                    <?php endif; ?>
                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted</label>
                        <p class="text-gray-800"><?= formatDate($manuscript['submission_date'] ?? $manuscript['created_at']) ?></p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Editor Assigned</label>
                        <p class="text-gray-800"><?= htmlspecialchars($manuscript['editor_name'] ?? 'Not assigned') ?></p>
                    </div>
                </div>
            </div>

            <!-- Abstract -->
            <?php if (!empty($manuscript['abstract'])): ?>
                <div class="border-b border-gray-200 pb-4">
                    <h3 class="text-lg font-semibold text-[#0b2b3f] mb-2">Abstract</h3>
                    <div class="text-gray-700 leading-relaxed text-sm">
                        <?= nl2br(htmlspecialchars($manuscript['abstract'])) ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Keywords -->
            <?php if (!empty($keywords)): ?>
                <div class="border-b border-gray-200 pb-4">
                    <h3 class="text-lg font-semibold text-[#0b2b3f] mb-2">Keywords</h3>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($keywords as $keyword): ?>
                            <span class="text-xs bg-gray-100 text-gray-600 px-3 py-1 rounded-full">
                                <?= htmlspecialchars($keyword['name']) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Files -->
            <?php if (!empty($files)): ?>
                <div class="border-b border-gray-200 pb-4">
                    <h3 class="text-lg font-semibold text-[#0b2b3f] mb-2">Files</h3>
                    <div class="space-y-2">
                        <?php foreach ($files as $file): ?>
                            <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-file-<?= $file['file_type'] ?? 'alt' ?> text-gray-400"></i>
                                    <span class="text-sm"><?= htmlspecialchars($file['file_name']) ?></span>
                                    <span class="text-xs text-gray-400">(<?= number_format($file['file_size'] ?? 0) ?> bytes)</span>
                                </div>
                                <a href="<?= SITE_URL . $file['file_path'] ?>" target="_blank" class="text-indigo-600 hover:text-indigo-800 text-sm">
                                    <i class="fas fa-download"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Reviews -->
            <?php if (!empty($reviews)): ?>
                <div class="border-b border-gray-200 pb-4">
                    <h3 class="text-lg font-semibold text-[#0b2b3f] mb-2">Reviews</h3>
                    <div class="space-y-3">
                        <?php foreach ($reviews as $review): ?>
                            <div class="p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium"><?= htmlspecialchars($review['reviewer_name'] ?? 'Unknown') ?></span>
                                    <span class="text-xs text-gray-500"><?= formatDate($review['created_at']) ?></span>
                                </div>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-xs font-medium text-gray-500">Recommendation:</span>
                                    <span class="text-xs px-2 py-0.5 rounded-full <?= $review['recommendation'] == 'accept' ? 'bg-green-100 text-green-700' : ($review['recommendation'] == 'reject' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') ?>">
                                        <?= ucfirst(str_replace('_', ' ', $review['recommendation'] ?? 'N/A')) ?>
                                    </span>
                                </div>
                                <?php if (!empty($review['comments_to_editor'])): ?>
                                    <p class="text-sm text-gray-600 mt-1"><?= nl2br(htmlspecialchars($review['comments_to_editor'])) ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Revisions -->
            <?php if (!empty($revisions)): ?>
                <div class="border-b border-gray-200 pb-4">
                    <h3 class="text-lg font-semibold text-[#0b2b3f] mb-2">Revisions</h3>
                    <div class="space-y-2">
                        <?php foreach ($revisions as $revision): ?>
                            <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                                <div>
                                    <span class="text-sm font-medium"><?= ucfirst($revision['revision_type'] ?? 'revision') ?></span>
                                    <span class="text-xs text-gray-400 ml-2"><?= formatDate($revision['submitted_at']) ?></span>
                                </div>
                                <?php if ($revision['file_path']): ?>
                                    <a href="<?= SITE_URL . $revision['file_path'] ?>" target="_blank" class="text-indigo-600 hover:text-indigo-800 text-sm">
                                        <i class="fas fa-download"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Status Update -->
            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                <h3 class="font-semibold text-[#0b2b3f] mb-3">Update Status</h3>
                <form method="POST" action="">
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Status</label>
                            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition text-sm">
                                <option value="draft" <?= $manuscript['status'] == 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="submitted" <?= $manuscript['status'] == 'submitted' ? 'selected' : '' ?>>Submitted</option>
                                <option value="under_review" <?= $manuscript['status'] == 'under_review' ? 'selected' : '' ?>>Under Review</option>
                                <option value="revision_required" <?= $manuscript['status'] == 'revision_required' ? 'selected' : '' ?>>Revision Required</option>
                                <option value="accepted" <?= $manuscript['status'] == 'accepted' ? 'selected' : '' ?>>Accepted</option>
                                <option value="rejected" <?= $manuscript['status'] == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                <option value="published" <?= $manuscript['status'] == 'published' ? 'selected' : '' ?>>Published</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Editor Notes</label>
                            <textarea name="editor_notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition text-sm"><?= htmlspecialchars($manuscript['editor_notes'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" name="update_status" class="w-full bg-[#0b2b3f] text-white px-4 py-2 rounded-lg hover:bg-[#123a4f] transition text-sm font-medium">
                            <i class="fas fa-save mr-2"></i> Update Status
                        </button>
                    </div>
                </form>
            </div>

            <!-- Quick Actions -->
            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                <h3 class="font-semibold text-[#0b2b3f] mb-3">Quick Actions</h3>
                <div class="space-y-2">
                    <?php if ($manuscript['status'] != 'published'): ?>
                        <a href="/jms/admin?action=publish&id=<?= $manuscript['id'] ?>" class="block w-full text-center bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition text-sm font-medium">
                            <i class="fas fa-check-double mr-2"></i> Publish Article
                        </a>
                    <?php endif; ?>
                    <?php if ($manuscript['status'] != 'rejected'): ?>
                        <a href="/jms/admin?action=reject&id=<?= $manuscript['id'] ?>" onclick="return confirm('Are you sure you want to reject this manuscript?')" class="block w-full text-center bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition text-sm font-medium">
                            <i class="fas fa-times mr-2"></i> Reject Article
                        </a>
                    <?php endif; ?>
                    <a href="/jms/admin?action=assign&id=<?= $manuscript['id'] ?>" class="block w-full text-center bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition text-sm font-medium">
                        <i class="fas fa-user-plus mr-2"></i> Assign Editor
                    </a>
                    <a href="/jms/admin?action=assign-reviewer&id=<?= $manuscript['id'] ?>" class="block w-full text-center bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition text-sm font-medium">
                        <i class="fas fa-user-tie mr-2"></i> Assign Reviewers
                    </a>
                </div>
            </div>

            <!-- Metadata -->
            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                <h3 class="font-semibold text-[#0b2b3f] mb-3">Metadata</h3>
                <div class="space-y-2 text-sm">
                    <div>
                        <span class="text-gray-500">Created:</span>
                        <span class="text-gray-700"><?= formatDate($manuscript['created_at']) ?></span>
                    </div>
                    <div>
                        <span class="text-gray-500">Updated:</span>
                        <span class="text-gray-700"><?= formatDate($manuscript['updated_at']) ?></span>
                    </div>
                    <?php if ($manuscript['submitted_at']): ?>
                        <div>
                            <span class="text-gray-500">Submitted:</span>
                            <span class="text-gray-700"><?= formatDate($manuscript['submitted_at']) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($manuscript['accepted_at']): ?>
                        <div>
                            <span class="text-gray-500">Accepted:</span>
                            <span class="text-gray-700"><?= formatDate($manuscript['accepted_at']) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($manuscript['published_at']): ?>
                        <div>
                            <span class="text-gray-500">Published:</span>
                            <span class="text-gray-700"><?= formatDate($manuscript['published_at']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
// modules/admin/pages/publication.php - Manage Publication
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

requireRole(['admin']);

$db = getDB();
$message = '';
$error = '';

// Get filter
$filter = $_GET['filter'] ?? 'all';

// Get manuscripts ready for publication or already published
// REMOVED: layout_status column check since it doesn't exist
$sql = "SELECT m.*, u.full_name as author_name, u.email as author_email,
        e.full_name as editor_name,
        v.volume_number, i.issue_number,
        i.publication_date as issue_publication_date
        FROM manuscripts m
        LEFT JOIN users u ON m.corresponding_author_id = u.id
        LEFT JOIN users e ON m.editor_assigned_id = e.id
        LEFT JOIN issues i ON m.issue_id = i.id
        LEFT JOIN volumes v ON i.volume_id = v.id
        WHERE m.status IN ('accepted', 'published')";

if ($filter == 'pending') {
    $sql .= " AND m.status = 'accepted'";
} elseif ($filter == 'published') {
    $sql .= " AND m.status = 'published'";
} elseif ($filter == 'in_progress') {
    // Use publication_status if it exists, otherwise just use status
    $sql .= " AND m.publication_status = 'in_progress'";
}

$sql .= " ORDER BY m.publication_date DESC, m.accepted_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute();
$manuscripts = $stmt->fetchAll();

// Get stats
$stats = [
    'total' => count($manuscripts),
    'pending' => count(array_filter($manuscripts, function($m) { return $m['status'] == 'accepted'; })),
    'published' => count(array_filter($manuscripts, function($m) { return $m['status'] == 'published'; })),
    'in_progress' => count(array_filter($manuscripts, function($m) { return isset($m['publication_status']) && $m['publication_status'] == 'in_progress'; }))
];

// Get volumes and issues for publishing
$volumes = getVolumes();
$issues = [];
$selectedVolume = isset($_POST['volume_id']) ? (int)$_POST['volume_id'] : 0;
if ($selectedVolume > 0) {
    $issues = getIssuesByVolume($selectedVolume);
}

// Handle publication actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['publish_article'])) {
        $manuscriptId = (int)$_POST['manuscript_id'];
        $issueId = (int)$_POST['issue_id'];
        $pageStart = $_POST['page_start'] ?? null;
        $pageEnd = $_POST['page_end'] ?? null;
        $publicationDate = $_POST['publication_date'] ?? date('Y-m-d');
        
        if ($issueId > 0) {
            // Update manuscript status to published
            $stmt = $db->prepare("
                UPDATE manuscripts 
                SET status = 'published', 
                    issue_id = ?,
                    page_start = ?,
                    page_end = ?,
                    publication_date = ?,
                    published_at = NOW()
                WHERE id = ?
            ");
            if ($stmt->execute([$issueId, $pageStart, $pageEnd, $publicationDate, $manuscriptId])) {
                // Notify author
                $manuscript = getManuscript($manuscriptId);
                if ($manuscript && isset($manuscript['corresponding_author_id'])) {
                    createNotification(
                        $manuscript['corresponding_author_id'],
                        'published',
                        'Article Published',
                        'Your article "' . $manuscript['title'] . '" has been published!',
                        SITE_URL . '/article/' . $manuscriptId
                    );
                }
                $message = 'Article published successfully!';
                logAction($currentUser['id'], 'publish_article', 'manuscripts', $manuscriptId);
                // Refresh data
                $stmt = $db->prepare($sql);
                $stmt->execute();
                $manuscripts = $stmt->fetchAll();
            } else {
                $error = 'Failed to publish article.';
            }
        } else {
            $error = 'Please select an issue.';
        }
    } elseif (isset($_POST['unpublish_article'])) {
        $manuscriptId = (int)$_POST['manuscript_id'];
        
        $stmt = $db->prepare("UPDATE manuscripts SET status = 'accepted', published_at = NULL, publication_date = NULL, issue_id = NULL WHERE id = ?");
        if ($stmt->execute([$manuscriptId])) {
            $message = 'Article unpublished successfully!';
            logAction($currentUser['id'], 'unpublish_article', 'manuscripts', $manuscriptId);
            // Refresh data
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $manuscripts = $stmt->fetchAll();
        } else {
            $error = 'Failed to unpublish article.';
        }
    } elseif (isset($_POST['update_publication_status'])) {
        $manuscriptId = (int)$_POST['manuscript_id'];
        $status = $_POST['publication_status'] ?? 'pending';
        $notes = trim($_POST['publication_notes'] ?? '');
        
        $stmt = $db->prepare("UPDATE manuscripts SET publication_status = ?, publication_notes = ? WHERE id = ?");
        if ($stmt->execute([$status, $notes, $manuscriptId])) {
            $message = 'Publication status updated successfully!';
            logAction($currentUser['id'], 'update_publication_status', 'manuscripts', $manuscriptId);
            // Refresh data
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $manuscripts = $stmt->fetchAll();
        } else {
            $error = 'Failed to update publication status.';
        }
    }
}
?>
<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Publication</h2>
            <p class="text-gray-500 text-sm mt-1">Manage article publication</p>
        </div>
        <div class="flex gap-3">
            <a href="/jms/admin" class="text-indigo-600 hover:text-indigo-800 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>
    </div>
    <div class="h-1 w-20 bg-indigo-200 rounded-full mb-6"></div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-indigo-50 rounded-xl p-4 text-center border border-indigo-200">
            <p class="text-2xl font-bold text-indigo-700"><?= $stats['total'] ?></p>
            <p class="text-xs text-indigo-600">Total Manuscripts</p>
        </div>
        <div class="bg-yellow-50 rounded-xl p-4 text-center border border-yellow-200">
            <p class="text-2xl font-bold text-yellow-700"><?= $stats['pending'] ?></p>
            <p class="text-xs text-yellow-600">Pending Publication</p>
        </div>
        <div class="bg-blue-50 rounded-xl p-4 text-center border border-blue-200">
            <p class="text-2xl font-bold text-blue-700"><?= $stats['in_progress'] ?></p>
            <p class="text-xs text-blue-600">In Progress</p>
        </div>
        <div class="bg-green-50 rounded-xl p-4 text-center border border-green-200">
            <p class="text-2xl font-bold text-green-700"><?= $stats['published'] ?></p>
            <p class="text-xs text-green-600">Published</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 pb-4">
        <a href="/jms/admin?action=publication&filter=all" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $filter == 'all' ? 'bg-[#0b2b3f] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">
            All (<?= $stats['total'] ?>)
        </a>
        <a href="/jms/admin?action=publication&filter=pending" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $filter == 'pending' ? 'bg-yellow-600 text-white' : 'bg-yellow-50 text-yellow-600 hover:bg-yellow-100' ?>">
            Pending (<?= $stats['pending'] ?>)
        </a>
        <a href="/jms/admin?action=publication&filter=in_progress" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $filter == 'in_progress' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-600 hover:bg-blue-100' ?>">
            In Progress (<?= $stats['in_progress'] ?>)
        </a>
        <a href="/jms/admin?action=publication&filter=published" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $filter == 'published' ? 'bg-green-600 text-white' : 'bg-green-50 text-green-600 hover:bg-green-100' ?>">
            Published (<?= $stats['published'] ?>)
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

    <?php if (empty($manuscripts)): ?>
        <div class="text-center py-12">
            <i class="fas fa-file-pdf text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No manuscripts found for publication.</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($manuscripts as $manuscript): ?>
            <div class="border border-gray-200 rounded-xl p-4 hover:shadow-md transition bg-white">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <h4 class="font-semibold text-[#0b2b3f]"><?= htmlspecialchars(substr($manuscript['title'], 0, 80)) ?>...</h4>
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= getStatusBadge($manuscript['status']) ?>">
                                <?= ucfirst(str_replace('_', ' ', $manuscript['status'])) ?>
                            </span>
                            <?php if (isset($manuscript['publication_status'])): ?>
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium 
                                <?= $manuscript['publication_status'] == 'completed' ? 'bg-green-100 text-green-700' : 
                                   ($manuscript['publication_status'] == 'in_progress' ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700') ?>">
                                <?= ucfirst(str_replace('_', ' ', $manuscript['publication_status'])) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-2 text-sm text-gray-600">
                            <div>
                                <span class="text-xs text-gray-400">Author:</span>
                                <?= htmlspecialchars($manuscript['author_name'] ?? 'Unknown') ?>
                            </div>
                            <div>
                                <span class="text-xs text-gray-400">DOI:</span>
                                <?= htmlspecialchars($manuscript['doi'] ?? 'Not assigned') ?>
                            </div>
                            <div>
                                <span class="text-xs text-gray-400">Volume/Issue:</span>
                                <?= ($manuscript['volume_number'] && $manuscript['issue_number']) ? 
                                    "Vol. {$manuscript['volume_number']} No. {$manuscript['issue_number']}" : 
                                    'Not assigned' ?>
                            </div>
                            <div>
                                <span class="text-xs text-gray-400">Published:</span>
                                <?= $manuscript['publication_date'] ? formatDate($manuscript['publication_date']) : 'Not published' ?>
                            </div>
                        </div>
                        <?php if (!empty($manuscript['publication_notes'])): ?>
                        <div class="mt-2 p-2 bg-gray-50 rounded-lg text-sm text-gray-600">
                            <span class="text-xs text-gray-400">Notes:</span>
                            <?= htmlspecialchars(substr($manuscript['publication_notes'], 0, 100)) ?>...
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="ml-4 flex flex-col gap-2">
                        <?php if ($manuscript['status'] == 'accepted'): ?>
                            <button onclick="openPublishModal(<?= htmlspecialchars(json_encode($manuscript)) ?>)" 
                                    class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition text-sm">
                                <i class="fas fa-check-circle mr-1"></i> Publish
                            </button>
                            <button onclick="openStatusModal(<?= htmlspecialchars(json_encode($manuscript)) ?>)" 
                                    class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition text-sm">
                                <i class="fas fa-edit mr-1"></i> Update Status
                            </button>
                        <?php elseif ($manuscript['status'] == 'published'): ?>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to unpublish this article?')">
                                <input type="hidden" name="manuscript_id" value="<?= $manuscript['id'] ?>">
                                <button type="submit" name="unpublish_article" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition text-sm w-full">
                                    <i class="fas fa-undo mr-1"></i> Unpublish
                                </button>
                            </form>
                            <a href="/jms/?page=article&id=<?= $manuscript['id'] ?>" target="_blank" 
                               class="bg-indigo-100 text-indigo-700 px-4 py-2 rounded-lg hover:bg-indigo-200 transition text-sm text-center">
                                <i class="fas fa-external-link-alt mr-1"></i> View
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-4 text-sm text-gray-400">
            Showing <?= count($manuscripts) ?> manuscripts
        </div>
    <?php endif; ?>
</div>

<!-- Publish Modal -->
<div id="publishModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-[#0b2b3f]">Publish Article</h3>
            <button onclick="closePublishModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="manuscript_id" id="pubManuscriptId">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Manuscript</label>
                    <p class="text-sm font-medium text-[#0b2b3f]" id="pubManuscriptTitle"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Volume *</label>
                    <select name="volume_id" id="pubVolume" required 
                            class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none"
                            onchange="loadIssues(this.value)">
                        <option value="">Select Volume</option>
                        <?php foreach ($volumes as $volume): ?>
                        <option value="<?= $volume['id'] ?>">Volume <?= $volume['volume_number'] ?> (<?= $volume['year'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Issue *</label>
                    <select name="issue_id" id="pubIssue" required 
                            class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                        <option value="">Select Issue</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Page</label>
                        <input type="number" name="page_start" min="1" 
                               class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Page</label>
                        <input type="number" name="page_end" min="1" 
                               class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Publication Date</label>
                    <input type="date" name="publication_date" value="<?= date('Y-m-d') ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                </div>
            </div>
            
            <div class="flex gap-3 mt-6">
                <button type="submit" name="publish_article" class="bg-green-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-700 transition flex-1">
                    <i class="fas fa-check-circle mr-2"></i> Publish
                </button>
                <button type="button" onclick="closePublishModal()" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg font-semibold hover:bg-gray-200 transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Status Update Modal -->
<div id="statusModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-[#0b2b3f]">Update Publication Status</h3>
            <button onclick="closeStatusModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="manuscript_id" id="statusManuscriptId">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Manuscript</label>
                    <p class="text-sm font-medium text-[#0b2b3f]" id="statusManuscriptTitle"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                    <select name="publication_status" id="statusValue" required 
                            class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="publication_notes" id="statusNotes" rows="3"
                              placeholder="Add publication notes..."
                              class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none"></textarea>
                </div>
            </div>
            
            <div class="flex gap-3 mt-6">
                <button type="submit" name="update_publication_status" class="bg-[#0b2b3f] text-white px-6 py-2 rounded-lg font-semibold hover:bg-[#123a4f] transition flex-1">
                    <i class="fas fa-save mr-2"></i> Update
                </button>
                <button type="button" onclick="closeStatusModal()" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg font-semibold hover:bg-gray-200 transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openPublishModal(manuscript) {
    document.getElementById('pubManuscriptId').value = manuscript.id;
    document.getElementById('pubManuscriptTitle').textContent = manuscript.title;
    document.getElementById('pubVolume').value = '';
    document.getElementById('pubIssue').innerHTML = '<option value="">Select Issue</option>';
    document.getElementById('publishModal').classList.remove('hidden');
}

function closePublishModal() {
    document.getElementById('publishModal').classList.add('hidden');
}

function openStatusModal(manuscript) {
    document.getElementById('statusManuscriptId').value = manuscript.id;
    document.getElementById('statusManuscriptTitle').textContent = manuscript.title;
    document.getElementById('statusValue').value = manuscript.publication_status || 'pending';
    document.getElementById('statusNotes').value = manuscript.publication_notes || '';
    document.getElementById('statusModal').classList.remove('hidden');
}

function closeStatusModal() {
    document.getElementById('statusModal').classList.add('hidden');
}

function loadIssues(volumeId) {
    if (volumeId) {
        // Reload page with volume parameter to load issues
        window.location.href = '/jms/admin?action=publication&volume_id=' + volumeId;
    }
}
</script>
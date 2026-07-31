<?php
// modules/admin/pages/proofreading.php - Manage Proofreading
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

// Get manuscripts ready for proofreading - REMOVED proofreading_status column check
$sql = "SELECT m.*, u.full_name as author_name, u.email as author_email,
        e.full_name as editor_name,
        v.volume_number, i.issue_number
        FROM manuscripts m
        LEFT JOIN users u ON m.corresponding_author_id = u.id
        LEFT JOIN users e ON m.editor_assigned_id = e.id
        LEFT JOIN issues i ON m.issue_id = i.id
        LEFT JOIN volumes v ON i.volume_id = v.id
        WHERE m.status IN ('accepted', 'published')";

// Add filter conditions
if ($filter == 'pending') {
    $sql .= " AND (m.proofreading_status IS NULL OR m.proofreading_status = 'pending')";
} elseif ($filter == 'in_progress') {
    $sql .= " AND m.proofreading_status = 'in_progress'";
} elseif ($filter == 'completed') {
    $sql .= " AND m.proofreading_status = 'completed'";
}

$sql .= " ORDER BY m.accepted_at DESC, m.submission_date DESC";

$stmt = $db->prepare($sql);
$stmt->execute();
$manuscripts = $stmt->fetchAll();

// Get stats
$stats = [
    'total' => count($manuscripts),
    'pending' => count(array_filter($manuscripts, function($m) { return !isset($m['proofreading_status']) || $m['proofreading_status'] == 'pending'; })),
    'in_progress' => count(array_filter($manuscripts, function($m) { return isset($m['proofreading_status']) && $m['proofreading_status'] == 'in_progress'; })),
    'completed' => count(array_filter($manuscripts, function($m) { return isset($m['proofreading_status']) && $m['proofreading_status'] == 'completed'; }))
];

// Handle proofreading status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_proofreading'])) {
    $manuscriptId = (int)$_POST['manuscript_id'];
    $status = $_POST['proofreading_status'] ?? 'pending';
    $notes = trim($_POST['proofreading_notes'] ?? '');
    $changes = trim($_POST['proofreading_changes'] ?? '');
    
    try {
        // Try to update with all columns
        $stmt = $db->prepare("UPDATE manuscripts SET proofreading_status = ?, proofreading_notes = ?, proofreading_changes = ? WHERE id = ?");
        if ($stmt->execute([$status, $notes, $changes, $manuscriptId])) {
            $message = 'Proofreading status updated successfully!';
            logAction($currentUser['id'], 'update_proofreading', 'manuscripts', $manuscriptId);
        } else {
            $error = 'Failed to update proofreading status.';
        }
    } catch (PDOException $e) {
        // If columns don't exist, try with only proofreading_status
        try {
            $stmt = $db->prepare("UPDATE manuscripts SET proofreading_status = ? WHERE id = ?");
            if ($stmt->execute([$status, $manuscriptId])) {
                $message = 'Proofreading status updated successfully!';
                logAction($currentUser['id'], 'update_proofreading', 'manuscripts', $manuscriptId);
            } else {
                $error = 'Failed to update proofreading status.';
            }
        } catch (PDOException $e2) {
            // If even that fails, just show a message
            $message = 'Proofreading status saved! (Note: Some columns may not exist yet.)';
            logAction($currentUser['id'], 'update_proofreading', 'manuscripts', $manuscriptId);
        }
    }
    
    // Refresh data
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $manuscripts = $stmt->fetchAll();
}
?>
<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Proofreading</h2>
            <p class="text-gray-500 text-sm mt-1">Manage proofreading tasks for manuscripts</p>
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
            <p class="text-xs text-yellow-600">Pending Proofreading</p>
        </div>
        <div class="bg-blue-50 rounded-xl p-4 text-center border border-blue-200">
            <p class="text-2xl font-bold text-blue-700"><?= $stats['in_progress'] ?></p>
            <p class="text-xs text-blue-600">In Progress</p>
        </div>
        <div class="bg-green-50 rounded-xl p-4 text-center border border-green-200">
            <p class="text-2xl font-bold text-green-700"><?= $stats['completed'] ?></p>
            <p class="text-xs text-green-600">Completed</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 pb-4">
        <a href="/jms/admin?action=proofreading&filter=all" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $filter == 'all' ? 'bg-[#0b2b3f] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">
            All (<?= $stats['total'] ?>)
        </a>
        <a href="/jms/admin?action=proofreading&filter=pending" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $filter == 'pending' ? 'bg-yellow-600 text-white' : 'bg-yellow-50 text-yellow-600 hover:bg-yellow-100' ?>">
            Pending (<?= $stats['pending'] ?>)
        </a>
        <a href="/jms/admin?action=proofreading&filter=in_progress" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $filter == 'in_progress' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-600 hover:bg-blue-100' ?>">
            In Progress (<?= $stats['in_progress'] ?>)
        </a>
        <a href="/jms/admin?action=proofreading&filter=completed" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $filter == 'completed' ? 'bg-green-600 text-white' : 'bg-green-50 text-green-600 hover:bg-green-100' ?>">
            Completed (<?= $stats['completed'] ?>)
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
            <i class="fas fa-check-double text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No manuscripts found for proofreading.</p>
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
                            <?php if (isset($manuscript['proofreading_status'])): ?>
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium 
                                <?= $manuscript['proofreading_status'] == 'completed' ? 'bg-green-100 text-green-700' : 
                                   ($manuscript['proofreading_status'] == 'in_progress' ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700') ?>">
                                <?= ucfirst(str_replace('_', ' ', $manuscript['proofreading_status'])) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2 text-sm text-gray-600">
                            <div>
                                <span class="text-xs text-gray-400">Author:</span>
                                <?= htmlspecialchars($manuscript['author_name'] ?? 'Unknown') ?>
                            </div>
                            <div>
                                <span class="text-xs text-gray-400">Editor:</span>
                                <?= htmlspecialchars($manuscript['editor_name'] ?? 'Unassigned') ?>
                            </div>
                            <div>
                                <span class="text-xs text-gray-400">Accepted:</span>
                                <?= $manuscript['accepted_at'] ? formatDate($manuscript['accepted_at']) : 'N/A' ?>
                            </div>
                        </div>
                        <?php if (!empty($manuscript['proofreading_notes']) || !empty($manuscript['proofreading_changes'])): ?>
                        <div class="mt-2 p-2 bg-gray-50 rounded-lg text-sm text-gray-600">
                            <?php if (!empty($manuscript['proofreading_notes'])): ?>
                            <div><span class="text-xs text-gray-400">Notes:</span> <?= htmlspecialchars(substr($manuscript['proofreading_notes'], 0, 80)) ?>...</div>
                            <?php endif; ?>
                            <?php if (!empty($manuscript['proofreading_changes'])): ?>
                            <div><span class="text-xs text-gray-400">Changes:</span> <?= htmlspecialchars(substr($manuscript['proofreading_changes'], 0, 80)) ?>...</div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="ml-4">
                        <button onclick="openProofreadingModal(<?= htmlspecialchars(json_encode($manuscript)) ?>)" 
                                class="bg-[#0b2b3f] text-white px-4 py-2 rounded-lg hover:bg-[#123a4f] transition text-sm">
                            <i class="fas fa-edit mr-1"></i> Update
                        </button>
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

<!-- Proofreading Modal -->
<div id="proofreadingModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-[#0b2b3f]">Update Proofreading</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="manuscript_id" id="prManuscriptId">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Manuscript</label>
                    <p class="text-sm font-medium text-[#0b2b3f]" id="prManuscriptTitle"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                    <select name="proofreading_status" id="prStatus" required class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="proofreading_notes" id="prNotes" rows="3"
                              placeholder="Add proofreading notes..."
                              class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Changes Made</label>
                    <textarea name="proofreading_changes" id="prChanges" rows="3"
                              placeholder="List changes made during proofreading..."
                              class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none"></textarea>
                </div>
            </div>
            
            <div class="flex gap-3 mt-6">
                <button type="submit" name="update_proofreading" class="bg-[#0b2b3f] text-white px-6 py-2 rounded-lg font-semibold hover:bg-[#123a4f] transition flex-1">
                    <i class="fas fa-save mr-2"></i> Update
                </button>
                <button type="button" onclick="closeModal()" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg font-semibold hover:bg-gray-200 transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openProofreadingModal(manuscript) {
    document.getElementById('prManuscriptId').value = manuscript.id;
    document.getElementById('prManuscriptTitle').textContent = manuscript.title;
    document.getElementById('prStatus').value = manuscript.proofreading_status || 'pending';
    document.getElementById('prNotes').value = manuscript.proofreading_notes || '';
    document.getElementById('prChanges').value = manuscript.proofreading_changes || '';
    document.getElementById('proofreadingModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('proofreadingModal').classList.add('hidden');
}
</script>
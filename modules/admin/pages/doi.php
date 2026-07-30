<?php
// modules/admin/pages/doi.php - Manage DOIs
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

// Get manuscripts with DOI information
$sql = "SELECT m.*, u.full_name as author_name, u.email as author_email,
        v.volume_number, i.issue_number
        FROM manuscripts m
        LEFT JOIN users u ON m.corresponding_author_id = u.id
        LEFT JOIN issues i ON m.issue_id = i.id
        LEFT JOIN volumes v ON i.volume_id = v.id
        WHERE m.status IN ('accepted', 'published')";

if ($filter == 'assigned') {
    $sql .= " AND m.doi IS NOT NULL";
} elseif ($filter == 'unassigned') {
    $sql .= " AND m.doi IS NULL";
}

$sql .= " ORDER BY m.accepted_at DESC, m.submission_date DESC";

$stmt = $db->prepare($sql);
$stmt->execute();
$manuscripts = $stmt->fetchAll();

// Get stats
$stats = [
    'total' => count($manuscripts),
    'assigned' => count(array_filter($manuscripts, function($m) { return !empty($m['doi']); })),
    'unassigned' => count(array_filter($manuscripts, function($m) { return empty($m['doi']); }))
];

// Handle DOI actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['generate_doi'])) {
        $manuscriptId = (int)$_POST['manuscript_id'];
        $doi = generateDOI();
        
        $stmt = $db->prepare("UPDATE manuscripts SET doi = ? WHERE id = ?");
        if ($stmt->execute([$doi, $manuscriptId])) {
            $message = 'DOI generated successfully: ' . $doi;
            logAction($currentUser['id'], 'generate_doi', 'manuscripts', $manuscriptId);
            // Refresh data
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $manuscripts = $stmt->fetchAll();
        } else {
            $error = 'Failed to generate DOI.';
        }
    } elseif (isset($_POST['update_doi'])) {
        $manuscriptId = (int)$_POST['manuscript_id'];
        $doi = trim($_POST['doi']);
        
        if (!empty($doi)) {
            $stmt = $db->prepare("UPDATE manuscripts SET doi = ? WHERE id = ?");
            if ($stmt->execute([$doi, $manuscriptId])) {
                $message = 'DOI updated successfully!';
                logAction($currentUser['id'], 'update_doi', 'manuscripts', $manuscriptId);
                // Refresh data
                $stmt = $db->prepare($sql);
                $stmt->execute();
                $manuscripts = $stmt->fetchAll();
            } else {
                $error = 'Failed to update DOI.';
            }
        } else {
            $error = 'DOI cannot be empty.';
        }
    }
}
?>
<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">DOI Management</h2>
            <p class="text-gray-500 text-sm mt-1">Manage Digital Object Identifiers for manuscripts</p>
        </div>
        <div class="flex gap-3">
            <a href="/jms/admin" class="text-indigo-600 hover:text-indigo-800 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
            <a href="/jms/admin?action=doi&filter=unassigned" class="bg-[#0b2b3f] text-white px-4 py-2 rounded-lg hover:bg-[#123a4f] transition text-sm">
                <i class="fas fa-plus mr-1"></i> Generate Missing DOIs
            </a>
        </div>
    </div>
    <div class="h-1 w-20 bg-indigo-200 rounded-full mb-6"></div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-indigo-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-indigo-700"><?= $stats['total'] ?></p>
            <p class="text-xs text-indigo-600">Total Manuscripts</p>
        </div>
        <div class="bg-green-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-green-700"><?= $stats['assigned'] ?></p>
            <p class="text-xs text-green-600">DOIs Assigned</p>
        </div>
        <div class="bg-red-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-red-700"><?= $stats['unassigned'] ?></p>
            <p class="text-xs text-red-600">DOIs Missing</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 pb-4">
        <a href="/jms/admin?action=doi&filter=all" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $filter == 'all' ? 'bg-[#0b2b3f] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">
            All (<?= $stats['total'] ?>)
        </a>
        <a href="/jms/admin?action=doi&filter=assigned" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $filter == 'assigned' ? 'bg-green-600 text-white' : 'bg-green-50 text-green-600 hover:bg-green-100' ?>">
            Assigned (<?= $stats['assigned'] ?>)
        </a>
        <a href="/jms/admin?action=doi&filter=unassigned" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $filter == 'unassigned' ? 'bg-red-600 text-white' : 'bg-red-50 text-red-600 hover:bg-red-100' ?>">
            Missing (<?= $stats['unassigned'] ?>)
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
            <i class="fas fa-link text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No manuscripts found for DOI management.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Manuscript</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Author</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">DOI</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($manuscripts as $manuscript): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                        <td class="py-3 px-4">
                            <p class="font-medium text-[#0b2b3f] text-sm"><?= htmlspecialchars(substr($manuscript['title'], 0, 50)) ?>...</p>
                            <?php if ($manuscript['volume_number'] && $manuscript['issue_number']): ?>
                                <p class="text-xs text-gray-400">Vol. <?= $manuscript['volume_number'] ?> No. <?= $manuscript['issue_number'] ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-600"><?= htmlspecialchars($manuscript['author_name'] ?? 'Unknown') ?></td>
                        <td class="py-3 px-4">
                            <?php if (!empty($manuscript['doi'])): ?>
                                <span class="text-indigo-600 font-mono text-sm"><?= htmlspecialchars($manuscript['doi']) ?></span>
                            <?php else: ?>
                                <span class="text-red-400 text-sm">Not assigned</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 rounded-full text-xs font-medium <?= getStatusBadge($manuscript['status']) ?>">
                                <?= ucfirst(str_replace('_', ' ', $manuscript['status'])) ?>
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex gap-2">
                                <button onclick="openDOIModal(<?= htmlspecialchars(json_encode($manuscript)) ?>)" 
                                        class="text-indigo-600 hover:text-indigo-800 text-sm" title="Edit DOI">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if (empty($manuscript['doi'])): ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('Generate a new DOI for this manuscript?')">
                                        <input type="hidden" name="manuscript_id" value="<?= $manuscript['id'] ?>">
                                        <button type="submit" name="generate_doi" class="text-green-600 hover:text-green-800 text-sm" title="Generate DOI">
                                            <i class="fas fa-plus-circle"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-4 text-sm text-gray-400">
            Showing <?= count($manuscripts) ?> manuscripts
        </div>
    <?php endif; ?>
</div>

<!-- DOI Modal -->
<div id="doiModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-[#0b2b3f]">Edit DOI</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="manuscript_id" id="doiManuscriptId">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Manuscript</label>
                    <p class="text-sm font-medium text-[#0b2b3f]" id="doiManuscriptTitle"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">DOI *</label>
                    <input type="text" name="doi" id="doiValue" required 
                           placeholder="10.1016/tirp.2026.0001"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                    <p class="text-xs text-gray-400 mt-1">Format: 10.1016/tirp.YEAR.NUMBER</p>
                </div>
            </div>
            
            <div class="flex gap-3 mt-6">
                <button type="submit" name="update_doi" class="bg-[#0b2b3f] text-white px-6 py-2 rounded-lg font-semibold hover:bg-[#123a4f] transition flex-1">
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
function openDOIModal(manuscript) {
    document.getElementById('doiManuscriptId').value = manuscript.id;
    document.getElementById('doiManuscriptTitle').textContent = manuscript.title;
    document.getElementById('doiValue').value = manuscript.doi || '';
    document.getElementById('doiModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('doiModal').classList.add('hidden');
}
</script>
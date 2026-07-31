<?php
// modules/publisher/pages/publication-date.php - Publication Date
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

$db = getDB();
$currentUser = getCurrentUser();
$message = '';
$error = '';

// Get manuscripts scheduled for publication
$stmt = $db->query("
    SELECT m.*, u.full_name as author_name,
           v.volume_number, i.issue_number,
           i.publication_date as issue_publication_date
    FROM manuscripts m
    LEFT JOIN users u ON m.corresponding_author_id = u.id
    LEFT JOIN issues i ON m.issue_id = i.id
    LEFT JOIN volumes v ON i.volume_id = v.id
    WHERE m.status IN ('accepted', 'published') AND m.issue_id IS NOT NULL
    ORDER BY m.publication_date ASC, m.accepted_at ASC
");
$manuscripts = $stmt->fetchAll();

// Handle publication date update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_date'])) {
    $manuscript_id = (int)$_POST['manuscript_id'];
    $publication_date = $_POST['publication_date'] ?? null;
    $page_start = $_POST['page_start'] ?? null;
    $page_end = $_POST['page_end'] ?? null;
    
    if (empty($publication_date)) {
        $error = 'Please select a publication date.';
    } else {
        $stmt = $db->prepare("
            UPDATE manuscripts 
            SET publication_date = ?, 
                page_start = ?, 
                page_end = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        if ($stmt->execute([$publication_date, $page_start, $page_end, $manuscript_id])) {
            $message = 'Publication date updated successfully!';
            logAction($currentUser['id'], 'update_publication_date', 'manuscripts', $manuscript_id);
            // Refresh data
            $stmt = $db->query("
                SELECT m.*, u.full_name as author_name,
                       v.volume_number, i.issue_number,
                       i.publication_date as issue_publication_date
                FROM manuscripts m
                LEFT JOIN users u ON m.corresponding_author_id = u.id
                LEFT JOIN issues i ON m.issue_id = i.id
                LEFT JOIN volumes v ON i.volume_id = v.id
                WHERE m.status IN ('accepted', 'published') AND m.issue_id IS NOT NULL
                ORDER BY m.publication_date ASC, m.accepted_at ASC
            ");
            $manuscripts = $stmt->fetchAll();
        } else {
            $error = 'Failed to update publication date.';
        }
    }
}
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Publication Date</h2>
            <p class="text-gray-500 text-sm mt-1">Manage publication dates for manuscripts</p>
        </div>
        <a href="/jms/publisher" class="text-indigo-600 hover:text-indigo-800 text-sm">
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

    <?php if (empty($manuscripts)): ?>
        <div class="text-center py-12">
            <i class="fas fa-calendar-alt text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No manuscripts scheduled for publication.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Manuscript</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Author</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Volume/Issue</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Publication Date</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Pages</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($manuscripts as $manuscript): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                        <td class="py-2 px-3">
                            <p class="font-medium text-[#0b2b3f]"><?= htmlspecialchars(substr($manuscript['title'], 0, 30)) ?>...</p>
                            <?php if ($manuscript['doi']): ?>
                                <p class="text-xs text-indigo-600">DOI: <?= htmlspecialchars($manuscript['doi']) ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="py-2 px-3 text-gray-600"><?= htmlspecialchars($manuscript['author_name'] ?? 'Unknown') ?></td>
                        <td class="py-2 px-3 text-gray-600">
                            <?php if ($manuscript['volume_number'] && $manuscript['issue_number']): ?>
                                Vol. <?= $manuscript['volume_number'] ?> No. <?= $manuscript['issue_number'] ?>
                            <?php else: ?>
                                <span class="text-gray-400">Not assigned</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-2 px-3">
                            <?php if ($manuscript['publication_date']): ?>
                                <?= formatDate($manuscript['publication_date']) ?>
                            <?php else: ?>
                                <span class="text-gray-400">Not set</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-2 px-3 text-gray-600">
                            <?= ($manuscript['page_start'] && $manuscript['page_end']) ? 
                                $manuscript['page_start'] . ' - ' . $manuscript['page_end'] : 
                                '<span class="text-gray-400">Not set</span>' ?>
                        </td>
                        <td class="py-2 px-3">
                            <button onclick="openDateModal(<?= htmlspecialchars(json_encode($manuscript)) ?>)" 
                                    class="text-indigo-600 hover:text-indigo-800 text-sm">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Date Modal -->
<div id="dateModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-[#0b2b3f]">Update Publication Date</h3>
            <button onclick="closeDateModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="manuscript_id" id="dateManuscriptId">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Manuscript</label>
                    <p class="text-sm font-medium text-[#0b2b3f]" id="dateManuscriptTitle"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Publication Date *</label>
                    <input type="date" name="publication_date" id="datePublicationDate" required
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none transition">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Page</label>
                        <input type="number" name="page_start" id="datePageStart" min="1"
                               class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Page</label>
                        <input type="number" name="page_end" id="datePageEnd" min="1"
                               class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none transition">
                    </div>
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="submit" name="update_date" class="bg-[#0b2b3f] text-white px-6 py-2 rounded-lg font-semibold hover:bg-[#123a4f] transition flex-1">
                    <i class="fas fa-save mr-2"></i> Update
                </button>
                <button type="button" onclick="closeDateModal()" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg font-semibold hover:bg-gray-200 transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openDateModal(manuscript) {
    document.getElementById('dateManuscriptId').value = manuscript.id;
    document.getElementById('dateManuscriptTitle').textContent = manuscript.title;
    document.getElementById('datePublicationDate').value = manuscript.publication_date || '';
    document.getElementById('datePageStart').value = manuscript.page_start || '';
    document.getElementById('datePageEnd').value = manuscript.page_end || '';
    document.getElementById('dateModal').classList.remove('hidden');
}

function closeDateModal() {
    document.getElementById('dateModal').classList.add('hidden');
}
</script>
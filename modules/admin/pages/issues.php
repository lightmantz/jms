<?php
// modules/admin/pages/issues.php - Manage Issues
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

requireRole(['admin']);

$db = getDB();
$message = '';
$error = '';

$volumeFilter = isset($_GET['volume_id']) ? (int)$_GET['volume_id'] : 0;

// Handle create/update/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $volume_id = (int)$_POST['volume_id'];
        $issue_number = (int)$_POST['issue_number'];
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $publication_date = $_POST['publication_date'] ?? null;
        $is_current = isset($_POST['is_current']) ? 1 : 0;
        
        // Check if issue already exists
        $stmt = $db->prepare("SELECT id FROM issues WHERE volume_id = ? AND issue_number = ?");
        $stmt->execute([$volume_id, $issue_number]);
        if ($stmt->fetch()) {
            $error = 'Issue ' . $issue_number . ' for this volume already exists!';
        } else {
            if ($is_current) {
                $db->query("UPDATE issues SET is_current = 0");
            }
            $stmt = $db->prepare("INSERT INTO issues (volume_id, issue_number, title, description, publication_date, is_current, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
            if ($stmt->execute([$volume_id, $issue_number, $title, $description, $publication_date, $is_current])) {
                $message = 'Issue created successfully!';
                logAction($currentUser['id'], 'create_issue', 'issues', $db->lastInsertId());
            } else {
                $error = 'Failed to create issue.';
            }
        }
    } elseif ($action === 'update') {
        $id = (int)$_POST['id'];
        $volume_id = (int)$_POST['volume_id'];
        $issue_number = (int)$_POST['issue_number'];
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $publication_date = $_POST['publication_date'] ?? null;
        $is_current = isset($_POST['is_current']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if ($is_current) {
            $db->query("UPDATE issues SET is_current = 0");
        }
        $stmt = $db->prepare("UPDATE issues SET volume_id = ?, issue_number = ?, title = ?, description = ?, publication_date = ?, is_current = ?, is_active = ? WHERE id = ?");
        if ($stmt->execute([$volume_id, $issue_number, $title, $description, $publication_date, $is_current, $is_active, $id])) {
            $message = 'Issue updated successfully!';
            logAction($currentUser['id'], 'update_issue', 'issues', $id);
        } else {
            $error = 'Failed to update issue.';
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        
        // Check if issue has articles
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM manuscripts WHERE issue_id = ? AND status = 'published'");
        $stmt->execute([$id]);
        $count = $stmt->fetch()['count'];
        
        if ($count > 0) {
            $error = 'Cannot delete issue with published articles.';
        } else {
            $stmt = $db->prepare("DELETE FROM issues WHERE id = ?");
            if ($stmt->execute([$id])) {
                $message = 'Issue deleted successfully!';
                logAction($currentUser['id'], 'delete_issue', 'issues', $id);
            } else {
                $error = 'Failed to delete issue.';
            }
        }
    }
}

// Get volumes for dropdown
$volumes = getVolumes();

// Get issues
$issues = [];
if ($volumeFilter > 0) {
    $issues = getIssuesByVolume($volumeFilter);
} else {
    $stmt = $db->query("SELECT i.*, v.volume_number, v.year FROM issues i JOIN volumes v ON i.volume_id = v.id ORDER BY v.volume_number DESC, i.issue_number DESC");
    $issues = $stmt->fetchAll();
}
?>
<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Issues</h2>
            <p class="text-gray-500 text-sm mt-1">Manage journal issues</p>
        </div>
        <div class="flex gap-3">
            <a href="/jms/admin" class="text-indigo-600 hover:text-indigo-800 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
            <button onclick="openCreateModal()" class="bg-[#0b2b3f] text-white px-4 py-2 rounded-lg hover:bg-[#123a4f] transition text-sm">
                <i class="fas fa-plus mr-1"></i> New Issue
            </button>
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

    <!-- Filter -->
    <div class="mb-6 flex flex-wrap gap-3 items-center">
        <label class="text-sm font-medium text-gray-700">Filter by Volume:</label>
        <select onchange="window.location.href='?action=issues&volume_id='+this.value" class="px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none text-sm">
            <option value="0">All Volumes</option>
            <?php foreach ($volumes as $volume): ?>
            <option value="<?= $volume['id'] ?>" <?= $volumeFilter == $volume['id'] ? 'selected' : '' ?>>
                Volume <?= $volume['volume_number'] ?> (<?= $volume['year'] ?>)
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php if (empty($issues)): ?>
        <div class="text-center py-12">
            <i class="fas fa-folder-open text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No issues found.</p>
            <?php if (!empty($volumes)): ?>
                <button onclick="openCreateModal()" class="mt-3 bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                    <i class="fas fa-plus mr-2"></i> Create First Issue
                </button>
            <?php else: ?>
                <p class="text-sm text-gray-400 mt-2">Please create a volume first.</p>
                <a href="/jms/admin?action=volumes" class="mt-3 inline-block bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                    <i class="fas fa-layer-group mr-2"></i> Create Volume
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Issue</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Volume</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Title</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Publication Date</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Articles</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($issues as $issue): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                        <td class="py-3 px-4 font-medium text-[#0b2b3f]">Issue <?= $issue['issue_number'] ?></td>
                        <td class="py-3 px-4 text-gray-600">Vol. <?= $issue['volume_number'] ?> (<?= $issue['year'] ?>)</td>
                        <td class="py-3 px-4 text-gray-600"><?= htmlspecialchars($issue['title'] ?? '-') ?></td>
                        <td class="py-3 px-4 text-gray-600"><?= $issue['publication_date'] ? formatDate($issue['publication_date']) : '-' ?></td>
                        <td class="py-3 px-4 text-gray-600"><?= $issue['article_count'] ?? 0 ?></td>
                        <td class="py-3 px-4">
                            <div class="flex gap-2">
                                <?php if ($issue['is_current']): ?>
                                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">Current</span>
                                <?php endif; ?>
                                <span class="px-2 py-1 rounded-full text-xs font-medium <?= $issue['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' ?>">
                                    <?= $issue['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </div>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex gap-2">
                                <button onclick="openEditModal(<?= htmlspecialchars(json_encode($issue)) ?>)" 
                                        class="text-indigo-600 hover:text-indigo-800 text-sm">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="deleteIssue(<?= $issue['id'] ?>)" 
                                        class="text-red-600 hover:text-red-800 text-sm">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Create/Edit Modal -->
<div id="issueModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-[#0b2b3f]" id="modalTitle">Create Issue</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" id="issueForm">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="formId" value="">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Volume *</label>
                    <select name="volume_id" id="issueVolume" required class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                        <option value="">Select Volume</option>
                        <?php foreach ($volumes as $volume): ?>
                        <option value="<?= $volume['id'] ?>">Volume <?= $volume['volume_number'] ?> (<?= $volume['year'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Issue Number *</label>
                    <input type="number" name="issue_number" id="issueNumber" required min="1"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                    <input type="text" name="title" id="issueTitle" 
                           placeholder="e.g., Special Issue on Rehabilitation"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" id="issueDescription" rows="3"
                              class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Publication Date</label>
                    <input type="date" name="publication_date" id="issueDate"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_current" id="issueCurrent">
                        <span class="text-sm text-gray-700">Set as Current Issue</span>
                    </label>
                </div>
                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" id="issueActive" checked>
                        <span class="text-sm text-gray-700">Active</span>
                    </label>
                </div>
            </div>
            
            <div class="flex gap-3 mt-6">
                <button type="submit" class="bg-[#0b2b3f] text-white px-6 py-2 rounded-lg font-semibold hover:bg-[#123a4f] transition flex-1">
                    <i class="fas fa-save mr-2"></i> Save
                </button>
                <button type="button" onclick="closeModal()" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg font-semibold hover:bg-gray-200 transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Create Issue';
    document.getElementById('formAction').value = 'create';
    document.getElementById('formId').value = '';
    document.getElementById('issueVolume').value = '<?= $volumeFilter ?>';
    document.getElementById('issueNumber').value = '';
    document.getElementById('issueTitle').value = '';
    document.getElementById('issueDescription').value = '';
    document.getElementById('issueDate').value = '';
    document.getElementById('issueCurrent').checked = false;
    document.getElementById('issueActive').checked = true;
    document.getElementById('issueModal').classList.remove('hidden');
}

function openEditModal(issue) {
    document.getElementById('modalTitle').textContent = 'Edit Issue';
    document.getElementById('formAction').value = 'update';
    document.getElementById('formId').value = issue.id;
    document.getElementById('issueVolume').value = issue.volume_id;
    document.getElementById('issueNumber').value = issue.issue_number;
    document.getElementById('issueTitle').value = issue.title || '';
    document.getElementById('issueDescription').value = issue.description || '';
    document.getElementById('issueDate').value = issue.publication_date || '';
    document.getElementById('issueCurrent').checked = issue.is_current == 1;
    document.getElementById('issueActive').checked = issue.is_active == 1;
    document.getElementById('issueModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('issueModal').classList.add('hidden');
}

function deleteIssue(id) {
    if (confirm('Are you sure you want to delete this issue? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
<?php
// modules/admin/pages/volumes.php - Manage Volumes
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

requireRole(['admin']);

$db = getDB();
$message = '';
$error = '';

// Handle create/update/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $volume_number = (int)$_POST['volume_number'];
        $year = (int)$_POST['year'];
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        
        // Check if volume already exists
        $stmt = $db->prepare("SELECT id FROM volumes WHERE volume_number = ? AND year = ?");
        $stmt->execute([$volume_number, $year]);
        if ($stmt->fetch()) {
            $error = 'Volume ' . $volume_number . ' for year ' . $year . ' already exists!';
        } else {
            $stmt = $db->prepare("INSERT INTO volumes (volume_number, year, title, description, is_active) VALUES (?, ?, ?, ?, 1)");
            if ($stmt->execute([$volume_number, $year, $title, $description])) {
                $message = 'Volume created successfully!';
                logAction($currentUser['id'], 'create_volume', 'volumes', $db->lastInsertId());
            } else {
                $error = 'Failed to create volume.';
            }
        }
    } elseif ($action === 'update') {
        $id = (int)$_POST['id'];
        $volume_number = (int)$_POST['volume_number'];
        $year = (int)$_POST['year'];
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt = $db->prepare("UPDATE volumes SET volume_number = ?, year = ?, title = ?, description = ?, is_active = ? WHERE id = ?");
        if ($stmt->execute([$volume_number, $year, $title, $description, $is_active, $id])) {
            $message = 'Volume updated successfully!';
            logAction($currentUser['id'], 'update_volume', 'volumes', $id);
        } else {
            $error = 'Failed to update volume.';
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        
        // Check if volume has issues
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM issues WHERE volume_id = ?");
        $stmt->execute([$id]);
        $count = $stmt->fetch()['count'];
        
        if ($count > 0) {
            $error = 'Cannot delete volume with existing issues. Delete issues first.';
        } else {
            $stmt = $db->prepare("DELETE FROM volumes WHERE id = ?");
            if ($stmt->execute([$id])) {
                $message = 'Volume deleted successfully!';
                logAction($currentUser['id'], 'delete_volume', 'volumes', $id);
            } else {
                $error = 'Failed to delete volume.';
            }
        }
    }
}

// Get all volumes
$volumes = getVolumes();
?>
<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Volumes</h2>
            <p class="text-gray-500 text-sm mt-1">Manage journal volumes</p>
        </div>
        <div class="flex gap-3">
            <a href="/jms/admin" class="text-indigo-600 hover:text-indigo-800 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
            <button onclick="openCreateModal()" class="bg-[#0b2b3f] text-white px-4 py-2 rounded-lg hover:bg-[#123a4f] transition text-sm">
                <i class="fas fa-plus mr-1"></i> New Volume
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

    <?php if (empty($volumes)): ?>
        <div class="text-center py-12">
            <i class="fas fa-layer-group text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No volumes created yet.</p>
            <button onclick="openCreateModal()" class="mt-3 bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                <i class="fas fa-plus mr-2"></i> Create First Volume
            </button>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Volume</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Year</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Title</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Issues</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Articles</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($volumes as $volume): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                        <td class="py-3 px-4 font-medium text-[#0b2b3f]">Vol. <?= $volume['volume_number'] ?></td>
                        <td class="py-3 px-4 text-gray-600"><?= $volume['year'] ?></td>
                        <td class="py-3 px-4 text-gray-600"><?= htmlspecialchars($volume['title'] ?? '-') ?></td>
                        <td class="py-3 px-4 text-gray-600"><?= $volume['issue_count'] ?? 0 ?></td>
                        <td class="py-3 px-4 text-gray-600"><?= $volume['article_count'] ?? 0 ?></td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 rounded-full text-xs font-medium <?= $volume['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' ?>">
                                <?= $volume['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex gap-2">
                                <button onclick="openEditModal(<?= htmlspecialchars(json_encode($volume)) ?>)" 
                                        class="text-indigo-600 hover:text-indigo-800 text-sm">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="deleteVolume(<?= $volume['id'] ?>)" 
                                        class="text-red-600 hover:text-red-800 text-sm">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <a href="/jms/admin?action=issues&volume_id=<?= $volume['id'] ?>" 
                                   class="text-blue-600 hover:text-blue-800 text-sm">
                                    <i class="fas fa-folder-open"></i>
                                </a>
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
<div id="volumeModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-[#0b2b3f]" id="modalTitle">Create Volume</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" id="volumeForm">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="formId" value="">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Volume Number *</label>
                    <input type="number" name="volume_number" id="volumeNumber" required min="1"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Year *</label>
                    <input type="number" name="year" id="volumeYear" required min="2000" max="2100"
                           value="<?= date('Y') ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                    <input type="text" name="title" id="volumeTitle" 
                           placeholder="e.g., Volume 12"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" id="volumeDescription" rows="3"
                              class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition"></textarea>
                </div>
                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" id="volumeActive" checked>
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
    document.getElementById('modalTitle').textContent = 'Create Volume';
    document.getElementById('formAction').value = 'create';
    document.getElementById('formId').value = '';
    document.getElementById('volumeNumber').value = '';
    document.getElementById('volumeYear').value = new Date().getFullYear();
    document.getElementById('volumeTitle').value = '';
    document.getElementById('volumeDescription').value = '';
    document.getElementById('volumeActive').checked = true;
    document.getElementById('volumeModal').classList.remove('hidden');
}

function openEditModal(volume) {
    document.getElementById('modalTitle').textContent = 'Edit Volume';
    document.getElementById('formAction').value = 'update';
    document.getElementById('formId').value = volume.id;
    document.getElementById('volumeNumber').value = volume.volume_number;
    document.getElementById('volumeYear').value = volume.year;
    document.getElementById('volumeTitle').value = volume.title || '';
    document.getElementById('volumeDescription').value = volume.description || '';
    document.getElementById('volumeActive').checked = volume.is_active == 1;
    document.getElementById('volumeModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('volumeModal').classList.add('hidden');
}

function deleteVolume(id) {
    if (confirm('Are you sure you want to delete this volume? This action cannot be undone.')) {
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
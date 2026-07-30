<?php
// modules/admin/pages/editors.php - Manage Editors
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

requireRole(['admin']);

$db = getDB();
$message = '';
$error = '';
$currentUser = getCurrentUser();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_editor') {
        $user_id = (int)$_POST['user_id'];
        $position = trim($_POST['position'] ?? '');
        $affiliation = trim($_POST['affiliation'] ?? '');
        $expertise = trim($_POST['expertise'] ?? '');
        $biography = trim($_POST['biography'] ?? '');
        
        // Check if already on editorial board
        $stmt = $db->prepare("SELECT id FROM editorial_board WHERE user_id = ?");
        $stmt->execute([$user_id]);
        if ($stmt->fetch()) {
            $error = 'This user is already on the editorial board.';
        } else {
            $stmt = $db->prepare("INSERT INTO editorial_board (user_id, position, affiliation, expertise, biography, is_active) VALUES (?, ?, ?, ?, ?, 1)");
            if ($stmt->execute([$user_id, $position, $affiliation, $expertise, $biography])) {
                // Update user role to editor if not already
                $stmt = $db->prepare("UPDATE users SET role = 'editor' WHERE id = ? AND role NOT IN ('admin')");
                $stmt->execute([$user_id]);
                
                $message = 'Editor added successfully!';
                logAction($currentUser['id'], 'add_editor', 'editorial_board', $db->lastInsertId());
            } else {
                $error = 'Failed to add editor.';
            }
        }
    } elseif ($action === 'update_editor') {
        $id = (int)$_POST['id'];
        $position = trim($_POST['position'] ?? '');
        $affiliation = trim($_POST['affiliation'] ?? '');
        $expertise = trim($_POST['expertise'] ?? '');
        $biography = trim($_POST['biography'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt = $db->prepare("UPDATE editorial_board SET position = ?, affiliation = ?, expertise = ?, biography = ?, is_active = ? WHERE id = ?");
        if ($stmt->execute([$position, $affiliation, $expertise, $biography, $is_active, $id])) {
            $message = 'Editor updated successfully!';
            logAction($currentUser['id'], 'update_editor', 'editorial_board', $id);
        } else {
            $error = 'Failed to update editor.';
        }
    } elseif ($action === 'remove_editor') {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("DELETE FROM editorial_board WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = 'Editor removed successfully!';
            logAction($currentUser['id'], 'remove_editor', 'editorial_board', $id);
        } else {
            $error = 'Failed to remove editor.';
        }
    }
}

// Get all editorial board members
$boardMembers = getEditorialBoard();

// Get all users who can be editors (not already on board)
$stmt = $db->query("
    SELECT u.* FROM users u 
    WHERE u.id NOT IN (SELECT user_id FROM editorial_board) 
    AND u.role IN ('admin', 'editor', 'author')
    AND u.is_active = 1
    ORDER BY u.full_name
");
$availableUsers = $stmt->fetchAll();
?>
<!-- NO OUTER CONTAINER - The main dashboard provides it -->
<div class="space-y-6">
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

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Editors</h2>
            <p class="text-gray-500 text-sm mt-1">Manage journal editors and editorial board members</p>
        </div>
        <button onclick="openCreateModal()" class="bg-[#0b2b3f] text-white px-4 py-2 rounded-lg hover:bg-[#123a4f] transition text-sm">
            <i class="fas fa-plus mr-1"></i> Add Editor
        </button>
    </div>
    <div class="h-1 w-20 bg-indigo-200 rounded-full"></div>

    <?php if (empty($boardMembers)): ?>
        <div class="text-center py-12">
            <i class="fas fa-user-edit text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No editors added yet.</p>
            <button onclick="openCreateModal()" class="mt-3 bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                <i class="fas fa-plus mr-2"></i> Add First Editor
            </button>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($boardMembers as $member): ?>
            <div class="border border-gray-200 rounded-xl p-4 hover:shadow-md transition bg-white">
                <div class="flex items-start gap-3">
                    <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-lg flex-shrink-0">
                        <?php 
                        $initials = '';
                        $nameParts = explode(' ', $member['full_name']);
                        foreach ($nameParts as $part) {
                            if (!empty($part)) {
                                $initials .= strtoupper(substr($part, 0, 1));
                            }
                        }
                        echo htmlspecialchars(substr($initials, 0, 2));
                        ?>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-start justify-between">
                            <div>
                                <h4 class="font-semibold text-[#0b2b3f]"><?= htmlspecialchars($member['full_name']) ?></h4>
                                <p class="text-xs text-indigo-600 font-medium"><?= htmlspecialchars($member['position'] ?? 'Editor') ?></p>
                            </div>
                            <div class="flex gap-1">
                                <button onclick="openEditModal(<?= htmlspecialchars(json_encode($member)) ?>)" 
                                        class="text-indigo-600 hover:text-indigo-800 text-sm">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="removeEditor(<?= $member['id'] ?>)" 
                                        class="text-red-600 hover:text-red-800 text-sm">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <?php if ($member['affiliation']): ?>
                            <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($member['affiliation']) ?></p>
                        <?php endif; ?>
                        <?php if ($member['expertise']): ?>
                            <p class="text-xs text-gray-400 mt-1">Expertise: <?= htmlspecialchars($member['expertise']) ?></p>
                        <?php endif; ?>
                        <div class="mt-2 flex gap-2">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $member['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' ?>">
                                <?= $member['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-600">
                                <?= ucfirst($member['role'] ?? 'editor') ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-4 text-sm text-gray-400">
            Showing <?= count($boardMembers) ?> editors
        </div>
    <?php endif; ?>
</div>

<!-- Create/Edit Modal -->
<div id="editorModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-[#0b2b3f]" id="modalTitle">Add Editor</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" id="editorForm">
            <input type="hidden" name="action" id="formAction" value="add_editor">
            <input type="hidden" name="id" id="formId" value="">
            
            <div class="space-y-4">
                <div id="userSelectWrapper">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Select User *</label>
                    <select name="user_id" id="editorUser" required class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                        <option value="">Select a user...</option>
                        <?php foreach ($availableUsers as $user): ?>
                        <option value="<?= $user['id'] ?>">
                            <?= htmlspecialchars($user['full_name']) ?> (<?= htmlspecialchars($user['email']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Position *</label>
                    <input type="text" name="position" id="editorPosition" required 
                           placeholder="e.g., Editor-in-Chief, Associate Editor"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Affiliation</label>
                    <input type="text" name="affiliation" id="editorAffiliation" 
                           placeholder="e.g., University of Dar es Salaam"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Expertise</label>
                    <input type="text" name="expertise" id="editorExpertise" 
                           placeholder="e.g., Occupational Therapy, Rehabilitation"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Biography</label>
                    <textarea name="biography" id="editorBiography" rows="3"
                              placeholder="Brief biography of the editor"
                              class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none"></textarea>
                </div>
                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" id="editorActive" checked>
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
    document.getElementById('modalTitle').textContent = 'Add Editor';
    document.getElementById('formAction').value = 'add_editor';
    document.getElementById('formId').value = '';
    document.getElementById('editorUser').value = '';
    document.getElementById('editorPosition').value = '';
    document.getElementById('editorAffiliation').value = '';
    document.getElementById('editorExpertise').value = '';
    document.getElementById('editorBiography').value = '';
    document.getElementById('editorActive').checked = true;
    document.getElementById('userSelectWrapper').style.display = 'block';
    document.getElementById('editorModal').classList.remove('hidden');
}

function openEditModal(editor) {
    document.getElementById('modalTitle').textContent = 'Edit Editor';
    document.getElementById('formAction').value = 'update_editor';
    document.getElementById('formId').value = editor.id;
    document.getElementById('userSelectWrapper').style.display = 'none';
    document.getElementById('editorPosition').value = editor.position || '';
    document.getElementById('editorAffiliation').value = editor.affiliation || '';
    document.getElementById('editorExpertise').value = editor.expertise || '';
    document.getElementById('editorBiography').value = editor.biography || '';
    document.getElementById('editorActive').checked = editor.is_active == 1;
    document.getElementById('editorModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('editorModal').classList.add('hidden');
}

function removeEditor(id) {
    if (confirm('Are you sure you want to remove this editor from the editorial board?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="remove_editor">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
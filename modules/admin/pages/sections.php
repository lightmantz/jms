<?php
// modules/admin/pages/sections.php - Manage Sections/Categories
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
        $name = trim($_POST['name'] ?? '');
        $description = $_POST['description'] ?? '';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($name)) {
            $error = 'Section name is required.';
        } else {
            $stmt = $db->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            if ($stmt->execute([$name, $description])) {
                $message = 'Section created successfully!';
                logAction($currentUser['id'], 'create_section', 'categories', $db->lastInsertId());
            } else {
                $error = 'Failed to create section.';
            }
        }
    } elseif ($action === 'update') {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name'] ?? '');
        $description = $_POST['description'] ?? '';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($name)) {
            $error = 'Section name is required.';
        } else {
            $stmt = $db->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            if ($stmt->execute([$name, $description, $id])) {
                $message = 'Section updated successfully!';
                logAction($currentUser['id'], 'update_section', 'categories', $id);
            } else {
                $error = 'Failed to update section.';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        
        // Check if section has articles
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM manuscript_keywords WHERE category_id = ?");
        $stmt->execute([$id]);
        $count = $stmt->fetch()['count'];
        
        if ($count > 0) {
            $error = 'Cannot delete section that has articles assigned.';
        } else {
            $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
            if ($stmt->execute([$id])) {
                $message = 'Section deleted successfully!';
                logAction($currentUser['id'], 'delete_section', 'categories', $id);
            } else {
                $error = 'Failed to delete section.';
            }
        }
    }
}

// Get all sections (categories)
$sections = getCategories();
?>
<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Sections</h2>
            <p class="text-gray-500 text-sm mt-1">Manage article sections/categories</p>
        </div>
        <div class="flex gap-3">
            <a href="/jms/admin" class="text-indigo-600 hover:text-indigo-800 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
            <button onclick="openCreateModal()" class="bg-[#0b2b3f] text-white px-4 py-2 rounded-lg hover:bg-[#123a4f] transition text-sm">
                <i class="fas fa-plus mr-1"></i> New Section
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

    <?php if (empty($sections)): ?>
        <div class="text-center py-12">
            <i class="fas fa-tags text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No sections created yet.</p>
            <button onclick="openCreateModal()" class="mt-3 bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                <i class="fas fa-plus mr-2"></i> Create First Section
            </button>
        </div>
    <?php else: ?>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($sections as $section): ?>
            <div class="border border-gray-200 rounded-xl p-4 hover:shadow-md transition">
                <div class="flex items-start justify-between">
                    <div>
                        <h4 class="font-semibold text-[#0b2b3f]"><?= htmlspecialchars($section['name']) ?></h4>
                        <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($section['description'] ?? 'No description') ?></p>
                        <?php 
                        // Get article count for this section
                        $stmt = $db->prepare("SELECT COUNT(*) as count FROM manuscript_keywords WHERE category_id = ?");
                        $stmt->execute([$section['id']]);
                        $count = $stmt->fetch()['count'];
                        ?>
                        <span class="text-xs text-gray-400 mt-2 inline-block"><?= $count ?> articles</span>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="openEditModal(<?= htmlspecialchars(json_encode($section)) ?>)" 
                                class="text-indigo-600 hover:text-indigo-800 text-sm">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteSection(<?= $section['id'] ?>)" 
                                class="text-red-600 hover:text-red-800 text-sm">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Create/Edit Modal -->
<div id="sectionModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-[#0b2b3f]" id="modalTitle">Create Section</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" id="sectionForm">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="formId" value="">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Section Name *</label>
                    <input type="text" name="name" id="sectionName" required 
                           placeholder="e.g., Occupational Therapy"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" id="sectionDescription" rows="3"
                              placeholder="Brief description of this section"
                              class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition"></textarea>
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
    document.getElementById('modalTitle').textContent = 'Create Section';
    document.getElementById('formAction').value = 'create';
    document.getElementById('formId').value = '';
    document.getElementById('sectionName').value = '';
    document.getElementById('sectionDescription').value = '';
    document.getElementById('sectionModal').classList.remove('hidden');
}

function openEditModal(section) {
    document.getElementById('modalTitle').textContent = 'Edit Section';
    document.getElementById('formAction').value = 'update';
    document.getElementById('formId').value = section.id;
    document.getElementById('sectionName').value = section.name;
    document.getElementById('sectionDescription').value = section.description || '';
    document.getElementById('sectionModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('sectionModal').classList.add('hidden');
}

function deleteSection(id) {
    if (confirm('Are you sure you want to delete this section? This action cannot be undone.')) {
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
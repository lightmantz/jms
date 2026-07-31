<?php
// modules/editor/pages/editor-assignment.php - Editor Assignment
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

$db = getDB();
$currentUser = getCurrentUser();
$message = '';
$error = '';

$manuscriptId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($manuscriptId > 0) {
    $manuscript = getManuscript($manuscriptId);
}

// Get editors
$editors = getEditors();

// Handle assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_editor'])) {
    $manuscript_id = (int)$_POST['manuscript_id'];
    $editor_id = (int)$_POST['editor_id'];
    
    if ($editor_id <= 0) {
        $error = 'Please select an editor.';
    } else {
        if (assignEditor($manuscript_id, $editor_id, $currentUser['id'])) {
            $message = 'Editor assigned successfully!';
            // Refresh manuscript data
            $manuscript = getManuscript($manuscript_id);
        } else {
            $error = 'Failed to assign editor.';
        }
    }
}

// Get manuscripts without editor
$stmt = $db->query("
    SELECT m.id, m.title, u.full_name as author_name 
    FROM manuscripts m
    LEFT JOIN users u ON m.corresponding_author_id = u.id
    WHERE m.editor_assigned_id IS NULL AND m.status = 'submitted'
    ORDER BY m.submission_date DESC
");
$unassignedManuscripts = $stmt->fetchAll();
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Editor Assignment</h2>
            <p class="text-gray-500 text-sm mt-1">Assign editors to manuscripts</p>
        </div>
        <a href="/jms/editor" class="text-indigo-600 hover:text-indigo-800 text-sm">
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

    <?php if ($manuscriptId > 0 && isset($manuscript)): ?>
        <!-- Assign Editor to Specific Manuscript -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">
                Assign Editor to: <?= htmlspecialchars(substr($manuscript['title'], 0, 60)) ?>...
            </h3>
            
            <div class="grid md:grid-cols-2 gap-4 mb-4">
                <div>
                    <p class="text-sm text-gray-500">Author</p>
                    <p class="font-medium"><?= htmlspecialchars($manuscript['author_name'] ?? 'Unknown') ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Current Editor</p>
                    <p class="font-medium"><?= htmlspecialchars($manuscript['editor_name'] ?? 'Not assigned') ?></p>
                </div>
            </div>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="manuscript_id" value="<?= $manuscriptId ?>">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Select Editor *</label>
                    <select name="editor_id" required class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none transition">
                        <option value="">Select an editor...</option>
                        <?php foreach ($editors as $editor): ?>
                        <option value="<?= $editor['id'] ?>" <?= $manuscript['editor_assigned_id'] == $editor['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($editor['full_name']) ?> (<?= htmlspecialchars($editor['email']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="assign_editor" class="bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                    <i class="fas fa-user-plus mr-2"></i> Assign Editor
                </button>
            </form>
        </div>
    <?php endif; ?>

    <!-- Unassigned Manuscripts -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">Unassigned Manuscripts</h3>
        <?php if (empty($unassignedManuscripts)): ?>
            <p class="text-sm text-gray-500">All manuscripts have assigned editors.</p>
        <?php else: ?>
            <div class="space-y-2">
                <?php foreach ($unassignedManuscripts as $manuscript): ?>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                    <div>
                        <p class="font-medium text-[#0b2b3f]"><?= htmlspecialchars(substr($manuscript['title'], 0, 50)) ?>...</p>
                        <p class="text-xs text-gray-500">Author: <?= htmlspecialchars($manuscript['author_name'] ?? 'Unknown') ?></p>
                    </div>
                    <a href="/jms/editor?action=editor-assignment&id=<?= $manuscript['id'] ?>" class="bg-[#0b2b3f] text-white px-4 py-2 rounded-lg hover:bg-[#123a4f] transition text-sm">
                        <i class="fas fa-user-plus mr-1"></i> Assign
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
// modules/publisher/pages/create-volume.php - Create Volume
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

$db = getDB();
$currentUser = getCurrentUser();
$message = '';
$error = '';

// Get existing volumes
$volumes = getVolumes();

// Handle volume creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_volume'])) {
    $volume_number = (int)$_POST['volume_number'];
    $year = (int)$_POST['year'];
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if ($volume_number <= 0 || $year <= 0) {
        $error = 'Please enter valid volume number and year.';
    } else {
        // Check if volume already exists
        $stmt = $db->prepare("SELECT id FROM volumes WHERE volume_number = ? AND year = ?");
        $stmt->execute([$volume_number, $year]);
        if ($stmt->fetch()) {
            $error = 'Volume ' . $volume_number . ' for year ' . $year . ' already exists.';
        } else {
            $data = [
                'volume_number' => $volume_number,
                'year' => $year,
                'title' => $title,
                'description' => $description
            ];
            if (createVolume($data)) {
                $message = 'Volume created successfully!';
                logAction($currentUser['id'], 'create_volume', 'volumes', $db->lastInsertId());
                // Refresh data
                $volumes = getVolumes();
            } else {
                $error = 'Failed to create volume.';
            }
        }
    }
}
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Create Volume</h2>
            <p class="text-gray-500 text-sm mt-1">Create a new journal volume</p>
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

    <div class="grid md:grid-cols-2 gap-6">
        <!-- Create Volume Form -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">New Volume</h3>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Volume Number *</label>
                    <input type="number" name="volume_number" required min="1" 
                           value="<?= count($volumes) + 1 ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Year *</label>
                    <input type="number" name="year" required min="2000" max="2100" 
                           value="<?= date('Y') ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                    <input type="text" name="title" 
                           placeholder="e.g., Volume 12"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="3"
                              placeholder="Volume description..."
                              class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none transition"></textarea>
                </div>
                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" checked>
                        <span class="text-sm text-gray-700">Active</span>
                    </label>
                </div>
                <button type="submit" name="create_volume" class="bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition w-full">
                    <i class="fas fa-plus mr-2"></i> Create Volume
                </button>
            </form>
        </div>

        <!-- Existing Volumes -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">Existing Volumes</h3>
            <?php if (empty($volumes)): ?>
                <p class="text-sm text-gray-500">No volumes created yet.</p>
            <?php else: ?>
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    <?php foreach ($volumes as $volume): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                        <div>
                            <p class="font-medium text-[#0b2b3f]">Volume <?= $volume['volume_number'] ?></p>
                            <p class="text-xs text-gray-500"><?= $volume['year'] ?> · <?= $volume['issue_count'] ?? 0 ?> issues</p>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $volume['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' ?>">
                            <?= $volume['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
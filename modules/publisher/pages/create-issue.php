<?php
// modules/publisher/pages/create-issue.php - Create Issue
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

$db = getDB();
$currentUser = getCurrentUser();
$message = '';
$error = '';

$volumes = getVolumes();
$selectedVolume = isset($_GET['volume_id']) ? (int)$_GET['volume_id'] : 0;

// Get issues for selected volume
$issues = [];
if ($selectedVolume > 0) {
    $issues = getIssuesByVolume($selectedVolume);
}

// Handle issue creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_issue'])) {
    $volume_id = (int)$_POST['volume_id'];
    $issue_number = (int)$_POST['issue_number'];
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $publication_date = $_POST['publication_date'] ?? null;
    $is_current = isset($_POST['is_current']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if ($volume_id <= 0 || $issue_number <= 0) {
        $error = 'Please select a volume and enter an issue number.';
    } else {
        // Check if issue already exists
        $stmt = $db->prepare("SELECT id FROM issues WHERE volume_id = ? AND issue_number = ?");
        $stmt->execute([$volume_id, $issue_number]);
        if ($stmt->fetch()) {
            $error = 'Issue ' . $issue_number . ' for this volume already exists.';
        } else {
            $data = [
                'volume_id' => $volume_id,
                'issue_number' => $issue_number,
                'title' => $title,
                'description' => $description,
                'publication_date' => $publication_date,
                'is_current' => $is_current
            ];
            if (createIssue($data)) {
                $message = 'Issue created successfully!';
                logAction($currentUser['id'], 'create_issue', 'issues', $db->lastInsertId());
                // Refresh data
                if ($selectedVolume > 0) {
                    $issues = getIssuesByVolume($selectedVolume);
                }
            } else {
                $error = 'Failed to create issue.';
            }
        }
    }
}
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Create Issue</h2>
            <p class="text-gray-500 text-sm mt-1">Create a new issue for a volume</p>
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

    <?php if (empty($volumes)): ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 text-center">
            <i class="fas fa-exclamation-triangle text-3xl text-yellow-500 mb-3"></i>
            <p class="text-yellow-700">No volumes available. Please create a volume first.</p>
            <a href="/jms/publisher?action=create-volume" class="mt-3 inline-block bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                <i class="fas fa-plus mr-2"></i> Create Volume
            </a>
        </div>
    <?php else: ?>
        <div class="grid md:grid-cols-2 gap-6">
            <!-- Create Issue Form -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">New Issue</h3>
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Volume *</label>
                        <select name="volume_id" required 
                                class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none transition"
                                onchange="window.location.href='/jms/publisher?action=create-issue&volume_id='+this.value">
                            <option value="">Select Volume...</option>
                            <?php foreach ($volumes as $volume): ?>
                            <option value="<?= $volume['id'] ?>" <?= $selectedVolume == $volume['id'] ? 'selected' : '' ?>>
                                Volume <?= $volume['volume_number'] ?> (<?= $volume['year'] ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Issue Number *</label>
                        <input type="number" name="issue_number" required min="1" 
                               value="<?= count($issues) + 1 ?>"
                               class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                        <input type="text" name="title" 
                               placeholder="e.g., Special Issue on Rehabilitation"
                               class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" rows="3"
                                  placeholder="Issue description..."
                                  class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none transition"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Publication Date</label>
                        <input type="date" name="publication_date" value="<?= date('Y-m-d') ?>"
                               class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none transition">
                    </div>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="is_current">
                            <span class="text-sm text-gray-700">Set as Current Issue</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="is_active" checked>
                            <span class="text-sm text-gray-700">Active</span>
                        </label>
                    </div>
                    <button type="submit" name="create_issue" class="bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition w-full">
                        <i class="fas fa-plus mr-2"></i> Create Issue
                    </button>
                </form>
            </div>

            <!-- Existing Issues -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">
                    Issues for Selected Volume
                    <?php if ($selectedVolume > 0): ?>
                        <span class="text-sm font-normal text-gray-500">
                            (Vol. <?= $selectedVolume ?>)
                        </span>
                    <?php endif; ?>
                </h3>
                <?php if ($selectedVolume == 0): ?>
                    <p class="text-sm text-gray-500">Select a volume to view its issues.</p>
                <?php elseif (empty($issues)): ?>
                    <p class="text-sm text-gray-500">No issues found for this volume.</p>
                <?php else: ?>
                    <div class="space-y-2 max-h-96 overflow-y-auto">
                        <?php foreach ($issues as $issue): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <div>
                                <p class="font-medium text-[#0b2b3f]">Issue <?= $issue['issue_number'] ?></p>
                                <p class="text-xs text-gray-500"><?= htmlspecialchars($issue['title'] ?? 'No title') ?></p>
                            </div>
                            <div class="flex items-center gap-2">
                                <?php if ($issue['is_current']): ?>
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">Current</span>
                                <?php endif; ?>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $issue['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' ?>">
                                    <?= $issue['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
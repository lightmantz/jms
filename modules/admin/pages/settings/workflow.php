<?php
// modules/admin/pages/settings/workflow.php - Workflow Settings
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../../includes/functions.php';
    require_once __DIR__ . '/../../../../includes/auth.php';
}

$db = getDB();
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_workflow_settings'])) {
    $settings = [
        'workflow_review_type' => $_POST['workflow_review_type'] ?? 'double_blind',
        'workflow_required_reviews' => (int)$_POST['workflow_required_reviews'] ?? 2,
        'workflow_review_days' => (int)$_POST['workflow_review_days'] ?? 14,
        'workflow_editor_assignment' => isset($_POST['workflow_editor_assignment']) ? 'true' : 'false',
        'workflow_auto_assign_reviewers' => isset($_POST['workflow_auto_assign_reviewers']) ? 'true' : 'false',
        'workflow_notify_author_on_status' => isset($_POST['workflow_notify_author_on_status']) ? 'true' : 'false',
        'workflow_max_revisions' => (int)$_POST['workflow_max_revisions'] ?? 2,
        'workflow_revision_days' => (int)$_POST['workflow_revision_days'] ?? 30,
        'workflow_plagiarism_check' => isset($_POST['workflow_plagiarism_check']) ? 'true' : 'false',
        'workflow_plagiarism_threshold' => (float)$_POST['workflow_plagiarism_threshold'] ?? 20,
        'workflow_auto_assign_editor' => isset($_POST['workflow_auto_assign_editor']) ? 'true' : 'false',
    ];

    try {
        foreach ($settings as $key => $value) {
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, setting_group) 
                                   VALUES (?, ?, 'workflow') 
                                   ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }
        $message = 'Workflow settings saved successfully!';
        logAction($currentUser['id'], 'update_workflow_settings', 'settings', 0);
    } catch (Exception $e) {
        $error = 'Failed to save settings: ' . $e->getMessage();
    }
}

// Get current settings
$settings = [];
$stmt = $db->query("SELECT * FROM settings WHERE setting_group = 'workflow'");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Default values
$defaults = [
    'workflow_review_type' => 'double_blind',
    'workflow_required_reviews' => '2',
    'workflow_review_days' => '14',
    'workflow_editor_assignment' => 'true',
    'workflow_auto_assign_reviewers' => 'false',
    'workflow_notify_author_on_status' => 'true',
    'workflow_max_revisions' => '2',
    'workflow_revision_days' => '30',
    'workflow_plagiarism_check' => 'true',
    'workflow_plagiarism_threshold' => '20',
    'workflow_auto_assign_editor' => 'false'
];

foreach ($defaults as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}
?>
<div>
    <form method="POST" class="space-y-8">
        <!-- Review Settings -->
        <div>
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">Review Settings</h3>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Review Type</label>
                    <select name="workflow_review_type" class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                        <option value="single_blind" <?= $settings['workflow_review_type'] == 'single_blind' ? 'selected' : '' ?>>Single-Blind</option>
                        <option value="double_blind" <?= $settings['workflow_review_type'] == 'double_blind' ? 'selected' : '' ?>>Double-Blind</option>
                        <option value="open_review" <?= $settings['workflow_review_type'] == 'open_review' ? 'selected' : '' ?>>Open Review</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Required Reviews</label>
                    <input type="number" name="workflow_required_reviews" min="1" max="10"
                           value="<?= htmlspecialchars($settings['workflow_required_reviews']) ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Review Deadline (Days)</label>
                    <input type="number" name="workflow_review_days" min="1" max="60"
                           value="<?= htmlspecialchars($settings['workflow_review_days']) ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Max Revisions</label>
                    <input type="number" name="workflow_max_revisions" min="0" max="5"
                           value="<?= htmlspecialchars($settings['workflow_max_revisions']) ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Revision Deadline (Days)</label>
                    <input type="number" name="workflow_revision_days" min="1" max="90"
                           value="<?= htmlspecialchars($settings['workflow_revision_days']) ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                </div>
            </div>
        </div>

        <!-- Assignment Settings -->
        <div>
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">Assignment Settings</h3>
            <div class="space-y-3">
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="workflow_editor_assignment" <?= $settings['workflow_editor_assignment'] == 'true' ? 'checked' : '' ?>>
                    <span class="text-sm text-gray-700">Require editor assignment before review</span>
                </label>
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="workflow_auto_assign_editor" <?= $settings['workflow_auto_assign_editor'] == 'true' ? 'checked' : '' ?>>
                    <span class="text-sm text-gray-700">Auto-assign editor based on expertise</span>
                </label>
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="workflow_auto_assign_reviewers" <?= $settings['workflow_auto_assign_reviewers'] == 'true' ? 'checked' : '' ?>>
                    <span class="text-sm text-gray-700">Auto-suggest reviewers based on keywords</span>
                </label>
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="workflow_notify_author_on_status" <?= $settings['workflow_notify_author_on_status'] == 'true' ? 'checked' : '' ?>>
                    <span class="text-sm text-gray-700">Notify authors on status changes</span>
                </label>
            </div>
        </div>

        <!-- Plagiarism Settings -->
        <div>
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">Plagiarism Check</h3>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="workflow_plagiarism_check" <?= $settings['workflow_plagiarism_check'] == 'true' ? 'checked' : '' ?>>
                        <span class="text-sm text-gray-700">Enable plagiarism checking</span>
                    </label>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Similarity Threshold (%)</label>
                    <input type="number" name="workflow_plagiarism_threshold" min="0" max="100" step="0.5"
                           value="<?= htmlspecialchars($settings['workflow_plagiarism_threshold']) ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" name="save_workflow_settings" 
                    class="bg-[#0b2b3f] text-white px-6 py-2.5 rounded-lg font-semibold hover:bg-[#123a4f] transition shadow-sm">
                <i class="fas fa-save mr-2"></i> Save Settings
            </button>
            <a href="/jms/admin" class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg font-semibold hover:bg-gray-200 transition">
                Cancel
            </a>
        </div>
    </form>
</div>
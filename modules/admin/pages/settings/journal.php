<?php
// modules/admin/pages/settings/journal.php - Journal Settings
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../../includes/functions.php';
    require_once __DIR__ . '/../../../../includes/auth.php';
}

$db = getDB();
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_journal_settings'])) {
    // ... form handling code ...
}

// Get current settings
$settings = [];
$stmt = $db->query("SELECT * FROM settings WHERE setting_group = 'general'");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Default values
$defaults = [
    'site_name' => 'Tanzania Journal of Rehabilitation Practice',
    'site_tagline' => 'Advancing rehabilitation research in Tanzania',
    'journal_issn' => '1234-5678',
    // ... more defaults
];

foreach ($defaults as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}
?>
<!-- NO CONTAINER - just the content -->
<div class="space-y-8">
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

    <form method="POST" class="space-y-8">
        <!-- Basic Information -->
        <div>
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">Basic Information</h3>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Journal Name *</label>
                    <input type="text" name="site_name" required 
                           value="<?= htmlspecialchars($settings['site_name']) ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
                <!-- ... more form fields ... -->
            </div>
        </div>
        <!-- ... more sections ... -->
        
        <div class="flex gap-3">
            <button type="submit" name="save_journal_settings" 
                    class="bg-[#0b2b3f] text-white px-6 py-2.5 rounded-lg font-semibold hover:bg-[#123a4f] transition shadow-sm">
                <i class="fas fa-save mr-2"></i> Save Settings
            </button>
            <a href="/jms/admin" class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg font-semibold hover:bg-gray-200 transition">
                Cancel
            </a>
        </div>
    </form>
</div>
<?php
// modules/admin/pages/settings/email.php - Email Settings
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../../includes/functions.php';
    require_once __DIR__ . '/../../../../includes/auth.php';
}

$db = getDB();
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_email_settings'])) {
    $settings = [
        'email_from_name' => trim($_POST['email_from_name'] ?? ''),
        'email_from_address' => trim($_POST['email_from_address'] ?? ''),
        'email_reply_to' => trim($_POST['email_reply_to'] ?? ''),
        'email_driver' => $_POST['email_driver'] ?? 'mail',
        'email_host' => trim($_POST['email_host'] ?? ''),
        'email_port' => (int)$_POST['email_port'] ?? 587,
        'email_username' => trim($_POST['email_username'] ?? ''),
        'email_password' => trim($_POST['email_password'] ?? ''),
        'email_encryption' => $_POST['email_encryption'] ?? 'tls',
        'email_test_recipient' => trim($_POST['email_test_recipient'] ?? ''),
        'email_notifications_enabled' => isset($_POST['email_notifications_enabled']) ? 'true' : 'false',
    ];

    try {
        foreach ($settings as $key => $value) {
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, setting_group) 
                                   VALUES (?, ?, 'email') 
                                   ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }
        $message = 'Email settings saved successfully!';
        logAction($currentUser['id'], 'update_email_settings', 'settings', 0);
    } catch (Exception $e) {
        $error = 'Failed to save settings: ' . $e->getMessage();
    }
}

// Get current settings
$settings = [];
$stmt = $db->query("SELECT * FROM settings WHERE setting_group = 'email'");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Default values
$defaults = [
    'email_from_name' => 'TIRP Journal',
    'email_from_address' => 'noreply@tirp.lightmantz.com',
    'email_reply_to' => 'info@lightmantz.com',
    'email_driver' => 'mail',
    'email_host' => 'smtp.gmail.com',
    'email_port' => '587',
    'email_username' => '',
    'email_password' => '',
    'email_encryption' => 'tls',
    'email_test_recipient' => '',
    'email_notifications_enabled' => 'true'
];

foreach ($defaults as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}
?>
<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Email Settings</h2>
            <p class="text-gray-500 text-sm mt-1">Configure email settings and notifications</p>
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

    <form method="POST" class="space-y-8">
        <!-- General Email Settings -->
        <div>
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">General Email Settings</h3>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">From Name *</label>
                    <input type="text" name="email_from_name" required 
                           value="<?= htmlspecialchars($settings['email_from_name']) ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">From Email *</label>
                    <input type="email" name="email_from_address" required 
                           value="<?= htmlspecialchars($settings['email_from_address']) ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reply-To Email</label>
                    <input type="email" name="email_reply_to" 
                           value="<?= htmlspecialchars($settings['email_reply_to']) ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notifications</label>
                    <div class="flex items-center gap-3 mt-2">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="email_notifications_enabled" <?= $settings['email_notifications_enabled'] == 'true' ? 'checked' : '' ?>>
                            <span class="text-sm">Enable email notifications</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- SMTP Settings -->
        <div>
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">SMTP Settings</h3>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mail Driver</label>
                    <select name="email_driver" class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                        <option value="mail" <?= $settings['email_driver'] == 'mail' ? 'selected' : '' ?>>PHP Mail</option>
                        <option value="smtp" <?= $settings['email_driver'] == 'smtp' ? 'selected' : '' ?>>SMTP</option>
                        <option value="sendmail" <?= $settings['email_driver'] == 'sendmail' ? 'selected' : '' ?>>Sendmail</option>
                        <option value="ses" <?= $settings['email_driver'] == 'ses' ? 'selected' : '' ?>>Amazon SES</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Host</label>
                    <input type="text" name="email_host" 
                           value="<?= htmlspecialchars($settings['email_host']) ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Port</label>
                    <input type="number" name="email_port" 
                           value="<?= htmlspecialchars($settings['email_port']) ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Encryption</label>
                    <select name="email_encryption" class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                        <option value="none" <?= $settings['email_encryption'] == 'none' ? 'selected' : '' ?>>None</option>
                        <option value="tls" <?= $settings['email_encryption'] == 'tls' ? 'selected' : '' ?>>TLS</option>
                        <option value="ssl" <?= $settings['email_encryption'] == 'ssl' ? 'selected' : '' ?>>SSL</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" name="email_username" 
                           value="<?= htmlspecialchars($settings['email_username']) ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="email_password" 
                           value="<?= htmlspecialchars($settings['email_password']) ?>"
                           placeholder="Leave blank to keep current password"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
            </div>
        </div>

        <!-- Test Email -->
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">Test Email</h3>
            <div class="flex gap-4">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Send Test Email To</label>
                    <input type="email" name="test_email" 
                           value="<?= htmlspecialchars($settings['email_test_recipient'] ?? $settings['email_from_address']) ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
                <div class="flex items-end">
                    <button type="submit" name="send_test_email" 
                            class="bg-green-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-700 transition">
                        <i class="fas fa-paper-plane mr-2"></i> Send Test
                    </button>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">A test email will be sent to verify your email configuration.</p>
        </div>

        <div class="flex gap-3">
            <button type="submit" name="save_email_settings" 
                    class="bg-[#0b2b3f] text-white px-6 py-2.5 rounded-lg font-semibold hover:bg-[#123a4f] transition shadow-sm">
                <i class="fas fa-save mr-2"></i> Save Settings
            </button>
            <a href="/jms/admin" class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg font-semibold hover:bg-gray-200 transition">
                Cancel
            </a>
        </div>
    </form>
</div>
 <?php 
    // Include footer with absolute path
    $footerPath = $basePath . '/includes/footer.php';
    if (file_exists($footerPath)) {
        include $footerPath;
    }
    ?>
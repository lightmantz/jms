<?php
// modules/admin/pages/settings/security.php - Security Settings
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../../includes/functions.php';
    require_once __DIR__ . '/../../../../includes/auth.php';
}

$db = getDB();
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_security_settings'])) {
    $settings = [
        'security_https' => isset($_POST['security_https']) ? 'true' : 'false',
        'security_session_lifetime' => (int)$_POST['security_session_lifetime'] ?? 60,
        'security_max_login_attempts' => (int)$_POST['security_max_login_attempts'] ?? 5,
        'security_lockout_time' => (int)$_POST['security_lockout_time'] ?? 15,
        'security_2fa_enabled' => isset($_POST['security_2fa_enabled']) ? 'true' : 'false',
        'security_2fa_required_roles' => $_POST['security_2fa_required_roles'] ?? 'admin',
        'security_password_min_length' => (int)$_POST['security_password_min_length'] ?? 8,
        'security_password_require_uppercase' => isset($_POST['security_password_require_uppercase']) ? 'true' : 'false',
        'security_password_require_numbers' => isset($_POST['security_password_require_numbers']) ? 'true' : 'false',
        'security_password_require_special' => isset($_POST['security_password_require_special']) ? 'true' : 'false',
        'security_session_regenerate' => isset($_POST['security_session_regenerate']) ? 'true' : 'false',
        'security_ip_whitelist' => trim($_POST['security_ip_whitelist'] ?? ''),
        'security_ip_blacklist' => trim($_POST['security_ip_blacklist'] ?? ''),
    ];

    try {
        foreach ($settings as $key => $value) {
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, setting_group) 
                                   VALUES (?, ?, 'security') 
                                   ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }
        $message = 'Security settings saved successfully!';
        logAction($currentUser['id'], 'update_security_settings', 'settings', 0);
    } catch (Exception $e) {
        $error = 'Failed to save settings: ' . $e->getMessage();
    }
}

// Get current settings
$settings = [];
$stmt = $db->query("SELECT * FROM settings WHERE setting_group = 'security'");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Default values
$defaults = [
    'security_https' => 'false',
    'security_session_lifetime' => '60',
    'security_max_login_attempts' => '5',
    'security_lockout_time' => '15',
    'security_2fa_enabled' => 'false',
    'security_2fa_required_roles' => 'admin',
    'security_password_min_length' => '8',
    'security_password_require_uppercase' => 'true',
    'security_password_require_numbers' => 'true',
    'security_password_require_special' => 'true',
    'security_session_regenerate' => 'true',
    'security_ip_whitelist' => '',
    'security_ip_blacklist' => ''
];

foreach ($defaults as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}
?>
<div>
    <form method="POST" class="space-y-8">
        <!-- Session & Login Security -->
        <div>
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">Session & Login Security</h3>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Session Lifetime (Minutes)</label>
                    <input type="number" name="security_session_lifetime" min="1" max="1440"
                           value="<?= htmlspecialchars($settings['security_session_lifetime']) ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Max Login Attempts</label>
                    <input type="number" name="security_max_login_attempts" min="1" max="20"
                           value="<?= htmlspecialchars($settings['security_max_login_attempts']) ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lockout Time (Minutes)</label>
                    <input type="number" name="security_lockout_time" min="1" max="1440"
                           value="<?= htmlspecialchars($settings['security_lockout_time']) ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                </div>
                <div>
                    <label class="flex items-center gap-3 mt-6">
                        <input type="checkbox" name="security_session_regenerate" <?= $settings['security_session_regenerate'] == 'true' ? 'checked' : '' ?>>
                        <span class="text-sm text-gray-700">Regenerate session on login</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- HTTPS & SSL -->
        <div>
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">HTTPS & SSL</h3>
            <label class="flex items-center gap-3">
                <input type="checkbox" name="security_https" <?= $settings['security_https'] == 'true' ? 'checked' : '' ?>>
                <span class="text-sm text-gray-700">Force HTTPS (SSL) for all pages</span>
            </label>
            <p class="text-xs text-gray-400 mt-2">Requires a valid SSL certificate installed on your server.</p>
        </div>

        <!-- Two-Factor Authentication -->
        <div>
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">Two-Factor Authentication (2FA)</h3>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="security_2fa_enabled" <?= $settings['security_2fa_enabled'] == 'true' ? 'checked' : '' ?>>
                        <span class="text-sm text-gray-700">Enable 2FA</span>
                    </label>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Require 2FA for Roles</label>
                    <select name="security_2fa_required_roles" class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                        <option value="admin" <?= $settings['security_2fa_required_roles'] == 'admin' ? 'selected' : '' ?>>Admin Only</option>
                        <option value="editor" <?= $settings['security_2fa_required_roles'] == 'editor' ? 'selected' : '' ?>>Admin & Editors</option>
                        <option value="reviewer" <?= $settings['security_2fa_required_roles'] == 'reviewer' ? 'selected' : '' ?>>Admin, Editors & Reviewers</option>
                        <option value="all" <?= $settings['security_2fa_required_roles'] == 'all' ? 'selected' : '' ?>>All Users</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Password Policy -->
        <div>
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">Password Policy</h3>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Minimum Password Length</label>
                    <input type="number" name="security_password_min_length" min="6" max="20"
                           value="<?= htmlspecialchars($settings['security_password_min_length']) ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                </div>
                <div class="space-y-2 mt-6">
                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="security_password_require_uppercase" <?= $settings['security_password_require_uppercase'] == 'true' ? 'checked' : '' ?>>
                        <span class="text-sm text-gray-700">Require uppercase letter</span>
                    </label>
                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="security_password_require_numbers" <?= $settings['security_password_require_numbers'] == 'true' ? 'checked' : '' ?>>
                        <span class="text-sm text-gray-700">Require numbers</span>
                    </label>
                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="security_password_require_special" <?= $settings['security_password_require_special'] == 'true' ? 'checked' : '' ?>>
                        <span class="text-sm text-gray-700">Require special characters</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- IP Whitelist/Blacklist -->
        <div>
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">IP Access Control</h3>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">IP Whitelist</label>
                    <textarea name="security_ip_whitelist" rows="4"
                              placeholder="One IP per line&#10;192.168.1.1&#10;10.0.0.0/24"
                              class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none"><?= htmlspecialchars($settings['security_ip_whitelist']) ?></textarea>
                    <p class="text-xs text-gray-400 mt-1">Leave empty to allow all IPs. Supports CIDR notation.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">IP Blacklist</label>
                    <textarea name="security_ip_blacklist" rows="4"
                              placeholder="One IP per line&#10;192.168.1.100&#10;10.0.0.50/32"
                              class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none"><?= htmlspecialchars($settings['security_ip_blacklist']) ?></textarea>
                    <p class="text-xs text-gray-400 mt-1">Blocked IPs will be denied access. Supports CIDR notation.</p>
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" name="save_security_settings" 
                    class="bg-[#0b2b3f] text-white px-6 py-2.5 rounded-lg font-semibold hover:bg-[#123a4f] transition shadow-sm">
                <i class="fas fa-save mr-2"></i> Save Settings
            </button>
            <a href="/jms/admin" class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg font-semibold hover:bg-gray-200 transition">
                Cancel
            </a>
        </div>
    </form>
</div>
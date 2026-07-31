<?php
// modules/admin/pages/journal-settings.php - Journal Settings
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

// Check if user is admin
requireRole(['admin']);

$db = getDB();
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_journal_settings'])) {
    $settings = [
        'journal_name' => $_POST['journal_name'] ?? '',
        'journal_short_name' => $_POST['journal_short_name'] ?? '',
        'journal_issn' => $_POST['journal_issn'] ?? '',
        'journal_eissn' => $_POST['journal_eissn'] ?? '',
        'journal_frequency' => $_POST['journal_frequency'] ?? '',
        'journal_open_access' => isset($_POST['journal_open_access']) ? 'true' : 'false',
        'journal_publication_fee' => $_POST['journal_publication_fee'] ?? '',
        'journal_waiver_policy' => $_POST['journal_waiver_policy'] ?? '',
        'journal_description' => $_POST['journal_description'] ?? '',
        'journal_aims' => $_POST['journal_aims'] ?? '',
        'journal_scope' => $_POST['journal_scope'] ?? '',
        'journal_contact_email' => $_POST['journal_contact_email'] ?? '',
        'journal_contact_phone' => $_POST['journal_contact_phone'] ?? '',
        'journal_address' => $_POST['journal_address'] ?? '',
        'journal_website' => $_POST['journal_website'] ?? '',
        'journal_facebook' => $_POST['journal_facebook'] ?? '',
        'journal_twitter' => $_POST['journal_twitter'] ?? '',
        'journal_linkedin' => $_POST['journal_linkedin'] ?? '',
        'journal_youtube' => $_POST['journal_youtube'] ?? '',
        'journal_timezone' => $_POST['journal_timezone'] ?? '',
        'journal_date_format' => $_POST['journal_date_format'] ?? '',
    ];

    try {
        foreach ($settings as $key => $value) {
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, setting_group) 
                                   VALUES (?, ?, 'journal') 
                                   ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }
        $message = 'Journal settings saved successfully!';
        logAction($currentUser['id'], 'update_journal_settings', 'settings', 0);
    } catch (Exception $e) {
        $error = 'Failed to save settings: ' . $e->getMessage();
    }
}

// Get current settings
$settings = [];
$stmt = $db->query("SELECT * FROM settings WHERE setting_group = 'journal'");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Default values if not set
$defaults = [
    'journal_name' => 'Tanzania Journal of Rehabilitation Practice',
    'journal_short_name' => 'TIRP',
    'journal_issn' => '1234-5678',
    'journal_eissn' => '',
    'journal_frequency' => 'Quarterly',
    'journal_open_access' => 'true',
    'journal_publication_fee' => '0',
    'journal_waiver_policy' => 'No publication fees for authors from low-income countries.',
    'journal_description' => 'A peer-reviewed, open-access journal dedicated to advancing rehabilitation science in Tanzania.',
    'journal_aims' => 'To advance the field of rehabilitation science and practice in Tanzania and across the African continent.',
    'journal_scope' => 'Original research, reviews, case studies, and perspectives in rehabilitation.',
    'journal_contact_email' => 'info@lightmantz.com',
    'journal_contact_phone' => '+255 763 872 771',
    'journal_address' => 'P.O. Box 1541, KCMC, Moshi, Tanzania',
    'journal_website' => 'https://tirp.lightmantz.com',
    'journal_facebook' => '',
    'journal_twitter' => '',
    'journal_linkedin' => '',
    'journal_youtube' => '',
    'journal_timezone' => 'Africa/Dar_es_Salaam',
    'journal_date_format' => 'd M Y',
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
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Journal Settings</h2>
            <p class="text-gray-500 text-sm mt-1">Configure your journal's basic information and settings</p>
        </div>
        <a href="/jms/admin" class="text-indigo-600 hover:text-indigo-800 text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
        </a>
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
        <!-- Basic Information -->
        <div>
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">Basic Information</h3>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Journal Name *</label>
                    <input type="text" name="journal_name" required 
                           value="<?= htmlspecialchars($settings['journal_name']) ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Short Name / Acronym *</label>
                    <input type="text" name="journal_short_name" required 
                           value="<?= htmlspecialchars($settings['journal_short_name']) ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ISSN</label>
                    <input type="text" name="journal_issn" 
                           value="<?= htmlspecialchars($settings['journal_issn']) ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">E-ISSN</label>
                    <input type="text" name="journal_eissn" 
                           value="<?= htmlspecialchars($settings['journal_eissn']) ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Publication Frequency</label>
                    <select name="journal_frequency" class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                        <option value="Monthly" <?= $settings['journal_frequency'] == 'Monthly' ? 'selected' : '' ?>>Monthly</option>
                        <option value="Bimonthly" <?= $settings['journal_frequency'] == 'Bimonthly' ? 'selected' : '' ?>>Bimonthly</option>
                        <option value="Quarterly" <?= $settings['journal_frequency'] == 'Quarterly' ? 'selected' : '' ?>>Quarterly</option>
                        <option value="Semi-annually" <?= $settings['journal_frequency'] == 'Semi-annually' ? 'selected' : '' ?>>Semi-annually</option>
                        <option value="Annually" <?= $settings['journal_frequency'] == 'Annually' ? 'selected' : '' ?>>Annually</option>
                        <option value="Continuous" <?= $settings['journal_frequency'] == 'Continuous' ? 'selected' : '' ?>>Continuous</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Open Access</label>
                    <div class="flex items-center gap-3 mt-2">
                        <label class="flex items-center gap-2">
                            <input type="radio" name="journal_open_access" value="true" <?= $settings['journal_open_access'] == 'true' ? 'checked' : '' ?>>
                            <span class="text-sm">Yes</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="radio" name="journal_open_access" value="false" <?= $settings['journal_open_access'] != 'true' ? 'checked' : '' ?>>
                            <span class="text-sm">No</span>
                        </label>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Publication Fee (USD)</label>
                    <input type="number" name="journal_publication_fee" min="0"
                           value="<?= htmlspecialchars($settings['journal_publication_fee']) ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Waiver Policy</label>
                    <input type="text" name="journal_waiver_policy" 
                           value="<?= htmlspecialchars($settings['journal_waiver_policy']) ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="journal_description" rows="3"
                              class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition"><?= htmlspecialchars($settings['journal_description']) ?></textarea>
                </div>
            </div>
        </div>

        <!-- Aims & Scope -->
        <div>
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">Aims & Scope</h3>
            <div class="grid md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Aims</label>
                    <textarea name="journal_aims" rows="3"
                              class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition"><?= htmlspecialchars($settings['journal_aims']) ?></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Scope</label>
                    <textarea name="journal_scope" rows="3"
                              class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition"><?= htmlspecialchars($settings['journal_scope']) ?></textarea>
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div>
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">Contact Information</h3>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Email</label>
                    <input type="email" name="journal_contact_email" 
                           value="<?= htmlspecialchars($settings['journal_contact_email']) ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Phone</label>
                    <input type="text" name="journal_contact_phone" 
                           value="<?= htmlspecialchars($settings['journal_contact_phone']) ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <input type="text" name="journal_address" 
                           value="<?= htmlspecialchars($settings['journal_address']) ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Website</label>
                    <input type="url" name="journal_website" 
                           value="<?= htmlspecialchars($settings['journal_website']) ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
            </div>
        </div>

        <!-- Social Media -->
        <div>
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">Social Media</h3>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fab fa-facebook text-blue-600 mr-1"></i> Facebook</label>
                    <input type="url" name="journal_facebook" 
                           value="<?= htmlspecialchars($settings['journal_facebook']) ?>"
                           placeholder="https://facebook.com/your-journal"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fab fa-twitter text-blue-400 mr-1"></i> Twitter</label>
                    <input type="url" name="journal_twitter" 
                           value="<?= htmlspecialchars($settings['journal_twitter']) ?>"
                           placeholder="https://twitter.com/your-journal"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fab fa-linkedin text-blue-700 mr-1"></i> LinkedIn</label>
                    <input type="url" name="journal_linkedin" 
                           value="<?= htmlspecialchars($settings['journal_linkedin']) ?>"
                           placeholder="https://linkedin.com/company/your-journal"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fab fa-youtube text-red-600 mr-1"></i> YouTube</label>
                    <input type="url" name="journal_youtube" 
                           value="<?= htmlspecialchars($settings['journal_youtube']) ?>"
                           placeholder="https://youtube.com/your-journal"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
            </div>
        </div>

        <!-- Regional Settings -->
        <div>
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">Regional Settings</h3>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Timezone</label>
                    <select name="journal_timezone" class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                        <option value="Africa/Dar_es_Salaam" <?= $settings['journal_timezone'] == 'Africa/Dar_es_Salaam' ? 'selected' : '' ?>>Africa/Dar_es_Salaam</option>
                        <option value="Africa/Nairobi" <?= $settings['journal_timezone'] == 'Africa/Nairobi' ? 'selected' : '' ?>>Africa/Nairobi</option>
                        <option value="Africa/Johannesburg" <?= $settings['journal_timezone'] == 'Africa/Johannesburg' ? 'selected' : '' ?>>Africa/Johannesburg</option>
                        <option value="UTC" <?= $settings['journal_timezone'] == 'UTC' ? 'selected' : '' ?>>UTC</option>
                        <option value="America/New_York" <?= $settings['journal_timezone'] == 'America/New_York' ? 'selected' : '' ?>>America/New_York</option>
                        <option value="Europe/London" <?= $settings['journal_timezone'] == 'Europe/London' ? 'selected' : '' ?>>Europe/London</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date Format</label>
                    <select name="journal_date_format" class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                        <option value="d M Y" <?= $settings['journal_date_format'] == 'd M Y' ? 'selected' : '' ?>>14 Jul 2026</option>
                        <option value="M d, Y" <?= $settings['journal_date_format'] == 'M d, Y' ? 'selected' : '' ?>>Jul 14, 2026</option>
                        <option value="Y-m-d" <?= $settings['journal_date_format'] == 'Y-m-d' ? 'selected' : '' ?>>2026-07-14</option>
                        <option value="d/m/Y" <?= $settings['journal_date_format'] == 'd/m/Y' ? 'selected' : '' ?>>14/07/2026</option>
                        <option value="m/d/Y" <?= $settings['journal_date_format'] == 'm/d/Y' ? 'selected' : '' ?>>07/14/2026</option>
                    </select>
                </div>
            </div>
        </div>

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
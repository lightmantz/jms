<?php
// modules/admin/pages/settings/integrations.php - Integrations
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../../includes/functions.php';
    require_once __DIR__ . '/../../../../includes/auth.php';
}

$db = getDB();
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_integration'])) {
    $integration = $_POST['integration'] ?? '';
    $settings = [];

    if ($integration == 'doi') {
        $settings = [
            'doi_enabled' => isset($_POST['doi_enabled']) ? 'true' : 'false',
            'doi_prefix' => trim($_POST['doi_prefix'] ?? '10.1016/tirp'),
            'doi_api_url' => trim($_POST['doi_api_url'] ?? ''),
            'doi_api_username' => trim($_POST['doi_api_username'] ?? ''),
            'doi_api_password' => trim($_POST['doi_api_password'] ?? ''),
        ];
    } elseif ($integration == 'google_scholar') {
        $settings = [
            'google_scholar_enabled' => isset($_POST['google_scholar_enabled']) ? 'true' : 'false',
            'google_scholar_meta' => isset($_POST['google_scholar_meta']) ? 'true' : 'false',
        ];
    } elseif ($integration == 'crossref') {
        $settings = [
            'crossref_enabled' => isset($_POST['crossref_enabled']) ? 'true' : 'false',
            'crossref_api_url' => trim($_POST['crossref_api_url'] ?? ''),
            'crossref_username' => trim($_POST['crossref_username'] ?? ''),
            'crossref_password' => trim($_POST['crossref_password'] ?? ''),
        ];
    } elseif ($integration == 'orcid') {
        $settings = [
            'orcid_enabled' => isset($_POST['orcid_enabled']) ? 'true' : 'false',
            'orcid_client_id' => trim($_POST['orcid_client_id'] ?? ''),
            'orcid_client_secret' => trim($_POST['orcid_client_secret'] ?? ''),
            'orcid_redirect_uri' => trim($_POST['orcid_redirect_uri'] ?? SITE_URL . '/auth/orcid-callback'),
        ];
    }

    try {
        foreach ($settings as $key => $value) {
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, setting_group) 
                                   VALUES (?, ?, 'integrations') 
                                   ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }
        $message = 'Integration settings saved successfully!';
        logAction($currentUser['id'], 'update_integration_settings', 'settings', 0);
    } catch (Exception $e) {
        $error = 'Failed to save settings: ' . $e->getMessage();
    }
}

// Get current settings
$settings = [];
$stmt = $db->query("SELECT * FROM settings WHERE setting_group = 'integrations'");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Default values
$defaults = [
    'doi_enabled' => 'true',
    'doi_prefix' => '10.1016/tirp',
    'doi_api_url' => '',
    'doi_api_username' => '',
    'doi_api_password' => '',
    'google_scholar_enabled' => 'true',
    'google_scholar_meta' => 'true',
    'crossref_enabled' => 'false',
    'crossref_api_url' => 'https://api.crossref.org',
    'crossref_username' => '',
    'crossref_password' => '',
    'orcid_enabled' => 'false',
    'orcid_client_id' => '',
    'orcid_client_secret' => '',
    'orcid_redirect_uri' => SITE_URL . '/auth/orcid-callback'
];

foreach ($defaults as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}
?>
<div>
    <!-- Integration Tabs -->
    <div class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 pb-4" id="integrationTabs">
        <button onclick="showIntegration('doi')" class="px-4 py-2 rounded-lg text-sm font-medium transition bg-[#0b2b3f] text-white" id="tab-doi">
            <i class="fas fa-link mr-1"></i> DOI
        </button>
        <button onclick="showIntegration('google_scholar')" class="px-4 py-2 rounded-lg text-sm font-medium transition bg-gray-100 text-gray-600 hover:bg-gray-200" id="tab-google_scholar">
            <i class="fab fa-google mr-1"></i> Google Scholar
        </button>
        <button onclick="showIntegration('crossref')" class="px-4 py-2 rounded-lg text-sm font-medium transition bg-gray-100 text-gray-600 hover:bg-gray-200" id="tab-crossref">
            <i class="fas fa-book mr-1"></i> Crossref
        </button>
        <button onclick="showIntegration('orcid')" class="px-4 py-2 rounded-lg text-sm font-medium transition bg-gray-100 text-gray-600 hover:bg-gray-200" id="tab-orcid">
            <i class="fab fa-orcid mr-1"></i> ORCID
        </button>
    </div>

    <!-- DOI Integration -->
    <div id="panel-doi" class="integration-panel">
        <form method="POST" class="space-y-6">
            <input type="hidden" name="integration" value="doi">
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">DOI Integration</h3>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="doi_enabled" <?= $settings['doi_enabled'] == 'true' ? 'checked' : '' ?>>
                        <span class="text-sm text-gray-700">Enable DOI registration</span>
                    </label>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">DOI Prefix</label>
                    <input type="text" name="doi_prefix" 
                           value="<?= htmlspecialchars($settings['doi_prefix']) ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">API URL</label>
                    <input type="url" name="doi_api_url" 
                           value="<?= htmlspecialchars($settings['doi_api_url']) ?>"
                           placeholder="https://api.datacite.org"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">API Username</label>
                    <input type="text" name="doi_api_username" 
                           value="<?= htmlspecialchars($settings['doi_api_username']) ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">API Password</label>
                    <input type="password" name="doi_api_password" 
                           value="<?= htmlspecialchars($settings['doi_api_password']) ?>"
                           placeholder="Leave blank to keep current"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                </div>
            </div>
            <button type="submit" name="save_integration" class="bg-[#0b2b3f] text-white px-6 py-2 rounded-lg font-semibold hover:bg-[#123a4f] transition">
                <i class="fas fa-save mr-2"></i> Save DOI Settings
            </button>
        </form>
    </div>

    <!-- Google Scholar Integration -->
    <div id="panel-google_scholar" class="integration-panel" style="display:none;">
        <form method="POST" class="space-y-6">
            <input type="hidden" name="integration" value="google_scholar">
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">Google Scholar Integration</h3>
            <div class="space-y-3">
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="google_scholar_enabled" <?= $settings['google_scholar_enabled'] == 'true' ? 'checked' : '' ?>>
                    <span class="text-sm text-gray-700">Enable Google Scholar indexing</span>
                </label>
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="google_scholar_meta" <?= $settings['google_scholar_meta'] == 'true' ? 'checked' : '' ?>>
                    <span class="text-sm text-gray-700">Add Google Scholar meta tags to articles</span>
                </label>
            </div>
            <div class="bg-blue-50 rounded-xl p-4 border border-blue-200">
                <p class="text-sm text-blue-700">
                    <i class="fas fa-info-circle mr-2"></i>
                    Google Scholar automatically indexes content. This setting adds meta tags to help with discovery.
                </p>
            </div>
            <button type="submit" name="save_integration" class="bg-[#0b2b3f] text-white px-6 py-2 rounded-lg font-semibold hover:bg-[#123a4f] transition">
                <i class="fas fa-save mr-2"></i> Save Google Scholar Settings
            </button>
        </form>
    </div>

    <!-- Crossref Integration -->
    <div id="panel-crossref" class="integration-panel" style="display:none;">
        <form method="POST" class="space-y-6">
            <input type="hidden" name="integration" value="crossref">
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">Crossref Integration</h3>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="crossref_enabled" <?= $settings['crossref_enabled'] == 'true' ? 'checked' : '' ?>>
                        <span class="text-sm text-gray-700">Enable Crossref integration</span>
                    </label>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">API URL</label>
                    <input type="url" name="crossref_api_url" 
                           value="<?= htmlspecialchars($settings['crossref_api_url']) ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" name="crossref_username" 
                           value="<?= htmlspecialchars($settings['crossref_username']) ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="crossref_password" 
                           value="<?= htmlspecialchars($settings['crossref_password']) ?>"
                           placeholder="Leave blank to keep current"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                </div>
            </div>
            <div class="bg-yellow-50 rounded-xl p-4 border border-yellow-200">
                <p class="text-sm text-yellow-700">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Crossref integration requires a registered account with Crossref.
                </p>
            </div>
            <button type="submit" name="save_integration" class="bg-[#0b2b3f] text-white px-6 py-2 rounded-lg font-semibold hover:bg-[#123a4f] transition">
                <i class="fas fa-save mr-2"></i> Save Crossref Settings
            </button>
        </form>
    </div>

    <!-- ORCID Integration -->
    <div id="panel-orcid" class="integration-panel" style="display:none;">
        <form method="POST" class="space-y-6">
            <input type="hidden" name="integration" value="orcid">
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">ORCID Integration</h3>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="orcid_enabled" <?= $settings['orcid_enabled'] == 'true' ? 'checked' : '' ?>>
                        <span class="text-sm text-gray-700">Enable ORCID integration</span>
                    </label>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Redirect URI</label>
                    <input type="url" name="orcid_redirect_uri" 
                           value="<?= htmlspecialchars($settings['orcid_redirect_uri']) ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Client ID</label>
                    <input type="text" name="orcid_client_id" 
                           value="<?= htmlspecialchars($settings['orcid_client_id']) ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Client Secret</label>
                    <input type="password" name="orcid_client_secret" 
                           value="<?= htmlspecialchars($settings['orcid_client_secret']) ?>"
                           placeholder="Leave blank to keep current"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                </div>
            </div>
            <div class="bg-blue-50 rounded-xl p-4 border border-blue-200">
                <p class="text-sm text-blue-700">
                    <i class="fas fa-info-circle mr-2"></i>
                    ORCID integration allows users to authenticate using their ORCID credentials.
                    <a href="https://orcid.org/developer-tools" target="_blank" class="text-blue-600 hover:underline">Get API credentials</a>
                </p>
            </div>
            <button type="submit" name="save_integration" class="bg-[#0b2b3f] text-white px-6 py-2 rounded-lg font-semibold hover:bg-[#123a4f] transition">
                <i class="fas fa-save mr-2"></i> Save ORCID Settings
            </button>
        </form>
    </div>
</div>

<script>
function showIntegration(tab) {
    // Hide all panels
    document.querySelectorAll('.integration-panel').forEach(el => el.style.display = 'none');
    // Show selected panel
    document.getElementById('panel-' + tab).style.display = 'block';
    // Update tab styles
    document.querySelectorAll('#integrationTabs button').forEach(el => {
        el.className = 'px-4 py-2 rounded-lg text-sm font-medium transition bg-gray-100 text-gray-600 hover:bg-gray-200';
    });
    document.getElementById('tab-' + tab).className = 'px-4 py-2 rounded-lg text-sm font-medium transition bg-[#0b2b3f] text-white';
}
</script>
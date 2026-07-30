<?php
// modules/admin/pages/settings.php - Main Settings Dashboard
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

requireRole(['admin']);

$db = getDB();
$message = '';
$error = '';

// Get the subaction
$subaction = $_GET['subaction'] ?? 'journal';

// Handle settings page inclusion
$settingsPage = __DIR__ . '/settings/' . $subaction . '.php';
?>
<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">
                <?php 
                $settingsLabels = [
                    'journal' => 'Journal Settings',
                    'email' => 'Email Settings',
                    'workflow' => 'Workflow Settings',
                    'integrations' => 'Integrations',
                    'security' => 'Security Settings',
                    'backups' => 'Backups'
                ];
                echo $settingsLabels[$subaction] ?? 'Settings';
                ?>
            </h2>
            <p class="text-gray-500 text-sm mt-1">Configure and manage system settings</p>
        </div>
        <div class="flex gap-3">
            <a href="/jms/admin" class="text-indigo-600 hover:text-indigo-800 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>
    </div>
    <div class="h-1 w-20 bg-indigo-200 rounded-full mb-6"></div>

    <!-- Settings Navigation -->
    <div class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 pb-4">
        <a href="/jms/admin?action=settings&subaction=journal" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $subaction == 'journal' ? 'bg-[#0b2b3f] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">
            <i class="fas fa-cog mr-1"></i> Journal
        </a>
        <a href="/jms/admin?action=settings&subaction=email" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $subaction == 'email' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-600 hover:bg-blue-100' ?>">
            <i class="fas fa-envelope mr-1"></i> Email
        </a>
        <a href="/jms/admin?action=settings&subaction=workflow" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $subaction == 'workflow' ? 'bg-purple-600 text-white' : 'bg-purple-50 text-purple-600 hover:bg-purple-100' ?>">
            <i class="fas fa-project-diagram mr-1"></i> Workflow
        </a>
        <a href="/jms/admin?action=settings&subaction=integrations" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $subaction == 'integrations' ? 'bg-green-600 text-white' : 'bg-green-50 text-green-600 hover:bg-green-100' ?>">
            <i class="fas fa-plug mr-1"></i> Integrations
        </a>
        <a href="/jms/admin?action=settings&subaction=security" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $subaction == 'security' ? 'bg-red-600 text-white' : 'bg-red-50 text-red-600 hover:bg-red-100' ?>">
            <i class="fas fa-shield-alt mr-1"></i> Security
        </a>
        <a href="/jms/admin?action=settings&subaction=backups" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $subaction == 'backups' ? 'bg-yellow-600 text-white' : 'bg-yellow-50 text-yellow-600 hover:bg-yellow-100' ?>">
            <i class="fas fa-database mr-1"></i> Backups
        </a>
    </div>

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

    <?php
    // Include the appropriate settings page
    if (file_exists($settingsPage)) {
        include $settingsPage;
    } else {
        echo '<div class="text-center py-12">
                <i class="fas fa-cog text-5xl text-gray-300 mb-4"></i>
                <p class="text-gray-500">Settings page not found.</p>
              </div>';
    }
    ?>
</div>
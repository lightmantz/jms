<?php
// modules/admin/pages/settings/backups.php - Backups
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../../includes/functions.php';
    require_once __DIR__ . '/../../../../includes/auth.php';
}

$db = getDB();
$message = '';
$error = '';

// Handle backup actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_backup'])) {
        $backupType = $_POST['backup_type'] ?? 'full';
        $backupName = trim($_POST['backup_name'] ?? '');
        
        if (empty($backupName)) {
            $backupName = 'backup_' . date('Y-m-d_H-i-s');
        }
        
        // Create backup directory if it doesn't exist
        $backupDir = __DIR__ . '/../../../../backups/';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        // Create backup file
        $filename = $backupName . '.sql';
        $filepath = $backupDir . $filename;
        
        // Export database
        $command = sprintf(
            'mysqldump --user=%s --password=%s --host=%s %s > %s 2>&1',
            escapeshellarg(DB_USER),
            escapeshellarg(DB_PASS),
            escapeshellarg(DB_HOST),
            escapeshellarg(DB_NAME),
            escapeshellarg($filepath)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0) {
            $message = 'Backup created successfully: ' . $filename;
            logAction($currentUser['id'], 'create_backup', 'settings', 0);
        } else {
            $error = 'Failed to create backup. Please check database credentials.';
        }
    } elseif (isset($_POST['restore_backup'])) {
        $backupFile = $_POST['backup_file'] ?? '';
        if (empty($backupFile)) {
            $error = 'No backup file selected.';
        } else {
            $backupDir = __DIR__ . '/../../../../backups/';
            $filepath = $backupDir . $backupFile;
            
            if (file_exists($filepath)) {
                // Restore database
                $command = sprintf(
                    'mysql --user=%s --password=%s --host=%s %s < %s 2>&1',
                    escapeshellarg(DB_USER),
                    escapeshellarg(DB_PASS),
                    escapeshellarg(DB_HOST),
                    escapeshellarg(DB_NAME),
                    escapeshellarg($filepath)
                );
                
                exec($command, $output, $returnCode);
                
                if ($returnCode === 0) {
                    $message = 'Database restored successfully from: ' . $backupFile;
                    logAction($currentUser['id'], 'restore_backup', 'settings', 0);
                } else {
                    $error = 'Failed to restore backup. Please check the backup file.';
                }
            } else {
                $error = 'Backup file not found.';
            }
        }
    } elseif (isset($_POST['delete_backup'])) {
        $backupFile = $_POST['backup_file'] ?? '';
        if (empty($backupFile)) {
            $error = 'No backup file selected.';
        } else {
            $backupDir = __DIR__ . '/../../../../backups/';
            $filepath = $backupDir . $backupFile;
            
            if (file_exists($filepath) && unlink($filepath)) {
                $message = 'Backup deleted successfully: ' . $backupFile;
                logAction($currentUser['id'], 'delete_backup', 'settings', 0);
            } else {
                $error = 'Failed to delete backup file.';
            }
        }
    }
}

// Get list of backups
$backupDir = __DIR__ . '/../../../../backups/';
$backups = [];
if (is_dir($backupDir)) {
    $files = scandir($backupDir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) == 'sql') {
            $filepath = $backupDir . $file;
            $backups[] = [
                'name' => $file,
                'size' => filesize($filepath),
                'modified' => date('Y-m-d H:i:s', filemtime($filepath)),
                'size_human' => formatFileSize(filesize($filepath))
            ];
        }
    }
    // Sort by modified time descending
    usort($backups, function($a, $b) {
        return strtotime($b['modified']) - strtotime($a['modified']);
    });
}

function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' B';
    }
}

// Get backup statistics
$totalBackups = count($backups);
$totalSize = array_sum(array_column($backups, 'size'));
?>
<div>
    <!-- Backup Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-blue-700"><?= $totalBackups ?></p>
            <p class="text-xs text-blue-600">Total Backups</p>
        </div>
        <div class="bg-green-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-green-700"><?= formatFileSize($totalSize) ?></p>
            <p class="text-xs text-green-600">Total Size</p>
        </div>
        <div class="bg-purple-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-purple-700"><?= date('Y-m-d') ?></p>
            <p class="text-xs text-purple-600">Last Backup</p>
        </div>
        <div class="bg-yellow-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-yellow-700"><?= DB_NAME ?></p>
            <p class="text-xs text-yellow-600">Database</p>
        </div>
    </div>

    <!-- Create Backup -->
    <div class="border border-gray-200 rounded-xl p-4 mb-6">
        <h4 class="font-semibold text-[#0b2b3f] mb-4">Create New Backup</h4>
        <form method="POST" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Backup Name</label>
                <input type="text" name="backup_name" 
                       placeholder="backup_<?= date('Y-m-d_H-i-s') ?>"
                       class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select name="backup_type" class="px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                    <option value="full">Full Database</option>
                </select>
            </div>
            <button type="submit" name="create_backup" 
                    class="bg-green-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-700 transition">
                <i class="fas fa-database mr-2"></i> Create Backup
            </button>
        </form>
        <p class="text-xs text-gray-400 mt-2">Creates a complete SQL backup of the database. Backups are stored in the /backups directory.</p>
    </div>

    <!-- Backups List -->
    <div>
        <div class="flex items-center justify-between mb-3">
            <h4 class="font-semibold text-[#0b2b3f]">Available Backups</h4>
            <span class="text-xs text-gray-400"><?= $totalBackups ?> backups</span>
        </div>
        
        <?php if (empty($backups)): ?>
            <div class="text-center py-12">
                <i class="fas fa-database text-5xl text-gray-300 mb-4"></i>
                <p class="text-gray-500">No backups found.</p>
                <p class="text-sm text-gray-400">Create your first backup using the form above.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Filename</th>
                            <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Size</th>
                            <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Modified</th>
                            <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backups as $backup): ?>
                        <tr class="border-b border-gray-100">
                            <td class="py-2 px-3 font-medium text-[#0b2b3f]"><?= htmlspecialchars($backup['name']) ?></td>
                            <td class="py-2 px-3 text-gray-600"><?= $backup['size_human'] ?></td>
                            <td class="py-2 px-3 text-gray-600"><?= $backup['modified'] ?></td>
                            <td class="py-2 px-3">
                                <div class="flex gap-2">
                                    <a href="/jms/backups/<?= urlencode($backup['name']) ?>" 
                                       class="text-blue-600 hover:text-blue-800 text-sm" title="Download">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <form method="POST" class="inline" 
                                          onsubmit="return confirm('Restore this backup? This will overwrite the current database.')">
                                        <input type="hidden" name="backup_file" value="<?= htmlspecialchars($backup['name']) ?>">
                                        <button type="submit" name="restore_backup" 
                                                class="text-green-600 hover:text-green-800 text-sm" title="Restore">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    </form>
                                    <form method="POST" class="inline" 
                                          onsubmit="return confirm('Delete this backup?')">
                                        <input type="hidden" name="backup_file" value="<?= htmlspecialchars($backup['name']) ?>">
                                        <button type="submit" name="delete_backup" 
                                                class="text-red-600 hover:text-red-800 text-sm" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Backup Settings -->
    <div class="mt-6 border-t border-gray-200 pt-6">
        <h4 class="font-semibold text-[#0b2b3f] mb-4">Backup Settings</h4>
        <form method="POST" class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="auto_backup_enabled" <?= getSetting('auto_backup_enabled') == 'true' ? 'checked' : '' ?>>
                    <span class="text-sm text-gray-700">Enable automatic backups</span>
                </label>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Backup Frequency</label>
                <select name="auto_backup_frequency" class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                    <option value="daily" <?= getSetting('auto_backup_frequency') == 'daily' ? 'selected' : '' ?>>Daily</option>
                    <option value="weekly" <?= getSetting('auto_backup_frequency') == 'weekly' ? 'selected' : '' ?>>Weekly</option>
                    <option value="monthly" <?= getSetting('auto_backup_frequency') == 'monthly' ? 'selected' : '' ?>>Monthly</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Retain Backups</label>
                <input type="number" name="auto_backup_retain" min="1" max="100"
                       value="<?= getSetting('auto_backup_retain') ?? 10 ?>"
                       class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                <p class="text-xs text-gray-400 mt-1">Number of backups to keep (older backups will be deleted)</p>
            </div>
            <div class="flex items-end">
                <button type="submit" name="save_backup_settings" 
                        class="bg-[#0b2b3f] text-white px-6 py-2 rounded-lg font-semibold hover:bg-[#123a4f] transition">
                    <i class="fas fa-save mr-2"></i> Save Backup Settings
                </button>
            </div>
        </form>
    </div>

    <!-- Info Box -->
    <div class="mt-6 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
        <p class="text-sm text-yellow-700">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <strong>Important:</strong> Backup files contain sensitive database information. 
            Store them securely and never share them publicly. Regular backups are recommended.
        </p>
    </div>
</div>
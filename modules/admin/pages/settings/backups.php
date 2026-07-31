<?php
// modules/admin/pages/settings/backups.php - Database Backup Management
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../../includes/functions.php';
    require_once __DIR__ . '/../../../../includes/auth.php';
}

requireRole(['admin']);

$db = getDB();
$message = '';
$error = '';

// Get database configuration
$dbConfig = [];
if (file_exists(CONFIG_PATH . 'database.php')) {
    $dbConfig = require_once CONFIG_PATH . 'database.php';
}

// Define constants if not already defined
if (!defined('DB_NAME')) {
    define('DB_NAME', $dbConfig['database'] ?? 'tirp');
}
if (!defined('DB_USER')) {
    define('DB_USER', $dbConfig['username'] ?? 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', $dbConfig['password'] ?? '');
}
if (!defined('DB_HOST')) {
    define('DB_HOST', $dbConfig['host'] ?? 'localhost');
}

// Backup directory
$backupDir = BASE_PATH . '/backups/';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

// Handle backup creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_backup') {
        $backupType = $_POST['backup_type'] ?? 'full';
        $backupName = trim($_POST['backup_name'] ?? '');
        
        if (empty($backupName)) {
            $backupName = 'backup_' . date('Y-m-d_H-i-s');
        } else {
            $backupName = sanitizeFilename($backupName);
        }
        
        $filename = $backupName . '.sql';
        $filepath = $backupDir . $filename;
        
        try {
            // Create backup using mysqldump
            $host = DB_HOST;
            $user = DB_USER;
            $pass = DB_PASS;
            $dbname = DB_NAME;
            
            // Build mysqldump command
            $command = sprintf(
                'mysqldump --host=%s --user=%s --password=%s --add-drop-table --add-locks --create-options --disable-keys --extended-insert --lock-tables --quick --set-charset %s > %s 2>&1',
                escapeshellarg($host),
                escapeshellarg($user),
                escapeshellarg($pass),
                escapeshellarg($dbname),
                escapeshellarg($filepath)
            );
            
            // Execute the command
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($filepath) && filesize($filepath) > 0) {
                // Log the backup
                logAction($currentUser['id'], 'create_backup', 'system', 0);
                $message = 'Backup created successfully! File: ' . $filename;
            } else {
                // Fallback: Use PHP to create backup if mysqldump fails
                $backupContent = createBackupWithPHP($dbname);
                if (file_put_contents($filepath, $backupContent)) {
                    $message = 'Backup created successfully using PHP! File: ' . $filename;
                    logAction($currentUser['id'], 'create_backup_php', 'system', 0);
                } else {
                    $error = 'Failed to create backup. Please check file permissions.';
                }
            }
        } catch (Exception $e) {
            $error = 'Backup failed: ' . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'delete_backup') {
        $filename = basename($_POST['filename']);
        $filepath = $backupDir . $filename;
        
        if (file_exists($filepath) && unlink($filepath)) {
            $message = 'Backup deleted successfully!';
            logAction($currentUser['id'], 'delete_backup', 'system', 0);
        } else {
            $error = 'Failed to delete backup.';
        }
    } elseif ($_POST['action'] === 'restore_backup') {
        $filename = basename($_POST['filename']);
        $filepath = $backupDir . $filename;
        
        if (file_exists($filepath)) {
            // Restore backup
            $host = DB_HOST;
            $user = DB_USER;
            $pass = DB_PASS;
            $dbname = DB_NAME;
            
            $command = sprintf(
                'mysql --host=%s --user=%s --password=%s %s < %s 2>&1',
                escapeshellarg($host),
                escapeshellarg($user),
                escapeshellarg($pass),
                escapeshellarg($dbname),
                escapeshellarg($filepath)
            );
            
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0) {
                $message = 'Backup restored successfully!';
                logAction($currentUser['id'], 'restore_backup', 'system', 0);
            } else {
                $error = 'Failed to restore backup. Please check the backup file.';
            }
        } else {
            $error = 'Backup file not found.';
        }
    }
}

// Get list of backups
$backups = [];
$files = glob($backupDir . '*.sql');
foreach ($files as $file) {
    $backups[] = [
        'filename' => basename($file),
        'size' => filesize($file),
        'modified' => filemtime($file),
        'path' => $file
    ];
}

// Sort by modified date (newest first)
usort($backups, function($a, $b) {
    return $b['modified'] - $a['modified'];
});

// Format file size
function formatFileSize($bytes) {
    if ($bytes === 0) return '0 B';
    $k = 1024;
    $sizes = ['B', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

// Sanitize filename
function sanitizeFilename($filename) {
    $filename = strtolower($filename);
    $filename = preg_replace('/[^a-z0-9_-]/', '_', $filename);
    return substr($filename, 0, 50);
}

// Create backup using PHP (fallback method)
function createBackupWithPHP($dbname) {
    $db = getDB();
    $tables = [];
    $stmt = $db->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    
    $output = "-- Database backup created with PHP\n";
    $output .= "-- Database: " . $dbname . "\n";
    $output .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
    $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
    
    foreach ($tables as $table) {
        // Get create table statement
        $stmt = $db->query("SHOW CREATE TABLE `$table`");
        $row = $stmt->fetch(PDO::FETCH_NUM);
        $output .= "DROP TABLE IF EXISTS `$table`;\n";
        $output .= $row[1] . ";\n\n";
        
        // Get table data
        $stmt = $db->query("SELECT * FROM `$table`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($rows)) {
            $columns = array_keys($rows[0]);
            $columnList = '`' . implode('`, `', $columns) . '`';
            $placeholders = implode(',', array_fill(0, count($columns), '?'));
            
            foreach ($rows as $row) {
                $output .= "INSERT INTO `$table` ($columnList) VALUES (";
                $values = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $values[] = 'NULL';
                    } else {
                        $values[] = "'" . addslashes($value) . "'";
                    }
                }
                $output .= implode(', ', $values) . ");\n";
            }
            $output .= "\n";
        }
    }
    
    $output .= "\nSET FOREIGN_KEY_CHECKS=1;\n";
    return $output;
}
?>
<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Database Backups</h2>
            <p class="text-gray-500 text-sm mt-1">Create and manage database backups</p>
        </div>
        <div class="flex gap-3">
            <button onclick="openCreateBackupModal()" class="bg-[#0b2b3f] text-white px-4 py-2 rounded-lg hover:bg-[#123a4f] transition text-sm">
                <i class="fas fa-database mr-1"></i> Create Backup
            </button>
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

    <!-- Backup Info -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex items-center gap-3">
            <i class="fas fa-info-circle text-blue-600"></i>
            <div>
                <p class="text-sm text-blue-700">
                    <strong>Database:</strong> <?= DB_NAME ?> &nbsp;|&nbsp;
                    <strong>Backup Directory:</strong> <?= str_replace(BASE_PATH, '', $backupDir) ?>
                </p>
                <p class="text-xs text-blue-600 mt-1">
                    Backups are stored in the <code>backups/</code> folder. Regular backups are recommended.
                </p>
            </div>
        </div>
    </div>

    <!-- Backups List -->
    <?php if (empty($backups)): ?>
        <div class="text-center py-12">
            <i class="fas fa-database text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No backups found.</p>
            <button onclick="openCreateBackupModal()" class="mt-3 bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                <i class="fas fa-plus mr-2"></i> Create First Backup
            </button>
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
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                        <td class="py-2 px-3">
                            <span class="font-medium text-[#0b2b3f] text-sm"><?= htmlspecialchars($backup['filename']) ?></span>
                        </td>
                        <td class="py-2 px-3 text-sm text-gray-600"><?= formatFileSize($backup['size']) ?></td>
                        <td class="py-2 px-3 text-sm text-gray-600"><?= date('M d, Y H:i:s', $backup['modified']) ?></td>
                        <td class="py-2 px-3">
                            <div class="flex gap-2">
                                <a href="<?= SITE_URL ?>backups/<?= urlencode($backup['filename']) ?>" 
                                   download
                                   class="text-blue-600 hover:text-blue-800 text-sm" title="Download">
                                    <i class="fas fa-download"></i>
                                </a>
                                <button onclick="restoreBackup('<?= $backup['filename'] ?>')" 
                                        class="text-green-600 hover:text-green-800 text-sm" title="Restore">
                                    <i class="fas fa-undo"></i>
                                </button>
                                <button onclick="deleteBackup('<?= $backup['filename'] ?>')" 
                                        class="text-red-600 hover:text-red-800 text-sm" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-4 text-sm text-gray-400">
            Showing <?= count($backups) ?> backups
        </div>
    <?php endif; ?>
</div>

<!-- Create Backup Modal -->
<div id="createBackupModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-[#0b2b3f]">Create Backup</h3>
            <button onclick="closeCreateBackupModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="create_backup">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Backup Name</label>
                    <input type="text" name="backup_name" 
                           placeholder="Optional: backup_2026-07-31"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                    <p class="text-xs text-gray-400 mt-1">Leave empty for auto-generated name</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Backup Type</label>
                    <select name="backup_type" class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                        <option value="full">Full Database</option>
                        <option value="structure">Structure Only</option>
                        <option value="data">Data Only</option>
                    </select>
                </div>
            </div>
            
            <div class="flex gap-3 mt-6">
                <button type="submit" class="bg-[#0b2b3f] text-white px-6 py-2 rounded-lg font-semibold hover:bg-[#123a4f] transition flex-1">
                    <i class="fas fa-database mr-2"></i> Create Backup
                </button>
                <button type="button" onclick="closeCreateBackupModal()" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg font-semibold hover:bg-gray-200 transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateBackupModal() {
    document.getElementById('createBackupModal').classList.remove('hidden');
}

function closeCreateBackupModal() {
    document.getElementById('createBackupModal').classList.add('hidden');
}

function deleteBackup(filename) {
    if (confirm('Are you sure you want to delete this backup? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_backup">
            <input type="hidden" name="filename" value="${filename}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function restoreBackup(filename) {
    if (confirm('WARNING: Restoring this backup will overwrite the current database. Are you sure you want to continue?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="restore_backup">
            <input type="hidden" name="filename" value="${filename}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
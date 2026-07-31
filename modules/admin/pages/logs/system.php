<?php
// modules/admin/pages/logs/system.php - System Logs
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../../includes/functions.php';
    require_once __DIR__ . '/../../../../includes/auth.php';
}

$db = getDB();

// Get log files
$logDir = __DIR__ . '/../../../../logs/';
$logFiles = [];

// Ensure log directory exists
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Get PHP error log
$phpLog = ini_get('error_log');
$phpLogFile = $phpLog && file_exists($phpLog) ? basename($phpLog) : null;

// Get apache/nginx logs (if accessible)
$webLogs = [
    'access.log' => '/var/log/apache2/access.log',
    'error.log' => '/var/log/apache2/error.log'
];

// Check if we can read system logs
$canReadSystemLogs = is_readable('/var/log/apache2/access.log');

// Get application log files
if (is_dir($logDir)) {
    $files = scandir($logDir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) == 'log') {
            $filepath = $logDir . $file;
            $logFiles[] = [
                'name' => $file,
                'path' => $filepath,
                'size' => filesize($filepath),
                'modified' => date('Y-m-d H:i:s', filemtime($filepath)),
                'size_human' => formatFileSize(filesize($filepath)),
                'type' => 'application'
            ];
        }
    }
}

// Add web server logs if accessible
if ($canReadSystemLogs) {
    foreach ($webLogs as $name => $path) {
        if (file_exists($path) && is_readable($path)) {
            $logFiles[] = [
                'name' => $name,
                'path' => $path,
                'size' => filesize($path),
                'modified' => date('Y-m-d H:i:s', filemtime($path)),
                'size_human' => formatFileSize(filesize($path)),
                'type' => 'system'
            ];
        }
    }
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

// Handle log viewing
$viewLog = isset($_GET['view']) ? $_GET['view'] : '';
$logContent = '';
$logError = '';

if (!empty($viewLog)) {
    $logPath = $logDir . $viewLog;
    // Also check web logs
    if (isset($webLogs[$viewLog])) {
        $logPath = $webLogs[$viewLog];
    }
    
    if (file_exists($logPath) && is_readable($logPath)) {
        $content = file_get_contents($logPath);
        if ($content !== false) {
            // Limit to last 1000 lines for performance
            $lines = explode("\n", $content);
            $lines = array_slice($lines, -1000);
            $logContent = implode("\n", $lines);
        } else {
            $logError = 'Unable to read log file.';
        }
    } else {
        $logError = 'Log file not found or not readable.';
    }
}

// Handle log download
if (isset($_GET['download']) && !empty($_GET['download'])) {
    $logName = $_GET['download'];
    $logPath = $logDir . $logName;
    
    if (isset($webLogs[$logName])) {
        $logPath = $webLogs[$logName];
    }
    
    if (file_exists($logPath) && is_readable($logPath)) {
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . $logName . '"');
        readfile($logPath);
        exit;
    }
}

// Handle log deletion (application logs only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_log'])) {
    $logName = $_POST['log_name'];
    $logPath = $logDir . $logName;
    
    if (file_exists($logPath) && is_writable($logPath)) {
        if (unlink($logPath)) {
            $message = 'Log file deleted successfully!';
            logAction($currentUser['id'], 'delete_system_log', 'logs', 0);
            // Refresh page
            header('Location: /jms/admin?action=logs&subaction=system');
            exit;
        } else {
            $error = 'Failed to delete log file.';
        }
    } else {
        $error = 'Log file not found or not writable.';
    }
}

// Handle log clearing (application logs only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_log'])) {
    $logName = $_POST['log_name'];
    $logPath = $logDir . $logName;
    
    if (file_exists($logPath) && is_writable($logPath)) {
        if (file_put_contents($logPath, '') !== false) {
            $message = 'Log cleared successfully!';
            logAction($currentUser['id'], 'clear_system_log', 'logs', 0);
            // Refresh page
            header('Location: /jms/admin?action=logs&subaction=system');
            exit;
        } else {
            $error = 'Failed to clear log file.';
        }
    } else {
        $error = 'Log file not found or not writable.';
    }
}

// Get selected log content
$selectedLog = isset($_GET['view']) ? $_GET['view'] : (isset($logFiles[0]) ? $logFiles[0]['name'] : '');
?>
<div>
    <!-- System Info -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-blue-700"><?= count($logFiles) ?></p>
            <p class="text-xs text-blue-600">Log Files</p>
        </div>
        <div class="bg-green-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-green-700"><?= ini_get('log_errors') ? 'Enabled' : 'Disabled' ?></p>
            <p class="text-xs text-green-600">PHP Error Logging</p>
        </div>
        <div class="bg-purple-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-purple-700"><?= $canReadSystemLogs ? 'Yes' : 'No' ?></p>
            <p class="text-xs text-purple-600">Web Server Logs Access</p>
        </div>
        <div class="bg-yellow-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-yellow-700"><?= phpversion() ?></p>
            <p class="text-xs text-yellow-600">PHP Version</p>
        </div>
    </div>

    <?php if (isset($message)): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm mb-6">
            <i class="fas fa-check-circle mr-2"></i> <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm mb-6">
            <i class="fas fa-exclamation-circle mr-2"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Log Files List -->
        <div class="lg:col-span-1">
            <div class="bg-gray-50 rounded-xl p-4">
                <h4 class="font-semibold text-[#0b2b3f] mb-3">Log Files</h4>
                <div class="space-y-1 max-h-96 overflow-y-auto">
                    <?php foreach ($logFiles as $file): ?>
                    <div class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-100 transition cursor-pointer <?= $selectedLog == $file['name'] ? 'bg-indigo-50 border border-indigo-200' : '' ?>">
                        <a href="/jms/admin?action=logs&subaction=system&view=<?= urlencode($file['name']) ?>" class="flex-1">
                            <div>
                                <p class="text-sm font-medium text-[#0b2b3f]"><?= htmlspecialchars($file['name']) ?></p>
                                <p class="text-xs text-gray-400">
                                    <?= $file['size_human'] ?> · <?= $file['modified'] ?>
                                    <?php if ($file['type'] == 'system'): ?>
                                        <span class="ml-1 text-xs text-purple-600">(System)</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </a>
                        <?php if ($file['type'] == 'application'): ?>
                        <div class="flex gap-1">
                            <a href="/jms/admin?action=logs&subaction=system&download=<?= urlencode($file['name']) ?>" 
                               class="text-blue-600 hover:text-blue-800 text-sm" title="Download">
                                <i class="fas fa-download"></i>
                            </a>
                            <form method="POST" class="inline" onsubmit="return confirm('Clear this log file?')">
                                <input type="hidden" name="log_name" value="<?= htmlspecialchars($file['name']) ?>">
                                <button type="submit" name="clear_log" class="text-yellow-600 hover:text-yellow-800 text-sm" title="Clear">
                                    <i class="fas fa-eraser"></i>
                                </button>
                            </form>
                            <form method="POST" class="inline" onsubmit="return confirm('Delete this log file?')">
                                <input type="hidden" name="log_name" value="<?= htmlspecialchars($file['name']) ?>">
                                <button type="submit" name="delete_log" class="text-red-600 hover:text-red-800 text-sm" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($logFiles)): ?>
                        <p class="text-sm text-gray-500 text-center py-4">No log files found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Log Content -->
        <div class="lg:col-span-3">
            <div class="bg-gray-50 rounded-xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-semibold text-[#0b2b3f]">
                        <?= $selectedLog ? htmlspecialchars($selectedLog) : 'No log selected' ?>
                    </h4>
                    <div class="flex gap-2">
                        <?php if ($selectedLog): ?>
                            <a href="/jms/admin?action=logs&subaction=system&download=<?= urlencode($selectedLog) ?>" 
                               class="bg-blue-600 text-white px-3 py-1 rounded-lg hover:bg-blue-700 transition text-xs">
                                <i class="fas fa-download mr-1"></i> Download
                            </a>
                            <button onclick="window.location.reload()" class="bg-gray-100 text-gray-700 px-3 py-1 rounded-lg hover:bg-gray-200 transition text-xs">
                                <i class="fas fa-sync mr-1"></i> Refresh
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($logError): ?>
                    <div class="text-center py-8">
                        <p class="text-red-600"><?= htmlspecialchars($logError) ?></p>
                    </div>
                <?php elseif ($selectedLog && $logContent !== ''): ?>
                    <div class="bg-black rounded-lg p-4 overflow-x-auto max-h-96 overflow-y-auto">
                        <pre class="text-xs text-green-400 font-mono whitespace-pre-wrap"><?= htmlspecialchars($logContent) ?></pre>
                    </div>
                    <div class="mt-2 text-xs text-gray-400">
                        Showing last <?= count(explode("\n", $logContent)) ?> lines (max 1000)
                    </div>
                <?php elseif ($selectedLog): ?>
                    <div class="text-center py-12">
                        <i class="fas fa-file text-5xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">Log file is empty.</p>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <i class="fas fa-server text-5xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">Select a log file from the list to view its contents.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- PHP Info -->
    <div class="mt-6 border-t border-gray-200 pt-6">
        <details class="cursor-pointer">
            <summary class="font-semibold text-[#0b2b3f]">PHP Configuration</summary>
            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                <div class="flex justify-between p-2 bg-gray-50 rounded">
                    <span class="text-gray-600">PHP Version</span>
                    <span class="font-medium"><?= phpversion() ?></span>
                </div>
                <div class="flex justify-between p-2 bg-gray-50 rounded">
                    <span class="text-gray-600">Memory Limit</span>
                    <span class="font-medium"><?= ini_get('memory_limit') ?></span>
                </div>
                <div class="flex justify-between p-2 bg-gray-50 rounded">
                    <span class="text-gray-600">Max Execution Time</span>
                    <span class="font-medium"><?= ini_get('max_execution_time') ?> seconds</span>
                </div>
                <div class="flex justify-between p-2 bg-gray-50 rounded">
                    <span class="text-gray-600">Upload Max Filesize</span>
                    <span class="font-medium"><?= ini_get('upload_max_filesize') ?></span>
                </div>
                <div class="flex justify-between p-2 bg-gray-50 rounded">
                    <span class="text-gray-600">Post Max Size</span>
                    <span class="font-medium"><?= ini_get('post_max_size') ?></span>
                </div>
                <div class="flex justify-between p-2 bg-gray-50 rounded">
                    <span class="text-gray-600">Display Errors</span>
                    <span class="font-medium"><?= ini_get('display_errors') ? 'On' : 'Off' ?></span>
                </div>
                <div class="flex justify-between p-2 bg-gray-50 rounded">
                    <span class="text-gray-600">Error Log</span>
                    <span class="font-medium"><?= ini_get('error_log') ?: 'Not set' ?></span>
                </div>
                <div class="flex justify-between p-2 bg-gray-50 rounded">
                    <span class="text-gray-600">Log Errors</span>
                    <span class="font-medium"><?= ini_get('log_errors') ? 'On' : 'Off' ?></span>
                </div>
            </div>
        </details>
    </div>

    <!-- Server Info -->
    <div class="mt-4 border-t border-gray-200 pt-6">
        <details class="cursor-pointer">
            <summary class="font-semibold text-[#0b2b3f]">Server Information</summary>
            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                <div class="flex justify-between p-2 bg-gray-50 rounded">
                    <span class="text-gray-600">Server Software</span>
                    <span class="font-medium"><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></span>
                </div>
                <div class="flex justify-between p-2 bg-gray-50 rounded">
                    <span class="text-gray-600">Server Name</span>
                    <span class="font-medium"><?= $_SERVER['SERVER_NAME'] ?? 'Unknown' ?></span>
                </div>
                <div class="flex justify-between p-2 bg-gray-50 rounded">
                    <span class="text-gray-600">Document Root</span>
                    <span class="font-medium"><?= $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown' ?></span>
                </div>
                <div class="flex justify-between p-2 bg-gray-50 rounded">
                    <span class="text-gray-600">Current Time</span>
                    <span class="font-medium"><?= date('Y-m-d H:i:s') ?></span>
                </div>
                <div class="flex justify-between p-2 bg-gray-50 rounded">
                    <span class="text-gray-600">Timezone</span>
                    <span class="font-medium"><?= date_default_timezone_get() ?></span>
                </div>
                <div class="flex justify-between p-2 bg-gray-50 rounded">
                    <span class="text-gray-600">Database</span>
                    <span class="font-medium"><?= DB_NAME ?></span>
                </div>
            </div>
        </details>
    </div>
</div>
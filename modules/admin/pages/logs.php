<?php
// modules/admin/pages/logs.php - Main Logs Dashboard
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

requireRole(['admin']);

$db = getDB();
$message = '';
$error = '';

// Get the subaction
$subaction = $_GET['subaction'] ?? 'activity';

// Get log statistics
$stats = [];

// Total activity logs
$stmt = $db->query("SELECT COUNT(*) as count FROM audit_logs");
$stats['total_activity'] = $stmt->fetch()['count'] ?? 0;

// Today's activity
$stmt = $db->query("SELECT COUNT(*) as count FROM audit_logs WHERE DATE(created_at) = CURDATE()");
$stats['today_activity'] = $stmt->fetch()['count'] ?? 0;

// This week's activity
$stmt = $db->query("SELECT COUNT(*) as count FROM audit_logs WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
$stats['week_activity'] = $stmt->fetch()['count'] ?? 0;

// Unique users with activity
$stmt = $db->query("SELECT COUNT(DISTINCT user_id) as count FROM audit_logs");
$stats['active_users'] = $stmt->fetch()['count'] ?? 0;

// Most common actions
$stmt = $db->query("
    SELECT action, COUNT(*) as count 
    FROM audit_logs 
    GROUP BY action 
    ORDER BY count DESC 
    LIMIT 5
");
$stats['top_actions'] = $stmt->fetchAll();

// Get activity by hour (for chart)
$stmt = $db->query("
    SELECT HOUR(created_at) as hour, COUNT(*) as count 
    FROM audit_logs 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY HOUR(created_at)
    ORDER BY hour ASC
");
$hourlyActivity = $stmt->fetchAll();

// Get activity by day (for chart)
$stmt = $db->query("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM audit_logs 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$dailyActivity = $stmt->fetchAll();

// Get system log info
$logFiles = [];
$logDir = __DIR__ . '/../../../../logs/';
if (is_dir($logDir)) {
    $files = scandir($logDir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) == 'log') {
            $filepath = $logDir . $file;
            $logFiles[] = [
                'name' => $file,
                'size' => filesize($filepath),
                'modified' => date('Y-m-d H:i:s', filemtime($filepath)),
                'size_human' => formatFileSize(filesize($filepath))
            ];
        }
    }
    usort($logFiles, function($a, $b) {
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
?>
<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">
                <?php 
                $logLabels = [
                    'activity' => 'Activity Logs',
                    'audit' => 'Audit Trail',
                    'system' => 'System Logs'
                ];
                echo $logLabels[$subaction] ?? 'Logs';
                ?>
            </h2>
            <p class="text-gray-500 text-sm mt-1">Monitor system activity and logs</p>
        </div>
        <div class="flex gap-3">
            <a href="/jms/admin" class="text-indigo-600 hover:text-indigo-800 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
            <button onclick="window.location.reload()" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition text-sm">
                <i class="fas fa-sync mr-1"></i> Refresh
            </button>
            <button onclick="window.print()" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition text-sm">
                <i class="fas fa-print mr-1"></i> Print
            </button>
        </div>
    </div>
    <div class="h-1 w-20 bg-indigo-200 rounded-full mb-6"></div>

    <!-- Logs Navigation -->
    <div class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 pb-4">
        <a href="/jms/admin?action=logs&subaction=activity" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $subaction == 'activity' ? 'bg-[#0b2b3f] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">
            <i class="fas fa-list mr-1"></i> Activity Logs
            <span class="ml-1 text-xs">(<?= $stats['total_activity'] ?>)</span>
        </a>
        <a href="/jms/admin?action=logs&subaction=audit" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $subaction == 'audit' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-600 hover:bg-blue-100' ?>">
            <i class="fas fa-clipboard-list mr-1"></i> Audit Trail
        </a>
        <a href="/jms/admin?action=logs&subaction=system" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $subaction == 'system' ? 'bg-purple-600 text-white' : 'bg-purple-50 text-purple-600 hover:bg-purple-100' ?>">
            <i class="fas fa-server mr-1"></i> System Logs
        </a>
    </div>

    <?php if ($subaction == 'activity'): ?>
        <!-- Activity Logs -->
        <?php include 'logs/activity.php'; ?>
    <?php elseif ($subaction == 'audit'): ?>
        <!-- Audit Trail -->
        <?php include 'logs/audit.php'; ?>
    <?php elseif ($subaction == 'system'): ?>
        <!-- System Logs -->
        <?php include 'logs/system.php'; ?>
    <?php endif; ?>
</div>
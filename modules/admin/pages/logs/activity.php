<?php
// modules/admin/pages/logs/activity.php - Activity Logs
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../../includes/functions.php';
    require_once __DIR__ . '/../../../../includes/auth.php';
}

$db = getDB();
$message = '';
$error = '';

// Get filter parameters
$filterAction = $_GET['action_filter'] ?? 'all';
$filterUser = isset($_GET['user_filter']) ? (int)$_GET['user_filter'] : 0;
$filterTable = $_GET['table_filter'] ?? 'all';
$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT al.*, u.full_name as user_name, u.email as user_email, u.role as user_role
        FROM audit_logs al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE DATE(al.created_at) BETWEEN ? AND ?";
$params = [$dateFrom, $dateTo];

if ($filterAction != 'all') {
    $sql .= " AND al.action = ?";
    $params[] = $filterAction;
}

if ($filterUser > 0) {
    $sql .= " AND al.user_id = ?";
    $params[] = $filterUser;
}

if ($filterTable != 'all') {
    $sql .= " AND al.table_name = ?";
    $params[] = $filterTable;
}

if (!empty($search)) {
    $sql .= " AND (al.action LIKE ? OR al.table_name LIKE ? OR u.full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY al.created_at DESC LIMIT 500";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get distinct actions for filter
$stmt = $db->query("SELECT DISTINCT action FROM audit_logs ORDER BY action");
$actions = $stmt->fetchAll();

// Get distinct tables for filter
$stmt = $db->query("SELECT DISTINCT table_name FROM audit_logs WHERE table_name IS NOT NULL ORDER BY table_name");
$tables = $stmt->fetchAll();

// Get users for filter
$stmt = $db->query("SELECT id, full_name FROM users WHERE is_active = 1 ORDER BY full_name");
$users = $stmt->fetchAll();

// Get stats for activity
$stmt = $db->query("SELECT COUNT(*) as count FROM audit_logs WHERE DATE(created_at) = CURDATE()");
$todayCount = $stmt->fetch()['count'] ?? 0;

$stmt = $db->query("SELECT COUNT(*) as count FROM audit_logs WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
$weekCount = $stmt->fetch()['count'] ?? 0;

$stmt = $db->query("SELECT COUNT(*) as count FROM audit_logs WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
$monthCount = $stmt->fetch()['count'] ?? 0;

// Get action colors
$actionColors = [
    'login' => 'bg-green-100 text-green-700',
    'logout' => 'bg-gray-100 text-gray-700',
    'register' => 'bg-blue-100 text-blue-700',
    'submit_manuscript' => 'bg-purple-100 text-purple-700',
    'update_status' => 'bg-yellow-100 text-yellow-700',
    'assign_editor' => 'bg-indigo-100 text-indigo-700',
    'invite_reviewer' => 'bg-pink-100 text-pink-700',
    'submit_review' => 'bg-teal-100 text-teal-700',
    'publish_article' => 'bg-green-100 text-green-700',
    'delete' => 'bg-red-100 text-red-700',
    'update' => 'bg-orange-100 text-orange-700',
    'create' => 'bg-blue-100 text-blue-700'
];
?>
<div>
    <!-- Quick Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-blue-700"><?= $todayCount ?></p>
            <p class="text-xs text-blue-600">Today</p>
        </div>
        <div class="bg-purple-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-purple-700"><?= $weekCount ?></p>
            <p class="text-xs text-purple-600">Last 7 Days</p>
        </div>
        <div class="bg-green-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-green-700"><?= $monthCount ?></p>
            <p class="text-xs text-green-600">Last 30 Days</p>
        </div>
        <div class="bg-yellow-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-yellow-700"><?= count($logs) ?></p>
            <p class="text-xs text-yellow-600">Filtered Results</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-gray-50 rounded-xl p-4 mb-6">
        <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-3">
            <input type="hidden" name="action" value="logs">
            <input type="hidden" name="subaction" value="activity">
            
            <div>
                <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Date From</label>
                <input type="date" name="date_from" value="<?= $dateFrom ?>"
                       class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Date To</label>
                <input type="date" name="date_to" value="<?= $dateTo ?>"
                       class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Action</label>
                <select name="action_filter" class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none text-sm">
                    <option value="all">All Actions</option>
                    <?php foreach ($actions as $action): ?>
                    <option value="<?= htmlspecialchars($action['action']) ?>" <?= $filterAction == $action['action'] ? 'selected' : '' ?>>
                        <?= ucfirst(str_replace('_', ' ', $action['action'])) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 uppercase mb-1">User</label>
                <select name="user_filter" class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none text-sm">
                    <option value="0">All Users</option>
                    <?php foreach ($users as $user): ?>
                    <option value="<?= $user['id'] ?>" <?= $filterUser == $user['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($user['full_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Table</label>
                <select name="table_filter" class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none text-sm">
                    <option value="all">All Tables</option>
                    <?php foreach ($tables as $table): ?>
                    <option value="<?= htmlspecialchars($table['table_name']) ?>" <?= $filterTable == $table['table_name'] ? 'selected' : '' ?>>
                        <?= ucfirst($table['table_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="md:col-span-3 lg:col-span-5 flex gap-2">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Search</label>
                    <input type="text" name="search" placeholder="Search logs..." 
                           value="<?= htmlspecialchars($search) ?>"
                           class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none text-sm">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="bg-[#0b2b3f] text-white px-4 py-2 rounded-lg hover:bg-[#123a4f] transition text-sm">
                        <i class="fas fa-filter mr-1"></i> Filter
                    </button>
                    <a href="/jms/admin?action=logs&subaction=activity" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition text-sm">
                        <i class="fas fa-times mr-1"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <?php if (empty($logs)): ?>
        <div class="text-center py-12">
            <i class="fas fa-search text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No activity logs found matching your filters.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Timestamp</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">User</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Action</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Table</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Record ID</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                        <td class="py-2 px-3 text-gray-600 whitespace-nowrap">
                            <?= formatDate($log['created_at']) ?>
                            <br>
                            <span class="text-xs text-gray-400"><?= date('H:i:s', strtotime($log['created_at'])) ?></span>
                        </td>
                        <td class="py-2 px-3">
                            <div>
                                <p class="font-medium text-[#0b2b3f] text-sm"><?= htmlspecialchars($log['user_name'] ?? 'System') ?></p>
                                <?php if ($log['user_email']): ?>
                                    <p class="text-xs text-gray-400"><?= htmlspecialchars($log['user_email']) ?></p>
                                <?php endif; ?>
                                <?php if ($log['user_role']): ?>
                                    <span class="text-xs px-1.5 py-0.5 rounded bg-gray-100 text-gray-600"><?= ucfirst($log['user_role']) ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="py-2 px-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium <?= $actionColors[$log['action']] ?? 'bg-gray-100 text-gray-700' ?>">
                                <?= ucfirst(str_replace('_', ' ', $log['action'])) ?>
                            </span>
                        </td>
                        <td class="py-2 px-3 text-gray-600"><?= ucfirst($log['table_name'] ?? '-') ?></td>
                        <td class="py-2 px-3 text-gray-600"><?= $log['record_id'] ?? '-' ?></td>
                        <td class="py-2 px-3">
                            <button onclick="showLogDetails(<?= htmlspecialchars(json_encode($log)) ?>)" 
                                    class="text-indigo-600 hover:text-indigo-800 text-sm">
                                <i class="fas fa-eye"></i> View
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-4 text-sm text-gray-400">
            Showing <?= count($logs) ?> log entries (limited to 500)
        </div>
    <?php endif; ?>
</div>

<!-- Log Details Modal -->
<div id="logDetailsModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-2xl p-6 w-full max-w-2xl max-h-[80vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-[#0b2b3f]">Log Details</h3>
            <button onclick="closeLogDetails()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="space-y-4" id="logDetailsContent">
            <!-- Dynamic content loaded via JavaScript -->
        </div>
        <div class="mt-6">
            <button onclick="closeLogDetails()" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg font-semibold hover:bg-gray-200 transition">
                Close
            </button>
        </div>
    </div>
</div>

<script>
function showLogDetails(log) {
    const content = document.getElementById('logDetailsContent');
    content.innerHTML = `
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase">Timestamp</p>
                <p class="text-sm text-gray-700">${log.created_at}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase">User</p>
                <p class="text-sm text-gray-700">${log.user_name || 'System'}</p>
                <p class="text-xs text-gray-500">${log.user_email || ''}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase">Action</p>
                <p class="text-sm text-gray-700">${log.action}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase">Table</p>
                <p class="text-sm text-gray-700">${log.table_name || '-'}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase">Record ID</p>
                <p class="text-sm text-gray-700">${log.record_id || '-'}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase">IP Address</p>
                <p class="text-sm text-gray-700">${log.ip_address || '-'}</p>
            </div>
        </div>
        <div>
            <p class="text-xs font-medium text-gray-500 uppercase">User Agent</p>
            <p class="text-sm text-gray-700 text-xs break-all">${log.user_agent || '-'}</p>
        </div>
        ${log.old_data ? `
        <div>
            <p class="text-xs font-medium text-gray-500 uppercase">Old Data</p>
            <pre class="text-sm bg-gray-50 p-3 rounded-lg overflow-x-auto">${JSON.stringify(JSON.parse(log.old_data), null, 2)}</pre>
        </div>
        ` : ''}
        ${log.new_data ? `
        <div>
            <p class="text-xs font-medium text-gray-500 uppercase">New Data</p>
            <pre class="text-sm bg-gray-50 p-3 rounded-lg overflow-x-auto">${JSON.stringify(JSON.parse(log.new_data), null, 2)}</pre>
        </div>
        ` : ''}
    `;
    document.getElementById('logDetailsModal').classList.remove('hidden');
}

function closeLogDetails() {
    document.getElementById('logDetailsModal').classList.add('hidden');
}
</script>
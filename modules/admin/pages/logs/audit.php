<?php
// modules/admin/pages/logs/audit.php - Audit Trail
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../../includes/functions.php';
    require_once __DIR__ . '/../../../../includes/auth.php';
}

$db = getDB();

// Get filter parameters
$entityType = $_GET['entity_type'] ?? 'all';
$actionType = $_GET['action_type'] ?? 'all';
$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';

// Build query for audit trail
$sql = "SELECT al.*, u.full_name as user_name, u.email as user_email, u.role as user_role
        FROM audit_logs al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE DATE(al.created_at) BETWEEN ? AND ?";
$params = [$dateFrom, $dateTo];

if ($entityType != 'all') {
    $sql .= " AND al.table_name = ?";
    $params[] = $entityType;
}

if ($actionType != 'all') {
    $sql .= " AND al.action = ?";
    $params[] = $actionType;
}

if (!empty($search)) {
    $sql .= " AND (al.action LIKE ? OR al.table_name LIKE ? OR u.full_name LIKE ? OR al.record_id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY al.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$auditLogs = $stmt->fetchAll();

// Get entity types for filter
$stmt = $db->query("SELECT DISTINCT table_name FROM audit_logs WHERE table_name IS NOT NULL ORDER BY table_name");
$entities = $stmt->fetchAll();

// Get action types for filter
$stmt = $db->query("SELECT DISTINCT action FROM audit_logs ORDER BY action");
$actions = $stmt->fetchAll();

// Get audit summary
$stmt = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN action IN ('create', 'insert') THEN 1 ELSE 0 END) as creates,
        SUM(CASE WHEN action IN ('update', 'edit') THEN 1 ELSE 0 END) as updates,
        SUM(CASE WHEN action = 'delete' THEN 1 ELSE 0 END) as deletes,
        SUM(CASE WHEN action IN ('login', 'logout') THEN 1 ELSE 0 END) as auth_actions
    FROM audit_logs
    WHERE DATE(created_at) BETWEEN ? AND ?
");
$stmt->execute([$dateFrom, $dateTo]);
$auditSummary = $stmt->fetch();

// Get top users by activity
$stmt = $db->prepare("
    SELECT u.full_name, u.email, COUNT(al.id) as activity_count
    FROM audit_logs al
    JOIN users u ON al.user_id = u.id
    WHERE DATE(al.created_at) BETWEEN ? AND ?
    GROUP BY al.user_id
    ORDER BY activity_count DESC
    LIMIT 10
");
$stmt->execute([$dateFrom, $dateTo]);
$topUsers = $stmt->fetchAll();

// Get activity by table
$stmt = $db->prepare("
    SELECT table_name, COUNT(*) as count
    FROM audit_logs
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY table_name
    ORDER BY count DESC
    LIMIT 10
");
$stmt->execute([$dateFrom, $dateTo]);
$tableActivity = $stmt->fetchAll();

// Action colors
$actionColors = [
    'login' => 'bg-green-100 text-green-700',
    'logout' => 'bg-gray-100 text-gray-700',
    'register' => 'bg-blue-100 text-blue-700',
    'create' => 'bg-blue-100 text-blue-700',
    'insert' => 'bg-blue-100 text-blue-700',
    'update' => 'bg-yellow-100 text-yellow-700',
    'edit' => 'bg-yellow-100 text-yellow-700',
    'delete' => 'bg-red-100 text-red-700',
    'submit_manuscript' => 'bg-purple-100 text-purple-700',
    'update_status' => 'bg-orange-100 text-orange-700',
    'assign_editor' => 'bg-indigo-100 text-indigo-700',
    'invite_reviewer' => 'bg-pink-100 text-pink-700',
    'submit_review' => 'bg-teal-100 text-teal-700',
    'publish_article' => 'bg-green-100 text-green-700'
];
?>
<div>
    <!-- Audit Summary -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-blue-700"><?= $auditSummary['total'] ?? 0 ?></p>
            <p class="text-xs text-blue-600">Total Activities</p>
        </div>
        <div class="bg-green-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-green-700"><?= $auditSummary['creates'] ?? 0 ?></p>
            <p class="text-xs text-green-600">Create Operations</p>
        </div>
        <div class="bg-yellow-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-yellow-700"><?= $auditSummary['updates'] ?? 0 ?></p>
            <p class="text-xs text-yellow-600">Update Operations</p>
        </div>
        <div class="bg-red-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-red-700"><?= $auditSummary['deletes'] ?? 0 ?></p>
            <p class="text-xs text-red-600">Delete Operations</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-gray-50 rounded-xl p-4 mb-6">
        <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-3">
            <input type="hidden" name="action" value="logs">
            <input type="hidden" name="subaction" value="audit">
            
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
                <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Entity</label>
                <select name="entity_type" class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none text-sm">
                    <option value="all">All Entities</option>
                    <?php foreach ($entities as $entity): ?>
                    <option value="<?= htmlspecialchars($entity['table_name']) ?>" <?= $entityType == $entity['table_name'] ? 'selected' : '' ?>>
                        <?= ucfirst($entity['table_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Action Type</label>
                <select name="action_type" class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none text-sm">
                    <option value="all">All Actions</option>
                    <?php foreach ($actions as $action): ?>
                    <option value="<?= htmlspecialchars($action['action']) ?>" <?= $actionType == $action['action'] ? 'selected' : '' ?>>
                        <?= ucfirst(str_replace('_', ' ', $action['action'])) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Search</label>
                <div class="flex gap-2">
                    <input type="text" name="search" placeholder="Search..." 
                           value="<?= htmlspecialchars($search) ?>"
                           class="flex-1 px-3 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none text-sm">
                </div>
            </div>
            <div class="md:col-span-3 lg:col-span-5 flex gap-2">
                <button type="submit" class="bg-[#0b2b3f] text-white px-4 py-2 rounded-lg hover:bg-[#123a4f] transition text-sm">
                    <i class="fas fa-filter mr-1"></i> Apply
                </button>
                <a href="/jms/admin?action=logs&subaction=audit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition text-sm">
                    <i class="fas fa-times mr-1"></i> Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Top Users & Table Activity -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div>
            <h4 class="font-semibold text-[#0b2b3f] mb-3">Top Active Users</h4>
            <div class="space-y-2">
                <?php foreach ($topUsers as $user): ?>
                <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                    <div>
                        <p class="font-medium text-sm"><?= htmlspecialchars($user['full_name']) ?></p>
                        <p class="text-xs text-gray-400"><?= htmlspecialchars($user['email']) ?></p>
                    </div>
                    <span class="text-sm font-bold text-blue-600"><?= $user['activity_count'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div>
            <h4 class="font-semibold text-[#0b2b3f] mb-3">Activity by Table</h4>
            <div class="space-y-2">
                <?php foreach ($tableActivity as $table): ?>
                <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                    <span class="text-sm"><?= ucfirst($table['table_name'] ?? 'Unknown') ?></span>
                    <span class="text-sm font-bold text-purple-600"><?= $table['count'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Audit Logs Table -->
    <?php if (empty($auditLogs)): ?>
        <div class="text-center py-12">
            <i class="fas fa-clipboard-list text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No audit records found.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Timestamp</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">User</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Action</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Entity</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Record</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Changes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($auditLogs as $log): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                        <td class="py-2 px-3 text-gray-600 whitespace-nowrap">
                            <?= formatDate($log['created_at']) ?>
                            <br>
                            <span class="text-xs text-gray-400"><?= date('H:i:s', strtotime($log['created_at'])) ?></span>
                        </td>
                        <td class="py-2 px-3">
                            <p class="font-medium text-[#0b2b3f] text-sm"><?= htmlspecialchars($log['user_name'] ?? 'System') ?></p>
                            <p class="text-xs text-gray-400"><?= htmlspecialchars($log['user_email'] ?? '') ?></p>
                        </td>
                        <td class="py-2 px-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium <?= $actionColors[$log['action']] ?? 'bg-gray-100 text-gray-700' ?>">
                                <?= ucfirst(str_replace('_', ' ', $log['action'])) ?>
                            </span>
                        </td>
                        <td class="py-2 px-3 text-gray-600"><?= ucfirst($log['table_name'] ?? '-') ?></td>
                        <td class="py-2 px-3 text-gray-600">#<?= $log['record_id'] ?? '-' ?></td>
                        <td class="py-2 px-3">
                            <?php if ($log['old_data'] || $log['new_data']): ?>
                                <button onclick="showAuditDetails(<?= htmlspecialchars(json_encode($log)) ?>)" 
                                        class="text-indigo-600 hover:text-indigo-800 text-sm">
                                    <i class="fas fa-code-branch mr-1"></i> View Changes
                                </button>
                            <?php else: ?>
                                <span class="text-xs text-gray-400">No changes</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-4 text-sm text-gray-400">
            Showing <?= count($auditLogs) ?> audit records
        </div>
    <?php endif; ?>
</div>

<!-- Audit Details Modal -->
<div id="auditDetailsModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-2xl p-6 w-full max-w-2xl max-h-[80vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-[#0b2b3f]">Audit Details</h3>
            <button onclick="closeAuditDetails()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="auditDetailsContent">
            <!-- Dynamic content loaded via JavaScript -->
        </div>
        <div class="mt-6">
            <button onclick="closeAuditDetails()" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg font-semibold hover:bg-gray-200 transition">
                Close
            </button>
        </div>
    </div>
</div>

<script>
function showAuditDetails(log) {
    const content = document.getElementById('auditDetailsContent');
    let html = `
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase">Timestamp</p>
                <p class="text-sm text-gray-700">${log.created_at}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase">User</p>
                <p class="text-sm text-gray-700">${log.user_name || 'System'}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase">Action</p>
                <p class="text-sm text-gray-700 font-medium">${log.action}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase">Entity</p>
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
    `;
    
    if (log.old_data || log.new_data) {
        html += `<div class="border-t border-gray-200 pt-4">
            <p class="text-xs font-medium text-gray-500 uppercase mb-2">Data Changes</p>
            <div class="grid grid-cols-2 gap-4">`;
        if (log.old_data) {
            html += `<div>
                <p class="text-xs font-medium text-red-600">Old Data</p>
                <pre class="text-xs bg-red-50 p-3 rounded-lg overflow-x-auto border border-red-200">${JSON.stringify(JSON.parse(log.old_data), null, 2)}</pre>
            </div>`;
        }
        if (log.new_data) {
            html += `<div>
                <p class="text-xs font-medium text-green-600">New Data</p>
                <pre class="text-xs bg-green-50 p-3 rounded-lg overflow-x-auto border border-green-200">${JSON.stringify(JSON.parse(log.new_data), null, 2)}</pre>
            </div>`;
        }
        html += `</div></div>`;
    }
    
    content.innerHTML = html;
    document.getElementById('auditDetailsModal').classList.remove('hidden');
}

function closeAuditDetails() {
    document.getElementById('auditDetailsModal').classList.add('hidden');
}
</script>
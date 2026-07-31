<?php
// modules/admin/pages/finance/waivers.php - Waivers Management
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../../includes/functions.php';
    require_once __DIR__ . '/../../../../includes/auth.php';
}

$db = getDB();
$message = '';
$error = '';

// Handle waiver actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_waiver'])) {
        $manuscriptId = (int)$_POST['manuscript_id'];
        $amount = (float)$_POST['amount'];
        $currency = $_POST['currency'] ?? 'USD';
        $reason = trim($_POST['reason'] ?? '');
        $approvedBy = $currentUser['id'];
        
        $stmt = $db->prepare("
            INSERT INTO waivers (manuscript_id, amount, currency, reason, status, approved_by, approved_at, created_at) 
            VALUES (?, ?, ?, ?, 'approved', ?, NOW(), NOW())
        ");
        if ($stmt->execute([$manuscriptId, $amount, $currency, $reason, $approvedBy])) {
            $message = 'Waiver approved successfully!';
            logAction($currentUser['id'], 'create_waiver', 'waivers', $db->lastInsertId());
        } else {
            $error = 'Failed to create waiver.';
        }
    } elseif (isset($_POST['delete_waiver'])) {
        $waiverId = (int)$_POST['waiver_id'];
        $stmt = $db->prepare("DELETE FROM waivers WHERE id = ?");
        if ($stmt->execute([$waiverId])) {
            $message = 'Waiver deleted successfully!';
            logAction($currentUser['id'], 'delete_waiver', 'waivers', $waiverId);
        } else {
            $error = 'Failed to delete waiver.';
        }
    }
}

// Get all waivers
$sql = "SELECT w.*, m.title as manuscript_title, u.full_name as author_name,
        a.full_name as approver_name
        FROM waivers w
        LEFT JOIN manuscripts m ON w.manuscript_id = m.id
        LEFT JOIN users u ON m.corresponding_author_id = u.id
        LEFT JOIN users a ON w.approved_by = a.id
        ORDER BY w.created_at DESC";

$stmt = $db->query($sql);
$waivers = $stmt->fetchAll();

// Get waiver statistics
$stmt = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(amount) as total_amount,
        COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_count,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
        COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_count,
        SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as approved_total
    FROM waivers
");
$waiverStats = $stmt->fetch();

// Get manuscripts for waiver assignment
$stmt = $db->query("
    SELECT m.id, m.title, u.full_name as author_name
    FROM manuscripts m
    LEFT JOIN users u ON m.corresponding_author_id = u.id
    LEFT JOIN waivers w ON m.id = w.manuscript_id
    WHERE m.status IN ('accepted', 'published') AND w.id IS NULL
    ORDER BY m.accepted_at DESC
");
$unassignedManuscripts = $stmt->fetchAll();

// Get waiver reasons distribution
$stmt = $db->query("
    SELECT reason, COUNT(*) as count
    FROM waivers
    WHERE status = 'approved'
    GROUP BY reason
    ORDER BY count DESC
    LIMIT 5
");
$reasonStats = $stmt->fetchAll();
?>
<div>
    <!-- Waiver Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-blue-700"><?= $waiverStats['total'] ?? 0 ?></p>
            <p class="text-xs text-blue-600">Total Waivers</p>
        </div>
        <div class="bg-green-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-green-700"><?= $waiverStats['approved_count'] ?? 0 ?></p>
            <p class="text-xs text-green-600">Approved</p>
        </div>
        <div class="bg-yellow-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-yellow-700"><?= $waiverStats['pending_count'] ?? 0 ?></p>
            <p class="text-xs text-yellow-600">Pending</p>
        </div>
        <div class="bg-purple-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-purple-700"><?= getSetting('apc_currency') ?? 'USD' ?> <?= number_format($waiverStats['approved_total'] ?? 0, 2) ?></p>
            <p class="text-xs text-purple-600">Total Waived</p>
        </div>
    </div>

    <!-- Create Waiver -->
    <div class="border border-gray-200 rounded-xl p-4 mb-6">
        <h4 class="font-semibold text-[#0b2b3f] mb-4">Approve Waiver</h4>
        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Manuscript *</label>
                <select name="manuscript_id" required class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                    <option value="">Select manuscript...</option>
                    <?php foreach ($unassignedManuscripts as $manuscript): ?>
                    <option value="<?= $manuscript['id'] ?>">
                        <?= htmlspecialchars(substr($manuscript['title'], 0, 40)) ?> - <?= htmlspecialchars($manuscript['author_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Amount to Waive *</label>
                <div class="flex gap-2">
                    <input type="number" name="amount" step="0.01" min="0" required 
                           value="<?= getSetting('apc_amount') ?? 0 ?>"
                           class="flex-1 px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                    <input type="text" name="currency" value="<?= getSetting('apc_currency') ?? 'USD' ?>"
                           class="w-20 px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none text-center">
                </div>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Waiver *</label>
                <textarea name="reason" rows="3" required
                          placeholder="Explain why this APC should be waived..."
                          class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none"></textarea>
                <p class="text-xs text-gray-400 mt-1"><?= htmlspecialchars(getSetting('waiver_policy') ?? '') ?></p>
            </div>
            <div class="md:col-span-2">
                <button type="submit" name="create_waiver" class="bg-yellow-600 text-white px-6 py-2 rounded-lg hover:bg-yellow-700 transition">
                    <i class="fas fa-hand-holding-heart mr-2"></i> Approve Waiver
                </button>
            </div>
        </form>
    </div>

    <!-- Waiver Reasons Distribution -->
    <?php if (!empty($reasonStats)): ?>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <?php foreach ($reasonStats as $reason): ?>
        <div class="bg-gray-50 rounded-xl p-3 text-center">
            <p class="text-sm font-medium text-gray-700"><?= htmlspecialchars($reason['reason']) ?></p>
            <p class="text-lg font-bold text-[#0b2b3f]"><?= $reason['count'] ?></p>
            <p class="text-xs text-gray-500">waivers</p>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Waivers List -->
    <div>
        <h4 class="font-semibold text-[#0b2b3f] mb-3">All Waivers</h4>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Manuscript</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Author</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Amount</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Reason</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($waivers as $waiver): ?>
                    <tr class="border-b border-gray-100">
                        <td class="py-2 px-3 text-gray-600"><?= formatDate($waiver['created_at']) ?></td>
                        <td class="py-2 px-3 text-gray-600"><?= htmlspecialchars(substr($waiver['manuscript_title'] ?? '', 0, 30)) ?>...</td>
                        <td class="py-2 px-3 text-gray-600"><?= htmlspecialchars($waiver['author_name'] ?? 'Unknown') ?></td>
                        <td class="py-2 px-3 font-bold text-red-600">
                            <?= htmlspecialchars($waiver['currency'] ?? 'USD') ?> <?= number_format($waiver['amount'], 2) ?>
                        </td>
                        <td class="py-2 px-3 text-gray-600"><?= htmlspecialchars(substr($waiver['reason'] ?? '', 0, 30)) ?>...</td>
                        <td class="py-2 px-3">
                            <?php if ($waiver['status'] == 'approved'): ?>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Approved</span>
                            <?php elseif ($waiver['status'] == 'pending'): ?>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">Pending</span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Rejected</span>
                            <?php endif; ?>
                            <?php if ($waiver['approver_name']): ?>
                                <br><span class="text-xs text-gray-400">By: <?= htmlspecialchars($waiver['approver_name']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="py-2 px-3">
                            <form method="POST" class="inline" onsubmit="return confirm('Delete this waiver?')">
                                <input type="hidden" name="waiver_id" value="<?= $waiver['id'] ?>">
                                <button type="submit" name="delete_waiver" class="text-red-600 hover:text-red-800 text-sm" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
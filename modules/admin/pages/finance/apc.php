<?php
// modules/admin/pages/finance/apc.php - APC Management
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../../includes/functions.php';
    require_once __DIR__ . '/../../../../includes/auth.php';
}

$db = getDB();
$message = '';
$error = '';

// Get current APC settings
$apcAmount = getSetting('apc_amount') ?? 0;
$apcCurrency = getSetting('apc_currency') ?? 'USD';
$waiverPolicy = getSetting('waiver_policy') ?? '';
$apcEnabled = getSetting('apc_enabled') ?? 'true';

// Get APC statistics
$stmt = $db->query("
    SELECT 
        COUNT(*) as total_articles,
        SUM(CASE WHEN p.status = 'completed' THEN p.amount ELSE 0 END) as collected,
        SUM(CASE WHEN p.status = 'pending' THEN p.amount ELSE 0 END) as pending,
        SUM(CASE WHEN w.status = 'approved' THEN w.amount ELSE 0 END) as waived
    FROM manuscripts m
    LEFT JOIN payments p ON m.id = p.manuscript_id
    LEFT JOIN waivers w ON m.id = w.manuscript_id
    WHERE m.status IN ('accepted', 'published')
");
$apcStats = $stmt->fetch();

// Get recent APC assignments
$stmt = $db->query("
    SELECT m.id, m.title, u.full_name as author_name,
           m.accepted_at,
           (SELECT amount FROM payments WHERE manuscript_id = m.id AND status = 'completed' LIMIT 1) as paid_amount,
           (SELECT amount FROM waivers WHERE manuscript_id = m.id AND status = 'approved' LIMIT 1) as waived_amount,
           (SELECT status FROM payments WHERE manuscript_id = m.id ORDER BY created_at DESC LIMIT 1) as payment_status
    FROM manuscripts m
    LEFT JOIN users u ON m.corresponding_author_id = u.id
    WHERE m.status IN ('accepted', 'published')
    ORDER BY m.accepted_at DESC
    LIMIT 20
");
$apcAssignments = $stmt->fetchAll();

// Handle APC update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_apc'])) {
        $amount = (float)$_POST['apc_amount'];
        $currency = $_POST['apc_currency'] ?? 'USD';
        $policy = trim($_POST['waiver_policy'] ?? '');
        $enabled = isset($_POST['apc_enabled']) ? 'true' : 'false';
        
        try {
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, setting_group) 
                                   VALUES ('apc_amount', ?, 'finance') 
                                   ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$amount, $amount]);
            
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, setting_group) 
                                   VALUES ('apc_currency', ?, 'finance') 
                                   ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$currency, $currency]);
            
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, setting_group) 
                                   VALUES ('waiver_policy', ?, 'finance') 
                                   ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$policy, $policy]);
            
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, setting_group) 
                                   VALUES ('apc_enabled', ?, 'finance') 
                                   ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$enabled, $enabled]);
            
            $message = 'APC settings updated successfully!';
            logAction($currentUser['id'], 'update_apc_settings', 'settings', 0);
            
            // Refresh values
            $apcAmount = getSetting('apc_amount') ?? 0;
            $apcCurrency = getSetting('apc_currency') ?? 'USD';
            $waiverPolicy = getSetting('waiver_policy') ?? '';
            $apcEnabled = getSetting('apc_enabled') ?? 'true';
        } catch (Exception $e) {
            $error = 'Failed to update APC settings.';
        }
    } elseif (isset($_POST['assign_apc'])) {
        $manuscriptId = (int)$_POST['manuscript_id'];
        $amount = (float)$_POST['amount'];
        $currency = $_POST['currency'] ?? 'USD';
        
        // Check if payment already exists
        $stmt = $db->prepare("SELECT id FROM payments WHERE manuscript_id = ?");
        $stmt->execute([$manuscriptId]);
        if ($stmt->fetch()) {
            $error = 'APC already assigned to this manuscript.';
        } else {
            $stmt = $db->prepare("
                INSERT INTO payments (manuscript_id, amount, currency, status, created_at) 
                VALUES (?, ?, ?, 'pending', NOW())
            ");
            if ($stmt->execute([$manuscriptId, $amount, $currency])) {
                $message = 'APC assigned successfully!';
                logAction($currentUser['id'], 'assign_apc', 'payments', $db->lastInsertId());
                // Refresh data
                $stmt = $db->query("
                    SELECT m.id, m.title, u.full_name as author_name,
                           m.accepted_at,
                           (SELECT amount FROM payments WHERE manuscript_id = m.id AND status = 'completed' LIMIT 1) as paid_amount,
                           (SELECT amount FROM waivers WHERE manuscript_id = m.id AND status = 'approved' LIMIT 1) as waived_amount,
                           (SELECT status FROM payments WHERE manuscript_id = m.id ORDER BY created_at DESC LIMIT 1) as payment_status
                    FROM manuscripts m
                    LEFT JOIN users u ON m.corresponding_author_id = u.id
                    WHERE m.status IN ('accepted', 'published')
                    ORDER BY m.accepted_at DESC
                    LIMIT 20
                ");
                $apcAssignments = $stmt->fetchAll();
            } else {
                $error = 'Failed to assign APC.';
            }
        }
    }
}

// Get manuscripts without APC for assignment
$stmt = $db->query("
    SELECT m.id, m.title, u.full_name as author_name
    FROM manuscripts m
    LEFT JOIN users u ON m.corresponding_author_id = u.id
    LEFT JOIN payments p ON m.id = p.manuscript_id
    WHERE m.status IN ('accepted', 'published') AND p.id IS NULL
    ORDER BY m.accepted_at DESC
");
$unassignedManuscripts = $stmt->fetchAll();
?>
<div>
    <!-- APC Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-blue-700"><?= $apcStats['total_articles'] ?? 0 ?></p>
            <p class="text-xs text-blue-600">Total Articles</p>
        </div>
        <div class="bg-green-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-green-700"><?= $apcCurrency ?> <?= number_format($apcStats['collected'] ?? 0, 2) ?></p>
            <p class="text-xs text-green-600">Collected</p>
        </div>
        <div class="bg-yellow-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-yellow-700"><?= $apcCurrency ?> <?= number_format($apcStats['pending'] ?? 0, 2) ?></p>
            <p class="text-xs text-yellow-600">Pending</p>
        </div>
        <div class="bg-red-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-red-700"><?= $apcCurrency ?> <?= number_format($apcStats['waived'] ?? 0, 2) ?></p>
            <p class="text-xs text-red-600">Waived</p>
        </div>
    </div>

    <!-- APC Settings -->
    <div class="border border-gray-200 rounded-xl p-4 mb-6">
        <h4 class="font-semibold text-[#0b2b3f] mb-4">APC Settings</h4>
        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">APC Amount *</label>
                <div class="flex gap-2">
                    <input type="number" name="apc_amount" step="0.01" min="0" required 
                           value="<?= $apcAmount ?>"
                           class="flex-1 px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                    <input type="text" name="apc_currency" 
                           value="<?= htmlspecialchars($apcCurrency) ?>"
                           class="w-20 px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none text-center">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="apc_enabled" <?= $apcEnabled == 'true' ? 'checked' : '' ?>>
                    <span class="text-sm text-gray-700">Enable APC Collection</span>
                </label>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Waiver Policy</label>
                <textarea name="waiver_policy" rows="3"
                          class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none"><?= htmlspecialchars($waiverPolicy) ?></textarea>
                <p class="text-xs text-gray-400 mt-1">Describe the conditions under which APC waivers are granted.</p>
            </div>
            <div class="md:col-span-2">
                <button type="submit" name="update_apc" class="bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                    <i class="fas fa-save mr-2"></i> Save APC Settings
                </button>
            </div>
        </form>
    </div>

    <!-- Assign APC -->
    <div class="border border-gray-200 rounded-xl p-4 mb-6">
        <h4 class="font-semibold text-[#0b2b3f] mb-4">Assign APC to Manuscript</h4>
        <form method="POST" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Manuscript</label>
                <select name="manuscript_id" required class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                    <option value="">Select manuscript...</option>
                    <?php foreach ($unassignedManuscripts as $manuscript): ?>
                    <option value="<?= $manuscript['id'] ?>">
                        <?= htmlspecialchars(substr($manuscript['title'], 0, 50)) ?> - <?= htmlspecialchars($manuscript['author_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                <input type="number" name="amount" step="0.01" min="0" required 
                       value="<?= $apcAmount ?>"
                       class="w-32 px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Currency</label>
                <input type="text" name="currency" value="<?= htmlspecialchars($apcCurrency) ?>"
                       class="w-20 px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
            </div>
            <button type="submit" name="assign_apc" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-plus mr-2"></i> Assign APC
            </button>
        </form>
    </div>

    <!-- APC Assignments List -->
    <div>
        <h4 class="font-semibold text-[#0b2b3f] mb-3">APC Assignments</h4>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Manuscript</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Author</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Amount</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($apcAssignments as $assignment): ?>
                    <tr class="border-b border-gray-100">
                        <td class="py-2 px-3 text-gray-600"><?= htmlspecialchars(substr($assignment['title'], 0, 40)) ?>...</td>
                        <td class="py-2 px-3 text-gray-600"><?= htmlspecialchars($assignment['author_name'] ?? 'Unknown') ?></td>
                        <td class="py-2 px-3 font-medium">
                            <?php if ($assignment['waived_amount'] > 0): ?>
                                <span class="text-red-600">Waived: <?= $apcCurrency ?> <?= number_format($assignment['waived_amount'], 2) ?></span>
                            <?php elseif ($assignment['paid_amount'] > 0): ?>
                                <span class="text-green-600"><?= $apcCurrency ?> <?= number_format($assignment['paid_amount'], 2) ?></span>
                            <?php else: ?>
                                <span class="text-gray-400">Not assigned</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-2 px-3">
                            <?php if ($assignment['payment_status'] == 'completed'): ?>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Paid</span>
                            <?php elseif ($assignment['payment_status'] == 'pending'): ?>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">Pending</span>
                            <?php elseif ($assignment['waived_amount'] > 0): ?>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Waived</span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Not assigned</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-2 px-3">
                            <?php if ($assignment['payment_status'] != 'completed' && $assignment['waived_amount'] == 0): ?>
                                <a href="/jms/admin?action=finance&subaction=payments&manuscript_id=<?= $assignment['id'] ?>" 
                                   class="text-blue-600 hover:text-blue-800 text-sm">
                                    <i class="fas fa-credit-card"></i> Record Payment
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
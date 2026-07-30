<?php
// modules/admin/pages/finance/payments.php - Payments Management
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../../includes/functions.php';
    require_once __DIR__ . '/../../../../includes/auth.php';
}

$db = getDB();
$message = '';
$error = '';

$manuscriptId = isset($_GET['manuscript_id']) ? (int)$_GET['manuscript_id'] : 0;

// Handle payment actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['record_payment'])) {
        $manuscriptId = (int)$_POST['manuscript_id'];
        $amount = (float)$_POST['amount'];
        $currency = $_POST['currency'] ?? 'USD';
        $paymentMethod = $_POST['payment_method'] ?? 'other';
        $transactionId = trim($_POST['transaction_id'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        
        // Check if payment already exists
        $stmt = $db->prepare("SELECT id FROM payments WHERE manuscript_id = ?");
        $stmt->execute([$manuscriptId]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing payment
            $stmt = $db->prepare("
                UPDATE payments 
                SET amount = ?, currency = ?, payment_method = ?, transaction_id = ?, 
                    notes = ?, status = 'completed', completed_at = NOW()
                WHERE manuscript_id = ?
            ");
            if ($stmt->execute([$amount, $currency, $paymentMethod, $transactionId, $notes, $manuscriptId])) {
                $message = 'Payment updated successfully!';
                logAction($currentUser['id'], 'update_payment', 'payments', $existing['id']);
            } else {
                $error = 'Failed to update payment.';
            }
        } else {
            // Create new payment
            $stmt = $db->prepare("
                INSERT INTO payments (manuscript_id, amount, currency, payment_method, transaction_id, notes, status, completed_at, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'completed', NOW(), NOW())
            ");
            if ($stmt->execute([$manuscriptId, $amount, $currency, $paymentMethod, $transactionId, $notes])) {
                $message = 'Payment recorded successfully!';
                logAction($currentUser['id'], 'record_payment', 'payments', $db->lastInsertId());
            } else {
                $error = 'Failed to record payment.';
            }
        }
    } elseif (isset($_POST['delete_payment'])) {
        $paymentId = (int)$_POST['payment_id'];
        $stmt = $db->prepare("DELETE FROM payments WHERE id = ?");
        if ($stmt->execute([$paymentId])) {
            $message = 'Payment deleted successfully!';
            logAction($currentUser['id'], 'delete_payment', 'payments', $paymentId);
        } else {
            $error = 'Failed to delete payment.';
        }
    }
}

// Get all payments
$sql = "SELECT p.*, u.full_name as user_name, u.email as user_email, m.title as manuscript_title, m.doi
        FROM payments p
        LEFT JOIN users u ON p.user_id = u.id
        LEFT JOIN manuscripts m ON p.manuscript_id = m.id";

if ($manuscriptId > 0) {
    $sql .= " WHERE p.manuscript_id = " . $manuscriptId;
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $db->query($sql);
$payments = $stmt->fetchAll();

// Get manuscript details for the form
$manuscript = null;
if ($manuscriptId > 0) {
    $stmt = $db->prepare("SELECT m.*, u.full_name as author_name FROM manuscripts m 
                          LEFT JOIN users u ON m.corresponding_author_id = u.id 
                          WHERE m.id = ?");
    $stmt->execute([$manuscriptId]);
    $manuscript = $stmt->fetch();
}

// Get payment statistics
$stmt = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as collected,
        SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count
    FROM payments
");
$paymentStats = $stmt->fetch();

// Get payment methods distribution
$stmt = $db->query("
    SELECT payment_method, COUNT(*) as count, SUM(amount) as total
    FROM payments
    WHERE status = 'completed'
    GROUP BY payment_method
");
$methodStats = $stmt->fetchAll();
?>
<div>
    <!-- Payment Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-blue-700"><?= $paymentStats['total'] ?? 0 ?></p>
            <p class="text-xs text-blue-600">Total Payments</p>
        </div>
        <div class="bg-green-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-green-700"><?= $paymentStats['completed_count'] ?? 0 ?></p>
            <p class="text-xs text-green-600">Completed</p>
        </div>
        <div class="bg-yellow-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-yellow-700"><?= $paymentStats['pending_count'] ?? 0 ?></p>
            <p class="text-xs text-yellow-600">Pending</p>
        </div>
        <div class="bg-purple-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-purple-700"><?= getSetting('apc_currency') ?? 'USD' ?> <?= number_format($paymentStats['collected'] ?? 0, 2) ?></p>
            <p class="text-xs text-purple-600">Collected</p>
        </div>
    </div>

    <!-- Record Payment Form -->
    <?php if ($manuscriptId > 0 && $manuscript): ?>
    <div class="border border-gray-200 rounded-xl p-4 mb-6">
        <h4 class="font-semibold text-[#0b2b3f] mb-4">
            Record Payment for: <?= htmlspecialchars(substr($manuscript['title'], 0, 60)) ?>...
        </h4>
        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="hidden" name="manuscript_id" value="<?= $manuscriptId ?>">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Amount *</label>
                <div class="flex gap-2">
                    <input type="number" name="amount" step="0.01" min="0" required 
                           value="<?= getSetting('apc_amount') ?? 0 ?>"
                           class="flex-1 px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                    <input type="text" name="currency" value="<?= getSetting('apc_currency') ?? 'USD' ?>"
                           class="w-20 px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none text-center">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                <select name="payment_method" class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                    <option value="credit_card">Credit Card</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="paypal">PayPal</option>
                    <option value="mobile_money">Mobile Money</option>
                    <option value="cash">Cash</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Transaction ID</label>
                <input type="text" name="transaction_id" 
                       placeholder="Enter transaction reference"
                       class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <input type="text" name="notes" 
                       placeholder="Additional notes"
                       class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
            </div>
            <div class="md:col-span-2">
                <button type="submit" name="record_payment" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition">
                    <i class="fas fa-check-circle mr-2"></i> Record Payment
                </button>
                <a href="/jms/admin?action=finance&subaction=payments" class="ml-2 text-gray-500 hover:text-gray-700">
                    Cancel
                </a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Payment Methods Distribution -->
    <?php if (!empty($methodStats)): ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <?php foreach ($methodStats as $method): ?>
        <div class="bg-gray-50 rounded-xl p-3 text-center">
            <p class="text-sm font-medium text-gray-700"><?= ucfirst(str_replace('_', ' ', $method['payment_method'])) ?></p>
            <p class="text-lg font-bold text-[#0b2b3f]"><?= $method['count'] ?></p>
            <p class="text-xs text-gray-500"><?= getSetting('apc_currency') ?? 'USD' ?> <?= number_format($method['total'], 2) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Payments List -->
    <div>
        <h4 class="font-semibold text-[#0b2b3f] mb-3">All Payments</h4>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Manuscript</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">User</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Amount</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Method</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                    <tr class="border-b border-gray-100">
                        <td class="py-2 px-3 text-gray-600"><?= formatDate($payment['created_at']) ?></td>
                        <td class="py-2 px-3 text-gray-600">
                            <?= htmlspecialchars(substr($payment['manuscript_title'] ?? '', 0, 30)) ?>...
                            <?php if ($payment['doi']): ?>
                                <br><span class="text-xs text-gray-400">DOI: <?= htmlspecialchars($payment['doi']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="py-2 px-3 text-gray-600"><?= htmlspecialchars($payment['user_name'] ?? 'N/A') ?></td>
                        <td class="py-2 px-3 font-bold text-[#0b2b3f]">
                            <?= htmlspecialchars($payment['currency'] ?? 'USD') ?> <?= number_format($payment['amount'], 2) ?>
                        </td>
                        <td class="py-2 px-3 text-gray-600"><?= ucfirst(str_replace('_', ' ', $payment['payment_method'] ?? 'N/A')) ?></td>
                        <td class="py-2 px-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $payment['status'] == 'completed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' ?>">
                                <?= ucfirst($payment['status']) ?>
                            </span>
                        </td>
                        <td class="py-2 px-3">
                            <?php if ($payment['status'] != 'completed'): ?>
                                <form method="POST" class="inline" onsubmit="return confirm('Mark this payment as completed?')">
                                    <input type="hidden" name="payment_id" value="<?= $payment['id'] ?>">
                                    <button type="submit" name="complete_payment" class="text-green-600 hover:text-green-800 text-sm">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" class="inline" onsubmit="return confirm('Delete this payment?')">
                                <input type="hidden" name="payment_id" value="<?= $payment['id'] ?>">
                                <button type="submit" name="delete_payment" class="text-red-600 hover:text-red-800 text-sm">
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
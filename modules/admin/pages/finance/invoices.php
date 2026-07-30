<?php
// modules/admin/pages/finance/invoices.php - Invoices Management
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../../includes/functions.php';
    require_once __DIR__ . '/../../../../includes/auth.php';
}

$db = getDB();
$message = '';
$error = '';

// Handle invoice actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_invoice'])) {
        $manuscriptId = (int)$_POST['manuscript_id'];
        $amount = (float)$_POST['amount'];
        $currency = $_POST['currency'] ?? 'USD';
        $dueDate = $_POST['due_date'] ?? date('Y-m-d', strtotime('+30 days'));
        $notes = trim($_POST['notes'] ?? '');
        
        // Generate invoice number
        $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $stmt = $db->prepare("
            INSERT INTO invoices (manuscript_id, invoice_number, amount, currency, due_date, notes, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        if ($stmt->execute([$manuscriptId, $invoiceNumber, $amount, $currency, $dueDate, $notes])) {
            $message = 'Invoice created successfully! Invoice #' . $invoiceNumber;
            logAction($currentUser['id'], 'create_invoice', 'invoices', $db->lastInsertId());
        } else {
            $error = 'Failed to create invoice.';
        }
    } elseif (isset($_POST['mark_paid'])) {
        $invoiceId = (int)$_POST['invoice_id'];
        $paymentDate = $_POST['payment_date'] ?? date('Y-m-d');
        
        $stmt = $db->prepare("UPDATE invoices SET status = 'paid', paid_at = ? WHERE id = ?");
        if ($stmt->execute([$paymentDate, $invoiceId])) {
            $message = 'Invoice marked as paid!';
            logAction($currentUser['id'], 'mark_invoice_paid', 'invoices', $invoiceId);
        } else {
            $error = 'Failed to update invoice.';
        }
    } elseif (isset($_POST['delete_invoice'])) {
        $invoiceId = (int)$_POST['invoice_id'];
        $stmt = $db->prepare("DELETE FROM invoices WHERE id = ?");
        if ($stmt->execute([$invoiceId])) {
            $message = 'Invoice deleted successfully!';
            logAction($currentUser['id'], 'delete_invoice', 'invoices', $invoiceId);
        } else {
            $error = 'Failed to delete invoice.';
        }
    }
}

// Get all invoices
$sql = "SELECT i.*, m.title as manuscript_title, u.full_name as author_name,
        (SELECT amount FROM payments WHERE manuscript_id = i.manuscript_id AND status = 'completed' LIMIT 1) as paid_amount
        FROM invoices i
        LEFT JOIN manuscripts m ON i.manuscript_id = m.id
        LEFT JOIN users u ON m.corresponding_author_id = u.id
        ORDER BY i.created_at DESC";

$stmt = $db->query($sql);
$invoices = $stmt->fetchAll();

// Get invoice statistics
$stmt = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid_total,
        SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_total,
        SUM(CASE WHEN status = 'overdue' THEN amount ELSE 0 END) as overdue_total,
        COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_count,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
        COUNT(CASE WHEN status = 'overdue' THEN 1 END) as overdue_count
    FROM invoices
");
$invoiceStats = $stmt->fetch();

// Get manuscripts without invoices for assignment
$stmt = $db->query("
    SELECT m.id, m.title, u.full_name as author_name
    FROM manuscripts m
    LEFT JOIN users u ON m.corresponding_author_id = u.id
    LEFT JOIN invoices i ON m.id = i.manuscript_id
    WHERE m.status IN ('accepted', 'published') AND i.id IS NULL
    ORDER BY m.accepted_at DESC
");
$unassignedManuscripts = $stmt->fetchAll();
?>
<div>
    <!-- Invoice Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-blue-700"><?= $invoiceStats['total'] ?? 0 ?></p>
            <p class="text-xs text-blue-600">Total Invoices</p>
        </div>
        <div class="bg-green-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-green-700"><?= $invoiceStats['paid_count'] ?? 0 ?></p>
            <p class="text-xs text-green-600">Paid</p>
        </div>
        <div class="bg-yellow-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-yellow-700"><?= $invoiceStats['pending_count'] ?? 0 ?></p>
            <p class="text-xs text-yellow-600">Pending</p>
        </div>
        <div class="bg-red-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-red-700"><?= $invoiceStats['overdue_count'] ?? 0 ?></p>
            <p class="text-xs text-red-600">Overdue</p>
        </div>
    </div>

    <!-- Create Invoice -->
    <div class="border border-gray-200 rounded-xl p-4 mb-6">
        <h4 class="font-semibold text-[#0b2b3f] mb-4">Create New Invoice</h4>
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
                <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                <input type="date" name="due_date" value="<?= date('Y-m-d', strtotime('+30 days')) ?>"
                       class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <input type="text" name="notes" placeholder="Additional notes"
                       class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
            </div>
            <div class="md:col-span-2">
                <button type="submit" name="create_invoice" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition">
                    <i class="fas fa-file-invoice mr-2"></i> Create Invoice
                </button>
            </div>
        </form>
    </div>

    <!-- Invoices List -->
    <div>
        <h4 class="font-semibold text-[#0b2b3f] mb-3">All Invoices</h4>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Invoice #</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Manuscript</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Author</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Amount</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Due Date</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices as $invoice): ?>
                    <tr class="border-b border-gray-100">
                        <td class="py-2 px-3 font-medium text-[#0b2b3f]"><?= htmlspecialchars($invoice['invoice_number']) ?></td>
                        <td class="py-2 px-3 text-gray-600"><?= htmlspecialchars(substr($invoice['manuscript_title'] ?? '', 0, 30)) ?>...</td>
                        <td class="py-2 px-3 text-gray-600"><?= htmlspecialchars($invoice['author_name'] ?? 'Unknown') ?></td>
                        <td class="py-2 px-3 font-bold">
                            <?= htmlspecialchars($invoice['currency'] ?? 'USD') ?> <?= number_format($invoice['amount'], 2) ?>
                            <?php if ($invoice['paid_amount'] > 0): ?>
                                <br><span class="text-xs text-green-600">Paid: <?= number_format($invoice['paid_amount'], 2) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="py-2 px-3 text-gray-600 <?= $invoice['due_date'] < date('Y-m-d') && $invoice['status'] != 'paid' ? 'text-red-600 font-medium' : '' ?>">
                            <?= formatDate($invoice['due_date']) ?>
                            <?php if ($invoice['due_date'] < date('Y-m-d') && $invoice['status'] != 'paid'): ?>
                                <br><span class="text-xs text-red-500">Overdue!</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-2 px-3">
                            <?php if ($invoice['status'] == 'paid'): ?>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Paid</span>
                            <?php elseif ($invoice['due_date'] < date('Y-m-d')): ?>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Overdue</span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-2 px-3">
                            <div class="flex gap-2">
                                <?php if ($invoice['status'] != 'paid'): ?>
                                    <button onclick="openPaymentModal(<?= htmlspecialchars(json_encode($invoice)) ?>)" 
                                            class="text-green-600 hover:text-green-800 text-sm" title="Mark as Paid">
                                        <i class="fas fa-check-circle"></i>
                                    </button>
                                <?php endif; ?>
                                <form method="POST" class="inline" onsubmit="return confirm('Delete this invoice?')">
                                    <input type="hidden" name="invoice_id" value="<?= $invoice['id'] ?>">
                                    <button type="submit" name="delete_invoice" class="text-red-600 hover:text-red-800 text-sm" title="Delete">
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
    </div>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-[#0b2b3f]">Mark Invoice as Paid</h3>
            <button onclick="closePaymentModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="invoice_id" id="paymentInvoiceId">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Invoice</label>
                    <p class="text-sm font-medium text-[#0b2b3f]" id="paymentInvoiceNumber"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Payment Date</label>
                    <input type="date" name="payment_date" value="<?= date('Y-m-d') ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="submit" name="mark_paid" class="bg-green-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-700 transition flex-1">
                    <i class="fas fa-check mr-2"></i> Mark as Paid
                </button>
                <button type="button" onclick="closePaymentModal()" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg font-semibold hover:bg-gray-200 transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openPaymentModal(invoice) {
    document.getElementById('paymentInvoiceId').value = invoice.id;
    document.getElementById('paymentInvoiceNumber').textContent = invoice.invoice_number + ' - ' + invoice.currency + ' ' + invoice.amount;
    document.getElementById('paymentModal').classList.remove('hidden');
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.add('hidden');
}
</script>
<?php
// modules/admin/pages/finance.php - Main Finance Dashboard
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

requireRole(['admin']);

$db = getDB();
$message = '';
$error = '';

// Get the subaction
$subaction = $_GET['subaction'] ?? 'dashboard';

// Get finance statistics
$stats = [];

// Total APC revenue
$stmt = $db->query("SELECT SUM(amount) as total FROM payments WHERE status = 'completed'");
$stats['total_revenue'] = $stmt->fetch()['total'] ?? 0;

// Pending payments
$stmt = $db->query("SELECT COUNT(*) as count, SUM(amount) as total FROM payments WHERE status = 'pending'");
$pending = $stmt->fetch();
$stats['pending_count'] = $pending['count'] ?? 0;
$stats['pending_total'] = $pending['total'] ?? 0;

// Total invoices
$stmt = $db->query("SELECT COUNT(*) as count FROM invoices");
$stats['total_invoices'] = $stmt->fetch()['count'] ?? 0;

// Overdue invoices
$stmt = $db->query("SELECT COUNT(*) as count FROM invoices WHERE due_date < CURDATE() AND status != 'paid'");
$stats['overdue_invoices'] = $stmt->fetch()['count'] ?? 0;

// Total waivers
$stmt = $db->query("SELECT COUNT(*) as count, SUM(amount) as total FROM waivers WHERE status = 'approved'");
$waivers = $stmt->fetch();
$stats['total_waivers'] = $waivers['count'] ?? 0;
$stats['waivers_amount'] = $waivers['total'] ?? 0;

// Get recent payments
$stmt = $db->query("
    SELECT p.*, u.full_name as user_name, m.title as manuscript_title 
    FROM payments p
    LEFT JOIN users u ON p.user_id = u.id
    LEFT JOIN manuscripts m ON p.manuscript_id = m.id
    ORDER BY p.created_at DESC
    LIMIT 5
");
$recentPayments = $stmt->fetchAll();

// Get monthly revenue
$stmt = $db->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, 
           SUM(amount) as revenue,
           COUNT(*) as payments
    FROM payments 
    WHERE status = 'completed'
    AND created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");
$monthlyRevenue = $stmt->fetchAll();

// Get APC settings
$apcAmount = getSetting('apc_amount') ?? 0;
$apcCurrency = getSetting('apc_currency') ?? 'USD';
$waiverPolicy = getSetting('waiver_policy') ?? '';

// Handle APC update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_apc'])) {
    $amount = (float)$_POST['apc_amount'];
    $currency = $_POST['apc_currency'] ?? 'USD';
    $policy = trim($_POST['waiver_policy'] ?? '');
    
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
        
        $message = 'APC settings updated successfully!';
        logAction($currentUser['id'], 'update_apc_settings', 'settings', 0);
        
        // Refresh values
        $apcAmount = getSetting('apc_amount') ?? 0;
        $apcCurrency = getSetting('apc_currency') ?? 'USD';
        $waiverPolicy = getSetting('waiver_policy') ?? '';
    } catch (Exception $e) {
        $error = 'Failed to update APC settings.';
    }
}
?>
<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">
                <?php 
                $financeLabels = [
                    'dashboard' => 'Finance Dashboard',
                    'apc' => 'APC Management',
                    'payments' => 'Payments',
                    'invoices' => 'Invoices',
                    'waivers' => 'Waivers'
                ];
                echo $financeLabels[$subaction] ?? 'Finance';
                ?>
            </h2>
            <p class="text-gray-500 text-sm mt-1">Manage article processing charges and financial transactions</p>
        </div>
        <div class="flex gap-3">
            <a href="/jms/admin" class="text-indigo-600 hover:text-indigo-800 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
            <button onclick="window.print()" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition text-sm">
                <i class="fas fa-print mr-1"></i> Print Report
            </button>
        </div>
    </div>
    <div class="h-1 w-20 bg-indigo-200 rounded-full mb-6"></div>

    <!-- Finance Navigation -->
    <div class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 pb-4">
        <a href="/jms/admin?action=finance&subaction=dashboard" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $subaction == 'dashboard' ? 'bg-[#0b2b3f] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">
            <i class="fas fa-chart-pie mr-1"></i> Dashboard
        </a>
        <a href="/jms/admin?action=finance&subaction=apc" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $subaction == 'apc' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-600 hover:bg-blue-100' ?>">
            <i class="fas fa-coins mr-1"></i> APC
        </a>
        <a href="/jms/admin?action=finance&subaction=payments" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $subaction == 'payments' ? 'bg-green-600 text-white' : 'bg-green-50 text-green-600 hover:bg-green-100' ?>">
            <i class="fas fa-credit-card mr-1"></i> Payments
        </a>
        <a href="/jms/admin?action=finance&subaction=invoices" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $subaction == 'invoices' ? 'bg-purple-600 text-white' : 'bg-purple-50 text-purple-600 hover:bg-purple-100' ?>">
            <i class="fas fa-file-invoice mr-1"></i> Invoices
        </a>
        <a href="/jms/admin?action=finance&subaction=waivers" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $subaction == 'waivers' ? 'bg-yellow-600 text-white' : 'bg-yellow-50 text-yellow-600 hover:bg-yellow-100' ?>">
            <i class="fas fa-hand-holding-heart mr-1"></i> Waivers
        </a>
    </div>

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

    <?php if ($subaction == 'dashboard'): ?>
        <!-- Finance Dashboard -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-green-50 rounded-xl p-4 border border-green-200">
                <p class="text-sm text-green-600">Total Revenue</p>
                <p class="text-2xl font-bold text-green-700"><?= $apcCurrency ?> <?= number_format($stats['total_revenue'], 2) ?></p>
            </div>
            <div class="bg-yellow-50 rounded-xl p-4 border border-yellow-200">
                <p class="text-sm text-yellow-600">Pending Payments</p>
                <p class="text-2xl font-bold text-yellow-700"><?= $stats['pending_count'] ?></p>
                <p class="text-xs text-yellow-500"><?= $apcCurrency ?> <?= number_format($stats['pending_total'], 2) ?></p>
            </div>
            <div class="bg-purple-50 rounded-xl p-4 border border-purple-200">
                <p class="text-sm text-purple-600">Total Invoices</p>
                <p class="text-2xl font-bold text-purple-700"><?= $stats['total_invoices'] ?></p>
                <p class="text-xs text-purple-500"><?= $stats['overdue_invoices'] ?> overdue</p>
            </div>
            <div class="bg-blue-50 rounded-xl p-4 border border-blue-200">
                <p class="text-sm text-blue-600">Total Waivers</p>
                <p class="text-2xl font-bold text-blue-700"><?= $stats['total_waivers'] ?></p>
                <p class="text-xs text-blue-500"><?= $apcCurrency ?> <?= number_format($stats['waivers_amount'], 2) ?></p>
            </div>
        </div>

        <!-- Revenue Chart -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div>
                <h4 class="font-semibold text-[#0b2b3f] mb-3">Monthly Revenue</h4>
                <canvas id="revenueChart" height="200"></canvas>
            </div>
            <div>
                <h4 class="font-semibold text-[#0b2b3f] mb-3">Recent Payments</h4>
                <div class="space-y-2">
                    <?php foreach ($recentPayments as $payment): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="text-sm font-medium text-[#0b2b3f]"><?= htmlspecialchars($payment['user_name'] ?? 'Unknown') ?></p>
                            <p class="text-xs text-gray-500"><?= htmlspecialchars(substr($payment['manuscript_title'] ?? '', 0, 30)) ?>...</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-green-600"><?= $apcCurrency ?> <?= number_format($payment['amount'], 2) ?></p>
                            <p class="text-xs text-gray-400"><?= formatDate($payment['created_at']) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
            <a href="/jms/admin?action=finance&subaction=apc" 
               class="text-center p-4 bg-blue-50 rounded-xl hover:bg-blue-100 transition border border-blue-200">
                <i class="fas fa-coins text-2xl text-blue-600"></i>
                <p class="text-sm font-medium mt-2">Configure APC</p>
            </a>
            <a href="/jms/admin?action=finance&subaction=payments" 
               class="text-center p-4 bg-green-50 rounded-xl hover:bg-green-100 transition border border-green-200">
                <i class="fas fa-credit-card text-2xl text-green-600"></i>
                <p class="text-sm font-medium mt-2">Record Payment</p>
            </a>
            <a href="/jms/admin?action=finance&subaction=invoices" 
               class="text-center p-4 bg-purple-50 rounded-xl hover:bg-purple-100 transition border border-purple-200">
                <i class="fas fa-file-invoice text-2xl text-purple-600"></i>
                <p class="text-sm font-medium mt-2">Create Invoice</p>
            </a>
            <a href="/jms/admin?action=finance&subaction=waivers" 
               class="text-center p-4 bg-yellow-50 rounded-xl hover:bg-yellow-100 transition border border-yellow-200">
                <i class="fas fa-hand-holding-heart text-2xl text-yellow-600"></i>
                <p class="text-sm font-medium mt-2">Manage Waivers</p>
            </a>
        </div>

    <?php elseif ($subaction == 'apc'): ?>
        <!-- APC Management -->
        <?php include 'finance/apc.php'; ?>
    <?php elseif ($subaction == 'payments'): ?>
        <!-- Payments -->
        <?php include 'finance/payments.php'; ?>
    <?php elseif ($subaction == 'invoices'): ?>
        <!-- Invoices -->
        <?php include 'finance/invoices.php'; ?>
    <?php elseif ($subaction == 'waivers'): ?>
        <!-- Waivers -->
        <?php include 'finance/waivers.php'; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
<?php if ($subaction == 'dashboard'): ?>
// Revenue Chart
const ctx1 = document.getElementById('revenueChart').getContext('2d');
const revenueData = <?= json_encode($monthlyRevenue) ?>;
new Chart(ctx1, {
    type: 'bar',
    data: {
        labels: revenueData.map(item => item.month),
        datasets: [{
            label: 'Revenue',
            data: revenueData.map(item => item.revenue),
            backgroundColor: 'rgba(34, 197, 94, 0.6)',
            borderColor: 'rgba(34, 197, 94, 1)',
            borderWidth: 2,
            borderRadius: 4
        }, {
            label: 'Payments',
            data: revenueData.map(item => item.payments),
            backgroundColor: 'rgba(59, 130, 246, 0.6)',
            borderColor: 'rgba(59, 130, 246, 1)',
            borderWidth: 2,
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top'
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
<?php endif; ?>
</script>
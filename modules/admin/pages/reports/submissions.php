<?php
// modules/admin/pages/reports/submissions.php - Submission Reports
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../../includes/functions.php';
    require_once __DIR__ . '/../../../../includes/auth.php';
}

$db = getDB();

// Get filter parameters
$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-1 year'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');
$statusFilter = $_GET['status'] ?? 'all';

// Get submission data
$sql = "SELECT 
            DATE(m.submission_date) as submission_date,
            m.status,
            m.article_type,
            m.submission_type,
            u.full_name as author_name,
            u.email as author_email,
            u.institution as author_institution
        FROM manuscripts m
        LEFT JOIN users u ON m.corresponding_author_id = u.id
        WHERE m.submission_date BETWEEN ? AND ?";

$params = [$dateFrom, $dateTo];

if ($statusFilter != 'all') {
    $sql .= " AND m.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY m.submission_date DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$submissions = $stmt->fetchAll();

// Get summary statistics
$summary = [
    'total' => count($submissions),
    'by_status' => [],
    'by_article_type' => [],
    'by_submission_type' => [],
    'by_month' => []
];

foreach ($submissions as $sub) {
    $status = $sub['status'] ?? 'unknown';
    $summary['by_status'][$status] = ($summary['by_status'][$status] ?? 0) + 1;
    
    $type = $sub['article_type'] ?? 'other';
    $summary['by_article_type'][$type] = ($summary['by_article_type'][$type] ?? 0) + 1;
    
    $subType = $sub['submission_type'] ?? 'regular';
    $summary['by_submission_type'][$subType] = ($summary['by_submission_type'][$subType] ?? 0) + 1;
    
    $month = date('Y-m', strtotime($sub['submission_date']));
    $summary['by_month'][$month] = ($summary['by_month'][$month] ?? 0) + 1;
}

// Get acceptance rate
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as accepted,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM manuscripts 
    WHERE submission_date BETWEEN ? AND ?
");
$stmt->execute([$dateFrom, $dateTo]);
$rateData = $stmt->fetch();
$acceptanceRate = $rateData['total'] > 0 ? round(($rateData['accepted'] / $rateData['total']) * 100, 1) : 0;
?>
<div>
    <!-- Date Range Filter -->
    <form method="GET" action="" class="flex flex-wrap gap-3 mb-6 items-end">
        <input type="hidden" name="action" value="reports">
        <input type="hidden" name="subaction" value="submissions">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
            <input type="date" name="date_from" value="<?= $dateFrom ?>" 
                   class="px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
            <input type="date" name="date_to" value="<?= $dateTo ?>" 
                   class="px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="status" class="px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none text-sm">
                <option value="all" <?= $statusFilter == 'all' ? 'selected' : '' ?>>All Status</option>
                <option value="submitted" <?= $statusFilter == 'submitted' ? 'selected' : '' ?>>Submitted</option>
                <option value="under_review" <?= $statusFilter == 'under_review' ? 'selected' : '' ?>>Under Review</option>
                <option value="accepted" <?= $statusFilter == 'accepted' ? 'selected' : '' ?>>Accepted</option>
                <option value="rejected" <?= $statusFilter == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                <option value="published" <?= $statusFilter == 'published' ? 'selected' : '' ?>>Published</option>
            </select>
        </div>
        <button type="submit" class="bg-[#0b2b3f] text-white px-4 py-2 rounded-lg hover:bg-[#123a4f] transition text-sm">
            <i class="fas fa-filter mr-1"></i> Apply Filter
        </button>
    </form>

    <!-- Summary Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-blue-700"><?= $summary['total'] ?></p>
            <p class="text-xs text-blue-600">Total Submissions</p>
        </div>
        <div class="bg-green-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-green-700"><?= $rateData['accepted'] ?? 0 ?></p>
            <p class="text-xs text-green-600">Accepted</p>
        </div>
        <div class="bg-red-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-red-700"><?= $rateData['rejected'] ?? 0 ?></p>
            <p class="text-xs text-red-600">Rejected</p>
        </div>
        <div class="bg-purple-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-purple-700"><?= $acceptanceRate ?>%</p>
            <p class="text-xs text-purple-600">Acceptance Rate</p>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div>
            <h4 class="font-semibold text-[#0b2b3f] mb-3">Submissions by Status</h4>
            <canvas id="submissionStatusChart" height="200"></canvas>
        </div>
        <div>
            <h4 class="font-semibold text-[#0b2b3f] mb-3">Submissions by Type</h4>
            <canvas id="submissionTypeChart" height="200"></canvas>
        </div>
    </div>

    <!-- Submissions Table -->
    <div class="mt-4">
        <h4 class="font-semibold text-[#0b2b3f] mb-3">Detailed Submissions</h4>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Author</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Type</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Institution</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $sub): ?>
                    <tr class="border-b border-gray-100">
                        <td class="py-2 px-3 text-gray-600"><?= formatDate($sub['submission_date']) ?></td>
                        <td class="py-2 px-3 text-gray-600"><?= htmlspecialchars($sub['author_name'] ?? 'Unknown') ?></td>
                        <td class="py-2 px-3 text-gray-600"><?= ucfirst(str_replace('_', ' ', $sub['article_type'] ?? 'N/A')) ?></td>
                        <td class="py-2 px-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= getStatusBadge($sub['status']) ?>">
                                <?= ucfirst(str_replace('_', ' ', $sub['status'] ?? 'N/A')) ?>
                            </span>
                        </td>
                        <td class="py-2 px-3 text-gray-600"><?= htmlspecialchars($sub['author_institution'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Status Chart
const ctx3 = document.getElementById('submissionStatusChart').getContext('2d');
const statusData = <?= json_encode($summary['by_status']) ?>;
const statusColors = {
    'submitted': '#3b82f6',
    'under_review': '#eab308',
    'revision_required': '#f97316',
    'accepted': '#22c55e',
    'rejected': '#ef4444',
    'published': '#8b5cf6'
};
new Chart(ctx3, {
    type: 'pie',
    data: {
        labels: Object.keys(statusData).map(k => k.replace('_', ' ').toUpperCase()),
        datasets: [{
            data: Object.values(statusData),
            backgroundColor: Object.keys(statusData).map(k => statusColors[k] || '#9ca3af'),
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Type Chart
const ctx4 = document.getElementById('submissionTypeChart').getContext('2d');
const typeData = <?= json_encode($summary['by_article_type']) ?>;
const typeColors = ['#3b82f6', '#8b5cf6', '#22c55e', '#f97316', '#eab308', '#ef4444'];
new Chart(ctx4, {
    type: 'bar',
    data: {
        labels: Object.keys(typeData).map(k => k.replace('_', ' ').toUpperCase()),
        datasets: [{
            label: 'Submissions',
            data: Object.values(typeData),
            backgroundColor: Object.keys(typeData).map((_, i) => typeColors[i % typeColors.length] + '80'),
            borderColor: Object.keys(typeData).map((_, i) => typeColors[i % typeColors.length]),
            borderWidth: 2,
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script>
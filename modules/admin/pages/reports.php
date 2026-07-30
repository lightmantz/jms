<?php
// modules/admin/pages/reports.php - Main Reports Dashboard
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

// Get report data based on subaction
$reportData = [];

// Get date range filter
$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-1 year'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

// Overall statistics
$stats = getAdminStats();

// Get monthly submissions for chart
$stmt = $db->query("
    SELECT DATE_FORMAT(submission_date, '%Y-%m') as month, 
           COUNT(*) as count,
           SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_count,
           SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
    FROM manuscripts 
    WHERE submission_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(submission_date, '%Y-%m')
    ORDER BY month ASC
");
$monthlyData = $stmt->fetchAll();

// Get submissions by type
$stmt = $db->query("
    SELECT article_type, COUNT(*) as count 
    FROM manuscripts 
    GROUP BY article_type
");
$submissionTypes = $stmt->fetchAll();

// Get submissions by status
$stmt = $db->query("
    SELECT status, COUNT(*) as count 
    FROM manuscripts 
    GROUP BY status
");
$statusCounts = $stmt->fetchAll();

// Get top authors
$stmt = $db->query("
    SELECT u.full_name, u.email, u.institution, 
           COUNT(m.id) as submission_count,
           SUM(CASE WHEN m.status = 'published' THEN 1 ELSE 0 END) as published_count
    FROM users u
    JOIN manuscripts m ON u.id = m.corresponding_author_id
    GROUP BY u.id
    ORDER BY submission_count DESC
    LIMIT 10
");
$topAuthors = $stmt->fetchAll();

// Get top countries
$stmt = $db->query("
    SELECT country, COUNT(*) as count 
    FROM users 
    WHERE country IS NOT NULL AND country != ''
    GROUP BY country 
    ORDER BY count DESC 
    LIMIT 10
");
$topCountries = $stmt->fetchAll();

// Get recent activity
$stmt = $db->query("
    SELECT action, table_name, created_at, 
           (SELECT full_name FROM users WHERE id = user_id) as user_name
    FROM audit_logs 
    ORDER BY created_at DESC 
    LIMIT 20
");
$recentActivity = $stmt->fetchAll();

// Get review statistics
$stmt = $db->query("
    SELECT 
        COUNT(*) as total_reviews,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_reviews,
        SUM(CASE WHEN status = 'invited' THEN 1 ELSE 0 END) as invited_reviews,
        SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted_reviews,
        SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) as declined_reviews,
        AVG(DATEDIFF(completed_date, accepted_date)) as avg_days_to_complete
    FROM reviews
");
$reviewStats = $stmt->fetch();

// Get monthly review activity
$stmt = $db->query("
    SELECT DATE_FORMAT(completed_date, '%Y-%m') as month, 
           COUNT(*) as count
    FROM reviews 
    WHERE status = 'completed' 
    AND completed_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(completed_date, '%Y-%m')
    ORDER BY month ASC
");
$monthlyReviews = $stmt->fetchAll();

// Get reviewer performance
$stmt = $db->query("
    SELECT u.full_name, u.email,
           COUNT(r.id) as total_reviews,
           SUM(CASE WHEN r.status = 'completed' THEN 1 ELSE 0 END) as completed_reviews,
           AVG(DATEDIFF(r.completed_date, r.accepted_date)) as avg_days
    FROM users u
    JOIN reviews r ON u.id = r.reviewer_id
    GROUP BY u.id
    HAVING total_reviews > 0
    ORDER BY completed_reviews DESC
    LIMIT 20
");
$reviewerPerformance = $stmt->fetchAll();

// Get citation data (simulated - in production, you'd have a citations table)
// For now, we'll generate some sample data
$citationData = [
    'total_citations' => 0,
    'average_citations' => 0,
    'most_cited_articles' => []
];

// Get analytics data
$analyticsData = [
    'total_views' => $stats['total_views'] ?? 0,
    'total_downloads' => $stats['total_downloads'] ?? 0,
    'unique_visitors' => 0,
    'bounce_rate' => 0
];
?>
<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">
                <?php 
                $reportLabels = [
                    'dashboard' => 'Reports Dashboard',
                    'submissions' => 'Submission Reports',
                    'editorial' => 'Editorial Reports',
                    'reviewers' => 'Reviewer Reports',
                    'citations' => 'Citation Reports',
                    'analytics' => 'Analytics'
                ];
                echo $reportLabels[$subaction] ?? 'Reports';
                ?>
            </h2>
            <p class="text-gray-500 text-sm mt-1">View and analyze journal statistics</p>
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

    <!-- Report Navigation -->
    <div class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 pb-4">
        <a href="/jms/admin?action=reports&subaction=dashboard" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $subaction == 'dashboard' ? 'bg-[#0b2b3f] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">
            <i class="fas fa-chart-pie mr-1"></i> Dashboard
        </a>
        <a href="/jms/admin?action=reports&subaction=submissions" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $subaction == 'submissions' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-600 hover:bg-blue-100' ?>">
            <i class="fas fa-file-alt mr-1"></i> Submissions
        </a>
        <a href="/jms/admin?action=reports&subaction=editorial" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $subaction == 'editorial' ? 'bg-purple-600 text-white' : 'bg-purple-50 text-purple-600 hover:bg-purple-100' ?>">
            <i class="fas fa-users-cog mr-1"></i> Editorial
        </a>
        <a href="/jms/admin?action=reports&subaction=reviewers" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $subaction == 'reviewers' ? 'bg-yellow-600 text-white' : 'bg-yellow-50 text-yellow-600 hover:bg-yellow-100' ?>">
            <i class="fas fa-user-graduate mr-1"></i> Reviewers
        </a>
        <a href="/jms/admin?action=reports&subaction=citations" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $subaction == 'citations' ? 'bg-green-600 text-white' : 'bg-green-50 text-green-600 hover:bg-green-100' ?>">
            <i class="fas fa-quote-right mr-1"></i> Citations
        </a>
        <a href="/jms/admin?action=reports&subaction=analytics" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $subaction == 'analytics' ? 'bg-red-600 text-white' : 'bg-red-50 text-red-600 hover:bg-red-100' ?>">
            <i class="fas fa-chart-line mr-1"></i> Analytics
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
        <!-- Dashboard View -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow-card p-4 border border-gray-100/70">
                <p class="text-sm text-gray-500">Total Submissions</p>
                <p class="text-2xl font-bold text-[#0b2b3f]"><?= $stats['total_manuscripts'] ?></p>
            </div>
            <div class="bg-white rounded-xl shadow-card p-4 border border-gray-100/70">
                <p class="text-sm text-gray-500">Published Articles</p>
                <p class="text-2xl font-bold text-green-600"><?= $stats['manuscripts_by_status'][array_search('published', array_column($stats['manuscripts_by_status'], 'status'))]['count'] ?? 0 ?></p>
            </div>
            <div class="bg-white rounded-xl shadow-card p-4 border border-gray-100/70">
                <p class="text-sm text-gray-500">Total Users</p>
                <p class="text-2xl font-bold text-blue-600"><?= $stats['total_users'] ?></p>
            </div>
            <div class="bg-white rounded-xl shadow-card p-4 border border-gray-100/70">
                <p class="text-sm text-gray-500">Total Views</p>
                <p class="text-2xl font-bold text-purple-600"><?= number_format($stats['total_views'] ?? 0) ?></p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div>
                <h3 class="font-semibold text-[#0b2b3f] mb-3">Submissions (Last 12 Months)</h3>
                <canvas id="submissionsChart" height="200"></canvas>
            </div>
            <div>
                <h3 class="font-semibold text-[#0b2b3f] mb-3">Manuscripts by Status</h3>
                <canvas id="statusChart" height="200"></canvas>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
            <div>
                <h3 class="font-semibold text-[#0b2b3f] mb-3">Top Authors</h3>
                <div class="space-y-2">
                    <?php foreach ($topAuthors as $author): ?>
                    <div class="flex items-center justify-between text-sm">
                        <span><?= htmlspecialchars($author['full_name']) ?></span>
                        <span class="text-gray-500"><?= $author['submission_count'] ?> submissions (<?= $author['published_count'] ?> published)</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div>
                <h3 class="font-semibold text-[#0b2b3f] mb-3">Top Countries</h3>
                <div class="space-y-2">
                    <?php foreach ($topCountries as $country): ?>
                    <div class="flex items-center justify-between text-sm">
                        <span><?= htmlspecialchars($country['country'] ?: 'Unknown') ?></span>
                        <span class="text-gray-500"><?= $country['count'] ?> users</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php elseif ($subaction == 'submissions'): ?>
        <!-- Submission Reports -->
        <?php include 'reports/submissions.php'; ?>
    <?php elseif ($subaction == 'editorial'): ?>
        <!-- Editorial Reports -->
        <?php include 'reports/editorial.php'; ?>
    <?php elseif ($subaction == 'reviewers'): ?>
        <!-- Reviewer Reports -->
        <?php include 'reports/reviewers.php'; ?>
    <?php elseif ($subaction == 'citations'): ?>
        <!-- Citation Reports -->
        <?php include 'reports/citations.php'; ?>
    <?php elseif ($subaction == 'analytics'): ?>
        <!-- Analytics -->
        <?php include 'reports/analytics.php'; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
<?php if ($subaction == 'dashboard'): ?>
// Submissions Chart
const ctx1 = document.getElementById('submissionsChart').getContext('2d');
const monthlyData = <?= json_encode($monthlyData) ?>;
new Chart(ctx1, {
    type: 'bar',
    data: {
        labels: monthlyData.map(item => item.month),
        datasets: [{
            label: 'Submissions',
            data: monthlyData.map(item => item.count),
            backgroundColor: 'rgba(11, 43, 63, 0.6)',
            borderColor: 'rgba(11, 43, 63, 1)',
            borderWidth: 2,
            borderRadius: 4
        }, {
            label: 'Published',
            data: monthlyData.map(item => item.published_count),
            backgroundColor: 'rgba(34, 197, 94, 0.6)',
            borderColor: 'rgba(34, 197, 94, 1)',
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
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Status Chart
const ctx2 = document.getElementById('statusChart').getContext('2d');
const statusData = <?= json_encode($statusCounts) ?>;
const colors = {
    'draft': '#9ca3af',
    'submitted': '#3b82f6',
    'under_review': '#eab308',
    'revision_required': '#f97316',
    'accepted': '#22c55e',
    'rejected': '#ef4444',
    'published': '#8b5cf6'
};
new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: statusData.map(item => item.status.replace('_', ' ').toUpperCase()),
        datasets: [{
            data: statusData.map(item => item.count),
            backgroundColor: statusData.map(item => colors[item.status] || '#9ca3af'),
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
<?php endif; ?>
</script>
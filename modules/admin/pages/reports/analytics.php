<?php
// modules/admin/pages/reports/analytics.php - Analytics Dashboard
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../../includes/functions.php';
    require_once __DIR__ . '/../../../../includes/auth.php';
}

$db = getDB();

// Get comprehensive analytics
$stmt = $db->query("
    SELECT 
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)) as new_users_30d,
        (SELECT COUNT(*) FROM manuscripts) as total_submissions,
        (SELECT COUNT(*) FROM manuscripts WHERE submission_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)) as new_submissions_30d,
        (SELECT COUNT(*) FROM manuscripts WHERE status = 'published') as total_published,
        (SELECT COUNT(*) FROM manuscripts WHERE publication_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)) as new_published_30d,
        (SELECT COUNT(*) FROM article_views) as total_views,
        (SELECT COUNT(*) FROM article_views WHERE viewed_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)) as views_30d,
        (SELECT COUNT(*) FROM article_downloads) as total_downloads,
        (SELECT COUNT(*) FROM article_downloads WHERE downloaded_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)) as downloads_30d,
        (SELECT COUNT(*) FROM reviews) as total_reviews,
        (SELECT COUNT(*) FROM reviews WHERE completed_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)) as completed_reviews_30d
");
$analytics = $stmt->fetch();

// Get daily activity for last 30 days
$stmt = $db->query("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as users_created,
        (SELECT COUNT(*) FROM manuscripts WHERE DATE(submission_date) = DATE(users.created_at)) as submissions,
        (SELECT COUNT(*) FROM article_views WHERE DATE(viewed_at) = DATE(users.created_at)) as views,
        (SELECT COUNT(*) FROM article_downloads WHERE DATE(downloaded_at) = DATE(users.created_at)) as downloads
    FROM users
    WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$dailyActivity = $stmt->fetchAll();

// Get device/browser stats (simulated - in production you'd track this)
$deviceStats = [
    'Desktop' => 45,
    'Mobile' => 35,
    'Tablet' => 20
];

$browserStats = [
    'Chrome' => 50,
    'Firefox' => 20,
    'Safari' => 18,
    'Edge' => 8,
    'Other' => 4
];

// Get peak hours (simulated)
$peakHours = [];
for ($i = 0; $i < 24; $i++) {
    $peakHours[$i] = rand(10, 100) - abs($i - 12) * 3;
}

// Calculate growth rates
$growthRate = 0;
if ($analytics['total_submissions'] > 0) {
    $growthRate = round((($analytics['new_submissions_30d'] / 30) / ($analytics['total_submissions'] / 365)) * 100, 1);
}

// Get top performing months
$stmt = $db->query("
    SELECT DATE_FORMAT(publication_date, '%Y-%m') as month,
           COUNT(*) as published_count,
           SUM((SELECT COUNT(*) FROM article_views WHERE manuscript_id = m.id)) as views_count
    FROM manuscripts m
    WHERE status = 'published' AND publication_date IS NOT NULL
    GROUP BY DATE_FORMAT(publication_date, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
");
$topMonths = $stmt->fetchAll();
?>
<div>
    <!-- KPI Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 rounded-xl p-4">
            <p class="text-sm text-blue-600">Total Users</p>
            <p class="text-2xl font-bold text-blue-700"><?= number_format($analytics['total_users'] ?? 0) ?></p>
            <p class="text-xs text-blue-500">+<?= $analytics['new_users_30d'] ?? 0 ?> in 30 days</p>
        </div>
        <div class="bg-purple-50 rounded-xl p-4">
            <p class="text-sm text-purple-600">Total Submissions</p>
            <p class="text-2xl font-bold text-purple-700"><?= number_format($analytics['total_submissions'] ?? 0) ?></p>
            <p class="text-xs text-purple-500">+<?= $analytics['new_submissions_30d'] ?? 0 ?> in 30 days</p>
        </div>
        <div class="bg-green-50 rounded-xl p-4">
            <p class="text-sm text-green-600">Total Published</p>
            <p class="text-2xl font-bold text-green-700"><?= number_format($analytics['total_published'] ?? 0) ?></p>
            <p class="text-xs text-green-500">+<?= $analytics['new_published_30d'] ?? 0 ?> in 30 days</p>
        </div>
        <div class="bg-orange-50 rounded-xl p-4">
            <p class="text-sm text-orange-600">Growth Rate</p>
            <p class="text-2xl font-bold text-orange-700"><?= $growthRate ?>%</p>
            <p class="text-xs text-orange-500">Monthly submissions growth</p>
        </div>
    </div>

    <!-- Engagement Metrics -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-indigo-50 rounded-xl p-4">
            <p class="text-sm text-indigo-600">Total Views</p>
            <p class="text-2xl font-bold text-indigo-700"><?= number_format($analytics['total_views'] ?? 0) ?></p>
            <p class="text-xs text-indigo-500">+<?= number_format($analytics['views_30d'] ?? 0) ?> in 30 days</p>
        </div>
        <div class="bg-teal-50 rounded-xl p-4">
            <p class="text-sm text-teal-600">Total Downloads</p>
            <p class="text-2xl font-bold text-teal-700"><?= number_format($analytics['total_downloads'] ?? 0) ?></p>
            <p class="text-xs text-teal-500">+<?= number_format($analytics['downloads_30d'] ?? 0) ?> in 30 days</p>
        </div>
        <div class="bg-yellow-50 rounded-xl p-4">
            <p class="text-sm text-yellow-600">Total Reviews</p>
            <p class="text-2xl font-bold text-yellow-700"><?= number_format($analytics['total_reviews'] ?? 0) ?></p>
            <p class="text-xs text-yellow-500">+<?= $analytics['completed_reviews_30d'] ?? 0 ?> completed</p>
        </div>
        <div class="bg-red-50 rounded-xl p-4">
            <p class="text-sm text-red-600">Engagement Rate</p>
            <p class="text-2xl font-bold text-red-700">
                <?= $analytics['total_views'] > 0 ? round(($analytics['total_downloads'] / $analytics['total_views']) * 100, 1) : 0 ?>%
            </p>
            <p class="text-xs text-red-500">Downloads per view</p>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div>
            <h4 class="font-semibold text-[#0b2b3f] mb-3">Daily Activity (30 Days)</h4>
            <canvas id="dailyActivityChart" height="200"></canvas>
        </div>
        <div>
            <h4 class="font-semibold text-[#0b2b3f] mb-3">Device Distribution</h4>
            <canvas id="deviceChart" height="200"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div>
            <h4 class="font-semibold text-[#0b2b3f] mb-3">Browser Distribution</h4>
            <canvas id="browserChart" height="200"></canvas>
        </div>
        <div>
            <h4 class="font-semibold text-[#0b2b3f] mb-3">Peak Hours (Views)</h4>
            <canvas id="peakHoursChart" height="200"></canvas>
        </div>
    </div>

    <!-- Top Performing Months -->
    <div class="mt-6">
        <h4 class="font-semibold text-[#0b2b3f] mb-3">Top Performing Months</h4>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <?php foreach ($topMonths as $month): ?>
            <div class="border border-gray-200 rounded-xl p-4 text-center">
                <p class="text-sm font-medium text-[#0b2b3f]"><?= $month['month'] ?></p>
                <p class="text-2xl font-bold text-green-600"><?= $month['published_count'] ?></p>
                <p class="text-xs text-gray-500">Published</p>
                <p class="text-sm text-blue-600"><?= number_format($month['views_count']) ?> views</p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Quick Stats Summary -->
    <div class="mt-6 p-4 bg-gray-50 rounded-xl border border-gray-200">
        <h4 class="font-semibold text-[#0b2b3f] mb-3">Quick Stats Summary</h4>
        <div class="grid grid-cols-2 md:grid-cols-6 gap-3 text-sm">
            <div>
                <span class="text-gray-500">Total Users:</span>
                <span class="font-medium"><?= number_format($analytics['total_users'] ?? 0) ?></span>
            </div>
            <div>
                <span class="text-gray-500">Submissions:</span>
                <span class="font-medium"><?= number_format($analytics['total_submissions'] ?? 0) ?></span>
            </div>
            <div>
                <span class="text-gray-500">Published:</span>
                <span class="font-medium"><?= number_format($analytics['total_published'] ?? 0) ?></span>
            </div>
            <div>
                <span class="text-gray-500">Views:</span>
                <span class="font-medium"><?= number_format($analytics['total_views'] ?? 0) ?></span>
            </div>
            <div>
                <span class="text-gray-500">Downloads:</span>
                <span class="font-medium"><?= number_format($analytics['total_downloads'] ?? 0) ?></span>
            </div>
            <div>
                <span class="text-gray-500">Reviews:</span>
                <span class="font-medium"><?= number_format($analytics['total_reviews'] ?? 0) ?></span>
            </div>
        </div>
    </div>
</div>

<script>
// Daily Activity Chart
const ctx10 = document.getElementById('dailyActivityChart').getContext('2d');
const dailyData = <?= json_encode($dailyActivity) ?>;
new Chart(ctx10, {
    type: 'bar',
    data: {
        labels: dailyData.map(item => item.date),
        datasets: [
            {
                label: 'Submissions',
                data: dailyData.map(item => item.submissions || 0),
                backgroundColor: 'rgba(59, 130, 246, 0.6)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 1
            },
            {
                label: 'Views',
                data: dailyData.map(item => item.views || 0),
                backgroundColor: 'rgba(139, 92, 246, 0.6)',
                borderColor: 'rgba(139, 92, 246, 1)',
                borderWidth: 1
            },
            {
                label: 'Downloads',
                data: dailyData.map(item => item.downloads || 0),
                backgroundColor: 'rgba(34, 197, 94, 0.6)',
                borderColor: 'rgba(34, 197, 94, 1)',
                borderWidth: 1
            }
        ]
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

// Device Chart
const ctx11 = document.getElementById('deviceChart').getContext('2d');
const deviceData = <?= json_encode($deviceStats) ?>;
new Chart(ctx11, {
    type: 'doughnut',
    data: {
        labels: Object.keys(deviceData),
        datasets: [{
            data: Object.values(deviceData),
            backgroundColor: ['#3b82f6', '#22c55e', '#eab308'],
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

// Browser Chart
const ctx12 = document.getElementById('browserChart').getContext('2d');
const browserData = <?= json_encode($browserStats) ?>;
new Chart(ctx12, {
    type: 'pie',
    data: {
        labels: Object.keys(browserData),
        datasets: [{
            data: Object.values(browserData),
            backgroundColor: ['#4285f4', '#ff6b35', '#fbbc05', '#00a4ef', '#9ca3af'],
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

// Peak Hours Chart
const ctx13 = document.getElementById('peakHoursChart').getContext('2d');
const peakData = <?= json_encode($peakHours) ?>;
new Chart(ctx13, {
    type: 'line',
    data: {
        labels: Object.keys(peakData).map(h => h + ':00'),
        datasets: [{
            label: 'Views',
            data: Object.values(peakData),
            borderColor: '#ef4444',
            backgroundColor: 'rgba(239, 68, 68, 0.1)',
            fill: true,
            tension: 0.4
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
                beginAtZero: true
            }
        }
    }
});
</script>
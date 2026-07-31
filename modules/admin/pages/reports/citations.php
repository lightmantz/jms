<?php
// modules/admin/pages/reports/citations.php - Citation Reports
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../../includes/functions.php';
    require_once __DIR__ . '/../../../../includes/auth.php';
}

$db = getDB();

// Get citation statistics (using article_views and downloads as proxy metrics)
$stmt = $db->query("
    SELECT 
        COUNT(*) as total_articles,
        SUM(views) as total_views,
        SUM(downloads) as total_downloads,
        AVG(views) as avg_views,
        AVG(downloads) as avg_downloads,
        MAX(views) as max_views,
        MAX(downloads) as max_downloads
    FROM (
        SELECT 
            m.id,
            (SELECT COUNT(*) FROM article_views WHERE manuscript_id = m.id) as views,
            (SELECT COUNT(*) FROM article_downloads WHERE manuscript_id = m.id) as downloads
        FROM manuscripts m
        WHERE m.status = 'published'
    ) as metrics
");
$citationStats = $stmt->fetch();

// Get most viewed articles
$stmt = $db->query("
    SELECT m.id, m.title, m.doi, u.full_name as author_name,
           (SELECT COUNT(*) FROM article_views WHERE manuscript_id = m.id) as views,
           (SELECT COUNT(*) FROM article_downloads WHERE manuscript_id = m.id) as downloads,
           m.publication_date
    FROM manuscripts m
    LEFT JOIN users u ON m.corresponding_author_id = u.id
    WHERE m.status = 'published'
    ORDER BY views DESC
    LIMIT 10
");
$mostViewed = $stmt->fetchAll();

// Get most downloaded articles
$stmt = $db->query("
    SELECT m.id, m.title, m.doi, u.full_name as author_name,
           (SELECT COUNT(*) FROM article_views WHERE manuscript_id = m.id) as views,
           (SELECT COUNT(*) FROM article_downloads WHERE manuscript_id = m.id) as downloads,
           m.publication_date
    FROM manuscripts m
    LEFT JOIN users u ON m.corresponding_author_id = u.id
    WHERE m.status = 'published'
    ORDER BY downloads DESC
    LIMIT 10
");
$mostDownloaded = $stmt->fetchAll();

// Get monthly view trends
$stmt = $db->query("
    SELECT DATE_FORMAT(viewed_at, '%Y-%m') as month,
           COUNT(*) as views,
           COUNT(DISTINCT ip_address) as unique_visitors
    FROM article_views
    WHERE viewed_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(viewed_at, '%Y-%m')
    ORDER BY month ASC
");
$viewTrends = $stmt->fetchAll();

// Get monthly download trends
$stmt = $db->query("
    SELECT DATE_FORMAT(downloaded_at, '%Y-%m') as month,
           COUNT(*) as downloads
    FROM article_downloads
    WHERE downloaded_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(downloaded_at, '%Y-%m')
    ORDER BY month ASC
");
$downloadTrends = $stmt->fetchAll();

// Get citation by category
$stmt = $db->query("
    SELECT c.name as category,
           COUNT(DISTINCT m.id) as article_count,
           SUM((SELECT COUNT(*) FROM article_views WHERE manuscript_id = m.id)) as total_views,
           SUM((SELECT COUNT(*) FROM article_downloads WHERE manuscript_id = m.id)) as total_downloads
    FROM categories c
    JOIN manuscript_keywords mk ON c.id = mk.category_id
    JOIN manuscripts m ON mk.manuscript_id = m.id
    WHERE m.status = 'published'
    GROUP BY c.id
    ORDER BY total_views DESC
    LIMIT 10
");
$categoryStats = $stmt->fetchAll();

// Calculate h-index (simplified)
$stmt = $db->query("
    SELECT 
        m.id,
        (SELECT COUNT(*) FROM article_views WHERE manuscript_id = m.id) as views
    FROM manuscripts m
    WHERE m.status = 'published'
    ORDER BY views DESC
");
$viewData = $stmt->fetchAll();
$hIndex = 0;
foreach ($viewData as $index => $data) {
    if ($data['views'] >= ($index + 1)) {
        $hIndex = $index + 1;
    } else {
        break;
    }
}
?>
<div>
    <!-- Summary Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-green-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-green-700"><?= number_format($citationStats['total_views'] ?? 0) ?></p>
            <p class="text-xs text-green-600">Total Views</p>
        </div>
        <div class="bg-blue-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-blue-700"><?= number_format($citationStats['total_downloads'] ?? 0) ?></p>
            <p class="text-xs text-blue-600">Total Downloads</p>
        </div>
        <div class="bg-purple-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-purple-700"><?= round($citationStats['avg_views'] ?? 0) ?></p>
            <p class="text-xs text-purple-600">Avg Views per Article</p>
        </div>
        <div class="bg-indigo-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-indigo-700"><?= $hIndex ?></p>
            <p class="text-xs text-indigo-600">h-index (Views)</p>
        </div>
    </div>

    <!-- Trends -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div>
            <h4 class="font-semibold text-[#0b2b3f] mb-3">Views Trend (12 Months)</h4>
            <canvas id="viewsTrendChart" height="200"></canvas>
        </div>
        <div>
            <h4 class="font-semibold text-[#0b2b3f] mb-3">Downloads Trend (12 Months)</h4>
            <canvas id="downloadsTrendChart" height="200"></canvas>
        </div>
    </div>

    <!-- Most Viewed Articles -->
    <div class="mt-6">
        <h4 class="font-semibold text-[#0b2b3f] mb-3">Most Viewed Articles</h4>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Article</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Author</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Views</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Downloads</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Published</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mostViewed as $article): ?>
                    <tr class="border-b border-gray-100">
                        <td class="py-2 px-3 text-gray-600">
                            <a href="/jms/?page=article&id=<?= $article['id'] ?>" target="_blank" class="hover:text-indigo-600">
                                <?= htmlspecialchars(substr($article['title'], 0, 50)) ?>...
                            </a>
                        </td>
                        <td class="py-2 px-3 text-gray-600"><?= htmlspecialchars($article['author_name'] ?? 'Unknown') ?></td>
                        <td class="py-2 px-3 text-blue-600 font-medium"><?= number_format($article['views']) ?></td>
                        <td class="py-2 px-3 text-gray-600"><?= number_format($article['downloads']) ?></td>
                        <td class="py-2 px-3 text-gray-600"><?= formatDate($article['publication_date']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Most Downloaded Articles -->
    <div class="mt-6">
        <h4 class="font-semibold text-[#0b2b3f] mb-3">Most Downloaded Articles</h4>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Article</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Author</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Downloads</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Views</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Published</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mostDownloaded as $article): ?>
                    <tr class="border-b border-gray-100">
                        <td class="py-2 px-3 text-gray-600">
                            <a href="/jms/?page=article&id=<?= $article['id'] ?>" target="_blank" class="hover:text-indigo-600">
                                <?= htmlspecialchars(substr($article['title'], 0, 50)) ?>...
                            </a>
                        </td>
                        <td class="py-2 px-3 text-gray-600"><?= htmlspecialchars($article['author_name'] ?? 'Unknown') ?></td>
                        <td class="py-2 px-3 text-green-600 font-medium"><?= number_format($article['downloads']) ?></td>
                        <td class="py-2 px-3 text-gray-600"><?= number_format($article['views']) ?></td>
                        <td class="py-2 px-3 text-gray-600"><?= formatDate($article['publication_date']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Category Stats -->
    <div class="mt-6">
        <h4 class="font-semibold text-[#0b2b3f] mb-3">Category Performance</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($categoryStats as $category): ?>
            <div class="border border-gray-200 rounded-xl p-4">
                <h5 class="font-semibold text-[#0b2b3f]"><?= htmlspecialchars($category['category']) ?></h5>
                <div class="mt-2 space-y-1 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Articles:</span>
                        <span class="font-medium"><?= $category['article_count'] ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Views:</span>
                        <span class="font-medium text-blue-600"><?= number_format($category['total_views']) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Downloads:</span>
                        <span class="font-medium text-green-600"><?= number_format($category['total_downloads']) ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
// Views Trend Chart
const ctx8 = document.getElementById('viewsTrendChart').getContext('2d');
const viewData = <?= json_encode($viewTrends) ?>;
new Chart(ctx8, {
    type: 'line',
    data: {
        labels: viewData.map(item => item.month),
        datasets: [{
            label: 'Views',
            data: viewData.map(item => item.views),
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            fill: true,
            tension: 0.4
        }, {
            label: 'Unique Visitors',
            data: viewData.map(item => item.unique_visitors || 0),
            borderColor: '#8b5cf6',
            backgroundColor: 'rgba(139, 92, 246, 0.1)',
            fill: true,
            tension: 0.4
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

// Downloads Trend Chart
const ctx9 = document.getElementById('downloadsTrendChart').getContext('2d');
const downloadData = <?= json_encode($downloadTrends) ?>;
new Chart(ctx9, {
    type: 'line',
    data: {
        labels: downloadData.map(item => item.month),
        datasets: [{
            label: 'Downloads',
            data: downloadData.map(item => item.downloads),
            borderColor: '#22c55e',
            backgroundColor: 'rgba(34, 197, 94, 0.1)',
            fill: true,
            tension: 0.4
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
</script>
<?php
// modules/admin/pages/reports/reviewers.php - Reviewer Reports
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../../includes/functions.php';
    require_once __DIR__ . '/../../../../includes/auth.php';
}

$db = getDB();

// Get reviewer statistics
$stmt = $db->query("
    SELECT 
        COUNT(DISTINCT reviewer_id) as total_reviewers,
        COUNT(*) as total_reviews,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_reviews,
        SUM(CASE WHEN status = 'invited' THEN 1 ELSE 0 END) as invited_reviews,
        SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted_reviews,
        SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) as declined_reviews,
        AVG(DATEDIFF(completed_date, accepted_date)) as avg_days_to_complete
    FROM reviews
");
$reviewerStats = $stmt->fetch();

// Get reviewer performance
$stmt = $db->query("
    SELECT u.full_name, u.email, u.institution,
           COUNT(r.id) as total_reviews,
           SUM(CASE WHEN r.status = 'completed' THEN 1 ELSE 0 END) as completed_reviews,
           SUM(CASE WHEN r.status = 'invited' THEN 1 ELSE 0 END) as invited_reviews,
           SUM(CASE WHEN r.status = 'accepted' THEN 1 ELSE 0 END) as accepted_reviews,
           SUM(CASE WHEN r.status = 'declined' THEN 1 ELSE 0 END) as declined_reviews,
           AVG(DATEDIFF(r.completed_date, r.accepted_date)) as avg_days_to_complete,
           AVG(DATEDIFF(r.completed_date, r.invitation_date)) as avg_days_from_invite
    FROM users u
    JOIN reviews r ON u.id = r.reviewer_id
    GROUP BY u.id
    ORDER BY completed_reviews DESC
");
$reviewerPerformance = $stmt->fetchAll();

// Get monthly review activity
$stmt = $db->query("
    SELECT DATE_FORMAT(completed_date, '%Y-%m') as month,
           COUNT(*) as completed,
           SUM(CASE WHEN recommendation = 'accept' THEN 1 ELSE 0 END) as accepts,
           SUM(CASE WHEN recommendation = 'reject' THEN 1 ELSE 0 END) as rejects,
           SUM(CASE WHEN recommendation IN ('minor_revision', 'major_revision') THEN 1 ELSE 0 END) as revisions
    FROM reviews
    WHERE status = 'completed' 
    AND completed_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(completed_date, '%Y-%m')
    ORDER BY month ASC
");
$monthlyReviews = $stmt->fetchAll();

// Get recommendation distribution
$stmt = $db->query("
    SELECT recommendation, COUNT(*) as count
    FROM reviews
    WHERE status = 'completed' AND recommendation IS NOT NULL
    GROUP BY recommendation
    ORDER BY count DESC
");
$recommendations = $stmt->fetchAll();

// Get average review time by status
$stmt = $db->query("
    SELECT 
        AVG(CASE WHEN status = 'completed' THEN DATEDIFF(completed_date, accepted_date) END) as avg_completion_days,
        AVG(CASE WHEN status = 'declined' THEN DATEDIFF(accepted_date, invitation_date) END) as avg_decline_days,
        AVG(CASE WHEN status = 'accepted' THEN DATEDIFF(accepted_date, invitation_date) END) as avg_accept_days
    FROM reviews
");
$timeStats = $stmt->fetch();
?>
<div>
    <!-- Summary Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-yellow-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-yellow-700"><?= $reviewerStats['total_reviewers'] ?? 0 ?></p>
            <p class="text-xs text-yellow-600">Total Reviewers</p>
        </div>
        <div class="bg-blue-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-blue-700"><?= $reviewerStats['total_reviews'] ?? 0 ?></p>
            <p class="text-xs text-blue-600">Total Reviews</p>
        </div>
        <div class="bg-green-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-green-700"><?= $reviewerStats['completed_reviews'] ?? 0 ?></p>
            <p class="text-xs text-green-600">Completed Reviews</p>
        </div>
        <div class="bg-purple-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-purple-700"><?= round($reviewerStats['avg_days_to_complete'] ?? 0) ?></p>
            <p class="text-xs text-purple-600">Avg Days to Complete</p>
        </div>
    </div>

    <!-- Review Status -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div>
            <h4 class="font-semibold text-[#0b2b3f] mb-3">Review Status Distribution</h4>
            <canvas id="reviewStatusChart" height="200"></canvas>
        </div>
        <div>
            <h4 class="font-semibold text-[#0b2b3f] mb-3">Recommendation Distribution</h4>
            <canvas id="recommendationChart" height="200"></canvas>
        </div>
    </div>

    <!-- Review Time Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-gray-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-gray-700"><?= round($timeStats['avg_completion_days'] ?? 0) ?></p>
            <p class="text-xs text-gray-600">Avg Days to Complete Review</p>
        </div>
        <div class="bg-green-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-green-700"><?= round($timeStats['avg_accept_days'] ?? 0) ?></p>
            <p class="text-xs text-green-600">Avg Days to Accept Invitation</p>
        </div>
        <div class="bg-red-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-red-700"><?= round($timeStats['avg_decline_days'] ?? 0) ?></p>
            <p class="text-xs text-red-600">Avg Days to Decline</p>
        </div>
    </div>

    <!-- Reviewer Performance -->
    <div class="mt-6">
        <h4 class="font-semibold text-[#0b2b3f] mb-3">Reviewer Performance</h4>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Reviewer</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Institution</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Total</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Completed</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Invited</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Accepted</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Declined</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Avg Days</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reviewerPerformance as $reviewer): ?>
                    <tr class="border-b border-gray-100">
                        <td class="py-2 px-3 text-gray-600 font-medium"><?= htmlspecialchars($reviewer['full_name']) ?></td>
                        <td class="py-2 px-3 text-gray-600"><?= htmlspecialchars($reviewer['institution'] ?? '-') ?></td>
                        <td class="py-2 px-3 text-gray-600"><?= $reviewer['total_reviews'] ?></td>
                        <td class="py-2 px-3 text-green-600 font-medium"><?= $reviewer['completed_reviews'] ?></td>
                        <td class="py-2 px-3 text-gray-600"><?= $reviewer['invited_reviews'] ?></td>
                        <td class="py-2 px-3 text-blue-600"><?= $reviewer['accepted_reviews'] ?></td>
                        <td class="py-2 px-3 text-red-600"><?= $reviewer['declined_reviews'] ?></td>
                        <td class="py-2 px-3 text-gray-600"><?= round($reviewer['avg_days_to_complete'] ?? 0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Review Status Chart
const ctx6 = document.getElementById('reviewStatusChart').getContext('2d');
const reviewStatusData = {
    labels: ['Completed', 'Invited', 'Accepted', 'Declined'],
    values: [
        <?= $reviewerStats['completed_reviews'] ?? 0 ?>,
        <?= $reviewerStats['invited_reviews'] ?? 0 ?>,
        <?= $reviewerStats['accepted_reviews'] ?? 0 ?>,
        <?= $reviewerStats['declined_reviews'] ?? 0 ?>
    ]
};
new Chart(ctx6, {
    type: 'doughnut',
    data: {
        labels: reviewStatusData.labels,
        datasets: [{
            data: reviewStatusData.values,
            backgroundColor: ['#22c55e', '#3b82f6', '#eab308', '#ef4444'],
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

// Recommendation Chart
const ctx7 = document.getElementById('recommendationChart').getContext('2d');
const recommendationData = <?= json_encode($recommendations) ?>;
const recColors = {
    'accept': '#22c55e',
    'reject': '#ef4444',
    'minor_revision': '#3b82f6',
    'major_revision': '#f97316',
    'revise_resubmit': '#8b5cf6'
};
new Chart(ctx7, {
    type: 'pie',
    data: {
        labels: recommendationData.map(item => item.recommendation.replace('_', ' ').toUpperCase()),
        datasets: [{
            data: recommendationData.map(item => item.count),
            backgroundColor: recommendationData.map(item => recColors[item.recommendation] || '#9ca3af'),
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
</script>
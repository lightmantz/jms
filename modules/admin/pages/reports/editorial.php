<?php
// modules/admin/pages/reports/editorial.php - Editorial Reports
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../../includes/functions.php';
    require_once __DIR__ . '/../../../../includes/auth.php';
}

$db = getDB();

// Get editorial statistics
$stmt = $db->query("
    SELECT 
        COUNT(DISTINCT editor_assigned_id) as total_editors,
        COUNT(*) as total_assignments,
        SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as completed_assignments,
        AVG(DATEDIFF(accepted_at, submitted_at)) as avg_days_to_decision,
        AVG(DATEDIFF(published_at, accepted_at)) as avg_days_to_publish
    FROM manuscripts
    WHERE editor_assigned_id IS NOT NULL
");
$editorialStats = $stmt->fetch();

// Get editor performance
$stmt = $db->query("
    SELECT u.full_name, u.email,
           COUNT(m.id) as assigned_count,
           SUM(CASE WHEN m.status = 'published' THEN 1 ELSE 0 END) as published_count,
           SUM(CASE WHEN m.status = 'accepted' THEN 1 ELSE 0 END) as accepted_count,
           AVG(DATEDIFF(m.accepted_at, m.submitted_at)) as avg_days,
           AVG(DATEDIFF(m.published_at, m.accepted_at)) as avg_publish_days
    FROM users u
    JOIN manuscripts m ON u.id = m.editor_assigned_id
    GROUP BY u.id
    ORDER BY assigned_count DESC
");
$editorPerformance = $stmt->fetchAll();

// Get monthly editorial activity
$stmt = $db->query("
    SELECT DATE_FORMAT(submission_date, '%Y-%m') as month,
           COUNT(*) as submissions,
           SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted,
           SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
           SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published
    FROM manuscripts
    WHERE submission_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(submission_date, '%Y-%m')
    ORDER BY month ASC
");
$monthlyActivity = $stmt->fetchAll();

// Get editorial board stats
$stmt = $db->query("
    SELECT COUNT(*) as total,
           SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
           COUNT(DISTINCT affiliation) as institutions
    FROM editorial_board
");
$boardStats = $stmt->fetch();

// Get decision distribution
$stmt = $db->query("
    SELECT 
        COUNT(*) as total_decisions,
        SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN status = 'revision_required' THEN 1 ELSE 0 END) as revisions
    FROM manuscripts
    WHERE status IN ('accepted', 'rejected', 'revision_required')
");
$decisionStats = $stmt->fetch();

// Get average review count per manuscript
$stmt = $db->query("
    SELECT 
        AVG(review_count) as avg_reviews,
        MAX(review_count) as max_reviews,
        MIN(review_count) as min_reviews
    FROM (
        SELECT manuscript_id, COUNT(*) as review_count
        FROM reviews
        GROUP BY manuscript_id
    ) as review_counts
");
$reviewStats = $stmt->fetch();
?>
<div>
    <!-- Summary Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-blue-700"><?= $editorialStats['total_editors'] ?? 0 ?></p>
            <p class="text-xs text-blue-600">Total Editors</p>
        </div>
        <div class="bg-purple-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-purple-700"><?= $editorialStats['total_assignments'] ?? 0 ?></p>
            <p class="text-xs text-purple-600">Total Assignments</p>
        </div>
        <div class="bg-green-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-green-700"><?= round($editorialStats['avg_days_to_decision'] ?? 0) ?></p>
            <p class="text-xs text-green-600">Avg Days to Decision</p>
        </div>
        <div class="bg-orange-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-orange-700"><?= round($editorialStats['avg_days_to_publish'] ?? 0) ?></p>
            <p class="text-xs text-orange-600">Avg Days to Publish</p>
        </div>
    </div>

    <!-- Editorial Board Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-gray-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-gray-700"><?= $boardStats['total'] ?? 0 ?></p>
            <p class="text-xs text-gray-600">Board Members</p>
        </div>
        <div class="bg-green-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-green-700"><?= $boardStats['active'] ?? 0 ?></p>
            <p class="text-xs text-green-600">Active Members</p>
        </div>
        <div class="bg-indigo-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-indigo-700"><?= $boardStats['institutions'] ?? 0 ?></p>
            <p class="text-xs text-indigo-600">Institutions Represented</p>
        </div>
    </div>

    <!-- Decision Distribution -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div>
            <h4 class="font-semibold text-[#0b2b3f] mb-3">Decision Distribution</h4>
            <canvas id="decisionChart" height="200"></canvas>
        </div>
        <div>
            <h4 class="font-semibold text-[#0b2b3f] mb-3">Review Statistics</h4>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">Average Reviews per Manuscript</span>
                    <span class="text-lg font-bold text-[#0b2b3f]"><?= round($reviewStats['avg_reviews'] ?? 0, 1) ?></span>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">Maximum Reviews per Manuscript</span>
                    <span class="text-lg font-bold text-[#0b2b3f]"><?= $reviewStats['max_reviews'] ?? 0 ?></span>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">Minimum Reviews per Manuscript</span>
                    <span class="text-lg font-bold text-[#0b2b3f]"><?= $reviewStats['min_reviews'] ?? 0 ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Editor Performance -->
    <div class="mt-6">
        <h4 class="font-semibold text-[#0b2b3f] mb-3">Editor Performance</h4>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Editor</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Assigned</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Accepted</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Published</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Avg Days to Decision</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Avg Days to Publish</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($editorPerformance as $editor): ?>
                    <tr class="border-b border-gray-100">
                        <td class="py-2 px-3 text-gray-600 font-medium"><?= htmlspecialchars($editor['full_name']) ?></td>
                        <td class="py-2 px-3 text-gray-600"><?= $editor['assigned_count'] ?></td>
                        <td class="py-2 px-3 text-gray-600"><?= $editor['accepted_count'] ?? 0 ?></td>
                        <td class="py-2 px-3 text-gray-600"><?= $editor['published_count'] ?? 0 ?></td>
                        <td class="py-2 px-3 text-gray-600"><?= round($editor['avg_days'] ?? 0) ?> days</td>
                        <td class="py-2 px-3 text-gray-600"><?= round($editor['avg_publish_days'] ?? 0) ?> days</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Decision Chart
const ctx5 = document.getElementById('decisionChart').getContext('2d');
const decisionData = <?= json_encode($decisionStats) ?>;
new Chart(ctx5, {
    type: 'doughnut',
    data: {
        labels: ['Accepted', 'Rejected', 'Revision Required'],
        datasets: [{
            data: [decisionData.accepted || 0, decisionData.rejected || 0, decisionData.revisions || 0],
            backgroundColor: ['#22c55e', '#ef4444', '#f97316'],
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
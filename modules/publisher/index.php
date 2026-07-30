<?php
// modules/publisher/index.php - Publishers Module
require_once __DIR__ . '/../../includes/init.php';

// Require publisher role
$currentUser = requirePublisher();

$db = getDB();
$action = isset($_GET['action']) ? $_GET['action'] : 'dashboard';
$subaction = isset($_GET['subaction']) ? $_GET['subaction'] : '';

// Get volumes
$volumes = getVolumes();

// Get issues with volume information
$issues = [];
$volumesWithIssues = [];
foreach ($volumes as $volume) {
    $issueList = getIssuesByVolume($volume['id']);
    // Add volume_number to each issue
    foreach ($issueList as &$issue) {
        $issue['volume_number'] = $volume['volume_number'];
        $issue['volume_id'] = $volume['id'];
    }
    $issues = array_merge($issues, $issueList);
    if (!empty($issueList)) {
        $volumesWithIssues[$volume['id']] = $issueList;
    }
}

// Sort issues by publication date (newest first)
usort($issues, function($a, $b) {
    $dateA = strtotime($a['publication_date'] ?? $a['created_at'] ?? '1970-01-01');
    $dateB = strtotime($b['publication_date'] ?? $b['created_at'] ?? '1970-01-01');
    return $dateB - $dateA;
});

// Get published articles count
$stmt = $db->query("SELECT COUNT(*) as count FROM manuscripts WHERE status = 'published'");
$publishedCount = $stmt->fetch()['count'];

// Get articles published this month
$stmt = $db->query("SELECT COUNT(*) as count FROM manuscripts WHERE status = 'published' AND MONTH(publication_date) = MONTH(CURRENT_DATE()) AND YEAR(publication_date) = YEAR(CURRENT_DATE())");
$monthlyCount = $stmt->fetch()['count'] ?? 0;

// Get manuscripts ready for publishing
$stmt = $db->query("
    SELECT m.*, u.full_name as author_name 
    FROM manuscripts m 
    LEFT JOIN users u ON m.corresponding_author_id = u.id 
    WHERE m.status = 'accepted' 
    ORDER BY m.accepted_at DESC 
    LIMIT 5
");
$readyForPublishing = $stmt->fetchAll();

// If no accepted manuscripts, get published ones as fallback
if (empty($readyForPublishing)) {
    $stmt = $db->query("
        SELECT m.*, u.full_name as author_name 
        FROM manuscripts m 
        LEFT JOIN users u ON m.corresponding_author_id = u.id 
        WHERE m.status = 'published' 
        ORDER BY m.publication_date DESC 
        LIMIT 5
    ");
    $readyForPublishing = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publishers - <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f0f2f5; }
        .shadow-card { box-shadow: 0 8px 20px rgba(0,20,40,0.04); }
        .stat-card { transition: all 0.2s ease; }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 12px 30px rgba(0,20,40,0.08); }
        
        .sidebar {
            width: 280px;
            height: calc(100vh - 64px);
            background: #0b2b3f;
            position: fixed;
            left: 0;
            top: 64px;
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 40;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        .sidebar .sidebar-content {
            flex: 1;
            overflow-y: auto;
        }
        .sidebar .sidebar-footer {
            padding: 0.75rem 1.25rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: auto;
        }
        .sidebar .sidebar-footer .nav-item {
            color: rgba(255,255,255,0.7);
            padding: 0.75rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            font-size: 0.875rem;
            border-left: 3px solid transparent;
            border-radius: 0.5rem;
        }
        .sidebar .sidebar-footer .nav-item:hover {
            background: rgba(255,255,255,0.1);
            color: #fff;
        }
        .sidebar .sidebar-footer .nav-item i {
            width: 20px;
            text-align: center;
        }
        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-track { background: #0b2b3f; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 4px; }
        .sidebar { scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.2) #0b2b3f; }
        .sidebar .nav-item {
            color: rgba(255,255,255,0.7);
            padding: 0.75rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            font-size: 0.875rem;
            border-left: 3px solid transparent;
        }
        .sidebar .nav-item:hover { background: rgba(255,255,255,0.1); color: #fff; }
        .sidebar .nav-item.active { background: rgba(255,255,255,0.12); color: #fff; border-left-color: #60a5fa; }
        .sidebar .nav-item i { width: 20px; text-align: center; flex-shrink: 0; }
        .sidebar .nav-item .badge { margin-left: auto; background: rgba(255,255,255,0.15); padding: 0.1rem 0.6rem; border-radius: 9999px; font-size: 0.7rem; flex-shrink: 0; }
        .sidebar .nav-section { padding: 0.75rem 1.25rem 0.25rem; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255,255,255,0.4); font-weight: 600; }
        .sidebar .sub-menu { padding-left: 1.5rem; max-height: 0; overflow: hidden; transition: max-height 0.3s ease; }
        .sidebar .sub-menu.open { max-height: 600px; }
        .sidebar .sub-menu .nav-item { padding: 0.4rem 1.25rem; font-size: 0.8rem; border-left: none; }
        .sidebar .sub-menu .nav-item i { font-size: 0.75rem; width: 18px; }
        .sidebar .sub-menu .nav-item .badge { font-size: 0.65rem; }
        .main-content { margin-left: 280px; padding: 1.5rem; min-height: calc(100vh - 64px); }
        .sidebar-toggle { display: none; position: fixed; left: 1rem; top: 5rem; z-index: 50; background: #0b2b3f; color: white; border: none; padding: 0.5rem 0.75rem; border-radius: 0.5rem; cursor: pointer; }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 35; }

        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); width: 280px; }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 1rem; }
            .sidebar-toggle { display: block; }
            .sidebar-overlay.show { display: block; }
        }

        .fa-chevron-down, .fa-chevron-up { transition: transform 0.3s ease; margin-left: auto; font-size: 0.7rem; }
    </style>
</head>
<body class="antialiased text-gray-700 font-['Inter']">
    <?php include INCLUDES_PATH . 'header.php'; ?>

    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="publisherSidebar">
        <div class="sidebar-content">
            <div class="py-2">
                <div class="nav-section">Main</div>
                <a href="/jms/publisher" class="nav-item <?= $action == 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-th-large"></i> Dashboard
                </a>

                <div class="nav-section">Publishing</div>
                <div class="nav-item" onclick="toggleMenu('publisherMenu')">
                    <i class="fas fa-print"></i> Publishing
                    <i class="fas fa-chevron-down ml-auto text-xs"></i>
                </div>
                <div class="sub-menu" id="publisherMenu">
                    <a href="/jms/publisher?action=create-volume" class="nav-item <?= $action == 'create-volume' ? 'active' : '' ?>">
                        <i class="fas fa-layer-group"></i> Volume Creation
                    </a>
                    <a href="/jms/publisher?action=create-issue" class="nav-item <?= $action == 'create-issue' ? 'active' : '' ?>">
                        <i class="fas fa-folder-open"></i> Issue Creation
                    </a>
                    <a href="/jms/publisher?action=continuous" class="nav-item <?= $action == 'continuous' ? 'active' : '' ?>">
                        <i class="fas fa-infinity"></i> Continuous Publishing
                    </a>
                    <a href="/jms/publisher?action=doi" class="nav-item <?= $action == 'doi' ? 'active' : '' ?>">
                        <i class="fas fa-link"></i> DOI Assignment
                    </a>
                    <a href="/jms/publisher?action=publication-date" class="nav-item <?= $action == 'publication-date' ? 'active' : '' ?>">
                        <i class="fas fa-calendar-check"></i> Publication Date
                    </a>
                    <a href="/jms/publisher?action=featured" class="nav-item <?= $action == 'featured' ? 'active' : '' ?>">
                        <i class="fas fa-star"></i> Featured Articles
                    </a>
                    <a href="/jms/publisher?action=early-access" class="nav-item <?= $action == 'early-access' ? 'active' : '' ?>">
                        <i class="fas fa-rocket"></i> Early Access
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Sidebar Footer - Logout Button -->
        <div class="sidebar-footer">
            <a href="/jms/modules/logout/logout.php" class="nav-item">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <?php if ($action == 'dashboard'): ?>
        <!-- Dashboard Content -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-[#0b2b3f]">Publishers Dashboard</h1>
                <p class="text-gray-500 mt-1">Welcome back, <?= htmlspecialchars($currentUser['full_name'] ?? 'Publisher') ?>!</p>
            </div>
            <div class="flex gap-3">
                <a href="/jms/publisher?action=create-volume" class="bg-[#0b2b3f] text-white px-4 py-2 rounded-lg hover:bg-[#123a4f] transition">
                    <i class="fas fa-plus mr-2"></i> New Volume
                </a>
                <a href="/jms/publisher?action=create-issue" class="bg-white border border-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition">
                    <i class="fas fa-plus-circle mr-2"></i> New Issue
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow-card p-6 stat-card border border-gray-100/70">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Volumes</p>
                        <p class="text-2xl font-bold text-[#0b2b3f]"><?= count($volumes) ?></p>
                    </div>
                    <div class="w-12 h-12 bg-blue-50 rounded-full flex items-center justify-center text-blue-600">
                        <i class="fas fa-layer-group text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-card p-6 stat-card border border-gray-100/70">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Issues</p>
                        <p class="text-2xl font-bold text-[#0b2b3f]"><?= count($issues) ?></p>
                    </div>
                    <div class="w-12 h-12 bg-purple-50 rounded-full flex items-center justify-center text-purple-600">
                        <i class="fas fa-folder-open text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-card p-6 stat-card border border-gray-100/70">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Published Articles</p>
                        <p class="text-2xl font-bold text-[#0b2b3f]"><?= $publishedCount ?></p>
                    </div>
                    <div class="w-12 h-12 bg-green-50 rounded-full flex items-center justify-center text-green-600">
                        <i class="fas fa-file-alt text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-card p-6 stat-card border border-gray-100/70">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">This Month</p>
                        <p class="text-2xl font-bold text-[#0b2b3f]"><?= $monthlyCount ?></p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-50 rounded-full flex items-center justify-center text-yellow-600">
                        <i class="fas fa-calendar-alt text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Ready for Publishing -->
            <div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-[#0b2b3f]">Ready for Publishing</h3>
                    <a href="/jms/publisher?action=continuous" class="text-sm text-indigo-600 hover:underline">View all</a>
                </div>
                <?php if (empty($readyForPublishing)): ?>
                    <p class="text-sm text-gray-500 text-center py-4">No manuscripts ready for publishing.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($readyForPublishing as $manuscript): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-[#0b2b3f]"><?= htmlspecialchars(substr($manuscript['title'], 0, 40)) ?>...</p>
                                <div class="flex items-center gap-2 text-xs text-gray-500 mt-1">
                                    <span>Author: <?= htmlspecialchars($manuscript['author_name'] ?? 'Unknown') ?></span>
                                    <span>· <?= $manuscript['status'] == 'accepted' ? 'Accepted: ' . formatDate($manuscript['accepted_at']) : 'Published: ' . formatDate($manuscript['publication_date']) ?></span>
                                </div>
                            </div>
                            <a href="/jms/publisher?action=doi&id=<?= $manuscript['id'] ?>" class="text-indigo-600 hover:text-indigo-800 text-sm">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Issues -->
            <div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-[#0b2b3f]">Recent Issues</h3>
                    <a href="/jms/publisher?action=create-issue" class="text-sm text-indigo-600 hover:underline">Create Issue</a>
                </div>
                <?php if (empty($issues)): ?>
                    <p class="text-sm text-gray-500 text-center py-4">No issues created yet.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach (array_slice($issues, 0, 5) as $issue): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-[#0b2b3f]">
                                    Volume <?= $issue['volume_number'] ?? 'N/A' ?> - Issue <?= $issue['issue_number'] ?? 'N/A' ?>
                                </p>
                                <div class="flex items-center gap-2 text-xs text-gray-500 mt-1">
                                    <span><?= htmlspecialchars($issue['title'] ?? 'No title') ?></span>
                                    <?php if ($issue['is_current'] ?? false): ?>
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">Current</span>
                                    <?php endif; ?>
                                    <?php if ($issue['publication_date']): ?>
                                        <span>· <?= formatDate($issue['publication_date']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <a href="/jms/publisher?action=publication-date&id=<?= $issue['id'] ?>" class="text-indigo-600 hover:text-indigo-800 text-sm">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php elseif ($action == 'create-volume'): ?>
            <?php include __DIR__ . '/pages/create-volume.php'; ?>
        <?php elseif ($action == 'create-issue'): ?>
            <?php include __DIR__ . '/pages/create-issue.php'; ?>
        <?php elseif ($action == 'continuous'): ?>
            <?php include __DIR__ . '/pages/continuous.php'; ?>
        <?php elseif ($action == 'doi'): ?>
            <?php include __DIR__ . '/pages/doi.php'; ?>
        <?php elseif ($action == 'publication-date'): ?>
            <?php include __DIR__ . '/pages/publication-date.php'; ?>
        <?php elseif ($action == 'featured'): ?>
            <?php include __DIR__ . '/pages/featured.php'; ?>
        <?php elseif ($action == 'early-access'): ?>
            <?php include __DIR__ . '/pages/early-access.php'; ?>
        <?php else: ?>
            <div class="bg-white rounded-xl shadow-card p-8 border border-gray-100/70">
                <h2 class="text-2xl font-bold text-[#0b2b3f] mb-4">Page Under Development</h2>
                <p class="text-gray-500">Content for <?= htmlspecialchars($action) ?> will be displayed here.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
    function toggleMenu(menuId) {
        var menu = document.getElementById(menuId);
        if (!menu) return false;
        menu.classList.toggle('open');
        var parent = menu.previousElementSibling;
        if (parent) {
            var chevron = parent.querySelector('.fa-chevron-down, .fa-chevron-up');
            if (chevron) {
                chevron.classList.toggle('fa-chevron-down');
                chevron.classList.toggle('fa-chevron-up');
            }
        }
        return false;
    }

    function toggleSidebar() {
        var sidebar = document.getElementById('publisherSidebar');
        var overlay = document.getElementById('sidebarOverlay');
        if (sidebar) sidebar.classList.toggle('open');
        if (overlay) overlay.classList.toggle('show');
    }

    document.addEventListener('DOMContentLoaded', function() {
        var toggleBtn = document.getElementById('sidebarToggle');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function(e) {
                e.preventDefault();
                toggleSidebar();
            });
        }
        var overlay = document.getElementById('sidebarOverlay');
        if (overlay) {
            overlay.addEventListener('click', function() { toggleSidebar(); });
        }
    });
    </script>

    <?php include INCLUDES_PATH . 'footer.php'; ?>
</body>
</html>
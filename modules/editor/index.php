<?php
// modules/editor/index.php - Editorial Office Dashboard
require_once __DIR__ . '/../../includes/init.php';

// Require editor role
$currentUser = requireEditor();

$db = getDB();
$action = isset($_GET['action']) ? $_GET['action'] : 'dashboard';
$subaction = isset($_GET['subaction']) ? $_GET['subaction'] : '';

// Get submission queue
$submissions = getManuscriptsForEditor($currentUser['id']);

// Get counts by status
$statusCounts = [
    'submitted' => 0,
    'under_review' => 0,
    'revision_required' => 0,
    'accepted' => 0,
    'rejected' => 0
];

foreach ($submissions as $m) {
    if (isset($statusCounts[$m['status']])) {
        $statusCounts[$m['status']]++;
    }
}

// Get pending reviews
$stmt = $db->prepare("
    SELECT r.*, m.title as manuscript_title, u.full_name as reviewer_name
    FROM reviews r
    JOIN manuscripts m ON r.manuscript_id = m.id
    JOIN users u ON r.reviewer_id = u.id
    WHERE r.status IN ('invited', 'accepted') AND r.editor_id = ?
    ORDER BY r.due_date ASC
");
$stmt->execute([$currentUser['id']]);
$pendingReviews = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editorial Office - <?= SITE_NAME ?></title>
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
    <div class="sidebar" id="editorSidebar">
        <div class="sidebar-content">
            <div class="py-2">
                <div class="nav-section">Main</div>
                <a href="/jms/editor" class="nav-item <?= $action == 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-th-large"></i> Dashboard
                </a>

                <div class="nav-section">Editorial</div>
                <div class="nav-item" onclick="toggleMenu('editorialMenu')">
                    <i class="fas fa-users-cog"></i> Editorial Office
                    <i class="fas fa-chevron-down ml-auto text-xs"></i>
                </div>
                <div class="sub-menu" id="editorialMenu">
                    <a href="/jms/editor?action=submission-queue" class="nav-item <?= $action == 'submission-queue' ? 'active' : '' ?>">
                        <i class="fas fa-inbox"></i> Submission Queue
                        <span class="badge"><?= $statusCounts['submitted'] ?? 0 ?></span>
                    </a>
                    <a href="/jms/editor?action=editor-assignment" class="nav-item <?= $action == 'editor-assignment' ? 'active' : '' ?>">
                        <i class="fas fa-user-plus"></i> Editor Assignment
                    </a>
                    <a href="/jms/editor?action=reviewer-assignment" class="nav-item <?= $action == 'reviewer-assignment' ? 'active' : '' ?>">
                        <i class="fas fa-user-tie"></i> Reviewer Assignment
                        <span class="badge"><?= count($pendingReviews) ?></span>
                    </a>
                    <a href="/jms/editor?action=decision" class="nav-item <?= $action == 'decision' ? 'active' : '' ?>">
                        <i class="fas fa-gavel"></i> Decision Management
                    </a>
                    <a href="/jms/editor?action=notes" class="nav-item <?= $action == 'notes' ? 'active' : '' ?>">
                        <i class="fas fa-sticky-note"></i> Internal Notes
                    </a>
                    <a href="/jms/editor?action=revisions" class="nav-item <?= $action == 'revisions' ? 'active' : '' ?>">
                        <i class="fas fa-edit"></i> Revision Requests
                    </a>
                    <a href="/jms/editor?action=acceptance" class="nav-item <?= $action == 'acceptance' ? 'active' : '' ?>">
                        <i class="fas fa-check-circle"></i> Final Acceptance
                    </a>
                    <a href="/jms/editor?action=scheduling" class="nav-item <?= $action == 'scheduling' ? 'active' : '' ?>">
                        <i class="fas fa-calendar-alt"></i> Scheduling
                    </a>
                    <a href="/jms/editor?action=publishing" class="nav-item <?= $action == 'publishing' ? 'active' : '' ?>">
                        <i class="fas fa-print"></i> Publishing
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
                <h1 class="text-3xl font-bold text-[#0b2b3f]">Editorial Office</h1>
                <p class="text-gray-500 mt-1">Welcome back, <?= htmlspecialchars($currentUser['full_name'] ?? 'Editor') ?>!</p>
            </div>
            <div class="flex gap-3">
                <a href="/jms/editor?action=submission-queue" class="bg-[#0b2b3f] text-white px-4 py-2 rounded-lg hover:bg-[#123a4f] transition">
                    <i class="fas fa-inbox mr-2"></i> Submission Queue
                    <span class="bg-red-500 text-white text-xs rounded-full px-2 py-0.5 ml-2"><?= $statusCounts['submitted'] ?? 0 ?></span>
                </a>
                <a href="/jms/editor?action=reviewer-assignment" class="bg-white border border-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition">
                    <i class="fas fa-user-tie mr-2"></i> Assign Reviewers
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow-card p-6 stat-card border border-gray-100/70">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Total Submissions</p>
                        <p class="text-2xl font-bold text-[#0b2b3f]"><?= count($submissions) ?></p>
                    </div>
                    <div class="w-12 h-12 bg-blue-50 rounded-full flex items-center justify-center text-blue-600">
                        <i class="fas fa-file-alt text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-card p-6 stat-card border border-gray-100/70">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Under Review</p>
                        <p class="text-2xl font-bold text-[#0b2b3f]"><?= $statusCounts['under_review'] ?? 0 ?></p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-50 rounded-full flex items-center justify-center text-yellow-600">
                        <i class="fas fa-spinner text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-card p-6 stat-card border border-gray-100/70">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Pending Reviews</p>
                        <p class="text-2xl font-bold text-[#0b2b3f]"><?= count($pendingReviews) ?></p>
                    </div>
                    <div class="w-12 h-12 bg-red-50 rounded-full flex items-center justify-center text-red-600">
                        <i class="fas fa-clock text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-card p-6 stat-card border border-gray-100/70">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Accepted</p>
                        <p class="text-2xl font-bold text-[#0b2b3f]"><?= $statusCounts['accepted'] ?? 0 ?></p>
                    </div>
                    <div class="w-12 h-12 bg-green-50 rounded-full flex items-center justify-center text-green-600">
                        <i class="fas fa-check-circle text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Submission Queue -->
            <div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-[#0b2b3f]">Submission Queue</h3>
                    <a href="/jms/editor?action=submission-queue" class="text-sm text-indigo-600 hover:underline">View all</a>
                </div>
                <?php if (empty($submissions)): ?>
                    <p class="text-sm text-gray-500 text-center py-4">No submissions in queue.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach (array_slice($submissions, 0, 5) as $submission): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-[#0b2b3f]"><?= htmlspecialchars(substr($submission['title'], 0, 50)) ?>...</p>
                                <div class="flex items-center gap-2 text-xs text-gray-500 mt-1">
                                    <span class="px-2 py-0.5 rounded-full <?= getStatusBadgeClass($submission['status']) ?>">
                                        <?= ucfirst(str_replace('_', ' ', $submission['status'])) ?>
                                    </span>
                                    <span>· <?= timeAgo($submission['submission_date']) ?></span>
                                </div>
                            </div>
                            <a href="/jms/editor?action=decision&id=<?= $submission['id'] ?>" class="text-indigo-600 hover:text-indigo-800 text-sm">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pending Reviews -->
            <div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-[#0b2b3f]">Pending Reviews</h3>
                    <a href="/jms/editor?action=reviewer-assignment" class="text-sm text-indigo-600 hover:underline">View all</a>
                </div>
                <?php if (empty($pendingReviews)): ?>
                    <p class="text-sm text-gray-500 text-center py-4">No pending reviews.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($pendingReviews as $review): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-[#0b2b3f]"><?= htmlspecialchars(substr($review['manuscript_title'], 0, 40)) ?>...</p>
                                <div class="flex items-center gap-2 text-xs text-gray-500 mt-1">
                                    <span>Reviewer: <?= htmlspecialchars($review['reviewer_name']) ?></span>
                                    <?php if ($review['due_date']): ?>
                                        <span>· Due: <?= formatDate($review['due_date']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <a href="/jms/editor?action=reviewer-assignment&id=<?= $review['manuscript_id'] ?>" class="text-indigo-600 hover:text-indigo-800 text-sm">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php elseif ($action == 'submission-queue'): ?>
            <?php include __DIR__ . '/pages/submission-queue.php'; ?>
        <?php elseif ($action == 'editor-assignment'): ?>
            <?php include __DIR__ . '/pages/editor-assignment.php'; ?>
        <?php elseif ($action == 'reviewer-assignment'): ?>
            <?php include __DIR__ . '/pages/reviewer-assignment.php'; ?>
        <?php elseif ($action == 'decision'): ?>
            <?php include __DIR__ . '/pages/decision.php'; ?>
        <?php elseif ($action == 'notes'): ?>
            <?php include __DIR__ . '/pages/notes.php'; ?>
        <?php elseif ($action == 'revisions'): ?>
            <?php include __DIR__ . '/pages/revisions.php'; ?>
        <?php elseif ($action == 'acceptance'): ?>
            <?php include __DIR__ . '/pages/acceptance.php'; ?>
        <?php elseif ($action == 'scheduling'): ?>
            <?php include __DIR__ . '/pages/scheduling.php'; ?>
        <?php elseif ($action == 'publishing'): ?>
            <?php include __DIR__ . '/pages/publishing.php'; ?>
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
        var sidebar = document.getElementById('editorSidebar');
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
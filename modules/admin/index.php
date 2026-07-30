<?php
// modules/admin/index.php - Admin Dashboard with Sidebar

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define the base path
$basePath = dirname(dirname(__DIR__));

// Include required files
require_once $basePath . '/includes/functions.php';
require_once $basePath . '/includes/auth.php';

// Require admin role
$currentUser = requireAdmin();

$db = getDB();

// Get action from URL
$action = isset($_GET['action']) ? $_GET['action'] : 'dashboard';
$subaction = isset($_GET['subaction']) ? $_GET['subaction'] : '';

// Define valid pages for routing
$validPages = [
    'journal-settings', 'volumes', 'issues', 'sections',
    'submissions', 'manuscript', 'assign', 'publish',
    'editors', 'reviewers', 'editorial-board', 'assignments',
    'copyediting', 'proofreading', 'layout', 'doi', 'publication',
    'articles', 'article-view', 'create-article', 'create-submission',
    'users', 'roles',
    'reports',
    'finance',
    'settings',
    'logs',
    'dashboard',
    'pages', 'news', 'policies', 'guidelines'
];

$pageFile = null;
$isSubPage = false;

// Check if we need to load a sub-page
if (in_array($action, $validPages) && $action != 'dashboard') {
    $isSubPage = true;
    
    // Map actions to their actual files
    $actionMap = [
        'journal-settings' => 'journal-settings.php',
        'volumes' => 'volumes.php',
        'issues' => 'issues.php',
        'sections' => 'sections.php',
        'submissions' => 'submissions.php',
        'manuscript' => 'manuscript.php',
        'assign' => 'assign-reviewer.php',
        'publish' => 'publish.php',
        'editors' => 'editors.php',
        'reviewers' => 'reviewers.php',
        'editorial-board' => 'editorial-board.php',
        'assignments' => 'assignments.php',
        'copyediting' => 'copyediting.php',
        'proofreading' => 'proofreading.php',
        'layout' => 'layout.php',
        'doi' => 'doi.php',
        'publication' => 'publication.php',
        'articles' => 'articles.php',
        'article-view' => 'article-view.php',
        'create-article' => 'create-article.php',
        'create-submission' => 'create-submission.php',
        'users' => 'users.php',
        'roles' => 'roles.php',
        'reports' => 'reports.php',
        'finance' => 'finance.php',
        'settings' => 'settings.php',
        'logs' => 'logs.php',
        'pages' => 'pages.php',
        'news' => 'news.php',
        'policies' => 'policies.php',
        'guidelines' => 'guidelines.php'
    ];
    
    // Special handling for subactions
    $possibleFile = null;
    
    if ($action == 'submissions' && in_array($subaction, ['new', 'under_review', 'revisions', 'accepted', 'rejected', 'published', 'all'])) {
        $possibleFile = __DIR__ . '/pages/submissions.php';
    } elseif ($action == 'articles' && in_array($subaction, ['published', 'inpress', 'archives'])) {
        $possibleFile = __DIR__ . '/pages/articles.php';
    } elseif ($action == 'reports' && in_array($subaction, ['submissions', 'editorial', 'reviewers', 'citations', 'analytics', 'dashboard'])) {
        $possibleFile = __DIR__ . '/pages/reports.php';
    } elseif ($action == 'finance' && in_array($subaction, ['apc', 'payments', 'invoices', 'waivers', 'dashboard'])) {
        $possibleFile = __DIR__ . '/pages/finance.php';
    } elseif ($action == 'settings' && in_array($subaction, ['journal', 'email', 'workflow', 'integrations', 'security', 'backups'])) {
        $possibleFile = __DIR__ . '/pages/settings.php';
    } elseif ($action == 'logs' && in_array($subaction, ['activity', 'audit', 'system'])) {
        $possibleFile = __DIR__ . '/pages/logs.php';
    } elseif ($action == 'users' && in_array($subaction, ['authors', 'editors', 'reviewers', 'staff', 'all'])) {
        $possibleFile = __DIR__ . '/pages/users.php';
    } elseif (isset($actionMap[$action])) {
        $possibleFile = __DIR__ . '/pages/' . $actionMap[$action];
    }
    
    // Check if the file exists
    if ($possibleFile && file_exists($possibleFile)) {
        $pageFile = $possibleFile;
    } else {
        $defaultFile = __DIR__ . '/pages/' . $action . '.php';
        if (file_exists($defaultFile)) {
            $pageFile = $defaultFile;
        } else {
            $isSubPage = false;
        }
    }
}

// Get statistics with SAFE error handling
$stats = [
    'total_users' => 0,
    'total_manuscripts' => 0,
    'total_reviews' => 0,
    'submissions_this_month' => 0,
    'total_views' => 0,
    'total_downloads' => 0,
    'users_by_role' => [],
    'manuscripts_by_status' => [],
    'monthly_submissions' => []
];

try {
    $stmt = $db->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        $stmt = $db->query("SELECT COUNT(*) as count FROM users");
        $stats['total_users'] = $stmt->fetch()['count'] ?? 0;
        
        $stmt = $db->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
        $stats['users_by_role'] = $stmt->fetchAll();
    }
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
}

try {
    $stmt = $db->query("SHOW TABLES LIKE 'manuscripts'");
    if ($stmt->rowCount() > 0) {
        $stmt = $db->query("SELECT COUNT(*) as count FROM manuscripts");
        $stats['total_manuscripts'] = $stmt->fetch()['count'] ?? 0;
        
        $stmt = $db->query("SELECT status, COUNT(*) as count FROM manuscripts GROUP BY status");
        $stats['manuscripts_by_status'] = $stmt->fetchAll();
        
        $stmt = $db->query("SELECT COUNT(*) as count FROM manuscripts WHERE MONTH(submission_date) = MONTH(CURRENT_DATE()) AND YEAR(submission_date) = YEAR(CURRENT_DATE())");
        $stats['submissions_this_month'] = $stmt->fetch()['count'] ?? 0;
    }
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
}

$recentSubmissions = [];
$recentUsers = [];

try {
    $stmt = $db->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
    $recentUsers = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error getting users: " . $e->getMessage());
}

try {
    $stmt = $db->query("
        SELECT m.*, u.full_name as author_name 
        FROM manuscripts m 
        LEFT JOIN users u ON m.corresponding_author_id = u.id 
        ORDER BY m.submission_date DESC 
        LIMIT 5
    ");
    $recentSubmissions = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error getting submissions: " . $e->getMessage());
}

$statusCounts = [];
if (isset($stats['manuscripts_by_status']) && is_array($stats['manuscripts_by_status'])) {
    foreach ($stats['manuscripts_by_status'] as $status) {
        if (isset($status['status']) && isset($status['count'])) {
            $statusCounts[$status['status']] = $status['count'];
        }
    }
}

$userCounts = [];
if (isset($stats['users_by_role']) && is_array($stats['users_by_role'])) {
    foreach ($stats['users_by_role'] as $role) {
        if (isset($role['role']) && isset($role['count'])) {
            $userCounts[$role['role']] = $role['count'];
        }
    }
}
$staffCount = ($userCounts['admin'] ?? 0) + ($userCounts['staff'] ?? 0);

$articleStats = ['published' => 0, 'inpress' => 0, 'archives' => 0];
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM manuscripts WHERE status = 'published'");
    $articleStats['published'] = $stmt->fetch()['count'] ?? 0;
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM manuscripts WHERE status = 'accepted' AND issue_id IS NOT NULL");
    $articleStats['inpress'] = $stmt->fetch()['count'] ?? 0;
    
    $articleStats['archives'] = $articleStats['published'];
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
}

$acceptanceRate = safeGetSetting('acceptance_rate', '34');
$avgTurnaround = safeGetSetting('avg_turnaround', '28');

function safeGetSetting($key, $default = null) {
    try {
        if (function_exists('getSetting')) {
            return getSetting($key) ?? $default;
        }
        return $default;
    } catch (Exception $e) {
        return $default;
    }
}


$roleColors = [
    'admin' => 'bg-red-100 text-red-700',
    'editor' => 'bg-blue-100 text-blue-700',
    'reviewer' => 'bg-yellow-100 text-yellow-700',
    'author' => 'bg-green-100 text-green-700',
    'reader' => 'bg-gray-100 text-gray-700'
];
?>
<!-- REST OF YOUR ADMIN DASHBOARD HTML... (keep your existing HTML) -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?= defined('SITE_NAME') ? SITE_NAME : 'Journal Management' ?></title>
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
    <?php 
    $headerPath = $basePath . '/includes/header.php';
    if (file_exists($headerPath)) {
        include $headerPath;
    }
    ?>

    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="adminSidebar">
        <div class="sidebar-content">
            <div class="py-2">
                <!-- Dashboard -->
                <div class="nav-section">Main</div>
                <a href="/jms/admin" class="nav-item <?= $action == 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-th-large"></i> Dashboard
                </a>

                <!-- Journal -->
                <div class="nav-section">Journal</div>
                <div class="nav-item" onclick="toggleMenu('journalMenu')">
                    <i class="fas fa-book"></i> Journal
                    <i class="fas fa-chevron-down ml-auto text-xs"></i>
                </div>
                <div class="sub-menu" id="journalMenu">
                    <a href="/jms/admin?action=journal-settings" class="nav-item <?= $action == 'journal-settings' ? 'active' : '' ?>">
                        <i class="fas fa-cog"></i> Journals
                    </a>
                    <a href="/jms/admin?action=volumes" class="nav-item <?= $action == 'volumes' ? 'active' : '' ?>">
                        <i class="fas fa-layer-group"></i> Volumes
                    </a>
                    <a href="/jms/admin?action=issues" class="nav-item <?= $action == 'issues' ? 'active' : '' ?>">
                        <i class="fas fa-folder-open"></i> Issues
                    </a>
                    <a href="/jms/admin?action=sections" class="nav-item <?= $action == 'sections' ? 'active' : '' ?>">
                        <i class="fas fa-tags"></i> Sections
                    </a>
                </div>

                <!-- Submissions -->
                <div class="nav-section">Submissions</div>
                <div class="nav-item" onclick="toggleMenu('submissionsMenu')">
                    <i class="fas fa-paper-plane"></i> Submissions
                    <i class="fas fa-chevron-down ml-auto text-xs"></i>
                </div>
                <div class="sub-menu" id="submissionsMenu">
                    <a href="/jms/admin?action=create-submission" class="nav-item <?= $action == 'create-submission' ? 'active' : '' ?>">
                        <i class="fas fa-plus-circle"></i> Create Submission
                    </a>
                    <a href="/jms/admin?action=submissions&subaction=new" class="nav-item <?= ($action == 'submissions' && $subaction == 'new') ? 'active' : '' ?>">
                        <i class="fas fa-inbox"></i> New Submissions
                        <span class="badge"><?= ($statusCounts['submitted'] ?? 0) + ($statusCounts['draft'] ?? 0) ?></span>
                    </a>
                    <a href="/jms/admin?action=submissions&subaction=under_review" class="nav-item <?= ($action == 'submissions' && $subaction == 'under_review') ? 'active' : '' ?>">
                        <i class="fas fa-spinner"></i> Under Review
                        <span class="badge"><?= $statusCounts['under_review'] ?? 0 ?></span>
                    </a>
                    <a href="/jms/admin?action=submissions&subaction=revisions" class="nav-item <?= ($action == 'submissions' && $subaction == 'revisions') ? 'active' : '' ?>">
                        <i class="fas fa-edit"></i> Revisions
                        <span class="badge"><?= $statusCounts['revision_required'] ?? 0 ?></span>
                    </a>
                    <a href="/jms/admin?action=submissions&subaction=accepted" class="nav-item <?= ($action == 'submissions' && $subaction == 'accepted') ? 'active' : '' ?>">
                        <i class="fas fa-check-circle"></i> Accepted
                        <span class="badge"><?= $statusCounts['accepted'] ?? 0 ?></span>
                    </a>
                    <a href="/jms/admin?action=submissions&subaction=rejected" class="nav-item <?= ($action == 'submissions' && $subaction == 'rejected') ? 'active' : '' ?>">
                        <i class="fas fa-times-circle"></i> Rejected
                        <span class="badge"><?= $statusCounts['rejected'] ?? 0 ?></span>
                    </a>
                    <a href="/jms/admin?action=submissions&subaction=published" class="nav-item <?= ($action == 'submissions' && $subaction == 'published') ? 'active' : '' ?>">
                        <i class="fas fa-check-double"></i> Published
                        <span class="badge"><?= $statusCounts['published'] ?? 0 ?></span>
                    </a>
                </div>

                <!-- Editorial -->
                <div class="nav-section">Editorial</div>
                <div class="nav-item" onclick="toggleMenu('editorialMenu')">
                    <i class="fas fa-users-cog"></i> Editorial
                    <i class="fas fa-chevron-down ml-auto text-xs"></i>
                </div>
                <div class="sub-menu" id="editorialMenu">
                    <a href="/jms/admin?action=editors" class="nav-item <?= $action == 'editors' ? 'active' : '' ?>">
                        <i class="fas fa-user-edit"></i> Editors
                    </a>
                    <a href="/jms/admin?action=reviewers" class="nav-item <?= $action == 'reviewers' ? 'active' : '' ?>">
                        <i class="fas fa-user-tie"></i> Reviewers
                    </a>
                    <a href="/jms/admin?action=editorial-board" class="nav-item <?= $action == 'editorial-board' ? 'active' : '' ?>">
                        <i class="fas fa-users"></i> Editorial Board
                    </a>
                    <a href="/jms/admin?action=assignments" class="nav-item <?= $action == 'assignments' ? 'active' : '' ?>">
                        <i class="fas fa-tasks"></i> Assignments
                    </a>
                </div>

                <!-- Publishing -->
                <div class="nav-section">Publishing</div>
                <div class="nav-item" onclick="toggleMenu('publishingMenu')">
                    <i class="fas fa-print"></i> Publishing
                    <i class="fas fa-chevron-down ml-auto text-xs"></i>
                </div>
                <div class="sub-menu" id="publishingMenu">
                    <a href="/jms/admin?action=copyediting" class="nav-item <?= $action == 'copyediting' ? 'active' : '' ?>">
                        <i class="fas fa-pen-fancy"></i> Copyediting
                    </a>
                    <a href="/jms/admin?action=proofreading" class="nav-item <?= $action == 'proofreading' ? 'active' : '' ?>">
                        <i class="fas fa-check-double"></i> Proofreading
                    </a>
                    <a href="/jms/admin?action=layout" class="nav-item <?= $action == 'layout' ? 'active' : '' ?>">
                        <i class="fas fa-layer-group"></i> Layout
                    </a>
                    <a href="/jms/admin?action=doi" class="nav-item <?= $action == 'doi' ? 'active' : '' ?>">
                        <i class="fas fa-link"></i> DOI
                    </a>
                    <a href="/jms/admin?action=publication" class="nav-item <?= $action == 'publication' ? 'active' : '' ?>">
                        <i class="fas fa-file-pdf"></i> Publication
                    </a>
                </div>

                <!-- Articles -->
                <div class="nav-section">Articles</div>
                <div class="nav-item" onclick="toggleMenu('articlesMenu')">
                    <i class="fas fa-file-alt"></i> Articles
                    <i class="fas fa-chevron-down ml-auto text-xs"></i>
                </div>
                <div class="sub-menu" id="articlesMenu">
                    <a href="/jms/admin?action=create-article" class="nav-item <?= $action == 'create-article' ? 'active' : '' ?>">
                        <i class="fas fa-plus-circle"></i> Create Article
                    </a>
                    <a href="/jms/admin?action=articles&subaction=published" class="nav-item <?= ($action == 'articles' && $subaction == 'published') ? 'active' : '' ?>">
                        <i class="fas fa-check-circle"></i> Published
                        <span class="badge"><?= $articleStats['published'] ?? 0 ?></span>
                    </a>
                    <a href="/jms/admin?action=articles&subaction=inpress" class="nav-item <?= ($action == 'articles' && $subaction == 'inpress') ? 'active' : '' ?>">
                        <i class="fas fa-clock"></i> In Press
                        <span class="badge"><?= $articleStats['inpress'] ?? 0 ?></span>
                    </a>
                    <a href="/jms/admin?action=articles&subaction=archives" class="nav-item <?= ($action == 'articles' && $subaction == 'archives') ? 'active' : '' ?>">
                        <i class="fas fa-archive"></i> Archives
                        <span class="badge"><?= $articleStats['archives'] ?? 0 ?></span>
                    </a>
                </div>

                <!-- Users -->
                <div class="nav-section">Users</div>
                <div class="nav-item" onclick="toggleMenu('usersMenu')">
                    <i class="fas fa-users"></i> Users
                    <i class="fas fa-chevron-down ml-auto text-xs"></i>
                </div>
                <div class="sub-menu" id="usersMenu">
                    <a href="/jms/admin?action=users&subaction=authors" class="nav-item <?= ($action == 'users' && $subaction == 'authors') ? 'active' : '' ?>">
                        <i class="fas fa-user-edit"></i> Authors
                        <span class="badge"><?= $userCounts['author'] ?? 0 ?></span>
                    </a>
                    <a href="/jms/admin?action=users&subaction=editors" class="nav-item <?= ($action == 'users' && $subaction == 'editors') ? 'active' : '' ?>">
                        <i class="fas fa-user-tie"></i> Editors
                        <span class="badge"><?= $userCounts['editor'] ?? 0 ?></span>
                    </a>
                    <a href="/jms/admin?action=users&subaction=reviewers" class="nav-item <?= ($action == 'users' && $subaction == 'reviewers') ? 'active' : '' ?>">
                        <i class="fas fa-user-graduate"></i> Reviewers
                        <span class="badge"><?= $userCounts['reviewer'] ?? 0 ?></span>
                    </a>
                    <a href="/jms/admin?action=users&subaction=staff" class="nav-item <?= ($action == 'users' && $subaction == 'staff') ? 'active' : '' ?>">
                        <i class="fas fa-user-cog"></i> Staff
                        <span class="badge"><?= $staffCount ?? 0 ?></span>
                    </a>
                    <a href="/jms/admin?action=roles" class="nav-item <?= $action == 'roles' ? 'active' : '' ?>">
                        <i class="fas fa-lock"></i> Roles & Permissions
                    </a>
                </div>

                <!-- Content -->
                <div class="nav-section">Content</div>
                <div class="nav-item" onclick="toggleMenu('contentMenu')">
                    <i class="fas fa-newspaper"></i> Content
                    <i class="fas fa-chevron-down ml-auto text-xs"></i>
                </div>
                <div class="sub-menu" id="contentMenu">
                    <a href="/jms/admin?action=pages" class="nav-item <?= $action == 'pages' ? 'active' : '' ?>">
                        <i class="fas fa-file-alt"></i> Pages
                    </a>
                    <a href="/jms/admin?action=news" class="nav-item <?= $action == 'news' ? 'active' : '' ?>">
                        <i class="fas fa-newspaper"></i> News
                    </a>
                    <a href="/jms/admin?action=policies" class="nav-item <?= $action == 'policies' ? 'active' : '' ?>">
                        <i class="fas fa-gavel"></i> Policies
                    </a>
                    <a href="/jms/admin?action=guidelines" class="nav-item <?= $action == 'guidelines' ? 'active' : '' ?>">
                        <i class="fas fa-list-check"></i> Guidelines
                    </a>
                </div>

                <!-- Reports -->
                <div class="nav-section">Reports</div>
                <div class="nav-item" onclick="toggleMenu('reportsMenu')">
                    <i class="fas fa-chart-bar"></i> Reports
                    <i class="fas fa-chevron-down ml-auto text-xs"></i>
                </div>
                <div class="sub-menu" id="reportsMenu">
                    <a href="/jms/admin?action=reports&subaction=dashboard" class="nav-item <?= ($action == 'reports' && $subaction == 'dashboard') ? 'active' : '' ?>">
                        <i class="fas fa-chart-pie"></i> Dashboard
                    </a>
                    <a href="/jms/admin?action=reports&subaction=submissions" class="nav-item <?= ($action == 'reports' && $subaction == 'submissions') ? 'active' : '' ?>">
                        <i class="fas fa-file-alt"></i> Submission Reports
                    </a>
                    <a href="/jms/admin?action=reports&subaction=editorial" class="nav-item <?= ($action == 'reports' && $subaction == 'editorial') ? 'active' : '' ?>">
                        <i class="fas fa-users-cog"></i> Editorial Reports
                    </a>
                    <a href="/jms/admin?action=reports&subaction=reviewers" class="nav-item <?= ($action == 'reports' && $subaction == 'reviewers') ? 'active' : '' ?>">
                        <i class="fas fa-user-graduate"></i> Reviewer Reports
                    </a>
                    <a href="/jms/admin?action=reports&subaction=citations" class="nav-item <?= ($action == 'reports' && $subaction == 'citations') ? 'active' : '' ?>">
                        <i class="fas fa-quote-right"></i> Citation Reports
                    </a>
                    <a href="/jms/admin?action=reports&subaction=analytics" class="nav-item <?= ($action == 'reports' && $subaction == 'analytics') ? 'active' : '' ?>">
                        <i class="fas fa-chart-line"></i> Analytics
                    </a>
                </div>

                <!-- Finance -->
                <div class="nav-section">Finance</div>
                <div class="nav-item" onclick="toggleMenu('financeMenu')">
                    <i class="fas fa-coins"></i> Finance
                    <i class="fas fa-chevron-down ml-auto text-xs"></i>
                </div>
                <div class="sub-menu" id="financeMenu">
                    <a href="/jms/admin?action=finance&subaction=dashboard" class="nav-item <?= ($action == 'finance' && $subaction == 'dashboard') ? 'active' : '' ?>">
                        <i class="fas fa-chart-pie"></i> Dashboard
                    </a>
                    <a href="/jms/admin?action=finance&subaction=apc" class="nav-item <?= ($action == 'finance' && $subaction == 'apc') ? 'active' : '' ?>">
                        <i class="fas fa-coins"></i> APC
                    </a>
                    <a href="/jms/admin?action=finance&subaction=payments" class="nav-item <?= ($action == 'finance' && $subaction == 'payments') ? 'active' : '' ?>">
                        <i class="fas fa-credit-card"></i> Payments
                    </a>
                    <a href="/jms/admin?action=finance&subaction=invoices" class="nav-item <?= ($action == 'finance' && $subaction == 'invoices') ? 'active' : '' ?>">
                        <i class="fas fa-file-invoice"></i> Invoices
                    </a>
                    <a href="/jms/admin?action=finance&subaction=waivers" class="nav-item <?= ($action == 'finance' && $subaction == 'waivers') ? 'active' : '' ?>">
                        <i class="fas fa-hand-holding-heart"></i> Waivers
                    </a>
                </div>

                <!-- Settings -->
                <div class="nav-section">Settings</div>
                <div class="nav-item" onclick="toggleMenu('settingsMenu')">
                    <i class="fas fa-cog"></i> Settings
                    <i class="fas fa-chevron-down ml-auto text-xs"></i>
                </div>
                <div class="sub-menu" id="settingsMenu">
                    <a href="/jms/admin?action=settings&subaction=journal" class="nav-item <?= ($action == 'settings' && $subaction == 'journal') ? 'active' : '' ?>">
                        <i class="fas fa-cog"></i> Journal Settings
                    </a>
                    <a href="/jms/admin?action=settings&subaction=email" class="nav-item <?= ($action == 'settings' && $subaction == 'email') ? 'active' : '' ?>">
                        <i class="fas fa-envelope"></i> Email
                    </a>
                    <a href="/jms/admin?action=settings&subaction=workflow" class="nav-item <?= ($action == 'settings' && $subaction == 'workflow') ? 'active' : '' ?>">
                        <i class="fas fa-project-diagram"></i> Workflow
                    </a>
                    <a href="/jms/admin?action=settings&subaction=integrations" class="nav-item <?= ($action == 'settings' && $subaction == 'integrations') ? 'active' : '' ?>">
                        <i class="fas fa-plug"></i> Integrations
                    </a>
                    <a href="/jms/admin?action=settings&subaction=security" class="nav-item <?= ($action == 'settings' && $subaction == 'security') ? 'active' : '' ?>">
                        <i class="fas fa-shield-alt"></i> Security
                    </a>
                    <a href="/jms/admin?action=settings&subaction=backups" class="nav-item <?= ($action == 'settings' && $subaction == 'backups') ? 'active' : '' ?>">
                        <i class="fas fa-database"></i> Backups
                    </a>
                </div>

                <!-- Logs -->
                <div class="nav-section">System</div>
                <div class="nav-item" onclick="toggleMenu('logsMenu')">
                    <i class="fas fa-history"></i> Logs
                    <i class="fas fa-chevron-down ml-auto text-xs"></i>
                </div>
                <div class="sub-menu" id="logsMenu">
                    <a href="/jms/admin?action=logs&subaction=activity" class="nav-item <?= ($action == 'logs' && $subaction == 'activity') ? 'active' : '' ?>">
                        <i class="fas fa-list"></i> Activity Logs
                    </a>
                    <a href="/jms/admin?action=logs&subaction=audit" class="nav-item <?= ($action == 'logs' && $subaction == 'audit') ? 'active' : '' ?>">
                        <i class="fas fa-clipboard-list"></i> Audit Trail
                    </a>
                    <a href="/jms/admin?action=logs&subaction=system" class="nav-item <?= ($action == 'logs' && $subaction == 'system') ? 'active' : '' ?>">
                        <i class="fas fa-server"></i> System Logs
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
                <h1 class="text-3xl font-bold text-[#0b2b3f]">Dashboard</h1>
                <p class="text-gray-500 mt-1">Welcome back, <?= htmlspecialchars($currentUser['full_name'] ?? 'Admin') ?>!</p>
            </div>
            <div class="flex gap-3">
                <a href="/jms/admin?action=settings&subaction=journal" class="bg-white border border-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition">
                    <i class="fas fa-cog mr-2"></i> Settings
                </a>
                <a href="/jms/admin?action=users&subaction=all" class="bg-[#0b2b3f] text-white px-4 py-2 rounded-lg hover:bg-[#123a4f] transition">
                    <i class="fas fa-users mr-2"></i> Manage Users
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow-card p-6 stat-card border border-gray-100/70">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Total Users</p>
                        <p class="text-2xl font-bold text-[#0b2b3f]"><?= $stats['total_users'] ?? 0 ?></p>
                    </div>
                    <div class="w-12 h-12 bg-blue-50 rounded-full flex items-center justify-center text-blue-600">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-card p-6 stat-card border border-gray-100/70">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Total Manuscripts</p>
                        <p class="text-2xl font-bold text-[#0b2b3f]"><?= $stats['total_manuscripts'] ?? 0 ?></p>
                    </div>
                    <div class="w-12 h-12 bg-purple-50 rounded-full flex items-center justify-center text-purple-600">
                        <i class="fas fa-file-alt text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-card p-6 stat-card border border-gray-100/70">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Total Reviews</p>
                        <p class="text-2xl font-bold text-[#0b2b3f]"><?= $stats['total_reviews'] ?? 0 ?></p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-50 rounded-full flex items-center justify-center text-yellow-600">
                        <i class="fas fa-star text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-card p-6 stat-card border border-gray-100/70">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Submissions (This Month)</p>
                        <p class="text-2xl font-bold text-[#0b2b3f]"><?= $stats['submissions_this_month'] ?? 0 ?></p>
                    </div>
                    <div class="w-12 h-12 bg-green-50 rounded-full flex items-center justify-center text-green-600">
                        <i class="fas fa-upload text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Secondary Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow-card p-4 border border-gray-100/70">
                <p class="text-xs text-gray-500">Total Views</p>
                <p class="text-lg font-bold text-[#0b2b3f]"><?= number_format($stats['total_views'] ?? 0) ?></p>
            </div>
            <div class="bg-white rounded-xl shadow-card p-4 border border-gray-100/70">
                <p class="text-xs text-gray-500">Total Downloads</p>
                <p class="text-lg font-bold text-[#0b2b3f]"><?= number_format($stats['total_downloads'] ?? 0) ?></p>
            </div>
            <div class="bg-white rounded-xl shadow-card p-4 border border-gray-100/70">
                <p class="text-xs text-gray-500">Acceptance Rate</p>
                <p class="text-lg font-bold text-[#0b2b3f]"><?= $acceptanceRate ?>%</p>
            </div>
            <div class="bg-white rounded-xl shadow-card p-4 border border-gray-100/70">
                <p class="text-xs text-gray-500">Avg. Turnaround</p>
                <p class="text-lg font-bold text-[#0b2b3f]"><?= $avgTurnaround ?> days</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column - Recent Submissions -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-[#0b2b3f]">Recent Submissions</h3>
                        <a href="/jms/admin?action=submissions&subaction=all" class="text-sm text-indigo-600 hover:underline">View all</a>
                    </div>
                    <div class="space-y-3">
                        <?php if (empty($recentSubmissions)): ?>
                            <p class="text-sm text-gray-500">No submissions yet.</p>
                        <?php else: ?>
                            <?php foreach ($recentSubmissions as $submission): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-[#0b2b3f]"><?= htmlspecialchars(substr($submission['title'] ?? '', 0, 60)) ?>...</p>
                                    <div class="flex items-center gap-2 text-xs text-gray-500 mt-1">
                                        <span><?= htmlspecialchars($submission['author_name'] ?? 'Unknown') ?></span>
                                        <span>·</span>
                                        <span><?= timeAgo($submission['submission_date'] ?? null) ?></span>
                                        <span class="px-2 py-0.5 rounded-full <?= getStatusBadgeClass($submission['status'] ?? '') ?> text-xs">
                                            <?= ucfirst(str_replace('_', ' ', $submission['status'] ?? 'Unknown')) ?>
                                        </span>
                                    </div>
                                </div>
                                <a href="/jms/admin?action=manuscript&id=<?= $submission['id'] ?? '' ?>" class="text-indigo-600 hover:text-indigo-800 text-sm">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                <!-- Users by Role -->
                <div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
                    <h3 class="font-semibold text-[#0b2b3f] mb-4">Users by Role</h3>
                    <div class="space-y-3">
                        <?php 
                        if (!empty($stats['users_by_role']) && is_array($stats['users_by_role'])):
                            foreach ($stats['users_by_role'] as $roleData): 
                        ?>
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-1 rounded-full text-xs font-medium <?= $roleColors[$roleData['role'] ?? ''] ?? 'bg-gray-100 text-gray-700' ?>">
                                <?= ucfirst($roleData['role'] ?? 'Unknown') ?>
                            </span>
                            <span class="text-sm font-semibold"><?= $roleData['count'] ?? 0 ?></span>
                        </div>
                        <?php 
                            endforeach;
                        else:
                        ?>
                        <p class="text-sm text-gray-500">No user data available</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Manuscript Status -->
                <div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
                    <h3 class="font-semibold text-[#0b2b3f] mb-4">Manuscript Status</h3>
                    <div class="space-y-3">
                        <?php 
                        $statusColors = [
                            'draft' => 'bg-gray-100 text-gray-700',
                            'submitted' => 'bg-blue-100 text-blue-700',
                            'under_review' => 'bg-yellow-100 text-yellow-700',
                            'revision_required' => 'bg-orange-100 text-orange-700',
                            'accepted' => 'bg-green-100 text-green-700',
                            'rejected' => 'bg-red-100 text-red-700',
                            'published' => 'bg-purple-100 text-purple-700'
                        ];
                        if (!empty($stats['manuscripts_by_status']) && is_array($stats['manuscripts_by_status'])):
                            foreach ($stats['manuscripts_by_status'] as $statusData): 
                        ?>
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-1 rounded-full text-xs font-medium <?= $statusColors[$statusData['status'] ?? ''] ?? 'bg-gray-100 text-gray-700' ?>">
                                <?= ucfirst(str_replace('_', ' ', $statusData['status'] ?? 'Unknown')) ?>
                            </span>
                            <span class="text-sm font-semibold"><?= $statusData['count'] ?? 0 ?></span>
                        </div>
                        <?php 
                            endforeach;
                        else:
                        ?>
                        <p class="text-sm text-gray-500">No manuscript data available</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Users -->
                <div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-[#0b2b3f]">Recent Users</h3>
                        <a href="/jms/admin?action=users&subaction=all" class="text-sm text-indigo-600 hover:underline">View all</a>
                    </div>
                    <div class="space-y-2">
                        <?php foreach ($recentUsers as $user): ?>
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium"><?= htmlspecialchars($user['full_name'] ?? 'Unknown') ?></span>
                            <span class="text-xs text-gray-400"><?= ucfirst($user['role'] ?? '') ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php elseif ($isSubPage && $pageFile && file_exists($pageFile)): ?>
            <?php include $pageFile; ?>
        <?php else: ?>
            <div class="bg-white rounded-xl shadow-card p-8 border border-gray-100/70">
                <h2 class="text-2xl font-bold text-[#0b2b3f] mb-4">
                    <?= ucfirst(str_replace('_', ' ', $action)) ?>
                    <?php if ($subaction): ?>
                        <span class="text-gray-400 text-lg">- <?= ucfirst(str_replace('_', ' ', $subaction)) ?></span>
                    <?php endif; ?>
                </h2>
                <div class="h-1 w-20 bg-indigo-200 rounded-full mb-6"></div>
                <p class="text-gray-500">Page under development.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
    // SIMPLE toggle functions - NO LOOPS
    function toggleMenu(menuId) {
        var menu = document.getElementById(menuId);
        if (!menu) return false;
        
        // Toggle the menu
        if (menu.classList.contains('open')) {
            menu.classList.remove('open');
        } else {
            menu.classList.add('open');
        }
        
        // Toggle chevron
        var parent = menu.previousElementSibling;
        if (parent) {
            var chevron = parent.querySelector('.fa-chevron-down, .fa-chevron-up');
            if (chevron) {
                if (chevron.classList.contains('fa-chevron-down')) {
                    chevron.classList.remove('fa-chevron-down');
                    chevron.classList.add('fa-chevron-up');
                } else {
                    chevron.classList.remove('fa-chevron-up');
                    chevron.classList.add('fa-chevron-down');
                }
            }
        }
        return false;
    }

    function toggleSidebar() {
        var sidebar = document.getElementById('adminSidebar');
        var overlay = document.getElementById('sidebarOverlay');
        if (sidebar) {
            sidebar.classList.toggle('open');
        }
        if (overlay) {
            overlay.classList.toggle('show');
        }
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
            overlay.addEventListener('click', function() {
                toggleSidebar();
            });
        }
    });
    </script>

    <?php 
    $footerPath = $basePath . '/includes/footer.php';
    if (file_exists($footerPath)) {
        include $footerPath;
    }
    ?>
</body>
</html>
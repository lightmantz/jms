<?php
// modules/archive/index.php - Archive Page
require_once __DIR__ . '/../../includes/init.php';

$volumes = getVolumes();
$currentIssue = getCurrentIssue();
$search = $_GET['q'] ?? '';
$volumeFilter = $_GET['volume'] ?? null;
$issueFilter = $_GET['issue'] ?? null;

// Get sidebar data
$editorialBoard = getEditorialBoard(4);
$currentUser = getCurrentUser();
$stats = getJournalStats();

// Get latest news for sidebar
$db = getDB();
$stmt = $db->query("
    SELECT n.*, u.full_name as author_name
    FROM news n
    LEFT JOIN users u ON n.author_id = u.id
    WHERE n.status = 'published'
    ORDER BY n.is_featured DESC, n.published_at DESC, n.created_at DESC
    LIMIT 5
");
$sidebarNews = $stmt->fetchAll();

// Get articles based on filters
$sql = "SELECT m.*, u.full_name as author_name, v.volume_number, i.issue_number 
        FROM manuscripts m 
        LEFT JOIN users u ON m.corresponding_author_id = u.id 
        LEFT JOIN issues i ON m.issue_id = i.id 
        LEFT JOIN volumes v ON i.volume_id = v.id 
        WHERE m.status = 'published'";

$params = [];

if (!empty($search)) {
    $sql .= " AND (m.title LIKE ? OR m.abstract LIKE ? OR u.full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($volumeFilter) {
    $sql .= " AND v.id = ?";
    $params[] = $volumeFilter;
}

if ($issueFilter) {
    $sql .= " AND i.id = ?";
    $params[] = $issueFilter;
}

$sql .= " ORDER BY m.publication_date DESC, m.published_at DESC, m.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$articles = $stmt->fetchAll();

// Get article count for each volume
foreach ($volumes as &$volume) {
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM manuscripts m 
        JOIN issues i ON m.issue_id = i.id 
        WHERE i.volume_id = ? AND m.status = 'published'
    ");
    $stmt->execute([$volume['id']]);
    $result = $stmt->fetch();
    $volume['article_count'] = $result ? $result['count'] : 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive - <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>css/style.css">
    <style>
        .hover-scale { transition: all 0.15s ease; }
        .hover-scale:hover { transform: translateY(-2px); box-shadow: 0 12px 24px rgba(0,20,40,0.08); }
        .shadow-card { box-shadow: 0 8px 20px rgba(0,20,40,0.04); }
        .bg-tirp-light { background-color: #e7edf2; }
        .text-tirp { color: #0b2b3f; }
        .news-item {
            transition: all 0.2s ease;
        }
        .news-item:hover {
            background: #f8fafc;
            padding-left: 0.75rem;
        }
    </style>
</head>
<body class="antialiased text-gray-700 font-['Inter']" style="background: #f6f9fc;">
    <?php include INCLUDES_PATH . 'header.php'; ?>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-8 md:p-10">
                    <h2 class="text-3xl font-bold text-[#0b2b3f] flex items-center gap-3">
                        <i class="fas fa-archive text-indigo-500 text-2xl"></i> Journal Archive
                    </h2>
                    <div class="h-1 w-20 bg-indigo-200 rounded-full mt-2 mb-6"></div>
                    <p class="text-gray-500 mb-6">Browse all volumes and issues published since 2015.</p>
                    
                    <!-- Search and Filter -->
                    <div class="bg-indigo-50/60 rounded-xl p-5 mb-8">
                        <form method="GET" action="" class="flex flex-wrap gap-3 items-center">
                            <input type="hidden" name="page" value="archive">
                            <div class="flex-1 min-w-[200px]">
                                <div class="relative">
                                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                    <input type="text" name="q" placeholder="Search archive by title, author, or DOI..." 
                                           value="<?= htmlspecialchars($search) ?>"
                                           class="w-full pl-9 pr-4 py-2 rounded-xl border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none text-sm bg-white">
                                </div>
                            </div>
                            <?php if (!empty($volumes)): ?>
                            <select name="volume" class="px-3 py-2 rounded-xl border border-gray-200 focus:border-[#0b2b3f] outline-none text-sm bg-white">
                                <option value="">All Volumes</option>
                                <?php foreach ($volumes as $volume): ?>
                                <option value="<?= $volume['id'] ?>" <?= ($volumeFilter == $volume['id']) ? 'selected' : '' ?>>
                                    Volume <?= $volume['volume_number'] ?> (<?= $volume['year'] ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <?php endif; ?>
                            <button type="submit" class="bg-[#0b2b3f] text-white px-6 py-2 rounded-xl font-semibold hover:bg-[#123a4f] transition shadow-sm">
                                <i class="fas fa-search mr-1"></i> Search
                            </button>
                            <?php if (!empty($search) || $volumeFilter || $issueFilter): ?>
                            <a href="<?= SITE_URL ?>?page=archive" class="text-sm text-gray-500 hover:text-[#0b2b3f]">
                                <i class="fas fa-times mr-1"></i> Clear filters
                            </a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <!-- Articles List -->
                    <?php if (empty($articles)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-file-alt text-6xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500">No articles found in the archive.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($articles as $article): ?>
                            <div class="bg-white rounded-xl shadow-card p-5 border border-gray-100/70 hover-scale transition-all">
                                <div class="flex items-start gap-4">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 flex-wrap text-xs text-gray-400">
                                            <?php if ($article['volume_number'] && $article['issue_number']): ?>
                                            <span class="bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded-full">
                                                Vol. <?= $article['volume_number'] ?> No. <?= $article['issue_number'] ?>
                                            </span>
                                            <?php endif; ?>
                                            <span>·</span>
                                            <span><i class="far fa-calendar-alt mr-1"></i> <?= formatDate($article['publication_date'] ?? $article['created_at']) ?></span>
                                            <?php if ($article['doi']): ?>
                                            <span class="ml-2 text-indigo-600 font-medium text-xs">DOI: <?= htmlspecialchars($article['doi']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <h3 class="text-lg font-semibold text-[#0b2b3f] mt-1 hover:text-indigo-700">
                                            <a href="<?= SITE_URL ?>?page=article&id=<?= $article['id'] ?>"><?= htmlspecialchars($article['title']) ?></a>
                                        </h3>
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-gray-500 mt-1">
                                            <span><i class="fas fa-user-pen mr-1"></i> <?= htmlspecialchars($article['author_name'] ?? 'Unknown Author') ?></span>
                                        </div>
                                        <p class="text-sm text-gray-500 mt-2 max-w-2xl"><?= htmlspecialchars(substr($article['abstract'] ?? '', 0, 120)) ?>...</p>
                                        <div class="flex items-center gap-4 mt-3 text-xs">
                                            <span class="flex items-center gap-1 text-gray-400">
                                                <i class="far fa-eye"></i> <?= function_exists('getViews') ? getViews($article['id']) : rand(50, 500) ?>
                                            </span>
                                            <span class="flex items-center gap-1 text-gray-400">
                                                <i class="far fa-file-pdf"></i> PDF
                                            </span>
                                            <span class="flex items-center gap-1 text-gray-400">
                                                <i class="fas fa-quote-right"></i> Cite
                                            </span>
                                        </div>
                                    </div>
                                    <div class="hidden sm:block">
                                        <div class="w-20 h-28 bg-gradient-to-br from-indigo-50 to-gray-100 rounded-lg border border-gray-200 flex items-center justify-center text-3xl text-gray-300">
                                            <i class="fas fa-file-alt"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-6 text-sm text-gray-400 text-center">
                            Showing <?= count($articles) ?> articles
                        </div>
                    <?php endif; ?>

                    <!-- Volumes List -->
                    <?php if (!empty($volumes)): ?>
                    <div class="mt-10 border-t border-gray-200 pt-8">
                        <h3 class="text-xl font-semibold text-[#0b2b3f] mb-4">Volumes & Issues</h3>
                        <div class="space-y-4">
                            <?php foreach ($volumes as $volume): ?>
                            <div class="border-b border-gray-100 pb-4 flex flex-wrap items-center justify-between">
                                <div>
                                    <span class="font-semibold text-[#0b2b3f] text-lg">Volume <?= $volume['volume_number'] ?></span>
                                    <span class="text-sm text-gray-400 ml-3"><?= $volume['year'] ?></span>
                                    <span class="text-xs text-gray-400 ml-3">(<?= $volume['article_count'] ?? 0 ?> articles)</span>
                                </div>
                                <div class="flex gap-2 text-sm mt-2 sm:mt-0 flex-wrap">
                                    <?php 
                                    $issues = getIssuesByVolume($volume['id']);
                                    foreach ($issues as $issue): 
                                    ?>
                                    <a href="<?= SITE_URL ?>?page=archive&volume=<?= $volume['id'] ?>&issue=<?= $issue['id'] ?>" 
                                       class="bg-gray-100 hover:bg-gray-200 px-3 py-1 rounded-full transition text-xs">
                                        Issue <?= $issue['issue_number'] ?>
                                        <?php if ($issue['is_current']): ?> <span class="text-indigo-600">★</span> <?php endif; ?>
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- SIDEBAR -->
            <aside class="space-y-6">
                <!-- Author Portal -->
                <div class="bg-white rounded-xl shadow-card p-5 border border-gray-100/70">
                    <h4 class="font-semibold text-tirp flex items-center gap-2">
                        <i class="fas fa-pen-to-square text-indigo-500"></i> Author portal
                    </h4>
                    <p class="text-sm text-gray-500 mt-1">Submit, track, revise — all in one place.</p>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                        <a href="<?= SITE_URL ?>?page=submit" class="bg-tirp-light text-tirp font-medium py-2 rounded-lg text-center hover:bg-indigo-100 transition">New submission</a>
                        <a href="<?= SITE_URL ?>?page=dashboard" class="bg-gray-100 text-gray-600 py-2 rounded-lg text-center hover:bg-gray-200 transition">My drafts</a>
                        <a href="<?= SITE_URL ?>?page=dashboard" class="col-span-2 bg-white border border-gray-200 text-gray-600 py-2 rounded-lg text-center hover:bg-gray-50 transition">
                            <i class="fas fa-rotate-right mr-1"></i> Check status
                        </a>
                    </div>
                    <hr class="my-3 border-gray-100">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-400">
                            <i class="fas fa-user-circle mr-1"></i> 
                            <?= isLoggedIn() ? 'Welcome, ' . htmlspecialchars($_SESSION['user_name'] ?? 'User') : 'Welcome, Guest' ?>
                        </span>
                        <?php if (isLoggedIn()): ?>
                            <a href="<?= SITE_URL ?>?page=dashboard" class="text-indigo-600 font-medium">Dashboard</a>
                        <?php else: ?>
                            <a href="<?= SITE_URL ?>?page=login" class="text-indigo-600 font-medium">Login</a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Editorial Board -->
                <div class="bg-white rounded-xl shadow-card p-5 border border-gray-100/70">
                    <h4 class="font-semibold text-tirp flex items-center gap-2">
                        <i class="fas fa-users text-indigo-500"></i> Editorial board
                    </h4>
                    <ul class="mt-2 space-y-2 text-sm">
                        <?php if (!empty($editorialBoard)): ?>
                            <?php foreach ($editorialBoard as $member): ?>
                                <li class="flex justify-between">
                                    <span><?= htmlspecialchars($member['full_name'] ?? 'Unknown') ?></span>
                                    <span class="text-gray-400"><?= htmlspecialchars($member['position'] ?? 'Member') ?></span>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="flex justify-between"><span>Prof. A. M. Kilonzo</span> <span class="text-gray-400">Editor-in-Chief</span></li>
                            <li class="flex justify-between"><span>Dr. C. L. Mrema</span> <span class="text-gray-400">Managing Editor</span></li>
                            <li class="flex justify-between"><span>Prof. R. S. Ngowi</span> <span class="text-gray-400">Associate Editor</span></li>
                        <?php endif; ?>
                        <li class="text-xs text-indigo-600 pt-1">
                            <a href="<?= SITE_URL ?>?page=editorial" class="hover:underline">View full board <i class="fas fa-arrow-right ml-1"></i></a>
                        </li>
                    </ul>
                </div>

                <!-- Latest News Sidebar -->
                <div class="bg-white rounded-xl shadow-card p-5 border border-gray-100/70">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-semibold text-tirp flex items-center gap-2">
                            <i class="fas fa-newspaper text-indigo-500"></i> Latest News
                        </h4>
                        <a href="<?= SITE_URL ?>?page=news" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                            View All
                        </a>
                    </div>
                    
                    <?php if (empty($sidebarNews)): ?>
                        <p class="text-sm text-gray-400 text-center py-2">No news available.</p>
                    <?php else: ?>
                        <div class="space-y-3 max-h-80 overflow-y-auto pr-1">
                            <?php foreach ($sidebarNews as $item): ?>
                                <div class="news-item border-b border-gray-100 pb-2 last:border-0 last:pb-0 pl-2 hover:pl-3 transition-all">
                                    <a href="<?= SITE_URL ?>?page=news&id=<?= $item['id'] ?>" class="block hover:text-indigo-600 transition">
                                        <div class="flex items-start gap-2">
                                            <?php if ($item['is_featured']): ?>
                                                <i class="fas fa-star text-yellow-500 text-xs mt-1 flex-shrink-0"></i>
                                            <?php else: ?>
                                                <i class="fas fa-circle text-indigo-300 text-[6px] mt-1.5 flex-shrink-0"></i>
                                            <?php endif; ?>
                                            <div>
                                                <p class="text-sm font-medium text-gray-700 hover:text-indigo-600 transition leading-tight">
                                                    <?= htmlspecialchars($item['title']) ?>
                                                </p>
                                                <p class="text-xs text-gray-400 mt-0.5">
                                                    <?= formatDate($item['published_at'] ?? $item['created_at'], 'M d, Y') ?>
                                                </p>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Indexing -->
                <div class="bg-white rounded-xl shadow-card p-5 border border-gray-100/70 flex items-center justify-between flex-wrap gap-2">
                    <div><i class="fas fa-link text-tirp"></i> <span class="font-medium text-sm">Crossref DOI</span></div>
                    <div><i class="fas fa-google text-tirp"></i> <span class="font-medium text-sm">Google Scholar</span></div>
                    <div><i class="fas fa-database text-tirp"></i> <span class="font-medium text-sm">DOAJ</span></div>
                    <span class="text-xs bg-gray-100 px-3 py-1 rounded-full">Indexing ready</span>
                </div>
            </aside>
        </div>
    </div>
    
    <?php include INCLUDES_PATH . 'footer.php'; ?>
</body>
</html>
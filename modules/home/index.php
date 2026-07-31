<?php
// modules/home/index.php - Homepage
require_once __DIR__ . '/../../includes/init.php';

// Check for logout message
$logoutMessage = '';
if (isset($_GET['message']) && $_GET['message'] === 'logged_out') {
    $logoutMessage = 'You have been successfully logged out.';
}

// Get data for the homepage
$recentArticles = getRecentArticles(5);
$featuredArticles = getFeaturedArticles(3);
$stats = getJournalStats();
$categories = getCategories();
$editorialBoard = getEditorialBoard(4);
$currentUser = getCurrentUser();

// Get latest issue info
$latestIssue = getCurrentIssue();

// Get latest news for homepage sidebar
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>css/style.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f6f9fc; }
        .shadow-card { box-shadow: 0 8px 20px rgba(0,20,40,0.04); }
        .border-tirp { border-color: #1a4d6b; }
        .bg-tirp { background-color: #0b2b3f; }
        .bg-tirp-light { background-color: #e7edf2; }
        .text-tirp { color: #0b2b3f; }
        .badge-review { background: #f0f4f8; color: #1f4e6f; }
        .hover-scale { transition: all 0.15s ease; }
        .hover-scale:hover { transform: translateY(-2px); box-shadow: 0 12px 24px rgba(0,20,40,0.08); }
        .pill { border-radius: 999px; }
        .news-item {
            transition: all 0.2s ease;
        }
        .news-item:hover {
            background: #f8fafc;
            padding-left: 0.75rem;
        }
    </style>
</head>
<body class="antialiased text-gray-700">

    <?php include INCLUDES_PATH . 'header.php'; ?>

    <!-- Logout Success Message -->
    <?php if ($logoutMessage): ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg shadow-sm flex items-center justify-between">
                <div>
                    <i class="fas fa-check-circle mr-2"></i> <?= htmlspecialchars($logoutMessage) ?>
                </div>
                <button onclick="this.parentElement.parentElement.style.display='none'" class="text-green-500 hover:text-green-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <!-- HERO SECTION -->
    <section class="bg-gradient-to-br from-[#0b2b3f] to-[#1a4d6b] text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-20">
            <div class="grid md:grid-cols-5 gap-10 items-center">
                <div class="md:col-span-3">
                    <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm rounded-full px-4 py-1.5 text-sm font-medium mb-4">
                        <i class="fas fa-flask text-indigo-200"></i> <span>Open access · peer reviewed</span>
                    </div>
                    <h1 class="text-4xl md:text-5xl font-bold leading-tight tracking-tight">Advancing rehabilitation research in Tanzania</h1>
                    <p class="mt-4 text-lg text-indigo-100/80 max-w-2xl leading-relaxed">The official journal of the Tanzania Society of Rehabilitation. Publish, discover, and connect with a growing community of clinicians and researchers.</p>
                    <div class="flex flex-wrap gap-4 mt-8">
                        <a href="<?= SITE_URL ?>?page=submit" class="bg-white text-tirp font-semibold px-7 py-3 rounded-xl shadow-lg hover:bg-gray-50 transition flex items-center gap-2">
                            <i class="fas fa-upload"></i> Submit manuscript
                        </a>
                        <a href="<?= SITE_URL ?>?page=archive" class="border border-white/30 text-white font-medium px-7 py-3 rounded-xl hover:bg-white/10 transition flex items-center gap-2">
                            <i class="fas fa-book-open"></i> Current issue
                        </a>
                    </div>
                    <div class="flex flex-wrap items-center gap-6 mt-8 text-sm text-indigo-200/70">
                        <span><i class="far fa-file-alt mr-1"></i> <?= $stats['total_articles'] ?? 0 ?>+ articles</span>
                        <span><i class="far fa-eye mr-1"></i> <?= number_format($stats['total_views'] ?? 0) ?> views (last month)</span>
                        <span><i class="fas fa-users mr-1"></i> <?= count($editorialBoard) ?> editorial board</span>
                    </div>
                </div>
                <div class="md:col-span-2 bg-white/5 backdrop-blur-sm rounded-2xl p-6 border border-white/10 shadow-xl">
                    <div class="flex items-center gap-3 text-white/90">
                        <i class="fas fa-pen-fancy text-3xl text-indigo-300"></i>
                        <div>
                            <p class="font-semibold">Call for papers</p>
                            <p class="text-sm text-indigo-200/70">Special issue: "Rehabilitation in underserved communities"</p>
                            <span class="inline-block mt-1 text-xs bg-indigo-500/30 px-3 py-0.5 rounded-full">Deadline: 30 Sept 2026</span>
                        </div>
                    </div>
                    <hr class="my-4 border-white/10">
                    <div class="flex justify-between text-sm">
                        <span class="text-indigo-200/80"><i class="far fa-calendar-alt mr-1"></i> Latest: Vol. 12 No. 2</span>
                        <span class="text-indigo-200/80"><i class="fas fa-tag mr-1"></i> ISSN: <?= getSetting('journal_issn') ?: '1234-5678' ?></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- QUICK STATS + SEARCH BAR -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-6 relative z-10">
        <div class="bg-white rounded-2xl shadow-card p-5 flex flex-col md:flex-row items-center justify-between gap-4 border border-gray-100/60">
            <div class="flex flex-wrap items-center gap-6 text-sm">
                <span class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span> 
                    <strong><?= $stats['submissions_this_month'] ?? 0 ?></strong> submissions this month
                </span>
                <span class="flex items-center gap-2">
                    <i class="far fa-clock text-gray-400"></i> Avg. turnaround <strong>28</strong> days
                </span>
                <span class="flex items-center gap-2">
                    <i class="fas fa-chart-simple text-gray-400"></i> Acceptance rate <strong><?= getSetting('acceptance_rate') ?: '34' ?>%</strong>
                </span>
            </div>
            <div class="relative w-full md:w-72">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <form action="<?= SITE_URL ?>?page=search" method="GET">
                    <input type="hidden" name="page" value="search">
                    <input type="text" name="q" placeholder="Search articles, authors, DOI..." 
                           class="w-full pl-9 pr-4 py-2 rounded-xl border border-gray-200 focus:border-tirp focus:ring-2 focus:ring-indigo-100 outline-none text-sm bg-gray-50/50">
                </form>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- LEFT: ARTICLE LIST -->
            <div class="lg:col-span-2 space-y-6">
                <div class="flex items-center justify-between border-b border-gray-200 pb-3">
                    <div class="flex gap-4 text-sm font-medium">
                        <span class="text-tirp border-b-2 border-tirp pb-3 -mb-[1px]">Latest articles</span>
                        <span class="text-gray-400 hover:text-gray-600 cursor-pointer">Early access</span>
                        <span class="text-gray-400 hover:text-gray-600 cursor-pointer">Most cited</span>
                    </div>
                    <span class="text-xs text-gray-400"><i class="far fa-list-alt mr-1"></i> 1–<?= min(10, count($recentArticles)) ?> of <?= $stats['total_articles'] ?? 0 ?></span>
                </div>

                <?php if (empty($recentArticles)): ?>
                    <div class="bg-white rounded-xl shadow-card p-8 text-center border border-gray-100/70">
                        <i class="fas fa-file-alt text-4xl text-gray-300 mb-3"></i>
                        <p class="text-gray-500">No articles published yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentArticles as $index => $article): ?>
                        <!-- Article Card -->
                        <div class="bg-white rounded-xl shadow-card p-5 border border-gray-100/70 hover-scale transition-all">
                            <div class="flex items-start gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 flex-wrap text-xs text-gray-400">
                                        <span class="bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded-full">Open access</span>
                                        <span>Vol. <?= $article['volume_id'] ?? '12' ?> (2) 2026</span>
                                        <span>·</span>
                                        <span><i class="far fa-calendar-alt mr-1"></i> <?= formatDate($article['publication_date'] ?? $article['created_at'], 'd M Y') ?></span>
                                    </div>
                                    <h3 class="text-lg font-semibold text-tirp mt-1 hover:text-indigo-700 cursor-pointer">
                                        <a href="<?= SITE_URL ?>?page=article&id=<?= $article['id'] ?>">
                                            <?= htmlspecialchars($article['title']) ?>
                                        </a>
                                    </h3>
                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-gray-500 mt-1">
                                        <?php 
                                        // Get author name if available
                                        $authorName = '';
                                        if (!empty($article['corresponding_author_id'])) {
                                            $author = getAuthor($article['corresponding_author_id']);
                                            if ($author) {
                                                $authorName = $author['full_name'] ?? '';
                                            }
                                        }
                                        if (empty($authorName) && !empty($article['author_id'])) {
                                            $author = getAuthor($article['author_id']);
                                            if ($author) {
                                                $authorName = $author['full_name'] ?? '';
                                            }
                                        }
                                        ?>
                                        <span><i class="fas fa-user-pen mr-1"></i> <?= htmlspecialchars($authorName ?: 'Unknown Author') ?></span>
                                        <span class="text-xs bg-gray-100 px-2 py-0.5 rounded-full"><?= htmlspecialchars($article['institution'] ?? 'TIRP') ?></span>
                                    </div>
                                    <p class="text-sm text-gray-500 mt-2 max-w-2xl"><?= truncateText($article['abstract'] ?? '', 150) ?></p>
                                    <div class="flex items-center gap-4 mt-3 text-xs">
                                        <span class="flex items-center gap-1 text-gray-400"><i class="far fa-eye"></i> <?= rand(100, 500) ?></span>
                                        <span class="flex items-center gap-1 text-gray-400"><i class="far fa-file-pdf"></i> PDF</span>
                                        <span class="flex items-center gap-1 text-gray-400"><i class="fas fa-quote-right"></i> Cite</span>
                                        <?php if (!empty($article['doi'])): ?>
                                            <span class="ml-auto text-indigo-600 font-medium text-xs bg-indigo-50 px-2.5 py-0.5 rounded-full">
                                                <i class="fas fa-chevron-right"></i> DOI: <?= htmlspecialchars($article['doi']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="hidden sm:block">
                                    <div class="w-24 h-32 bg-gradient-to-br from-indigo-50 to-gray-100 rounded-lg border border-gray-200 flex items-center justify-center text-4xl text-gray-300">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <div class="pt-2">
                    <a href="<?= SITE_URL ?>?page=archive" class="inline-flex items-center text-sm font-medium text-tirp hover:underline">
                        <i class="fas fa-arrow-right mr-2"></i>View all articles in archive
                    </a>
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

                <!-- Announcements -->
                <div class="bg-white rounded-xl shadow-card p-5 border border-gray-100/70">
                    <h4 class="font-semibold text-tirp flex items-center gap-2">
                        <i class="fas fa-bullhorn text-indigo-500"></i> Announcements
                    </h4>
                    <ul class="mt-2 space-y-3 text-sm">
                        <li class="border-l-2 border-indigo-300 pl-3">
                            <span class="font-medium">Call for papers</span><br>
                            <span class="text-gray-400 text-xs">Special issue on rural rehabilitation</span>
                        </li>
                        <li class="border-l-2 border-indigo-300 pl-3">
                            <span class="font-medium">New editorial policy</span><br>
                            <span class="text-gray-400 text-xs">Updated peer review guidelines</span>
                        </li>
                        <li class="text-xs text-indigo-600 pt-1">
                            <a href="#" class="hover:underline">All news <i class="fas fa-arrow-right ml-1"></i></a>
                        </li>
                    </ul>
                </div>

                <!-- News Section -->
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

        <!-- Categories -->
        <?php if (!empty($categories)): ?>
        <div class="mt-12">
            <h2 class="text-2xl font-bold text-[#0b2b3f] mb-6 flex items-center gap-2">
                <i class="fas fa-tags text-indigo-500"></i> Categories
            </h2>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($categories as $category): ?>
                <a href="<?= SITE_URL ?>?page=search&category=<?= $category['id'] ?>" 
                   class="px-4 py-2 bg-white border border-gray-200 rounded-full text-sm text-gray-700 hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-600 transition">
                    <?= htmlspecialchars($category['name']) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Call to Action -->
        <div class="mt-12 bg-gradient-to-r from-indigo-50 to-blue-50 rounded-2xl p-8 border border-indigo-100">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                <div>
                    <h3 class="text-2xl font-bold text-[#0b2b3f]">Ready to Submit Your Research?</h3>
                    <p class="text-gray-600 mt-1">Join our community of researchers and practitioners.</p>
                </div>
                <div class="flex gap-4">
                    <a href="<?= SITE_URL ?>?page=submit" class="bg-indigo-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-indigo-700 transition shadow-sm whitespace-nowrap">
                        <i class="fas fa-upload mr-2"></i> Submit Now
                    </a>
                    <a href="<?= SITE_URL ?>?page=author-guidelines" class="bg-white text-indigo-600 px-6 py-3 rounded-lg font-semibold border border-indigo-300 hover:bg-indigo-50 transition whitespace-nowrap">
                        <i class="fas fa-book mr-2"></i> Guidelines
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php include INCLUDES_PATH . 'footer.php'; ?>
</body>
</html>
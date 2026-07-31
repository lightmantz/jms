<?php
// modules/pages/news.php - News Page
require_once __DIR__ . '/../../includes/init.php';

$db = getDB();
$newsId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Get sidebar data
$editorialBoard = getEditorialBoard(4);
$currentUser = getCurrentUser();
$stats = getJournalStats();

// Get latest news for sidebar
$stmt = $db->query("
    SELECT n.*, u.full_name as author_name
    FROM news n
    LEFT JOIN users u ON n.author_id = u.id
    WHERE n.status = 'published'
    ORDER BY n.is_featured DESC, n.published_at DESC, n.created_at DESC
    LIMIT 5
");
$sidebarNews = $stmt->fetchAll();

// If specific news ID, show single news
if ($newsId > 0) {
    $stmt = $db->prepare("
        SELECT n.*, u.full_name as author_name
        FROM news n
        LEFT JOIN users u ON n.author_id = u.id
        WHERE n.id = ? AND n.status = 'published'
    ");
    $stmt->execute([$newsId]);
    $news = $stmt->fetch();
    
    if (!$news) {
        http_response_code(404);
        require_once __DIR__ . '/404.php';
        exit;
    }
    
    // Get categories for this news
    $stmt = $db->prepare("
        SELECT c.* FROM news_categories c
        JOIN news_category_relations ncr ON c.id = ncr.category_id
        WHERE ncr.news_id = ?
    ");
    $stmt->execute([$newsId]);
    $categories = $stmt->fetchAll();
    
    // Get related news
    $categoryIds = array_column($categories, 'id');
    $relatedNews = [];
    if (!empty($categoryIds)) {
        $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
        $stmt = $db->prepare("
            SELECT DISTINCT n.*, u.full_name as author_name
            FROM news n
            JOIN news_category_relations ncr ON n.id = ncr.news_id
            LEFT JOIN users u ON n.author_id = u.id
            WHERE ncr.category_id IN ($placeholders)
            AND n.id != ? AND n.status = 'published'
            ORDER BY n.published_at DESC
            LIMIT 5
        ");
        $params = array_merge($categoryIds, [$newsId]);
        $stmt->execute($params);
        $relatedNews = $stmt->fetchAll();
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($news['title']) ?> - <?= SITE_NAME ?></title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <link rel="stylesheet" href="<?= SITE_URL ?>css/style.css">
        <style>
            .news-item {
                transition: all 0.2s ease;
            }
            .news-item:hover {
                background: #f8fafc;
                padding-left: 0.75rem;
            }
            .shadow-card { box-shadow: 0 8px 20px rgba(0,20,40,0.04); }
            .bg-tirp-light { background-color: #e7edf2; }
            .text-tirp { color: #0b2b3f; }
        </style>
    </head>
    <body class="antialiased text-gray-700 font-['Inter']" style="background: #f6f9fc;">
        <?php include INCLUDES_PATH . 'header.php'; ?>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-8 md:p-10">
                        <!-- Back link -->
                        <a href="<?= SITE_URL ?>?page=news" class="text-indigo-600 hover:text-indigo-800 text-sm mb-4 inline-block">
                            <i class="fas fa-arrow-left mr-1"></i> Back to News
                        </a>
                        
                        <!-- News Header -->
                        <div class="mb-6">
                            <?php if (!empty($categories)): ?>
                                <div class="flex flex-wrap gap-2 mb-3">
                                    <?php foreach ($categories as $cat): ?>
                                        <span class="text-xs bg-indigo-50 text-indigo-700 px-3 py-1 rounded-full">
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <h1 class="text-3xl md:text-4xl font-bold text-[#0b2b3f] leading-tight">
                                <?= htmlspecialchars($news['title']) ?>
                            </h1>
                            
                            <div class="flex flex-wrap items-center gap-4 mt-4 text-sm text-gray-500">
                                <span><i class="far fa-user mr-1"></i> <?= htmlspecialchars($news['author_name'] ?? 'Unknown') ?></span>
                                <span><i class="far fa-calendar-alt mr-1"></i> <?= formatDate($news['published_at'] ?? $news['created_at'], 'F j, Y') ?></span>
                                <?php if ($news['is_featured']): ?>
                                    <span class="text-yellow-500"><i class="fas fa-star mr-1"></i> Featured</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Featured Image -->
                        <?php if (!empty($news['featured_image'])): ?>
                            <div class="mb-6">
                                <img src="<?= SITE_URL . $news['featured_image'] ?>" alt="<?= htmlspecialchars($news['title']) ?>" 
                                     class="w-full rounded-xl max-h-96 object-cover">
                            </div>
                        <?php endif; ?>
                        
                        <!-- Content -->
                        <div class="prose max-w-none text-gray-700 leading-relaxed">
                            <?= nl2br(htmlspecialchars($news['content'])) ?>
                        </div>
                        
                        <!-- Share -->
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <p class="text-sm font-medium text-gray-600 mb-3">Share this news:</p>
                            <div class="flex gap-3">
                                <a href="https://twitter.com/intent/tweet?text=<?= urlencode($news['title']) ?>&url=<?= urlencode(SITE_URL . '?page=news&id=' . $news['id']) ?>" 
                                   target="_blank" class="text-gray-400 hover:text-blue-400 transition text-xl">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(SITE_URL . '?page=news&id=' . $news['id']) ?>" 
                                   target="_blank" class="text-gray-400 hover:text-blue-600 transition text-xl">
                                    <i class="fab fa-facebook"></i>
                                </a>
                                <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode(SITE_URL . '?page=news&id=' . $news['id']) ?>" 
                                   target="_blank" class="text-gray-400 hover:text-blue-700 transition text-xl">
                                    <i class="fab fa-linkedin"></i>
                                </a>
                                <a href="mailto:?subject=<?= urlencode($news['title']) ?>&body=<?= urlencode(SITE_URL . '?page=news&id=' . $news['id']) ?>" 
                                   class="text-gray-400 hover:text-gray-600 transition text-xl">
                                    <i class="fas fa-envelope"></i>
                                </a>
                            </div>
                        </div>
                        
                        <!-- Related News -->
                        <?php if (!empty($relatedNews)): ?>
                            <div class="mt-8 pt-6 border-t border-gray-200">
                                <h3 class="text-xl font-semibold text-[#0b2b3f] mb-4">Related News</h3>
                                <div class="space-y-3">
                                    <?php foreach ($relatedNews as $related): ?>
                                        <div class="border-b border-gray-100 pb-3 last:border-0">
                                            <a href="<?= SITE_URL ?>?page=news&id=<?= $related['id'] ?>" class="hover:text-indigo-600 transition">
                                                <p class="font-medium"><?= htmlspecialchars($related['title']) ?></p>
                                                <p class="text-sm text-gray-500"><?= formatDate($related['published_at'] ?? $related['created_at']) ?></p>
                                            </a>
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
    <?php
    exit;
}

// List all news
$sql = "SELECT n.*, u.full_name as author_name,
        (SELECT COUNT(*) FROM news_category_relations WHERE news_id = n.id) as category_count
        FROM news n
        LEFT JOIN users u ON n.author_id = u.id
        WHERE n.status = 'published'";

$params = [];

if ($categoryId > 0) {
    $sql .= " AND EXISTS (SELECT 1 FROM news_category_relations ncr WHERE ncr.news_id = n.id AND ncr.category_id = ?)";
    $params[] = $categoryId;
}

$sql .= " ORDER BY n.is_featured DESC, n.published_at DESC, n.created_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;

$stmt = $db->prepare($sql);
$stmt->execute($params);
$newsList = $stmt->fetchAll();

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total FROM news n WHERE n.status = 'published'";
$countParams = [];
if ($categoryId > 0) {
    $countSql .= " AND EXISTS (SELECT 1 FROM news_category_relations ncr WHERE ncr.news_id = n.id AND ncr.category_id = ?)";
    $countParams[] = $categoryId;
}
$stmt = $db->prepare($countSql);
$stmt->execute($countParams);
$total = $stmt->fetch()['total'] ?? 0;
$totalPages = ceil($total / $perPage);

// Get all categories
$stmt = $db->query("SELECT * FROM news_categories ORDER BY name");
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News - <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>css/style.css">
    <style>
        .news-item {
            transition: all 0.2s ease;
        }
        .news-item:hover {
            background: #f8fafc;
            padding-left: 0.75rem;
        }
        .shadow-card { box-shadow: 0 8px 20px rgba(0,20,40,0.04); }
        .bg-tirp-light { background-color: #e7edf2; }
        .text-tirp { color: #0b2b3f; }
    </style>
</head>
<body class="antialiased text-gray-700 font-['Inter']" style="background: #f6f9fc;">
    <?php include INCLUDES_PATH . 'header.php'; ?>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-8 md:p-10">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-3xl font-bold text-[#0b2b3f] flex items-center gap-3">
                                <i class="fas fa-newspaper text-indigo-500"></i> News & Announcements
                            </h1>
                            <p class="text-gray-500 text-sm mt-1">Stay updated with the latest news from TIRP</p>
                        </div>
                    </div>
                    <div class="h-1 w-20 bg-indigo-200 rounded-full mb-6"></div>
                    
                    <!-- Categories Filter -->
                    <?php if (!empty($categories)): ?>
                        <div class="flex flex-wrap gap-2 mb-6">
                            <a href="<?= SITE_URL ?>?page=news" 
                               class="px-3 py-1 rounded-full text-sm <?= $categoryId == 0 ? 'bg-[#0b2b3f] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?> transition">
                                All
                            </a>
                            <?php foreach ($categories as $cat): ?>
                                <a href="<?= SITE_URL ?>?page=news&category=<?= $cat['id'] ?>" 
                                   class="px-3 py-1 rounded-full text-sm <?= $categoryId == $cat['id'] ? 'bg-[#0b2b3f] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?> transition">
                                    <?= htmlspecialchars($cat['name']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- News List -->
                    <?php if (empty($newsList)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-newspaper text-6xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500">No news found.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-6">
                            <?php foreach ($newsList as $item): ?>
                                <div class="border-b border-gray-100 pb-6 last:border-0 last:pb-0">
                                    <div class="flex items-start gap-4">
                                        <div class="flex-1">
                                            <?php if ($item['is_featured']): ?>
                                                <span class="text-xs text-yellow-600 font-medium">
                                                    <i class="fas fa-star mr-1"></i> Featured
                                                </span>
                                            <?php endif; ?>
                                            <h2 class="text-xl font-semibold text-[#0b2b3f] hover:text-indigo-600 transition">
                                                <a href="<?= SITE_URL ?>?page=news&id=<?= $item['id'] ?>">
                                                    <?= htmlspecialchars($item['title']) ?>
                                                </a>
                                            </h2>
                                            <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500 mt-1">
                                                <span><i class="far fa-user mr-1"></i> <?= htmlspecialchars($item['author_name'] ?? 'Unknown') ?></span>
                                                <span><i class="far fa-calendar-alt mr-1"></i> <?= formatDate($item['published_at'] ?? $item['created_at'], 'F j, Y') ?></span>
                                                <?php if ($item['category_count'] > 0): ?>
                                                    <span><i class="fas fa-tag mr-1"></i> <?= $item['category_count'] ?> categories</span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($item['excerpt'])): ?>
                                                <p class="text-gray-600 mt-2"><?= htmlspecialchars($item['excerpt']) ?></p>
                                            <?php endif; ?>
                                            <a href="<?= SITE_URL ?>?page=news&id=<?= $item['id'] ?>" class="inline-block mt-2 text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                                                Read More <i class="fas fa-arrow-right ml-1"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <div class="flex justify-center gap-2 mt-8">
                                <?php if ($page > 1): ?>
                                    <a href="<?= SITE_URL ?>?page=news&page_num=<?= $page - 1 ?><?= $categoryId > 0 ? '&category=' . $categoryId : '' ?>" 
                                       class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition text-sm">
                                        Previous
                                    </a>
                                <?php endif; ?>
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <a href="<?= SITE_URL ?>?page=news&page_num=<?= $i ?><?= $categoryId > 0 ? '&category=' . $categoryId : '' ?>" 
                                       class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition text-sm <?= $i == $page ? 'bg-[#0b2b3f] text-white border-[#0b2b3f]' : '' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>
                                <?php if ($page < $totalPages): ?>
                                    <a href="<?= SITE_URL ?>?page=news&page_num=<?= $page + 1 ?><?= $categoryId > 0 ? '&category=' . $categoryId : '' ?>" 
                                       class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition text-sm">
                                        Next
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
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
<?php
// modules/pages/search.php - Search Page
require_once __DIR__ . '/../../includes/init.php';

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$results = [];

if (!empty($query) || $categoryId > 0) {
    if ($categoryId > 0) {
        $results = getManuscriptsByCategory($categoryId);
    } elseif (!empty($query)) {
        $results = searchManuscripts($query);
    }
}

$categories = getCategories();
$totalResults = count($results);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search - <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>css/style.css">
</head>
<body class="antialiased text-gray-700 font-['Inter']" style="background: #f6f9fc;">
    <?php include INCLUDES_PATH . 'header.php'; ?>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-8 md:p-10">
            <h1 class="text-3xl font-bold text-[#0b2b3f] flex items-center gap-3">
                <i class="fas fa-search text-indigo-500"></i> Search Articles
            </h1>
            <div class="h-1 w-20 bg-indigo-200 rounded-full mt-2 mb-6"></div>
            
            <!-- Search Form -->
            <form method="GET" action="" class="mb-8">
                <input type="hidden" name="page" value="search">
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1">
                        <div class="relative">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="text" name="q" placeholder="Search by title, author, abstract, or DOI..." 
                                   value="<?= htmlspecialchars($query) ?>"
                                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition">
                        </div>
                    </div>
                    <div class="md:w-48">
                        <select name="category" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition bg-white">
                            <option value="0">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="bg-indigo-600 text-white px-8 py-3 rounded-xl font-semibold hover:bg-indigo-700 transition shadow-sm whitespace-nowrap">
                        <i class="fas fa-search mr-2"></i> Search
                    </button>
                </div>
            </form>
            
            <!-- Results -->
            <?php if (!empty($query) || $categoryId > 0): ?>
                <div class="mb-4">
                    <p class="text-sm text-gray-500">
                        Found <strong><?= $totalResults ?></strong> result<?= $totalResults != 1 ? 's' : '' ?> 
                        <?php if (!empty($query)): ?>for "<strong><?= htmlspecialchars($query) ?></strong>"<?php endif; ?>
                        <?php if ($categoryId > 0): ?>
                            <?php 
                            $catName = '';
                            foreach ($categories as $cat) {
                                if ($cat['id'] == $categoryId) {
                                    $catName = $cat['name'];
                                    break;
                                }
                            }
                            ?>
                            in category "<strong><?= htmlspecialchars($catName) ?></strong>"
                        <?php endif; ?>
                    </p>
                </div>
                
                <?php if (empty($results)): ?>
                    <div class="text-center py-12">
                        <i class="fas fa-file-alt text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-600">No articles found</h3>
                        <p class="text-gray-500 mt-1">Try adjusting your search terms or filters.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($results as $article): ?>
                            <div class="bg-gray-50 rounded-xl p-5 border border-gray-200 hover:border-indigo-200 hover:shadow-md transition">
                                <div class="flex items-start gap-4">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 flex-wrap text-xs text-gray-400">
                                            <?php if (!empty($article['publication_date'])): ?>
                                                <span><i class="far fa-calendar-alt mr-1"></i> <?= formatDate($article['publication_date']) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($article['doi'])): ?>
                                                <span class="text-indigo-600">DOI: <?= htmlspecialchars($article['doi']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <h3 class="text-lg font-semibold text-[#0b2b3f] mt-1 hover:text-indigo-600">
                                            <a href="<?= SITE_URL ?>?page=article&id=<?= $article['id'] ?>">
                                                <?= htmlspecialchars($article['title']) ?>
                                            </a>
                                        </h3>
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-gray-500 mt-1">
                                            <?php 
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
                                        </div>
                                        <?php if (!empty($article['abstract'])): ?>
                                            <p class="text-sm text-gray-500 mt-2"><?= truncateText($article['abstract'], 200) ?></p>
                                        <?php endif; ?>
                                        <a href="<?= SITE_URL ?>?page=article&id=<?= $article['id'] ?>" class="inline-block mt-3 text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                                            Read More <i class="fas fa-arrow-right ml-1"></i>
                                        </a>
                                    </div>
                                    <div class="hidden sm:block">
                                        <div class="w-16 h-20 bg-gradient-to-br from-indigo-50 to-gray-100 rounded-lg border border-gray-200 flex items-center justify-center text-2xl text-gray-300">
                                            <i class="fas fa-file-alt"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-12">
                    <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-600">Search for Articles</h3>
                    <p class="text-gray-500 mt-1">Enter keywords above to search our journal archive.</p>
                    
                    <!-- Popular Categories -->
                    <?php if (!empty($categories)): ?>
                        <div class="mt-6">
                            <p class="text-sm text-gray-500 mb-3">Browse by category:</p>
                            <div class="flex flex-wrap justify-center gap-2">
                                <?php foreach (array_slice($categories, 0, 10) as $cat): ?>
                                    <a href="<?= SITE_URL ?>?page=search&category=<?= $cat['id'] ?>" 
                                       class="px-4 py-2 bg-gray-100 hover:bg-indigo-100 rounded-full text-sm text-gray-700 hover:text-indigo-600 transition">
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include INCLUDES_PATH . 'footer.php'; ?>
</body>
</html>
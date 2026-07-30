<?php
// modules/pages/article.php - Single Article View
require_once __DIR__ . '/../../includes/init.php';

// Get article ID from URL
$articleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($articleId <= 0) {
    header('Location: ' . SITE_URL . '?page=archive');
    exit;
}

// Get article details
$db = getDB();
$stmt = $db->prepare("
    SELECT m.*, 
           u.full_name as author_name, 
           u.email as author_email,
           u.institution as author_institution,
           v.volume_number,
           i.issue_number,
           i.publication_date as issue_publication_date
    FROM manuscripts m
    LEFT JOIN users u ON m.corresponding_author_id = u.id
    LEFT JOIN issues i ON m.issue_id = i.id
    LEFT JOIN volumes v ON i.volume_id = v.id
    WHERE m.id = ? AND m.status = 'published'
");
$stmt->execute([$articleId]);
$article = $stmt->fetch();

if (!$article) {
    // Article not found or not published
    http_response_code(404);
    require_once __DIR__ . '/404.php';
    exit;
}

// Get keywords/categories for this article
$stmt = $db->prepare("
    SELECT c.id, c.name 
    FROM manuscript_keywords mk
    JOIN categories c ON mk.category_id = c.id
    WHERE mk.manuscript_id = ?
");
$stmt->execute([$articleId]);
$keywords = $stmt->fetchAll();

// Get related articles (same category)
$relatedArticles = [];
if (!empty($keywords)) {
    $categoryIds = array_column($keywords, 'id');
    // Only proceed if we have category IDs
    if (!empty($categoryIds)) {
        $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
        $sql = "
            SELECT DISTINCT m.*, u.full_name as author_name
            FROM manuscripts m
            JOIN manuscript_keywords mk ON m.id = mk.manuscript_id
            LEFT JOIN users u ON m.corresponding_author_id = u.id
            WHERE mk.category_id IN ($placeholders) 
            AND m.id != ? 
            AND m.status = 'published'
            ORDER BY m.publication_date DESC
            LIMIT 5
        ";
        $params = array_merge($categoryIds, [$articleId]);
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $relatedArticles = $stmt->fetchAll();
    }
}

// Get citation count
$citationCount = getCitations($articleId);
$viewCount = getViews($articleId);
$downloadCount = getDownloads($articleId);

// Check if PDF exists
$hasPdf = !empty($article['pdf_file']) || !empty($article['file_path']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($article['title']) ?> - <?= SITE_NAME ?></title>
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
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-3">
                <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-8 md:p-10">
                    <!-- Article Header -->
                    <div class="mb-6">
                        <div class="flex items-center gap-2 flex-wrap text-sm text-gray-500 mb-3">
                            <?php if ($article['volume_number'] && $article['issue_number']): ?>
                                <span class="bg-indigo-50 text-indigo-700 px-3 py-1 rounded-full text-xs font-medium">
                                    Vol. <?= $article['volume_number'] ?> No. <?= $article['issue_number'] ?>
                                </span>
                            <?php endif; ?>
                            <span>·</span>
                            <span><i class="far fa-calendar-alt mr-1"></i> <?= formatDate($article['publication_date'] ?? $article['created_at']) ?></span>
                            <?php if ($article['doi']): ?>
                                <span>·</span>
                                <span class="text-indigo-600">DOI: <?= htmlspecialchars($article['doi']) ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <h1 class="text-3xl md:text-4xl font-bold text-[#0b2b3f] leading-tight">
                            <?= htmlspecialchars($article['title']) ?>
                        </h1>
                        
                        <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-gray-600">
                            <div>
                                <i class="fas fa-user-pen mr-1"></i>
                                <span class="font-medium"><?= htmlspecialchars($article['author_name'] ?? 'Unknown Author') ?></span>
                            </div>
                            <?php if ($article['author_institution']): ?>
                                <span>·</span>
                                <span><i class="fas fa-university mr-1"></i> <?= htmlspecialchars($article['author_institution']) ?></span>
                            <?php endif; ?>
                            <?php if ($article['author_email']): ?>
                                <span>·</span>
                                <a href="mailto:<?= htmlspecialchars($article['author_email']) ?>" class="text-indigo-600 hover:text-indigo-800">
                                    <i class="fas fa-envelope mr-1"></i> Email
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($keywords)): ?>
                            <div class="flex flex-wrap gap-2 mt-4">
                                <?php foreach ($keywords as $keyword): ?>
                                    <span class="text-xs bg-gray-100 text-gray-600 px-3 py-1 rounded-full">
                                        <?= htmlspecialchars($keyword['name']) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Article Metrics -->
                    <div class="flex flex-wrap gap-6 p-4 bg-gray-50 rounded-xl mb-6">
                        <div class="flex items-center gap-2">
                            <i class="far fa-eye text-gray-400"></i>
                            <span class="text-sm"><span class="font-semibold"><?= number_format($viewCount) ?></span> views</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="far fa-file-pdf text-gray-400"></i>
                            <span class="text-sm"><span class="font-semibold"><?= number_format($downloadCount) ?></span> downloads</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-quote-right text-gray-400"></i>
                            <span class="text-sm"><span class="font-semibold"><?= number_format($citationCount) ?></span> citations</span>
                        </div>
                    </div>

                    <!-- Abstract -->
                    <?php if (!empty($article['abstract'])): ?>
                        <div class="mb-6">
                            <h2 class="text-xl font-semibold text-[#0b2b3f] mb-3">Abstract</h2>
                            <div class="text-gray-700 leading-relaxed">
                                <?= nl2br(htmlspecialchars($article['abstract'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Full Article PDF Download -->
                    <div class="mb-6">
                        <h2 class="text-xl font-semibold text-[#0b2b3f] mb-3">Full Article</h2>
                        <?php if ($hasPdf): ?>
                            <div class="p-5 bg-gradient-to-r from-indigo-50 to-blue-50 rounded-xl border border-indigo-100">
                                <div class="flex items-center gap-4 flex-wrap">
                                    <div class="w-14 h-14 bg-red-100 rounded-xl flex items-center justify-center">
                                        <i class="fas fa-file-pdf text-3xl text-red-600"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-semibold text-[#0b2b3f]">Download PDF</p>
                                        <p class="text-sm text-gray-500">Download the complete article in PDF format</p>
                                    </div>
                                    <a href="<?= SITE_URL ?>?page=download&id=<?= $article['id'] ?>" 
                                       class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg transition flex items-center gap-2 shadow-md hover:shadow-lg">
                                        <i class="fas fa-download"></i> Download PDF
                                        <span class="text-xs bg-white/20 px-2 py-0.5 rounded-full">PDF</span>
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="p-5 bg-gray-50 rounded-xl border border-gray-200 text-center">
                                <i class="fas fa-file-pdf text-4xl text-gray-300 mb-2"></i>
                                <p class="text-gray-500">PDF version is not available for this article.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Additional Info -->
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <?php if ($article['funding_source']): ?>
                                <div>
                                    <span class="font-medium text-gray-500">Funding:</span>
                                    <p class="text-gray-700"><?= htmlspecialchars($article['funding_source']) ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if ($article['acknowledgments']): ?>
                                <div>
                                    <span class="font-medium text-gray-500">Acknowledgments:</span>
                                    <p class="text-gray-700"><?= htmlspecialchars($article['acknowledgments']) ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if ($article['has_conflict_of_interest']): ?>
                                <div class="md:col-span-2">
                                    <span class="font-medium text-gray-500">Conflict of Interest:</span>
                                    <p class="text-gray-700"><?= htmlspecialchars($article['conflicts'] ?? 'Declared') ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Citation -->
                    <div class="mt-6 p-4 bg-gray-50 rounded-xl border border-gray-200">
                        <p class="text-sm font-medium text-gray-500 mb-1">How to cite this article:</p>
                        <p class="text-sm text-gray-700 font-mono">
                            <?= htmlspecialchars($article['author_name'] ?? 'Author') ?> (<?= date('Y', strtotime($article['publication_date'] ?? $article['created_at'])) ?>). 
                            <?= htmlspecialchars($article['title']) ?>. 
                            <em><?= SITE_NAME ?></em>, 
                            <?php if ($article['volume_number']): ?>Vol. <?= $article['volume_number'] ?><?php endif; ?>
                            <?php if ($article['issue_number']): ?>(<?= $article['issue_number'] ?>)<?php endif; ?>
                            <?php if ($article['doi']): ?>DOI: <?= htmlspecialchars($article['doi']) ?><?php endif; ?>
                        </p>
                        <button onclick="copyCitation(this)" class="mt-2 text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                            <i class="fas fa-copy mr-1"></i> Copy citation
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Download -->
                <div class="bg-white rounded-xl shadow-card p-5 border border-gray-100/70">
                    <h4 class="font-semibold text-[#0b2b3f] mb-3 flex items-center gap-2">
                        <i class="fas fa-download text-indigo-500"></i> Download
                    </h4>
                    <?php if ($hasPdf): ?>
                        <a href="<?= SITE_URL ?>?page=download&id=<?= $article['id'] ?>" 
                           class="flex items-center justify-center gap-2 w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2.5 rounded-lg transition shadow-sm">
                            <i class="fas fa-file-pdf"></i> Download PDF
                        </a>
                        <p class="text-xs text-gray-400 mt-2 text-center"><?= number_format($downloadCount) ?> downloads</p>
                    <?php else: ?>
                        <p class="text-sm text-gray-500 text-center">PDF not available</p>
                    <?php endif; ?>
                </div>

                <!-- Share -->
                <div class="bg-white rounded-xl shadow-card p-5 border border-gray-100/70">
                    <h4 class="font-semibold text-[#0b2b3f] mb-3 flex items-center gap-2">
                        <i class="fas fa-share-alt text-indigo-500"></i> Share
                    </h4>
                    <div class="flex gap-3 justify-center">
                        <a href="https://twitter.com/intent/tweet?text=<?= urlencode($article['title']) ?>&url=<?= urlencode(SITE_URL . '?page=article&id=' . $article['id']) ?>" 
                           target="_blank" class="text-gray-400 hover:text-blue-400 transition text-xl">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(SITE_URL . '?page=article&id=' . $article['id']) ?>" 
                           target="_blank" class="text-gray-400 hover:text-blue-600 transition text-xl">
                            <i class="fab fa-facebook"></i>
                        </a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode(SITE_URL . '?page=article&id=' . $article['id']) ?>" 
                           target="_blank" class="text-gray-400 hover:text-blue-700 transition text-xl">
                            <i class="fab fa-linkedin"></i>
                        </a>
                        <a href="mailto:?subject=<?= urlencode($article['title']) ?>&body=<?= urlencode(SITE_URL . '?page=article&id=' . $article['id']) ?>" 
                           class="text-gray-400 hover:text-gray-600 transition text-xl">
                            <i class="fas fa-envelope"></i>
                        </a>
                    </div>
                </div>

                <!-- Related Articles -->
                <?php if (!empty($relatedArticles)): ?>
                    <div class="bg-white rounded-xl shadow-card p-5 border border-gray-100/70">
                        <h4 class="font-semibold text-[#0b2b3f] mb-3 flex items-center gap-2">
                            <i class="fas fa-link text-indigo-500"></i> Related Articles
                        </h4>
                        <div class="space-y-3">
                            <?php foreach ($relatedArticles as $related): ?>
                                <div class="border-b border-gray-100 pb-3 last:border-0 last:pb-0">
                                    <a href="<?= SITE_URL ?>?page=article&id=<?= $related['id'] ?>" class="hover:text-indigo-600 transition">
                                        <p class="text-sm font-medium"><?= htmlspecialchars(substr($related['title'], 0, 60)) ?>...</p>
                                        <p class="text-xs text-gray-400 mt-1"><?= htmlspecialchars($related['author_name'] ?? 'Unknown') ?></p>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    function copyCitation(button) {
        // Get the citation text from the parent element
        const citationElement = button.parentElement.querySelector('p');
        const citationText = citationElement.textContent.trim();
        
        // Create a temporary input element
        const tempInput = document.createElement('input');
        tempInput.value = citationText;
        document.body.appendChild(tempInput);
        
        // Select and copy the text
        tempInput.select();
        document.execCommand('copy');
        
        // Remove the temporary element
        document.body.removeChild(tempInput);
        
        // Show feedback
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check mr-1"></i> Copied!';
        setTimeout(() => {
            button.innerHTML = originalText;
        }, 2000);
    }
    </script>

    <?php include INCLUDES_PATH . 'footer.php'; ?>
</body>
</html>
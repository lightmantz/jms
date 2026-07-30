<?php
// modules/admin/pages/article-view.php - View Article Details
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

requireRole(['admin']);

$db = getDB();

$articleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$articleId) {
    echo '<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
            <div class="text-center py-12">
                <p class="text-gray-500">No article specified.</p>
                <a href="/jms/admin?action=articles" class="mt-3 inline-block bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Articles
                </a>
            </div>
        </div>';
    exit;
}

// Get article details
$sql = "SELECT m.*, u.full_name as author_name, u.email as author_email, u.institution as author_institution,
        e.full_name as editor_name,
        v.volume_number, i.issue_number, i.publication_date as issue_publication_date,
        (SELECT COUNT(*) FROM article_views WHERE manuscript_id = m.id) as view_count,
        (SELECT COUNT(*) FROM article_downloads WHERE manuscript_id = m.id) as download_count
        FROM manuscripts m
        LEFT JOIN users u ON m.corresponding_author_id = u.id
        LEFT JOIN users e ON m.editor_assigned_id = e.id
        LEFT JOIN issues i ON m.issue_id = i.id
        LEFT JOIN volumes v ON i.volume_id = v.id
        WHERE m.id = ?";

$stmt = $db->prepare($sql);
$stmt->execute([$articleId]);
$article = $stmt->fetch();

if (!$article) {
    echo '<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
            <div class="text-center py-12">
                <p class="text-gray-500">Article not found.</p>
                <a href="/jms/admin?action=articles" class="mt-3 inline-block bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Articles
                </a>
            </div>
        </div>';
    exit;
}

// Get categories for this article
$stmt = $db->prepare("
    SELECT c.* FROM categories c 
    JOIN manuscript_keywords mk ON c.id = mk.category_id 
    WHERE mk.manuscript_id = ?
");
$stmt->execute([$articleId]);
$categories = $stmt->fetchAll();
?>
<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Article Details</h2>
            <p class="text-gray-500 text-sm mt-1">View complete article information</p>
        </div>
        <div class="flex gap-3">
            <a href="/jms/admin?action=articles&subaction=<?= $article['status'] == 'published' ? 'published' : 'inpress' ?>" 
               class="text-indigo-600 hover:text-indigo-800 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
            <a href="/jms/?page=article&id=<?= $articleId ?>" target="_blank" 
               class="bg-[#0b2b3f] text-white px-4 py-2 rounded-lg hover:bg-[#123a4f] transition text-sm">
                <i class="fas fa-external-link-alt mr-1"></i> View Public
            </a>
        </div>
    </div>
    <div class="h-1 w-20 bg-indigo-200 rounded-full mb-6"></div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Title -->
            <div class="border-b border-gray-200 pb-4">
                <h3 class="text-xl font-bold text-[#0b2b3f]"><?= htmlspecialchars($article['title']) ?></h3>
                <div class="flex items-center gap-3 mt-2">
                    <span class="px-3 py-1 rounded-full text-sm font-medium <?= getStatusBadge($article['status']) ?>">
                        <?= ucfirst(str_replace('_', ' ', $article['status'])) ?>
                    </span>
                    <?php if ($article['doi']): ?>
                        <span class="text-sm text-indigo-600">DOI: <?= htmlspecialchars($article['doi']) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Abstract -->
            <div>
                <h4 class="font-semibold text-[#0b2b3f] mb-2">Abstract</h4>
                <p class="text-gray-600 text-sm leading-relaxed"><?= nl2br(htmlspecialchars($article['abstract'] ?? 'No abstract provided.')) ?></p>
            </div>

            <!-- Keywords/Categories -->
            <?php if (!empty($categories)): ?>
            <div>
                <h4 class="font-semibold text-[#0b2b3f] mb-2">Categories</h4>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($categories as $category): ?>
                        <span class="px-3 py-1 bg-indigo-50 text-indigo-700 rounded-full text-sm">
                            <?= htmlspecialchars($category['name']) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Additional Info -->
            <?php if ($article['funding_source'] || $article['acknowledgments']): ?>
            <div class="border-t border-gray-200 pt-4">
                <?php if ($article['funding_source']): ?>
                <div class="mb-3">
                    <h4 class="font-semibold text-[#0b2b3f] mb-1">Funding Source</h4>
                    <p class="text-gray-600 text-sm"><?= htmlspecialchars($article['funding_source']) ?></p>
                </div>
                <?php endif; ?>
                <?php if ($article['acknowledgments']): ?>
                <div>
                    <h4 class="font-semibold text-[#0b2b3f] mb-1">Acknowledgments</h4>
                    <p class="text-gray-600 text-sm"><?= nl2br(htmlspecialchars($article['acknowledgments'])) ?></p>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Publication Info -->
            <div class="border border-gray-200 rounded-xl p-4">
                <h4 class="font-semibold text-[#0b2b3f] mb-3">Publication Information</h4>
                <div class="space-y-2 text-sm">
                    <div>
                        <span class="text-gray-500">Volume:</span>
                        <span class="font-medium"><?= $article['volume_number'] ?? 'Not assigned' ?></span>
                    </div>
                    <div>
                        <span class="text-gray-500">Issue:</span>
                        <span class="font-medium"><?= $article['issue_number'] ?? 'Not assigned' ?></span>
                    </div>
                    <?php if ($article['publication_date']): ?>
                    <div>
                        <span class="text-gray-500">Published:</span>
                        <span class="font-medium"><?= formatDate($article['publication_date']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($article['page_start'] && $article['page_end']): ?>
                    <div>
                        <span class="text-gray-500">Pages:</span>
                        <span class="font-medium"><?= $article['page_start'] ?> - <?= $article['page_end'] ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Author Info -->
            <div class="border border-gray-200 rounded-xl p-4">
                <h4 class="font-semibold text-[#0b2b3f] mb-3">Author Information</h4>
                <div class="space-y-2 text-sm">
                    <div>
                        <span class="text-gray-500">Name:</span>
                        <span class="font-medium"><?= htmlspecialchars($article['author_name'] ?? 'Unknown') ?></span>
                    </div>
                    <?php if ($article['author_email']): ?>
                    <div>
                        <span class="text-gray-500">Email:</span>
                        <span class="font-medium"><?= htmlspecialchars($article['author_email']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($article['author_institution']): ?>
                    <div>
                        <span class="text-gray-500">Institution:</span>
                        <span class="font-medium"><?= htmlspecialchars($article['author_institution']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stats -->
            <div class="border border-gray-200 rounded-xl p-4">
                <h4 class="font-semibold text-[#0b2b3f] mb-3">Statistics</h4>
                <div class="grid grid-cols-2 gap-3 text-center">
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-2xl font-bold text-indigo-600"><?= number_format($article['view_count'] ?? 0) ?></p>
                        <p class="text-xs text-gray-500">Views</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-2xl font-bold text-green-600"><?= number_format($article['download_count'] ?? 0) ?></p>
                        <p class="text-xs text-gray-500">Downloads</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="border border-gray-200 rounded-xl p-4">
                <h4 class="font-semibold text-[#0b2b3f] mb-3">Quick Actions</h4>
                <div class="grid grid-cols-2 gap-2">
                    <a href="/jms/admin?action=manuscript&id=<?= $articleId ?>" 
                       class="text-center p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition text-sm">
                        <i class="fas fa-edit text-blue-600"></i>
                        <p class="text-xs mt-1">Edit</p>
                    </a>
                    <?php if ($article['status'] == 'published'): ?>
                    <form method="POST" action="/jms/admin?action=articles" class="inline" 
                          onsubmit="return confirm('Move this article to archives?')">
                        <input type="hidden" name="manuscript_id" value="<?= $articleId ?>">
                        <button type="submit" name="move_to_archives" 
                                class="w-full text-center p-3 bg-purple-50 rounded-lg hover:bg-purple-100 transition text-sm">
                            <i class="fas fa-archive text-purple-600"></i>
                            <p class="text-xs mt-1">Archive</p>
                        </button>
                    </form>
                    <?php else: ?>
                    <a href="/jms/admin?action=publication&id=<?= $articleId ?>" 
                       class="text-center p-3 bg-green-50 rounded-lg hover:bg-green-100 transition text-sm">
                        <i class="fas fa-check-circle text-green-600"></i>
                        <p class="text-xs mt-1">Publish</p>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
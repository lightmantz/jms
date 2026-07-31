<?php
// modules/editor/pages/publishing.php - Publishing
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

$db = getDB();
$currentUser = getCurrentUser();
$message = '';
$error = '';

// Handle publish action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publish'])) {
    $manuscript_id = (int)$_POST['manuscript_id'];
    
    $stmt = $db->prepare("
        UPDATE manuscripts 
        SET status = 'published', 
            published_at = NOW(),
            publication_date = COALESCE(publication_date, NOW()),
            updated_at = NOW()
        WHERE id = ? AND status = 'accepted'
    ");
    if ($stmt->execute([$manuscript_id])) {
        $message = 'Article published successfully!';
        logAction($currentUser['id'], 'publish_article', 'manuscripts', $manuscript_id);
        
        // Notify author
        $manuscript = getManuscript($manuscript_id);
        if ($manuscript && $manuscript['corresponding_author_id']) {
            createNotification(
                $manuscript['corresponding_author_id'],
                'published',
                'Article Published',
                'Your article "' . $manuscript['title'] . '" has been published!',
                SITE_URL . '/article/' . $manuscript_id
            );
        }
    } else {
        $error = 'Failed to publish article.';
    }
}

// Get manuscripts ready for publishing (accepted and scheduled)
$stmt = $db->query("
    SELECT m.*, u.full_name as author_name,
           v.volume_number, i.issue_number,
           i.publication_date as issue_publication_date
    FROM manuscripts m
    LEFT JOIN users u ON m.corresponding_author_id = u.id
    LEFT JOIN issues i ON m.issue_id = i.id
    LEFT JOIN volumes v ON i.volume_id = v.id
    WHERE m.status = 'accepted' AND m.issue_id IS NOT NULL
    ORDER BY m.publication_date ASC, m.accepted_at ASC
");
$readyToPublish = $stmt->fetchAll();

// Get published articles
$stmt = $db->query("
    SELECT m.*, u.full_name as author_name,
           v.volume_number, i.issue_number,
           (SELECT COUNT(*) FROM article_views WHERE manuscript_id = m.id) as views,
           (SELECT COUNT(*) FROM article_downloads WHERE manuscript_id = m.id) as downloads
    FROM manuscripts m
    LEFT JOIN users u ON m.corresponding_author_id = u.id
    LEFT JOIN issues i ON m.issue_id = i.id
    LEFT JOIN volumes v ON i.volume_id = v.id
    WHERE m.status = 'published'
    ORDER BY m.publication_date DESC
    LIMIT 10
");
$publishedArticles = $stmt->fetchAll();
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Publishing</h2>
            <p class="text-gray-500 text-sm mt-1">Publish articles and manage publications</p>
        </div>
        <a href="/jms/editor" class="text-indigo-600 hover:text-indigo-800 text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back
        </a>
    </div>
    <div class="h-1 w-20 bg-indigo-200 rounded-full"></div>

    <?php if ($message): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
            <i class="fas fa-check-circle mr-2"></i> <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
            <i class="fas fa-exclamation-circle mr-2"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="grid md:grid-cols-2 gap-6">
        <!-- Ready to Publish -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">Ready to Publish</h3>
            <?php if (empty($readyToPublish)): ?>
                <p class="text-sm text-gray-500">No manuscripts ready for publishing.</p>
            <?php else: ?>
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    <?php foreach ($readyToPublish as $manuscript): ?>
                    <div class="border border-green-200 rounded-lg p-3 hover:shadow-md transition">
                        <p class="font-medium text-[#0b2b3f]"><?= htmlspecialchars(substr($manuscript['title'], 0, 40)) ?>...</p>
                        <div class="flex items-center gap-2 text-xs text-gray-500 mt-1">
                            <span>Author: <?= htmlspecialchars($manuscript['author_name'] ?? 'Unknown') ?></span>
                            <?php if ($manuscript['volume_number'] && $manuscript['issue_number']): ?>
                                <span>Vol. <?= $manuscript['volume_number'] ?> No. <?= $manuscript['issue_number'] ?></span>
                            <?php endif; ?>
                            <?php if ($manuscript['publication_date']): ?>
                                <span>Scheduled: <?= formatDate($manuscript['publication_date']) ?></span>
                            <?php endif; ?>
                        </div>
                        <form method="POST" class="mt-2">
                            <input type="hidden" name="manuscript_id" value="<?= $manuscript['id'] ?>">
                            <button type="submit" name="publish" class="bg-green-600 text-white px-4 py-1 rounded-lg hover:bg-green-700 transition text-sm"
                                    onclick="return confirm('Publish this article? It will be publicly available.')">
                                <i class="fas fa-check-circle mr-1"></i> Publish Now
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Published Articles -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">Recently Published</h3>
            <?php if (empty($publishedArticles)): ?>
                <p class="text-sm text-gray-500">No published articles yet.</p>
            <?php else: ?>
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    <?php foreach ($publishedArticles as $article): ?>
                    <div class="border border-gray-200 rounded-lg p-3 hover:shadow-md transition">
                        <p class="font-medium text-[#0b2b3f]"><?= htmlspecialchars(substr($article['title'], 0, 40)) ?>...</p>
                        <div class="flex items-center gap-2 text-xs text-gray-500 mt-1">
                            <span>Author: <?= htmlspecialchars($article['author_name'] ?? 'Unknown') ?></span>
                            <?php if ($article['volume_number'] && $article['issue_number']): ?>
                                <span>Vol. <?= $article['volume_number'] ?> No. <?= $article['issue_number'] ?></span>
                            <?php endif; ?>
                            <span>· Views: <?= number_format($article['views'] ?? 0) ?></span>
                            <span>· Downloads: <?= number_format($article['downloads'] ?? 0) ?></span>
                        </div>
                        <div class="mt-2 flex gap-2">
                            <a href="/jms/?page=article&id=<?= $article['id'] ?>" target="_blank" 
                               class="text-indigo-600 hover:text-indigo-800 text-sm">
                                <i class="fas fa-external-link-alt mr-1"></i> View
                            </a>
                            <a href="/jms/publisher?action=doi&id=<?= $article['id'] ?>" 
                               class="text-blue-600 hover:text-blue-800 text-sm">
                                <i class="fas fa-link mr-1"></i> DOI
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
// modules/publisher/pages/featured.php - Featured Articles Management
// This file is included by modules/publisher/index.php when action=featured

if (!defined('SITE_URL')) {
    require_once dirname(dirname(dirname(__DIR__))) . '/includes/init.php';
}

$db = getDB();
$message = '';
$error = '';

// Handle featured status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $manuscriptId = isset($_POST['manuscript_id']) ? (int)$_POST['manuscript_id'] : 0;
    
    if ($_POST['action'] === 'feature') {
        // Check if the column exists, if not we'll handle it differently
        try {
            $stmt = $db->prepare("UPDATE manuscripts SET is_featured = 1 WHERE id = ?");
            $stmt->execute([$manuscriptId]);
            $message = 'Article featured successfully!';
        } catch (PDOException $e) {
            $error = 'Could not update featured status: ' . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'unfeature') {
        try {
            $stmt = $db->prepare("UPDATE manuscripts SET is_featured = 0 WHERE id = ?");
            $stmt->execute([$manuscriptId]);
            $message = 'Article removed from featured!';
        } catch (PDOException $e) {
            $error = 'Could not update featured status: ' . $e->getMessage();
        }
    }
}

// Get featured articles
try {
    // Check if is_featured column exists
    $stmt = $db->query("SHOW COLUMNS FROM manuscripts LIKE 'is_featured'");
    $hasFeaturedColumn = $stmt->rowCount() > 0;
    
    if ($hasFeaturedColumn) {
        $stmt = $db->query("
            SELECT m.*, u.full_name as author_name
            FROM manuscripts m
            LEFT JOIN users u ON m.corresponding_author_id = u.id
            WHERE m.is_featured = 1
            ORDER BY m.updated_at DESC, m.created_at DESC
        ");
        $featuredArticles = $stmt->fetchAll();
    } else {
        $featuredArticles = [];
        $error = 'The "is_featured" column does not exist in the manuscripts table. Please add it first.';
    }
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
    $featuredArticles = [];
}

// Get all published articles (for featuring)
try {
    $stmt = $db->query("
        SELECT m.*, u.full_name as author_name
        FROM manuscripts m
        LEFT JOIN users u ON m.corresponding_author_id = u.id
        WHERE m.status = 'published'
        AND (m.is_featured = 0 OR m.is_featured IS NULL)
        ORDER BY m.publication_date DESC, m.created_at DESC
        LIMIT 50
    ");
    $allArticles = $stmt->fetchAll();
} catch (PDOException $e) {
    $allArticles = [];
}
?>
<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-[#0b2b3f]">Featured Articles</h2>
        <span class="text-sm text-gray-500">Manage which articles appear in the featured section</span>
    </div>
    
    <?php if ($message): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">
            <i class="fas fa-check-circle mr-2"></i> <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
            <i class="fas fa-exclamation-circle mr-2"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <!-- Featured Articles List -->
    <div class="mb-8">
        <h3 class="text-lg font-semibold text-[#0b2b3f] mb-3 flex items-center gap-2">
            <i class="fas fa-star text-yellow-500"></i> Currently Featured
            <span class="text-sm font-normal text-gray-500">(<?= count($featuredArticles) ?> articles)</span>
        </h3>
        
        <?php if (empty($featuredArticles)): ?>
            <div class="text-center py-8 bg-gray-50 rounded-lg border border-gray-200">
                <i class="fas fa-star text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">No featured articles yet.</p>
                <p class="text-sm text-gray-400">Use the list below to feature articles.</p>
            </div>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($featuredArticles as $article): ?>
                <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg border border-yellow-200 hover:bg-yellow-100 transition">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-star text-yellow-500"></i>
                            <p class="text-sm font-medium text-[#0b2b3f]"><?= htmlspecialchars(substr($article['title'], 0, 60)) ?>...</p>
                        </div>
                        <div class="flex items-center gap-3 text-xs text-gray-500 mt-1">
                            <span>Author: <?= htmlspecialchars($article['author_name'] ?? 'Unknown') ?></span>
                            <span>· DOI: <?= htmlspecialchars($article['doi'] ?? 'N/A') ?></span>
                            <span>· Featured: <?= formatDate($article['updated_at'] ?? $article['created_at']) ?></span>
                        </div>
                    </div>
                    <form method="POST" action="" class="ml-4">
                        <input type="hidden" name="action" value="unfeature">
                        <input type="hidden" name="manuscript_id" value="<?= $article['id'] ?>">
                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium" 
                                onclick="return confirm('Remove this article from featured?')">
                            <i class="fas fa-star-half-alt mr-1"></i> Unfeature
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- All Articles (Available to Feature) -->
    <div>
        <h3 class="text-lg font-semibold text-[#0b2b3f] mb-3 flex items-center gap-2">
            <i class="fas fa-list text-indigo-500"></i> Available Articles
            <span class="text-sm font-normal text-gray-500">(<?= count($allArticles) ?> articles)</span>
        </h3>
        
        <?php if (empty($allArticles)): ?>
            <div class="text-center py-8 bg-gray-50 rounded-lg border border-gray-200">
                <i class="fas fa-file-alt text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">No published articles available to feature.</p>
            </div>
        <?php else: ?>
            <div class="space-y-3 max-h-96 overflow-y-auto pr-2">
                <?php foreach ($allArticles as $article): ?>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200 hover:bg-gray-100 transition">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-[#0b2b3f]"><?= htmlspecialchars(substr($article['title'], 0, 60)) ?>...</p>
                        <div class="flex items-center gap-3 text-xs text-gray-500 mt-1">
                            <span>Author: <?= htmlspecialchars($article['author_name'] ?? 'Unknown') ?></span>
                            <span>· DOI: <?= htmlspecialchars($article['doi'] ?? 'N/A') ?></span>
                            <span>· Published: <?= formatDate($article['publication_date'] ?? $article['created_at']) ?></span>
                        </div>
                    </div>
                    <form method="POST" action="" class="ml-4">
                        <input type="hidden" name="action" value="feature">
                        <input type="hidden" name="manuscript_id" value="<?= $article['id'] ?>">
                        <button type="submit" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                            <i class="fas fa-star mr-1"></i> Feature
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- SQL to add is_featured column if needed -->
    <?php if (strpos($error ?? '', 'is_featured') !== false): ?>
    <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
        <p class="text-sm text-yellow-700 font-medium">
            <i class="fas fa-info-circle mr-2"></i> The 'is_featured' column is missing from your database.
        </p>
        <p class="text-xs text-yellow-600 mt-1">Run this SQL to add it:</p>
        <pre class="mt-2 p-2 bg-gray-800 text-gray-200 text-xs rounded overflow-x-auto">
ALTER TABLE manuscripts ADD COLUMN is_featured TINYINT(1) DEFAULT 0;
UPDATE manuscripts SET is_featured = 0 WHERE is_featured IS NULL;
        </pre>
    </div>
    <?php endif; ?>
</div>
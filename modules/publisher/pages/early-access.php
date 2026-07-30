<?php
// modules/publisher/pages/early-access.php - Early Access Management
// This file is included by modules/publisher/index.php when action=early-access

if (!defined('SITE_URL')) {
    require_once dirname(dirname(dirname(__DIR__))) . '/includes/init.php';
}

$db = getDB();
$message = '';
$error = '';

// Check if early_access column exists
try {
    $stmt = $db->query("SHOW COLUMNS FROM manuscripts LIKE 'early_access'");
    $hasEarlyAccess = $stmt->rowCount() > 0;
} catch (PDOException $e) {
    $hasEarlyAccess = false;
}

// Handle early access status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $manuscriptId = isset($_POST['manuscript_id']) ? (int)$_POST['manuscript_id'] : 0;
    
    if ($_POST['action'] === 'enable_early_access') {
        try {
            $stmt = $db->prepare("UPDATE manuscripts SET early_access = 1, early_access_date = NOW() WHERE id = ?");
            $stmt->execute([$manuscriptId]);
            $message = 'Early access enabled successfully!';
        } catch (PDOException $e) {
            // Try without early_access_date if column doesn't exist
            try {
                $stmt = $db->prepare("UPDATE manuscripts SET early_access = 1 WHERE id = ?");
                $stmt->execute([$manuscriptId]);
                $message = 'Early access enabled successfully!';
            } catch (PDOException $e2) {
                $error = 'Could not enable early access: ' . $e2->getMessage();
            }
        }
    } elseif ($_POST['action'] === 'disable_early_access') {
        try {
            $stmt = $db->prepare("UPDATE manuscripts SET early_access = 0 WHERE id = ?");
            $stmt->execute([$manuscriptId]);
            $message = 'Early access disabled successfully!';
        } catch (PDOException $e) {
            $error = 'Could not disable early access: ' . $e->getMessage();
        }
    }
}

// Get early access articles
try {
    if ($hasEarlyAccess) {
        $stmt = $db->query("
            SELECT m.*, u.full_name as author_name
            FROM manuscripts m
            LEFT JOIN users u ON m.corresponding_author_id = u.id
            WHERE m.status = 'accepted' AND m.early_access = 1
            ORDER BY m.early_access_date DESC, m.accepted_at DESC
        ");
        $earlyAccessArticles = $stmt->fetchAll();
    } else {
        $earlyAccessArticles = [];
    }
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
    $earlyAccessArticles = [];
}

// Get accepted articles (available for early access)
try {
    $stmt = $db->query("
        SELECT m.*, u.full_name as author_name
        FROM manuscripts m
        LEFT JOIN users u ON m.corresponding_author_id = u.id
        WHERE m.status = 'accepted'
        ORDER BY m.accepted_at DESC
        LIMIT 50
    ");
    $acceptedArticles = $stmt->fetchAll();
    
    // Filter out articles already in early access
    if ($hasEarlyAccess && !empty($earlyAccessArticles)) {
        $earlyAccessIds = array_column($earlyAccessArticles, 'id');
        $acceptedArticles = array_filter($acceptedArticles, function($article) use ($earlyAccessIds) {
            return !in_array($article['id'], $earlyAccessIds);
        });
    }
} catch (PDOException $e) {
    $acceptedArticles = [];
}
?>
<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-[#0b2b3f]">Early Access</h2>
        <span class="text-sm text-gray-500">Manage early access articles</span>
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
    
    <!-- Early Access Articles List -->
    <div class="mb-8">
        <h3 class="text-lg font-semibold text-[#0b2b3f] mb-3 flex items-center gap-2">
            <i class="fas fa-rocket text-indigo-500"></i> Early Access Articles
            <span class="text-sm font-normal text-gray-500">(<?= count($earlyAccessArticles) ?> articles)</span>
        </h3>
        
        <?php if (empty($earlyAccessArticles)): ?>
            <div class="text-center py-8 bg-gray-50 rounded-lg border border-gray-200">
                <i class="fas fa-rocket text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">No early access articles.</p>
                <p class="text-sm text-gray-400">Articles in "Accepted" status can be made available for early access.</p>
            </div>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($earlyAccessArticles as $article): ?>
                <div class="flex items-center justify-between p-3 bg-indigo-50 rounded-lg border border-indigo-200 hover:bg-indigo-100 transition">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-rocket text-indigo-500"></i>
                            <p class="text-sm font-medium text-[#0b2b3f]"><?= htmlspecialchars(substr($article['title'], 0, 60)) ?>...</p>
                        </div>
                        <div class="flex items-center gap-3 text-xs text-gray-500 mt-1">
                            <span>Author: <?= htmlspecialchars($article['author_name'] ?? 'Unknown') ?></span>
                            <span>· DOI: <?= htmlspecialchars($article['doi'] ?? 'N/A') ?></span>
                            <span>· <?= $article['early_access_date'] ? 'Started: ' . formatDate($article['early_access_date']) : 'Early Access' ?></span>
                        </div>
                    </div>
                    <form method="POST" action="" class="ml-4">
                        <input type="hidden" name="action" value="disable_early_access">
                        <input type="hidden" name="manuscript_id" value="<?= $article['id'] ?>">
                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium" 
                                onclick="return confirm('Remove this article from early access?')">
                            <i class="fas fa-times-circle mr-1"></i> Remove
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Accepted Articles (Available for Early Access) -->
    <div>
        <h3 class="text-lg font-semibold text-[#0b2b3f] mb-3 flex items-center gap-2">
            <i class="fas fa-check-circle text-green-500"></i> Accepted Articles
            <span class="text-sm font-normal text-gray-500">(<?= count($acceptedArticles) ?> articles)</span>
        </h3>
        
        <?php if (empty($acceptedArticles)): ?>
            <div class="text-center py-8 bg-gray-50 rounded-lg border border-gray-200">
                <i class="fas fa-check-circle text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">No accepted articles available for early access.</p>
            </div>
        <?php else: ?>
            <div class="space-y-3 max-h-96 overflow-y-auto pr-2">
                <?php foreach ($acceptedArticles as $article): ?>
                <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg border border-green-200 hover:bg-green-100 transition">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-[#0b2b3f]"><?= htmlspecialchars(substr($article['title'], 0, 60)) ?>...</p>
                        <div class="flex items-center gap-3 text-xs text-gray-500 mt-1">
                            <span>Author: <?= htmlspecialchars($article['author_name'] ?? 'Unknown') ?></span>
                            <span>· DOI: <?= htmlspecialchars($article['doi'] ?? 'N/A') ?></span>
                            <span>· Accepted: <?= formatDate($article['accepted_at'] ?? $article['created_at']) ?></span>
                        </div>
                    </div>
                    <form method="POST" action="" class="ml-4">
                        <input type="hidden" name="action" value="enable_early_access">
                        <input type="hidden" name="manuscript_id" value="<?= $article['id'] ?>">
                        <button type="submit" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                            <i class="fas fa-rocket mr-1"></i> Enable Early Access
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- SQL to add early_access columns if needed -->
    <?php if (!$hasEarlyAccess): ?>
    <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
        <p class="text-sm text-yellow-700 font-medium">
            <i class="fas fa-info-circle mr-2"></i> The 'early_access' column is missing from your database.
        </p>
        <p class="text-xs text-yellow-600 mt-1">Run this SQL to add it:</p>
        <pre class="mt-2 p-2 bg-gray-800 text-gray-200 text-xs rounded overflow-x-auto">
ALTER TABLE manuscripts ADD COLUMN early_access TINYINT(1) DEFAULT 0;
ALTER TABLE manuscripts ADD COLUMN early_access_date DATETIME DEFAULT NULL;
ALTER TABLE manuscripts ADD INDEX idx_early_access (early_access);
        </pre>
    </div>
    <?php endif; ?>
</div>
<?php
// modules/publisher/pages/continuous.php - Continuous Publishing
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

$db = getDB();
$currentUser = getCurrentUser();
$message = '';
$error = '';

// Get accepted manuscripts ready for publishing
$stmt = $db->query("
    SELECT m.*, u.full_name as author_name,
           v.volume_number, i.issue_number
    FROM manuscripts m
    LEFT JOIN users u ON m.corresponding_author_id = u.id
    LEFT JOIN issues i ON m.issue_id = i.id
    LEFT JOIN volumes v ON i.volume_id = v.id
    WHERE m.status = 'accepted'
    ORDER BY m.accepted_at ASC
");
$manuscripts = $stmt->fetchAll();

// Get recently published articles
$stmt = $db->query("
    SELECT m.*, u.full_name as author_name,
           v.volume_number, i.issue_number,
           m.publication_date
    FROM manuscripts m
    LEFT JOIN users u ON m.corresponding_author_id = u.id
    LEFT JOIN issues i ON m.issue_id = i.id
    LEFT JOIN volumes v ON i.volume_id = v.id
    WHERE m.status = 'published'
    ORDER BY m.publication_date DESC
    LIMIT 10
");
$recentlyPublished = $stmt->fetchAll();

// Get volumes and issues
$volumes = getVolumes();

// Handle continuous publishing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publish_continuous'])) {
    $manuscript_id = (int)$_POST['manuscript_id'];
    $issue_id = (int)$_POST['issue_id'];
    $publication_date = $_POST['publication_date'] ?? date('Y-m-d');
    $page_start = $_POST['page_start'] ?? null;
    $page_end = $_POST['page_end'] ?? null;
    
    if ($issue_id <= 0) {
        $error = 'Please select an issue.';
    } else {
        if (publishArticle($manuscript_id, $issue_id, $page_start, $page_end)) {
            $message = 'Article published continuously!';
            logAction($currentUser['id'], 'continuous_publish', 'manuscripts', $manuscript_id);
            // Refresh data
            $stmt = $db->query("
                SELECT m.*, u.full_name as author_name,
                       v.volume_number, i.issue_number
                FROM manuscripts m
                LEFT JOIN users u ON m.corresponding_author_id = u.id
                LEFT JOIN issues i ON m.issue_id = i.id
                LEFT JOIN volumes v ON i.volume_id = v.id
                WHERE m.status = 'accepted'
                ORDER BY m.accepted_at ASC
            ");
            $manuscripts = $stmt->fetchAll();
        } else {
            $error = 'Failed to publish article.';
        }
    }
}
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Continuous Publishing</h2>
            <p class="text-gray-500 text-sm mt-1">Publish articles as they are accepted</p>
        </div>
        <a href="/jms/publisher" class="text-indigo-600 hover:text-indigo-800 text-sm">
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

    <?php if (empty($manuscripts)): ?>
        <div class="text-center py-12">
            <i class="fas fa-check-circle text-5xl text-green-300 mb-4"></i>
            <p class="text-gray-500">No manuscripts waiting to be published.</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($manuscripts as $manuscript): ?>
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h4 class="font-semibold text-[#0b2b3f]"><?= htmlspecialchars($manuscript['title']) ?></h4>
                        <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500 mt-1">
                            <span>Author: <?= htmlspecialchars($manuscript['author_name'] ?? 'Unknown') ?></span>
                            <span>Accepted: <?= formatDate($manuscript['accepted_at']) ?></span>
                            <?php if ($manuscript['doi']): ?>
                                <span class="text-indigo-600">DOI: <?= htmlspecialchars($manuscript['doi']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= getStatusBadge($manuscript['status']) ?>">
                        <?= ucfirst($manuscript['status']) ?>
                    </span>
                </div>

                <form method="POST" class="grid md:grid-cols-3 gap-4">
                    <input type="hidden" name="manuscript_id" value="<?= $manuscript['id'] ?>">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Issue *</label>
                        <select name="issue_id" required class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none transition">
                            <option value="">Select Issue...</option>
                            <?php foreach ($volumes as $volume): ?>
                                <optgroup label="Volume <?= $volume['volume_number'] ?>">
                                <?php $issues = getIssuesByVolume($volume['id']); ?>
                                <?php foreach ($issues as $issue): ?>
                                <option value="<?= $issue['id'] ?>">
                                    Issue <?= $issue['issue_number'] ?> - <?= htmlspecialchars($issue['title'] ?? 'No title') ?>
                                </option>
                                <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Publication Date</label>
                        <input type="date" name="publication_date" value="<?= date('Y-m-d') ?>"
                               class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none transition">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" name="publish_continuous" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition w-full">
                            <i class="fas fa-check-circle mr-2"></i> Publish Now
                        </button>
                    </div>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Recently Published -->
    <?php if (!empty($recentlyPublished)): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">Recently Published</h3>
        <div class="space-y-2">
            <?php foreach ($recentlyPublished as $article): ?>
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                <div>
                    <p class="font-medium text-[#0b2b3f]"><?= htmlspecialchars(substr($article['title'], 0, 40)) ?>...</p>
                    <p class="text-xs text-gray-500">
                        Vol. <?= $article['volume_number'] ?> No. <?= $article['issue_number'] ?> · <?= formatDate($article['publication_date']) ?>
                    </p>
                </div>
                <a href="/jms/?page=article&id=<?= $article['id'] ?>" target="_blank" 
                   class="text-indigo-600 hover:text-indigo-800 text-sm">
                    <i class="fas fa-external-link-alt"></i>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
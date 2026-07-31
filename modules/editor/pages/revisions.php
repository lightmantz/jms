<?php
// modules/editor/pages/revisions.php - Revision Requests
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

$db = getDB();
$currentUser = getCurrentUser();
$message = '';
$error = '';

// Get manuscripts with revisions requested
$stmt = $db->query("
    SELECT m.*, u.full_name as author_name,
           (SELECT COUNT(*) FROM revisions WHERE manuscript_id = m.id) as revision_count,
           (SELECT MAX(submitted_at) FROM revisions WHERE manuscript_id = m.id) as last_revision_date
    FROM manuscripts m
    LEFT JOIN users u ON m.corresponding_author_id = u.id
    WHERE m.status = 'revision_required'
    ORDER BY m.updated_at DESC
");
$revisionManuscripts = $stmt->fetchAll();

// Get revision history for specific manuscript
$manuscriptId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$revisionHistory = [];
if ($manuscriptId > 0) {
    $stmt = $db->prepare("
        SELECT r.*, u.full_name as user_name
        FROM revisions r
        JOIN users u ON r.user_id = u.id
        WHERE r.manuscript_id = ?
        ORDER BY r.submitted_at DESC
    ");
    $stmt->execute([$manuscriptId]);
    $revisionHistory = $stmt->fetchAll();
}
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Revision Requests</h2>
            <p class="text-gray-500 text-sm mt-1">Track manuscripts requiring revisions</p>
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

    <?php if ($manuscriptId > 0 && !empty($revisionHistory)): ?>
        <!-- Revision History -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-[#0b2b3f]">Revision History</h3>
                <a href="/jms/editor?action=revisions" class="text-indigo-600 hover:text-indigo-800 text-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Back to list
                </a>
            </div>
            <div class="space-y-3">
                <?php foreach ($revisionHistory as $revision): ?>
                <div class="border-b border-gray-100 pb-3 last:border-0">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium">Revision #<?= $revision['id'] ?></p>
                            <p class="text-xs text-gray-500">Submitted by <?= htmlspecialchars($revision['user_name']) ?></p>
                        </div>
                        <p class="text-xs text-gray-400"><?= formatDate($revision['submitted_at']) ?></p>
                    </div>
                    <?php if ($revision['comments']): ?>
                    <p class="text-sm text-gray-600 mt-1"><?= nl2br(htmlspecialchars($revision['comments'])) ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Manuscripts Requiring Revision -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">
            Manuscripts Requiring Revision (<?= count($revisionManuscripts) ?>)
        </h3>
        <?php if (empty($revisionManuscripts)): ?>
            <p class="text-sm text-gray-500">No manuscripts currently require revisions.</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($revisionManuscripts as $manuscript): ?>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                    <div>
                        <p class="font-medium text-[#0b2b3f]"><?= htmlspecialchars(substr($manuscript['title'], 0, 50)) ?>...</p>
                        <div class="flex items-center gap-2 text-xs text-gray-500">
                            <span>Author: <?= htmlspecialchars($manuscript['author_name'] ?? 'Unknown') ?></span>
                            <span>· Revisions: <?= $manuscript['revision_count'] ?? 0 ?></span>
                            <?php if ($manuscript['last_revision_date']): ?>
                                <span>· Last: <?= timeAgo($manuscript['last_revision_date']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <a href="/jms/editor?action=decision&id=<?= $manuscript['id'] ?>" 
                           class="text-indigo-600 hover:text-indigo-800 text-sm px-3 py-1 rounded hover:bg-indigo-50 transition">
                            <i class="fas fa-eye mr-1"></i> Review
                        </a>
                        <a href="/jms/editor?action=revisions&id=<?= $manuscript['id'] ?>" 
                           class="text-blue-600 hover:text-blue-800 text-sm px-3 py-1 rounded hover:bg-blue-50 transition">
                            <i class="fas fa-history mr-1"></i> History
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
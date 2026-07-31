<?php
// modules/author/pages/drafts.php - Saved Drafts
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

$db = getDB();
$currentUser = getCurrentUser();

// Get user's drafts
$stmt = $db->prepare("
    SELECT m.*, 
           (SELECT COUNT(*) FROM reviews WHERE manuscript_id = m.id) as review_count
    FROM manuscripts m
    WHERE m.corresponding_author_id = ? AND m.status = 'draft'
    ORDER BY m.created_at DESC
");
$stmt->execute([$currentUser['id']]);
$drafts = $stmt->fetchAll();
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Saved Drafts</h2>
            <p class="text-gray-500 text-sm mt-1">Continue working on your draft manuscripts</p>
        </div>
        <a href="/jms/author?action=new-submission" class="bg-[#0b2b3f] text-white px-4 py-2 rounded-lg hover:bg-[#123a4f] transition text-sm">
            <i class="fas fa-plus mr-1"></i> New Submission
        </a>
    </div>
    <div class="h-1 w-20 bg-indigo-200 rounded-full"></div>

    <?php if (empty($drafts)): ?>
        <div class="text-center py-12">
            <i class="fas fa-save text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No saved drafts found.</p>
            <a href="/jms/author?action=new-submission" class="mt-3 inline-block bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                <i class="fas fa-plus mr-2"></i> Create New Submission
            </a>
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($drafts as $draft): ?>
            <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <h4 class="font-semibold text-[#0b2b3f]"><?= htmlspecialchars($draft['title']) ?></h4>
                        <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500 mt-1">
                            <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">Draft</span>
                            <span>Created: <?= formatDate($draft['created_at']) ?></span>
                            <span>Last modified: <?= timeAgo($draft['updated_at'] ?? $draft['created_at']) ?></span>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <a href="/jms/author?action=new-submission&edit=<?= $draft['id'] ?>" 
                           class="text-indigo-600 hover:text-indigo-800 text-sm px-3 py-1 rounded hover:bg-indigo-50 transition">
                            <i class="fas fa-edit mr-1"></i> Edit
                        </a>
                        <a href="/jms/author?action=track&id=<?= $draft['id'] ?>" 
                           class="text-green-600 hover:text-green-800 text-sm px-3 py-1 rounded hover:bg-green-50 transition">
                            <i class="fas fa-paper-plane mr-1"></i> Submit
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-4 text-sm text-gray-400">
            Showing <?= count($drafts) ?> drafts
        </div>
    <?php endif; ?>
</div>
<?php
// modules/editor/pages/scheduling.php - Scheduling
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

$db = getDB();
$currentUser = getCurrentUser();
$message = '';
$error = '';

// Get accepted manuscripts waiting for scheduling
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

// Get volumes and issues
$volumes = getVolumes();

// Handle scheduling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule'])) {
    $manuscript_id = (int)$_POST['manuscript_id'];
    $issue_id = (int)$_POST['issue_id'];
    $page_start = $_POST['page_start'] ?? null;
    $page_end = $_POST['page_end'] ?? null;
    $publication_date = $_POST['publication_date'] ?? null;
    
    if ($issue_id <= 0) {
        $error = 'Please select an issue.';
    } else {
        $stmt = $db->prepare("
            UPDATE manuscripts 
            SET issue_id = ?, 
                page_start = ?, 
                page_end = ?, 
                publication_date = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        if ($stmt->execute([$issue_id, $page_start, $page_end, $publication_date, $manuscript_id])) {
            $message = 'Manuscript scheduled successfully!';
            logAction($currentUser['id'], 'schedule_manuscript', 'manuscripts', $manuscript_id);
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
            $error = 'Failed to schedule manuscript.';
        }
    }
}
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Scheduling</h2>
            <p class="text-gray-500 text-sm mt-1">Schedule accepted manuscripts for publication</p>
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

    <?php if (empty($manuscripts)): ?>
        <div class="text-center py-12">
            <i class="fas fa-calendar-check text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No manuscripts waiting for scheduling.</p>
            <a href="/jms/editor?action=acceptance" class="mt-3 inline-block bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                <i class="fas fa-check-circle mr-2"></i> Review Acceptances
            </a>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($manuscripts as $manuscript): ?>
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h4 class="font-semibold text-[#0b2b3f]"><?= htmlspecialchars($manuscript['title']) ?></h4>
                        <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500 mt-1">
                            <span>Author: <?= htmlspecialchars($manuscript['author_name'] ?? 'Unknown') ?></span>
                            <span>Accepted: <?= formatDate($manuscript['accepted_at']) ?></span>
                            <?php if ($manuscript['volume_number'] && $manuscript['issue_number']): ?>
                                <span class="text-green-600">Vol. <?= $manuscript['volume_number'] ?> No. <?= $manuscript['issue_number'] ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($manuscript['issue_id']): ?>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Scheduled</span>
                    <?php endif; ?>
                </div>

                <?php if (!$manuscript['issue_id']): ?>
                <form method="POST" class="mt-4 grid md:grid-cols-2 gap-4">
                    <input type="hidden" name="manuscript_id" value="<?= $manuscript['id'] ?>">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Issue *</label>
                        <select name="issue_id" required class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none transition">
                            <option value="">Select Issue...</option>
                            <?php foreach ($volumes as $volume): ?>
                                <optgroup label="Volume <?= $volume['volume_number'] ?> (<?= $volume['year'] ?>)">
                                <?php $issues = getIssuesByVolume($volume['id']); ?>
                                <?php foreach ($issues as $issue): ?>
                                <option value="<?= $issue['id'] ?>">
                                    Issue <?= $issue['issue_number'] ?> - <?= htmlspecialchars($issue['title'] ?? 'No title') ?>
                                    (<?= $issue['article_count'] ?? 0 ?> articles)
                                </option>
                                <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Start Page</label>
                            <input type="number" name="page_start" min="1" 
                                   class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">End Page</label>
                            <input type="number" name="page_end" min="1" 
                                   class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none transition">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Publication Date</label>
                        <input type="date" name="publication_date" value="<?= date('Y-m-d', strtotime('+1 month')) ?>"
                               class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none transition">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" name="schedule" class="bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                            <i class="fas fa-calendar-plus mr-2"></i> Schedule
                        </button>
                    </div>
                </form>
                <?php else: ?>
                <div class="mt-4 flex gap-3">
                    <a href="/jms/publisher?action=publication-date&id=<?= $manuscript['id'] ?>" 
                       class="text-indigo-600 hover:text-indigo-800 text-sm">
                        <i class="fas fa-edit mr-1"></i> Edit Schedule
                    </a>
                    <a href="/jms/publisher?action=doi&id=<?= $manuscript['id'] ?>" 
                       class="text-blue-600 hover:text-blue-800 text-sm">
                        <i class="fas fa-link mr-1"></i> Assign DOI
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
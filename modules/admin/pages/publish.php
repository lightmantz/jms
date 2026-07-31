<?php
// modules/admin/pages/publish.php - Publish Manuscript
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

requireRole(['admin']);

$db = getDB();
$message = '';
$error = '';

$manuscriptId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$manuscriptId) {
    echo '<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
            <div class="text-center py-12">
                <p class="text-gray-500">No manuscript specified.</p>
                <a href="/jms/admin?action=submissions" class="mt-3 inline-block bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                    <i class="fas fa-arrow-left mr-2"></i> Back
                </a>
            </div>
        </div>';
    exit;
}

$manuscript = getManuscript($manuscriptId);
if (!$manuscript) {
    echo '<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
            <div class="text-center py-12">
                <p class="text-gray-500">Manuscript not found.</p>
                <a href="/jms/admin?action=submissions" class="mt-3 inline-block bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                    <i class="fas fa-arrow-left mr-2"></i> Back
                </a>
            </div>
        </div>';
    exit;
}

// Get volumes and issues for dropdown
$volumes = getVolumes();

// Get issues for selected volume
$selectedVolume = isset($_POST['volume_id']) ? (int)$_POST['volume_id'] : 0;
$issues = [];
if ($selectedVolume > 0) {
    $issues = getIssuesByVolume($selectedVolume);
}

// Handle publication
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publish'])) {
    $issueId = (int)$_POST['issue_id'];
    $pageStart = $_POST['page_start'] ?? null;
    $pageEnd = $_POST['page_end'] ?? null;
    
    if ($issueId > 0) {
        if (publishArticle($manuscriptId, $issueId, $pageStart, $pageEnd)) {
            $message = 'Article published successfully!';
            // Refresh manuscript data
            $manuscript = getManuscript($manuscriptId);
            // Redirect to submissions
            header('Location: /jms/admin?action=submissions&subaction=published');
            exit;
        } else {
            $error = 'Failed to publish article.';
        }
    } else {
        $error = 'Please select an issue.';
    }
}
?>
<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Publish Manuscript</h2>
            <p class="text-gray-500 text-sm mt-1"><?= htmlspecialchars(substr($manuscript['title'], 0, 60)) ?>...</p>
        </div>
        <a href="/jms/admin?action=manuscript&id=<?= $manuscriptId ?>" class="text-indigo-600 hover:text-indigo-800 text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back
        </a>
    </div>
    <div class="h-1 w-20 bg-indigo-200 rounded-full mb-6"></div>

    <?php if ($message): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm mb-6">
            <i class="fas fa-check-circle mr-2"></i> <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm mb-6">
            <i class="fas fa-exclamation-circle mr-2"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Publication Form -->
        <div>
            <h3 class="font-semibold text-[#0b2b3f] mb-4">Publication Details</h3>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Volume *</label>
                    <select name="volume_id" id="volumeSelect" required 
                            class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none"
                            onchange="loadIssues(this.value)">
                        <option value="">Select Volume</option>
                        <?php foreach ($volumes as $volume): ?>
                        <option value="<?= $volume['id'] ?>" <?= $selectedVolume == $volume['id'] ? 'selected' : '' ?>>
                            Volume <?= $volume['volume_number'] ?> (<?= $volume['year'] ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Issue *</label>
                    <select name="issue_id" id="issueSelect" required 
                            class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                        <option value="">Select Issue</option>
                        <?php foreach ($issues as $issue): ?>
                        <option value="<?= $issue['id'] ?>">
                            Issue <?= $issue['issue_number'] ?> - <?= htmlspecialchars($issue['title'] ?? 'No title') ?>
                            (<?= $issue['article_count'] ?? 0 ?> articles)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Page</label>
                        <input type="number" name="page_start" min="1"
                               class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Page</label>
                        <input type="number" name="page_end" min="1"
                               class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                    </div>
                </div>
                <div class="pt-4">
                    <button type="submit" name="publish" 
                            class="bg-green-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-green-700 transition w-full">
                        <i class="fas fa-check-circle mr-2"></i> Publish Article
                    </button>
                </div>
            </form>
        </div>

        <!-- Manuscript Info -->
        <div>
            <h3 class="font-semibold text-[#0b2b3f] mb-4">Manuscript Information</h3>
            <div class="border border-gray-200 rounded-xl p-4 space-y-3">
                <div>
                    <label class="text-xs font-medium text-gray-500 uppercase">Title</label>
                    <p class="text-sm font-medium text-[#0b2b3f]"><?= htmlspecialchars($manuscript['title']) ?></p>
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-500 uppercase">Author</label>
                    <p class="text-sm text-gray-600"><?= htmlspecialchars($manuscript['author_name'] ?? 'Unknown') ?></p>
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-500 uppercase">DOI</label>
                    <p class="text-sm text-indigo-600"><?= htmlspecialchars($manuscript['doi']) ?></p>
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-500 uppercase">Status</label>
                    <p class="mt-1">
                        <span class="px-3 py-1 rounded-full text-sm font-medium <?= getStatusBadge($manuscript['status']) ?>">
                            <?= ucfirst(str_replace('_', ' ', $manuscript['status'])) ?>
                        </span>
                    </p>
                </div>
                <?php if ($manuscript['accepted_at']): ?>
                <div>
                    <label class="text-xs font-medium text-gray-500 uppercase">Accepted Date</label>
                    <p class="text-sm text-gray-600"><?= formatDate($manuscript['accepted_at']) ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Warning -->
            <div class="mt-4 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                <p class="text-sm text-yellow-700">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Once published, the article will be publicly available with the assigned DOI.
                    This action cannot be undone.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function loadIssues(volumeId) {
    if (volumeId) {
        // Reload page with selected volume to load issues
        window.location.href = '/jms/admin?action=publish&id=<?= $manuscriptId ?>&volume_id=' + volumeId;
    }
}
</script>
<?php
// modules/admin/pages/articles.php - Manage Articles
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

requireRole(['admin']);

$db = getDB();
$message = '';
$error = '';

// Get the subaction (status filter)
$subaction = $_GET['subaction'] ?? 'published';

// Map subaction to status
$statusMap = [
    'published' => 'published',
    'inpress' => 'accepted',
    'archives' => 'published'
];

$status = $statusMap[$subaction] ?? 'published';

// Build query
$sql = "SELECT m.*, u.full_name as author_name, u.email as author_email,
        e.full_name as editor_name,
        v.volume_number, i.issue_number,
        i.publication_date as issue_publication_date,
        (SELECT COUNT(*) FROM article_views WHERE manuscript_id = m.id) as view_count,
        (SELECT COUNT(*) FROM article_downloads WHERE manuscript_id = m.id) as download_count
        FROM manuscripts m
        LEFT JOIN users u ON m.corresponding_author_id = u.id
        LEFT JOIN users e ON m.editor_assigned_id = e.id
        LEFT JOIN issues i ON m.issue_id = i.id
        LEFT JOIN volumes v ON i.volume_id = v.id";

$params = [];

if ($subaction == 'published') {
    $sql .= " WHERE m.status = 'published'";
    $sql .= " ORDER BY m.publication_date DESC, m.published_at DESC";
} elseif ($subaction == 'inpress') {
    $sql .= " WHERE m.status = 'accepted' AND m.issue_id IS NOT NULL";
    $sql .= " ORDER BY m.accepted_at DESC";
} elseif ($subaction == 'archives') {
    $sql .= " WHERE m.status = 'published'";
    // For archives, get all published articles regardless of date
    $sql .= " ORDER BY m.publication_date DESC, m.published_at DESC";
}

$stmt = $db->prepare($sql);
$stmt->execute($params);
$articles = $stmt->fetchAll();

// Get counts
$stats = [
    'published' => 0,
    'inpress' => 0,
    'archives' => 0
];

// Published count
$stmt = $db->query("SELECT COUNT(*) as count FROM manuscripts WHERE status = 'published'");
$stats['published'] = $stmt->fetch()['count'];

// In Press count
$stmt = $db->query("SELECT COUNT(*) as count FROM manuscripts WHERE status = 'accepted' AND issue_id IS NOT NULL");
$stats['inpress'] = $stmt->fetch()['count'];

// Archives count (same as published, but can be filtered by date if needed)
$stats['archives'] = $stats['published'];

// Get years for archive filtering
$years = [];
$stmt = $db->query("SELECT DISTINCT YEAR(publication_date) as year FROM manuscripts WHERE status = 'published' ORDER BY year DESC");
while ($row = $stmt->fetch()) {
    $years[] = $row['year'];
}

// Handle article actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['move_to_archives'])) {
        $manuscriptId = (int)$_POST['manuscript_id'];
        // Move to archives - just keep as published, but we could add archive flag
        $stmt = $db->prepare("UPDATE manuscripts SET is_archived = 1 WHERE id = ?");
        if ($stmt->execute([$manuscriptId])) {
            $message = 'Article moved to archives successfully!';
            logAction($currentUser['id'], 'move_to_archives', 'manuscripts', $manuscriptId);
            // Refresh data
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $articles = $stmt->fetchAll();
        } else {
            $error = 'Failed to move article to archives.';
        }
    } elseif (isset($_POST['restore_from_archives'])) {
        $manuscriptId = (int)$_POST['manuscript_id'];
        $stmt = $db->prepare("UPDATE manuscripts SET is_archived = 0 WHERE id = ?");
        if ($stmt->execute([$manuscriptId])) {
            $message = 'Article restored from archives successfully!';
            logAction($currentUser['id'], 'restore_from_archives', 'manuscripts', $manuscriptId);
            // Refresh data
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $articles = $stmt->fetchAll();
        } else {
            $error = 'Failed to restore article from archives.';
        }
    } elseif (isset($_POST['delete_article'])) {
        $manuscriptId = (int)$_POST['manuscript_id'];
        // Soft delete - update status to deleted
        $stmt = $db->prepare("UPDATE manuscripts SET status = 'deleted', deleted_at = NOW() WHERE id = ?");
        if ($stmt->execute([$manuscriptId])) {
            $message = 'Article moved to trash successfully!';
            logAction($currentUser['id'], 'delete_article', 'manuscripts', $manuscriptId);
            // Refresh data
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $articles = $stmt->fetchAll();
        } else {
            $error = 'Failed to delete article.';
        }
    }
}

// Get archive year filter
$yearFilter = isset($_GET['year']) ? (int)$_GET['year'] : 0;

// If year filter is applied, modify the query
if ($yearFilter > 0 && $subaction == 'archives') {
    $sql = "SELECT m.*, u.full_name as author_name, u.email as author_email,
            e.full_name as editor_name,
            v.volume_number, i.issue_number,
            i.publication_date as issue_publication_date,
            (SELECT COUNT(*) FROM article_views WHERE manuscript_id = m.id) as view_count,
            (SELECT COUNT(*) FROM article_downloads WHERE manuscript_id = m.id) as download_count
            FROM manuscripts m
            LEFT JOIN users u ON m.corresponding_author_id = u.id
            LEFT JOIN users e ON m.editor_assigned_id = e.id
            LEFT JOIN issues i ON m.issue_id = i.id
            LEFT JOIN volumes v ON i.volume_id = v.id
            WHERE m.status = 'published' AND YEAR(m.publication_date) = ?
            ORDER BY m.publication_date DESC, m.published_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$yearFilter]);
    $articles = $stmt->fetchAll();
}

// Get current label
$labels = [
    'published' => 'Published Articles',
    'inpress' => 'In Press Articles',
    'archives' => 'Archives'
];
$currentLabel = $labels[$subaction] ?? 'Articles';
?>
<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]"><?= htmlspecialchars($currentLabel) ?></h2>
            <p class="text-gray-500 text-sm mt-1">Manage <?= strtolower($currentLabel) ?></p>
        </div>
        <div class="flex gap-3">
            <a href="/jms/admin" class="text-indigo-600 hover:text-indigo-800 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>
    </div>
    <div class="h-1 w-20 bg-indigo-200 rounded-full mb-6"></div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-green-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-green-700"><?= $stats['published'] ?></p>
            <p class="text-xs text-green-600">Published</p>
        </div>
        <div class="bg-blue-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-blue-700"><?= $stats['inpress'] ?></p>
            <p class="text-xs text-blue-600">In Press</p>
        </div>
        <div class="bg-purple-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-purple-700"><?= $stats['archives'] ?></p>
            <p class="text-xs text-purple-600">Archives</p>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 pb-4">
        <a href="/jms/admin?action=articles&subaction=published" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $subaction == 'published' ? 'bg-green-600 text-white' : 'bg-green-50 text-green-600 hover:bg-green-100' ?>">
            <i class="fas fa-check-circle mr-1"></i> Published (<?= $stats['published'] ?>)
        </a>
        <a href="/jms/admin?action=articles&subaction=inpress" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $subaction == 'inpress' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-600 hover:bg-blue-100' ?>">
            <i class="fas fa-clock mr-1"></i> In Press (<?= $stats['inpress'] ?>)
        </a>
        <a href="/jms/admin?action=articles&subaction=archives" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $subaction == 'archives' ? 'bg-purple-600 text-white' : 'bg-purple-50 text-purple-600 hover:bg-purple-100' ?>">
            <i class="fas fa-archive mr-1"></i> Archives (<?= $stats['archives'] ?>)
        </a>
    </div>

    <!-- Archive Year Filter -->
    <?php if ($subaction == 'archives' && !empty($years)): ?>
    <div class="mb-6 flex flex-wrap gap-2 items-center">
        <span class="text-sm font-medium text-gray-700">Filter by Year:</span>
        <a href="/jms/admin?action=articles&subaction=archives" 
           class="px-3 py-1 rounded-lg text-sm transition <?= $yearFilter == 0 ? 'bg-[#0b2b3f] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">
            All
        </a>
        <?php foreach ($years as $year): ?>
        <a href="/jms/admin?action=articles&subaction=archives&year=<?= $year ?>" 
           class="px-3 py-1 rounded-lg text-sm transition <?= $yearFilter == $year ? 'bg-[#0b2b3f] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">
            <?= $year ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Search -->
    <div class="mb-6">
        <form method="GET" action="" class="flex flex-wrap gap-3">
            <input type="hidden" name="action" value="articles">
            <input type="hidden" name="subaction" value="<?= htmlspecialchars($subaction) ?>">
            <?php if ($yearFilter > 0): ?>
            <input type="hidden" name="year" value="<?= $yearFilter ?>">
            <?php endif; ?>
            <div class="flex-1 min-w-[200px]">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input type="text" name="search" placeholder="Search by title, author, or DOI..." 
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                           class="w-full pl-9 pr-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none text-sm">
                </div>
            </div>
            <button type="submit" class="bg-[#0b2b3f] text-white px-4 py-2 rounded-lg hover:bg-[#123a4f] transition text-sm">
                <i class="fas fa-search mr-1"></i> Search
            </button>
            <?php if (!empty($_GET['search'])): ?>
                <a href="/jms/admin?action=articles&subaction=<?= $subaction ?><?= $yearFilter > 0 ? '&year=' . $yearFilter : '' ?>" 
                   class="text-sm text-gray-500 hover:text-[#0b2b3f]">
                    <i class="fas fa-times mr-1"></i> Clear
                </a>
            <?php endif; ?>
        </form>
    </div>

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

    <?php if (empty($articles)): ?>
        <div class="text-center py-12">
            <i class="fas fa-file-alt text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No <?= strtolower($currentLabel) ?> found.</p>
            <?php if ($subaction == 'inpress'): ?>
                <a href="/jms/admin?action=publication" class="mt-3 inline-block bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                    <i class="fas fa-plus mr-2"></i> Publish Articles
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Article</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Author</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Volume/Issue</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Views</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Downloads</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Date</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($articles as $article): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                        <td class="py-3 px-4">
                            <div>
                                <p class="font-medium text-[#0b2b3f] text-sm">
                                    <a href="/jms/?page=article&id=<?= $article['id'] ?>" target="_blank" class="hover:text-indigo-600">
                                        <?= htmlspecialchars(substr($article['title'], 0, 50)) ?>...
                                    </a>
                                </p>
                                <?php if ($article['doi']): ?>
                                    <p class="text-xs text-indigo-600">DOI: <?= htmlspecialchars($article['doi']) ?></p>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="py-3 px-4">
                            <p class="text-sm text-gray-600"><?= htmlspecialchars($article['author_name'] ?? 'Unknown') ?></p>
                            <p class="text-xs text-gray-400"><?= htmlspecialchars($article['author_email'] ?? '') ?></p>
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-600">
                            <?php if ($article['volume_number'] && $article['issue_number']): ?>
                                Vol. <?= $article['volume_number'] ?> No. <?= $article['issue_number'] ?>
                            <?php else: ?>
                                <span class="text-gray-400">Not assigned</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-600">
                            <?= number_format($article['view_count'] ?? 0) ?>
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-600">
                            <?= number_format($article['download_count'] ?? 0) ?>
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-600">
                            <?php if ($subaction == 'inpress'): ?>
                                <?= formatDate($article['accepted_at'] ?? $article['created_at']) ?>
                                <br>
                                <span class="text-xs text-gray-400">Accepted</span>
                            <?php else: ?>
                                <?= formatDate($article['publication_date'] ?? $article['created_at']) ?>
                                <br>
                                <span class="text-xs text-gray-400">Published</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex gap-2">
                                <a href="/jms/?page=article&id=<?= $article['id'] ?>" target="_blank" 
                                   class="text-indigo-600 hover:text-indigo-800 text-sm" title="View Article">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="/jms/admin?action=manuscript&id=<?= $article['id'] ?>" 
                                   class="text-blue-600 hover:text-blue-800 text-sm" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($subaction == 'published'): ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('Move this article to archives?')">
                                        <input type="hidden" name="manuscript_id" value="<?= $article['id'] ?>">
                                        <button type="submit" name="move_to_archives" class="text-purple-600 hover:text-purple-800 text-sm" title="Move to Archives">
                                            <i class="fas fa-archive"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($subaction == 'archives'): ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('Restore this article from archives?')">
                                        <input type="hidden" name="manuscript_id" value="<?= $article['id'] ?>">
                                        <button type="submit" name="restore_from_archives" class="text-green-600 hover:text-green-800 text-sm" title="Restore">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this article? This action cannot be undone.')">
                                    <input type="hidden" name="manuscript_id" value="<?= $article['id'] ?>">
                                    <button type="submit" name="delete_article" class="text-red-600 hover:text-red-800 text-sm" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-4 text-sm text-gray-400">
            Showing <?= count($articles) ?> <?= strtolower($currentLabel) ?>
        </div>
    <?php endif; ?>
</div>
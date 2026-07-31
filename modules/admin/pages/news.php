<?php
// modules/admin/pages/news.php - Manage News
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

requireRole(['admin']);

$db = getDB();
$currentUser = getCurrentUser();
$message = '';
$error = '';

// Get action - use a different parameter name to avoid conflict
$subAction = isset($_GET['subaction']) ? $_GET['subaction'] : 'list';
$newsId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $category_ids = $_POST['categories'] ?? [];
    $published_at = $_POST['published_at'] ?? null;
    
    if (empty($title)) {
        $error = 'Title is required.';
    } elseif (empty($content)) {
        $error = 'Content is required.';
    } else {
        // Generate slug
        $slug = createSlug($title);
        
        // Check if slug exists
        $stmt = $db->prepare("SELECT id FROM news WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $newsId]);
        if ($stmt->fetch()) {
            $slug = $slug . '-' . time();
        }
        
        if ($newsId > 0) {
            // Update existing news
            $stmt = $db->prepare("
                UPDATE news 
                SET title = ?, slug = ?, content = ?, excerpt = ?, 
                    status = ?, is_featured = ?, published_at = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$title, $slug, $content, $excerpt, $status, $is_featured, $published_at, $newsId]);
            $message = 'News updated successfully!';
            logAction($currentUser['id'], 'update_news', 'news', $newsId);
        } else {
            // Insert new news
            $stmt = $db->prepare("
                INSERT INTO news (title, slug, content, excerpt, status, is_featured, author_id, published_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $slug, $content, $excerpt, $status, $is_featured, $currentUser['id'], $published_at]);
            $newsId = $db->lastInsertId();
            $message = 'News created successfully!';
            logAction($currentUser['id'], 'create_news', 'news', $newsId);
        }
        
        // Update categories
        if ($newsId > 0) {
            // Remove existing categories
            $stmt = $db->prepare("DELETE FROM news_category_relations WHERE news_id = ?");
            $stmt->execute([$newsId]);
            
            // Add new categories
            if (!empty($category_ids)) {
                foreach ($category_ids as $catId) {
                    $stmt = $db->prepare("INSERT INTO news_category_relations (news_id, category_id) VALUES (?, ?)");
                    $stmt->execute([$newsId, $catId]);
                }
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM news WHERE id = ?");
    if ($stmt->execute([$deleteId])) {
        $message = 'News deleted successfully!';
        logAction($currentUser['id'], 'delete_news', 'news', $deleteId);
    } else {
        $error = 'Failed to delete news.';
    }
}

// Get news item for editing
$editNews = null;
if ($newsId > 0 && $subAction === 'edit') {
    $stmt = $db->prepare("SELECT * FROM news WHERE id = ?");
    $stmt->execute([$newsId]);
    $editNews = $stmt->fetch();
    
    // Get categories for this news
    $stmt = $db->prepare("
        SELECT category_id FROM news_category_relations WHERE news_id = ?
    ");
    $stmt->execute([$newsId]);
    $newsCategories = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Get all news
$stmt = $db->query("
    SELECT n.*, u.full_name as author_name,
           (SELECT COUNT(*) FROM news_category_relations WHERE news_id = n.id) as category_count
    FROM news n
    LEFT JOIN users u ON n.author_id = u.id
    ORDER BY n.created_at DESC
");
$newsList = $stmt->fetchAll();

// Get categories
$stmt = $db->query("SELECT * FROM news_categories ORDER BY name");
$categories = $stmt->fetchAll();
?>
<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">News Management</h2>
            <p class="text-gray-500 text-sm mt-1">Create and manage journal news and announcements</p>
        </div>
        <div class="flex gap-3">
            <?php if ($subAction === 'edit' || $subAction === 'create'): ?>
                <a href="/jms/admin?action=news" class="text-indigo-600 hover:text-indigo-800 text-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Back to List
                </a>
            <?php else: ?>
                <a href="/jms/admin?action=news&subaction=create" class="bg-[#0b2b3f] text-white px-4 py-2 rounded-lg hover:bg-[#123a4f] transition text-sm">
                    <i class="fas fa-plus mr-1"></i> Add News
                </a>
            <?php endif; ?>
        </div>
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

    <?php if ($subAction === 'create' || $subAction === 'edit'): ?>
        <!-- Create/Edit Form -->
        <form method="POST" action="" class="space-y-4">
            <input type="hidden" name="id" value="<?= $editNews['id'] ?? 0 ?>">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                <input type="text" name="title" required 
                       value="<?= htmlspecialchars($editNews['title'] ?? '') ?>"
                       class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Content *</label>
                <textarea name="content" rows="8" required
                          class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition"><?= htmlspecialchars($editNews['content'] ?? '') ?></textarea>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Excerpt (Short Description)</label>
                <textarea name="excerpt" rows="3"
                          class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition"><?= htmlspecialchars($editNews['excerpt'] ?? '') ?></textarea>
                <p class="text-xs text-gray-400 mt-1">A short summary of the news (max 500 characters)</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                        <option value="draft" <?= ($editNews['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="published" <?= ($editNews['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                        <option value="archived" <?= ($editNews['status'] ?? '') === 'archived' ? 'selected' : '' ?>>Archived</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Publish Date</label>
                    <input type="datetime-local" name="published_at" 
                           value="<?= isset($editNews['published_at']) ? date('Y-m-d\TH:i', strtotime($editNews['published_at'])) : '' ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_featured" value="1" 
                               <?= ($editNews['is_featured'] ?? 0) ? 'checked' : '' ?>
                               class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                        <span class="text-sm text-gray-700">Feature this news</span>
                    </label>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Categories</label>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($categories as $cat): ?>
                        <label class="flex items-center gap-1">
                            <input type="checkbox" name="categories[]" value="<?= $cat['id'] ?>"
                                   <?= isset($newsCategories) && in_array($cat['id'], $newsCategories) ? 'checked' : '' ?>
                                   class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                            <span class="text-sm text-gray-700"><?= htmlspecialchars($cat['name']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php if (empty($categories)): ?>
                    <p class="text-sm text-gray-400">No categories available. <a href="/jms/admin?action=news-categories" class="text-indigo-600 hover:underline">Create categories</a></p>
                <?php endif; ?>
            </div>
            
            <div class="flex gap-3 pt-4">
                <button type="submit" class="bg-[#0b2b3f] text-white px-6 py-2 rounded-lg font-semibold hover:bg-[#123a4f] transition">
                    <i class="fas fa-save mr-2"></i> <?= $newsId > 0 ? 'Update News' : 'Create News' ?>
                </button>
                <a href="/jms/admin?action=news" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg font-semibold hover:bg-gray-200 transition">
                    Cancel
                </a>
            </div>
        </form>
    <?php else: ?>
        <!-- News List -->
        <?php if (empty($newsList)): ?>
            <div class="text-center py-12">
                <i class="fas fa-newspaper text-5xl text-gray-300 mb-4"></i>
                <p class="text-gray-500">No news items created yet.</p>
                <a href="/jms/admin?action=news&subaction=create" class="mt-3 inline-block bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                    <i class="fas fa-plus mr-2"></i> Create First News
                </a>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Title</th>
                            <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Author</th>
                            <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Status</th>
                            <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Featured</th>
                            <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Categories</th>
                            <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Date</th>
                            <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($newsList as $item): ?>
                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                            <td class="py-3 px-4">
                                <p class="font-medium text-[#0b2b3f] text-sm"><?= htmlspecialchars($item['title']) ?></p>
                                <p class="text-xs text-gray-400"><?= htmlspecialchars(substr($item['excerpt'] ?? $item['content'], 0, 60)) ?>...</p>
                            </td>
                            <td class="py-3 px-4 text-sm text-gray-600"><?= htmlspecialchars($item['author_name'] ?? 'Unknown') ?></td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 rounded-full text-xs font-medium 
                                    <?= $item['status'] === 'published' ? 'bg-green-100 text-green-700' : 
                                       ($item['status'] === 'draft' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600') ?>">
                                    <?= ucfirst($item['status']) ?>
                                </span>
                            </td>
                            <td class="py-3 px-4 text-sm text-gray-600">
                                <?php if ($item['is_featured']): ?>
                                    <i class="fas fa-star text-yellow-500"></i>
                                <?php else: ?>
                                    <span class="text-gray-300">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4 text-sm text-gray-600"><?= $item['category_count'] ?? 0 ?></td>
                            <td class="py-3 px-4 text-sm text-gray-500">
                                <?= formatDate($item['published_at'] ?? $item['created_at']) ?>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex gap-2">
                                    <a href="/jms/admin?action=news&subaction=edit&id=<?= $item['id'] ?>" 
                                       class="text-indigo-600 hover:text-indigo-800 text-sm" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="/jms/?page=news&id=<?= $item['id'] ?>" target="_blank"
                                       class="text-blue-600 hover:text-blue-800 text-sm" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="/jms/admin?action=news&delete=<?= $item['id'] ?>" 
                                       onclick="return confirm('Are you sure you want to delete this news?')"
                                       class="text-red-600 hover:text-red-800 text-sm" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-4 text-sm text-gray-400">
                Showing <?= count($newsList) ?> news items
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
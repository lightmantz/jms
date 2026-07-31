<?php
// modules/admin/pages/create-article.php - Create and Publish Article with PDF Upload
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

requireRole(['admin']);

$db = getDB();
$message = '';
$error = '';
$currentUser = getCurrentUser();

// Create upload directory if it doesn't exist
$uploadDir = __DIR__ . '/../../../../uploads/articles/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Get categories/sections
$stmt = $db->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

// Get volumes and issues for publishing
$volumes = getVolumes();
$issues = [];

// Get users for author selection
$stmt = $db->query("SELECT id, full_name, email FROM users WHERE role IN ('author', 'admin') AND is_active = 1 ORDER BY full_name");
$authors = $stmt->fetchAll();

// Get selected volume for dynamic issue loading
$selectedVolume = isset($_GET['volume_id']) ? (int)$_GET['volume_id'] : 0;
if ($selectedVolume > 0) {
    $issues = getIssuesByVolume($selectedVolume);
}

// Handle form submission - BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Handle PDF upload
    $pdfFile = null;
    $pdfPath = null;
    
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
        $fileInfo = pathinfo($_FILES['pdf_file']['name']);
        $extension = strtolower($fileInfo['extension'] ?? '');
        
        if ($extension !== 'pdf') {
            $error = 'Please upload a valid PDF file.';
        } elseif ($_FILES['pdf_file']['size'] > 10485760) { // 10MB limit
            $error = 'PDF file size exceeds 10MB limit.';
        } else {
            $filename = 'article_' . time() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $fileInfo['filename']) . '.pdf';
            $pdfPath = $uploadDir . $filename;
            
            if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $pdfPath)) {
                $pdfFile = '/uploads/articles/' . $filename;
            } else {
                $error = 'Failed to upload PDF file.';
            }
        }
    }
    
    if ($action === 'save_draft') {
        // Save as draft
        $title = trim($_POST['title'] ?? '');
        $abstract = trim($_POST['abstract'] ?? '');
        $author_id = (int)($_POST['author_id'] ?? 0);
        $category_ids = $_POST['categories'] ?? [];
        $article_type = $_POST['article_type'] ?? 'original_research';
        $funding_source = trim($_POST['funding_source'] ?? '');
        $acknowledgments = trim($_POST['acknowledgments'] ?? '');
        $conflicts = trim($_POST['conflicts'] ?? '');
        
        if (empty($title) || empty($author_id)) {
            $error = 'Please fill in the required fields (Title and Author).';
        } else {
            // Create manuscript as draft
            $stmt = $db->prepare("
                INSERT INTO manuscripts (
                    title, abstract, author_id, corresponding_author_id, 
                    article_type, status, funding_source, acknowledgments, 
                    conflicts, pdf_file
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            if ($stmt->execute([
                $title, $abstract, $author_id, $author_id,
                $article_type, 'draft', $funding_source, $acknowledgments, 
                $conflicts, $pdfFile
            ])) {
                $manuscriptId = $db->lastInsertId();
                
                // Add categories
                if (!empty($category_ids)) {
                    foreach ($category_ids as $cat_id) {
                        $stmt = $db->prepare("INSERT INTO manuscript_keywords (manuscript_id, category_id) VALUES (?, ?)");
                        $stmt->execute([$manuscriptId, $cat_id]);
                    }
                }
                
                logAction($currentUser['id'], 'create_article_draft', 'manuscripts', $manuscriptId);
                
                header('Location: /jms/admin?action=manuscript&id=' . $manuscriptId . '&edit=1');
                exit;
            } else {
                $error = 'Failed to save draft. Please try again.';
                // Clean up uploaded file if failed
                if ($pdfPath && file_exists($pdfPath)) {
                    unlink($pdfPath);
                }
            }
        }
    } elseif ($action === 'publish') {
        // Publish article
        $title = trim($_POST['title'] ?? '');
        $abstract = trim($_POST['abstract'] ?? '');
        $author_id = (int)($_POST['author_id'] ?? 0);
        $category_ids = $_POST['categories'] ?? [];
        $article_type = $_POST['article_type'] ?? 'original_research';
        $funding_source = trim($_POST['funding_source'] ?? '');
        $acknowledgments = trim($_POST['acknowledgments'] ?? '');
        $conflicts = trim($_POST['conflicts'] ?? '');
        $issue_id = (int)($_POST['issue_id'] ?? 0);
        $page_start = $_POST['page_start'] ?? null;
        $page_end = $_POST['page_end'] ?? null;
        $publication_date = $_POST['publication_date'] ?? date('Y-m-d');
        
        if (empty($title) || empty($author_id) || empty($issue_id)) {
            $error = 'Please fill in all required fields (Title, Author, and Issue).';
        } else {
            // Generate DOI
            $doi = generateDOI();
            
            // Create manuscript as published
            $stmt = $db->prepare("
                INSERT INTO manuscripts (
                    title, abstract, author_id, corresponding_author_id, 
                    article_type, status, funding_source, acknowledgments, 
                    conflicts, doi, issue_id, page_start, page_end,
                    publication_date, published_at, created_at, submitted_at, pdf_file
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW(), ?)
            ");
            
            if ($stmt->execute([
                $title, $abstract, $author_id, $author_id,
                $article_type, 'published', $funding_source, $acknowledgments, 
                $conflicts, $doi, $issue_id, $page_start, $page_end, $publication_date,
                $pdfFile
            ])) {
                $manuscriptId = $db->lastInsertId();
                
                // Add categories
                if (!empty($category_ids)) {
                    foreach ($category_ids as $cat_id) {
                        $stmt = $db->prepare("INSERT INTO manuscript_keywords (manuscript_id, category_id) VALUES (?, ?)");
                        $stmt->execute([$manuscriptId, $cat_id]);
                    }
                }
                
                // Notify author
                createNotification(
                    $author_id,
                    'published',
                    'Article Published',
                    'Your article "' . $title . '" has been published!',
                    SITE_URL . '/article/' . $manuscriptId
                );
                
                logAction($currentUser['id'], 'publish_article', 'manuscripts', $manuscriptId);
                
                header('Location: /jms/admin?action=articles&subaction=published');
                exit;
            } else {
                $error = 'Failed to publish article. Please try again.';
                // Clean up uploaded file if failed
                if ($pdfPath && file_exists($pdfPath)) {
                    unlink($pdfPath);
                }
            }
        }
    }
}
?>
<div class="space-y-6">
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

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Create Article</h2>
            <p class="text-gray-500 text-sm mt-1">Create a new article and publish it to the journal</p>
        </div>
        <a href="/jms/admin" class="text-indigo-600 hover:text-indigo-800 text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
        </a>
    </div>
    <div class="h-1 w-20 bg-indigo-200 rounded-full"></div>

    <form method="POST" class="space-y-8" enctype="multipart/form-data">
        <!-- Article Information -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">Article Information</h3>
            <div class="grid md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                    <input type="text" name="title" required 
                           placeholder="Enter article title"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Abstract</label>
                    <textarea name="abstract" rows="5"
                              placeholder="Enter article abstract"
                              class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Author *</label>
                    <select name="author_id" required class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                        <option value="">Select Author...</option>
                        <?php foreach ($authors as $author): ?>
                        <option value="<?= $author['id'] ?>">
                            <?= htmlspecialchars($author['full_name']) ?> (<?= htmlspecialchars($author['email']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Article Type</label>
                    <select name="article_type" class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                        <option value="original_research">Original Research</option>
                        <option value="review">Review Article</option>
                        <option value="case_report">Case Report</option>
                        <option value="editorial">Editorial</option>
                        <option value="letter">Letter to the Editor</option>
                        <option value="commentary">Commentary</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categories/Sections</label>
                    <select name="categories[]" multiple 
                            class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition" 
                            style="min-height: 80px;">
                        <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Hold Ctrl/Cmd to select multiple categories</p>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Upload PDF</label>
                    <input type="file" name="pdf_file" accept=".pdf"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    <p class="text-xs text-gray-400 mt-1">Upload a PDF file (Max 10MB). The PDF will be linked to the article.</p>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Funding Source</label>
                    <input type="text" name="funding_source" 
                           placeholder="e.g., NIH Grant #12345"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Acknowledgments</label>
                    <textarea name="acknowledgments" rows="3"
                              placeholder="Enter acknowledgments"
                              class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition"></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Conflict of Interest</label>
                    <textarea name="conflicts" rows="2"
                              placeholder="Declare any conflicts of interest"
                              class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition"></textarea>
                </div>
            </div>
        </div>

        <!-- Publication Details -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">Publication Details</h3>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Volume</label>
                    <select name="volume_id" id="volumeSelect" 
                            class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition"
                            onchange="window.location.href='/jms/admin?action=create-article&volume_id='+this.value">
                        <option value="0">Select Volume</option>
                        <?php foreach ($volumes as $volume): ?>
                        <option value="<?= $volume['id'] ?>" <?= $selectedVolume == $volume['id'] ? 'selected' : '' ?>>
                            Volume <?= $volume['volume_number'] ?> (<?= $volume['year'] ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Issue</label>
                    <select name="issue_id" id="issueSelect" 
                            class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                        <option value="0">Select Issue</option>
                        <?php foreach ($issues as $issue): ?>
                        <option value="<?= $issue['id'] ?>">
                            Issue <?= $issue['issue_number'] ?> - <?= htmlspecialchars($issue['title'] ?? 'No title') ?>
                            (<?= $issue['article_count'] ?? 0 ?> articles)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Page</label>
                    <input type="number" name="page_start" min="1" 
                           placeholder="e.g., 1"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">End Page</label>
                    <input type="number" name="page_end" min="1" 
                           placeholder="e.g., 15"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Publication Date</label>
                    <input type="date" name="publication_date" value="<?= date('Y-m-d') ?>"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
            </div>
            
            <?php if (empty($volumes)): ?>
            <div class="mt-4 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                <p class="text-sm text-yellow-700">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    No volumes or issues available. Please create a volume and issue first.
                    <a href="/jms/admin?action=volumes" class="underline font-medium">Create Volume</a>
                </p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-3">
            <button type="submit" name="action" value="publish" 
                    class="bg-green-600 text-white px-8 py-2.5 rounded-lg font-semibold hover:bg-green-700 transition shadow-sm">
                <i class="fas fa-check-circle mr-2"></i> Publish Article
            </button>
            <button type="submit" name="action" value="save_draft" 
                    class="bg-yellow-600 text-white px-8 py-2.5 rounded-lg font-semibold hover:bg-yellow-700 transition shadow-sm">
                <i class="fas fa-save mr-2"></i> Save as Draft
            </button>
            <a href="/jms/admin" class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg font-semibold hover:bg-gray-200 transition">
                Cancel
            </a>
        </div>
    </form>
</div>
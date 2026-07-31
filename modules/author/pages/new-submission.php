<?php
// modules/author/pages/new-submission.php - New Submission
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

$db = getDB();
$message = '';
$error = '';
$currentUser = getCurrentUser();

// Get categories/sections
$stmt = $db->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $abstract = trim($_POST['abstract'] ?? '');
    $category_ids = $_POST['categories'] ?? [];
    $article_type = $_POST['article_type'] ?? 'original_research';
    $keywords = trim($_POST['keywords'] ?? '');
    $funding_source = trim($_POST['funding_source'] ?? '');
    $acknowledgments = trim($_POST['acknowledgments'] ?? '');
    $conflicts = trim($_POST['conflicts'] ?? '');
    $action_type = $_POST['action_type'] ?? 'submit';
    
    // Handle file upload
    $pdfFile = null;
    $pdfPath = null;
    $uploadDir = __DIR__ . '/../../../uploads/submissions/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
        $fileInfo = pathinfo($_FILES['pdf_file']['name']);
        $extension = strtolower($fileInfo['extension'] ?? '');
        if ($extension === 'pdf' && $_FILES['pdf_file']['size'] <= 10485760) {
            $filename = 'submission_' . time() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $fileInfo['filename']) . '.pdf';
            $pdfPath = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $pdfPath)) {
                $pdfFile = '/uploads/submissions/' . $filename;
            }
        }
    }
    
    if (empty($title)) {
        $error = 'Please fill in the required fields (Title).';
    } else {
        $status = ($action_type == 'save_draft') ? 'draft' : 'submitted';
        $doi = ($status == 'submitted') ? generateDOI() : null;
        
        $stmt = $db->prepare("
            INSERT INTO manuscripts (
                title, abstract, author_id, corresponding_author_id, 
                article_type, status, funding_source, acknowledgments, 
                conflicts, doi, pdf_file, submitted_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $submittedAt = ($status == 'submitted') ? date('Y-m-d H:i:s') : null;
        
        $result = $stmt->execute([
            $title, $abstract, $currentUser['id'], $currentUser['id'],
            $article_type, $status, $funding_source, $acknowledgments, 
            $conflicts, $doi, $pdfFile, $submittedAt
        ]);
        
        if ($result) {
            $manuscriptId = $db->lastInsertId();
            
            if (!empty($category_ids)) {
                foreach ($category_ids as $cat_id) {
                    $stmt = $db->prepare("INSERT INTO manuscript_keywords (manuscript_id, category_id) VALUES (?, ?)");
                    $stmt->execute([$manuscriptId, $cat_id]);
                }
            }
            
            if ($status == 'submitted') {
                $editors = getEditors();
                foreach ($editors as $editor) {
                    createNotification(
                        $editor['id'],
                        'new_submission',
                        'New Manuscript Submission',
                        'A new manuscript has been submitted: ' . $title,
                        SITE_URL . '/editor?action=decision&id=' . $manuscriptId
                    );
                }
                $message = 'Submission submitted successfully! DOI: ' . $doi;
                logAction($currentUser['id'], 'submit_manuscript', 'manuscripts', $manuscriptId);
                header('Location: /jms/author?action=track');
                exit;
            } else {
                $message = 'Submission saved as draft successfully!';
                logAction($currentUser['id'], 'create_draft', 'manuscripts', $manuscriptId);
                header('Location: /jms/author?action=drafts');
                exit;
            }
        } else {
            $error = 'Failed to create submission. Please try again.';
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
            <h2 class="text-2xl font-bold text-[#0b2b3f]">New Submission</h2>
            <p class="text-gray-500 text-sm mt-1">Submit a new manuscript for review</p>
        </div>
        <a href="/jms/author" class="text-indigo-600 hover:text-indigo-800 text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
        </a>
    </div>
    <div class="h-1 w-20 bg-indigo-200 rounded-full"></div>

    <form method="POST" class="space-y-8" enctype="multipart/form-data">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">Manuscript Information</h3>
            <div class="grid md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                    <input type="text" name="title" required 
                           placeholder="Enter manuscript title"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Abstract</label>
                    <textarea name="abstract" rows="5"
                              placeholder="Enter manuscript abstract"
                              class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition"></textarea>
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">Keywords</label>
                    <input type="text" name="keywords" 
                           placeholder="Enter keywords separated by commas"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                </div>
                <div class="md:col-span-2">
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">Upload Manuscript PDF</label>
                    <input type="file" name="pdf_file" accept=".pdf"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    <p class="text-xs text-gray-400 mt-1">Upload a PDF file (Max 10MB)</p>
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

        <div class="flex gap-3">
            <button type="submit" name="action_type" value="submit" 
                    class="bg-blue-600 text-white px-8 py-2.5 rounded-lg font-semibold hover:bg-blue-700 transition shadow-sm">
                <i class="fas fa-paper-plane mr-2"></i> Submit for Review
            </button>
            <button type="submit" name="action_type" value="save_draft" 
                    class="bg-yellow-600 text-white px-8 py-2.5 rounded-lg font-semibold hover:bg-yellow-700 transition shadow-sm">
                <i class="fas fa-save mr-2"></i> Save as Draft
            </button>
            <a href="/jms/author" class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg font-semibold hover:bg-gray-200 transition">
                Cancel
            </a>
        </div>
    </form>
</div>
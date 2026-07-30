<?php
// modules/pages/view-pdf.php - PDF Viewer
require_once __DIR__ . '/../../includes/init.php';

// Get article ID from URL
$articleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($articleId <= 0) {
    header('Location: ' . SITE_URL . '?page=archive');
    exit;
}

$db = getDB();

// Get article with file path
$stmt = $db->prepare("
    SELECT id, title, pdf_file, file_path 
    FROM manuscripts 
    WHERE id = ? AND status = 'published'
");
$stmt->execute([$articleId]);
$article = $stmt->fetch();

if (!$article) {
    http_response_code(404);
    die('Article not found.');
}

// Determine the file path
$filePath = null;
if (!empty($article['pdf_file'])) {
    $filePath = $article['pdf_file'];
} elseif (!empty($article['file_path'])) {
    $filePath = $article['file_path'];
}

if (!$filePath) {
    http_response_code(404);
    die('PDF file not available for this article.');
}

// Build full file path
$fullPath = BASE_PATH . '/' . ltrim($filePath, '/');

// Check if file exists
if (!file_exists($fullPath)) {
    // Try alternative paths
    $alternativePaths = [
        BASE_PATH . '/uploads/' . basename($filePath),
        BASE_PATH . '/files/' . basename($filePath),
        BASE_PATH . '/uploads/manuscripts/' . basename($filePath),
        BASE_PATH . '/' . $filePath
    ];
    
    foreach ($alternativePaths as $altPath) {
        if (file_exists($altPath)) {
            $fullPath = $altPath;
            break;
        }
    }
    
    if (!file_exists($fullPath)) {
        http_response_code(404);
        die('PDF file not found.');
    }
}

// Log the view (optional)
try {
    $stmt = $db->prepare("
        INSERT INTO article_views (manuscript_id, ip_address, viewed_at) 
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$articleId, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
} catch (PDOException $e) {
    // Table might not exist, ignore
}

// Set headers for PDF viewing
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . sanitizeFilename($article['title']) . '.pdf"');
header('Content-Length: ' . filesize($fullPath));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Read and output the file
readfile($fullPath);
exit;

function sanitizeFilename($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9\s\-]/', '', $filename);
    $filename = str_replace(' ', '_', $filename);
    return substr($filename, 0, 50);
}
?>
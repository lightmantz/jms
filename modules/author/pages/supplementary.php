<?php
// modules/author/pages/supplementary.php - Supplementary Files
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

$db = getDB();
$currentUser = getCurrentUser();
$message = '';
$error = '';

// Get user's manuscripts with supplementary files
$stmt = $db->prepare("
    SELECT m.id, m.title, m.status,
           (SELECT COUNT(*) FROM manuscript_files WHERE manuscript_id = m.id AND file_type = 'supplementary') as supp_count
    FROM manuscripts m
    WHERE m.corresponding_author_id = ? AND m.status != 'draft'
    ORDER BY m.created_at DESC
");
$stmt->execute([$currentUser['id']]);
$manuscripts = $stmt->fetchAll();

// Handle supplementary file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_supplementary'])) {
    $manuscript_id = (int)$_POST['manuscript_id'];
    
    if ($manuscript_id <= 0) {
        $error = 'Please select a manuscript.';
    } elseif (isset($_FILES['supp_file']) && $_FILES['supp_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../../uploads/supplementary/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileInfo = pathinfo($_FILES['supp_file']['name']);
        $extension = strtolower($fileInfo['extension'] ?? '');
        $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar', 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'avi', 'mov'];
        
        if (!in_array($extension, $allowedExtensions)) {
            $error = 'File type not allowed.';
        } elseif ($_FILES['supp_file']['size'] > 52428800) { // 50MB
            $error = 'File size exceeds 50MB limit.';
        } else {
            $filename = 'supp_' . $manuscript_id . '_' . time() . '.' . $extension;
            $filePath = $uploadDir . $filename;
            
            if (move_uploaded_file($_FILES['supp_file']['tmp_name'], $filePath)) {
                $stmt = $db->prepare("
                    INSERT INTO manuscript_files (manuscript_id, file_name, file_path, file_type, file_size, mime_type, uploaded_by) 
                    VALUES (?, ?, ?, 'supplementary', ?, ?, ?)
                ");
                if ($stmt->execute([
                    $manuscript_id, 
                    $_FILES['supp_file']['name'], 
                    '/uploads/supplementary/' . $filename,
                    $_FILES['supp_file']['size'],
                    $_FILES['supp_file']['type'],
                    $currentUser['id']
                ])) {
                    $message = 'Supplementary file uploaded successfully!';
                    logAction($currentUser['id'], 'upload_supplementary', 'manuscript_files', $db->lastInsertId());
                } else {
                    $error = 'Failed to save file information.';
                }
            } else {
                $error = 'Failed to upload file.';
            }
        }
    } else {
        $error = 'Please select a file to upload.';
    }
}

// Get supplementary files
$stmt = $db->prepare("
    SELECT mf.*, m.title as manuscript_title 
    FROM manuscript_files mf
    JOIN manuscripts m ON mf.manuscript_id = m.id
    WHERE m.corresponding_author_id = ? AND mf.file_type = 'supplementary'
    ORDER BY mf.uploaded_at DESC
");
$stmt->execute([$currentUser['id']]);
$files = $stmt->fetchAll();
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Supplementary Files</h2>
            <p class="text-gray-500 text-sm mt-1">Upload supplementary materials for your manuscripts</p>
        </div>
        <a href="/jms/author" class="text-indigo-600 hover:text-indigo-800 text-sm">
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

    <!-- Upload Form -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">Upload Supplementary File</h3>
        <form method="POST" enctype="multipart/form-data" class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Select Manuscript *</label>
                <select name="manuscript_id" required class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none transition">
                    <option value="">Select a manuscript...</option>
                    <?php foreach ($manuscripts as $manuscript): ?>
                    <option value="<?= $manuscript['id'] ?>">
                        <?= htmlspecialchars(substr($manuscript['title'], 0, 50)) ?> 
                        (<?= ucfirst($manuscript['status']) ?>) 
                        <?= $manuscript['supp_count'] > 0 ? '[' . $manuscript['supp_count'] . ' files]' : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Choose File *</label>
                <input type="file" name="supp_file" required 
                       class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none transition file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                <p class="text-xs text-gray-400 mt-1">Max 50MB. Supported: PDF, DOC, XLS, PPT, TXT, ZIP, Images, Videos</p>
            </div>
            <div class="md:col-span-2">
                <button type="submit" name="upload_supplementary" class="bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                    <i class="fas fa-upload mr-2"></i> Upload Supplementary File
                </button>
            </div>
        </form>
    </div>

    <!-- File List -->
    <?php if (!empty($files)): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">Supplementary Files</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">File Name</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Manuscript</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Size</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Uploaded</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($files as $file): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                        <td class="py-2 px-3 font-medium text-[#0b2b3f]"><?= htmlspecialchars($file['file_name']) ?></td>
                        <td class="py-2 px-3 text-gray-600"><?= htmlspecialchars(substr($file['manuscript_title'], 0, 30)) ?>...</td>
                        <td class="py-2 px-3 text-gray-600"><?= number_format($file['file_size'] / 1024, 1) ?> KB</td>
                        <td class="py-2 px-3 text-gray-600"><?= timeAgo($file['uploaded_at']) ?></td>
                        <td class="py-2 px-3">
                            <a href="<?= $file['file_path'] ?>" target="_blank" class="text-indigo-600 hover:text-indigo-800 text-sm">
                                <i class="fas fa-download"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php
// modules/author/pages/upload.php - Upload Files
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

$db = getDB();
$currentUser = getCurrentUser();
$message = '';
$error = '';

// Get user's submitted manuscripts for file upload
$stmt = $db->prepare("
    SELECT id, title, status FROM manuscripts 
    WHERE corresponding_author_id = ? AND status != 'draft'
    ORDER BY created_at DESC
");
$stmt->execute([$currentUser['id']]);
$manuscripts = $stmt->fetchAll();

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_file'])) {
    $manuscript_id = (int)$_POST['manuscript_id'];
    $file_type = $_POST['file_type'] ?? 'manuscript';
    
    if ($manuscript_id <= 0) {
        $error = 'Please select a manuscript.';
    } elseif (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../../uploads/manuscripts/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileInfo = pathinfo($_FILES['file']['name']);
        $extension = strtolower($fileInfo['extension'] ?? '');
        $allowedExtensions = ['pdf', 'doc', 'docx', 'txt', 'zip', 'jpg', 'jpeg', 'png'];
        
        if (!in_array($extension, $allowedExtensions)) {
            $error = 'File type not allowed. Allowed: ' . implode(', ', $allowedExtensions);
        } elseif ($_FILES['file']['size'] > 20971520) { // 20MB
            $error = 'File size exceeds 20MB limit.';
        } else {
            $filename = 'manuscript_' . $manuscript_id . '_' . time() . '.' . $extension;
            $filePath = $uploadDir . $filename;
            
            if (move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
                $stmt = $db->prepare("
                    INSERT INTO manuscript_files (manuscript_id, file_name, file_path, file_type, file_size, mime_type, uploaded_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                if ($stmt->execute([
                    $manuscript_id, 
                    $_FILES['file']['name'], 
                    '/uploads/manuscripts/' . $filename,
                    $file_type,
                    $_FILES['file']['size'],
                    $_FILES['file']['type'],
                    $currentUser['id']
                ])) {
                    $message = 'File uploaded successfully!';
                    logAction($currentUser['id'], 'upload_file', 'manuscript_files', $db->lastInsertId());
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

// Get uploaded files
$stmt = $db->prepare("
    SELECT mf.*, m.title as manuscript_title 
    FROM manuscript_files mf
    JOIN manuscripts m ON mf.manuscript_id = m.id
    WHERE m.corresponding_author_id = ?
    ORDER BY mf.uploaded_at DESC
");
$stmt->execute([$currentUser['id']]);
$files = $stmt->fetchAll();
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Upload Files</h2>
            <p class="text-gray-500 text-sm mt-1">Upload manuscript files and supplementary materials</p>
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
        <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">Upload New File</h3>
        <form method="POST" enctype="multipart/form-data" class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Select Manuscript *</label>
                <select name="manuscript_id" required class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none transition">
                    <option value="">Select a manuscript...</option>
                    <?php foreach ($manuscripts as $manuscript): ?>
                    <option value="<?= $manuscript['id'] ?>">
                        <?= htmlspecialchars(substr($manuscript['title'], 0, 50)) ?> (<?= ucfirst($manuscript['status']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">File Type</label>
                <select name="file_type" class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none transition">
                    <option value="manuscript">Manuscript</option>
                    <option value="supplementary">Supplementary File</option>
                    <option value="figures">Figures</option>
                    <option value="tables">Tables</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Choose File *</label>
                <input type="file" name="file" required 
                       class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none transition file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                <p class="text-xs text-gray-400 mt-1">Max 20MB. Allowed: PDF, DOC, DOCX, TXT, ZIP, JPG, PNG</p>
            </div>
            <div class="md:col-span-2">
                <button type="submit" name="upload_file" class="bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                    <i class="fas fa-upload mr-2"></i> Upload File
                </button>
            </div>
        </form>
    </div>

    <!-- File List -->
    <?php if (!empty($files)): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">Uploaded Files</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">File Name</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Manuscript</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Type</th>
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
                        <td class="py-2 px-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-600">
                                <?= ucfirst($file['file_type']) ?>
                            </span>
                        </td>
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
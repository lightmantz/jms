<?php
// modules/pages/submit.php - Submit Manuscript
require_once __DIR__ . '/../../includes/init.php';

// Check if user is logged in
if (!isLoggedIn()) {
    // Redirect to login page with return URL
    $returnUrl = urlencode(SITE_URL . '?page=submit');
    header('Location: ' . SITE_URL . '?page=login&redirect=' . $returnUrl);
    exit;
}

$currentUser = getCurrentUser();

// Check if user has author role or admin role
if (!isAuthor($currentUser) && !isAdmin($currentUser)) {
    // Redirect to dashboard with message
    header('Location: ' . SITE_URL . '?page=dashboard&error=unauthorized');
    exit;
}

$db = getDB();
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $abstract = trim($_POST['abstract'] ?? '');
    $category = $_POST['category'] ?? '';
    $keywords = trim($_POST['keywords'] ?? '');
    $cover_letter = trim($_POST['cover_letter'] ?? '');
    $agreement = isset($_POST['agreement']) ? true : false;
    
    if (empty($title)) {
        $error = 'Please enter the manuscript title.';
    } elseif (empty($abstract)) {
        $error = 'Please enter the abstract.';
    } elseif (empty($category)) {
        $error = 'Please select a category.';
    } elseif (!$agreement) {
        $error = 'Please confirm that your manuscript is original.';
    } else {
        // Handle file upload
        $filePath = '';
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = BASE_PATH . '/uploads/manuscripts/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $extension;
            $targetPath = $uploadDir . $filename;
            
            if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
                $filePath = 'uploads/manuscripts/' . $filename;
            } else {
                $error = 'Failed to upload file.';
            }
        } else {
            $error = 'Please select a file to upload.';
        }
        
        if (empty($error)) {
            // Insert manuscript
            $stmt = $db->prepare("
                INSERT INTO manuscripts (
                    title, abstract, article_type, author_id, corresponding_author_id,
                    keywords, submission_date, status, file_path, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW(), 'submitted', ?, NOW())
            ");
            
            // Get category ID from category name or use the value
            $articleType = $category;
            
            $stmt->execute([
                $title,
                $abstract,
                $articleType,
                $currentUser['id'],
                $currentUser['id'],
                $keywords,
                $filePath
            ]);
            
            $manuscriptId = $db->lastInsertId();
            
            // Insert keywords into manuscript_keywords
            if (!empty($keywords) && $category) {
                // Find category ID
                $stmt = $db->prepare("SELECT id FROM categories WHERE name = ? OR id = ?");
                $stmt->execute([$category, $category]);
                $cat = $stmt->fetch();
                if ($cat) {
                    $stmt = $db->prepare("INSERT INTO manuscript_keywords (manuscript_id, category_id) VALUES (?, ?)");
                    $stmt->execute([$manuscriptId, $cat['id']]);
                }
            }
            
            $message = 'Your manuscript has been submitted successfully! You will receive a confirmation email shortly.';
            
            // Log action
            logAction($currentUser['id'], 'submit_manuscript', 'manuscripts', $manuscriptId);
        }
    }
}

// Get categories
$categories = getCategories();

// Get sidebar data
$editorialBoard = getEditorialBoard(4);
$stats = getJournalStats();

// Get latest news for sidebar
$stmt = $db->query("
    SELECT n.*, u.full_name as author_name
    FROM news n
    LEFT JOIN users u ON n.author_id = u.id
    WHERE n.status = 'published'
    ORDER BY n.is_featured DESC, n.published_at DESC, n.created_at DESC
    LIMIT 5
");
$sidebarNews = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Manuscript - <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>css/style.css">
    <style>
        .shadow-card { box-shadow: 0 8px 20px rgba(0,20,40,0.04); }
        .bg-tirp-light { background-color: #e7edf2; }
        .text-tirp { color: #0b2b3f; }
        .news-item {
            transition: all 0.2s ease;
        }
        .news-item:hover {
            background: #f8fafc;
            padding-left: 0.75rem;
        }
        .input-field {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            transition: all 0.2s;
            font-size: 14px;
            background: white;
        }
        .input-field:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        textarea.input-field {
            resize: vertical;
        }
        .btn-submit {
            background: #4f46e5;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            width: 100%;
        }
        .btn-submit:hover {
            background: #4338ca;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }
    </style>
</head>
<body class="antialiased text-gray-700 font-['Inter']" style="background: #f6f9fc;">
    <?php include INCLUDES_PATH . 'header.php'; ?>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-8 md:p-10">
                    <h1 class="text-3xl font-bold text-[#0b2b3f] flex items-center gap-3">
                        <i class="fas fa-upload text-indigo-500"></i> Submit Manuscript
                    </h1>
                    <div class="h-1 w-20 bg-indigo-200 rounded-full mt-2 mb-6"></div>
                    
                    <?php if ($message): ?>
                        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                            <i class="fas fa-check-circle mr-2"></i> <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                            <i class="fas fa-exclamation-circle mr-2"></i> <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!$message): ?>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                            <p class="text-sm text-blue-700">
                                <i class="fas fa-info-circle mr-2"></i> 
                                Welcome, <strong><?= htmlspecialchars($currentUser['full_name']) ?></strong>. 
                                Please fill out the form below to submit your manuscript.
                            </p>
                        </div>
                        
                        <form method="POST" action="" enctype="multipart/form-data" class="space-y-6">
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Manuscript Title <span class="text-red-500">*</span></label>
                                <input type="text" id="title" name="title" required 
                                       class="input-field"
                                       placeholder="Enter the full title of your manuscript">
                            </div>
                            
                            <div>
                                <label for="abstract" class="block text-sm font-medium text-gray-700 mb-1">Abstract <span class="text-red-500">*</span></label>
                                <textarea id="abstract" name="abstract" rows="6" required 
                                          class="input-field"
                                          placeholder="Enter the abstract of your manuscript"></textarea>
                                <p class="text-xs text-gray-500 mt-1">Maximum 250 words</p>
                            </div>
                            
                            <div>
                                <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                                <select id="category" name="category" required 
                                        class="input-field">
                                    <option value="">Select a category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label for="keywords" class="block text-sm font-medium text-gray-700 mb-1">Keywords</label>
                                <input type="text" id="keywords" name="keywords" 
                                       class="input-field"
                                       placeholder="Enter keywords separated by commas">
                                <p class="text-xs text-gray-500 mt-1">Separate keywords with commas (e.g., rehabilitation, physical therapy, Tanzania)</p>
                            </div>
                            
                            <div>
                                <label for="file" class="block text-sm font-medium text-gray-700 mb-1">Manuscript File <span class="text-red-500">*</span></label>
                                <input type="file" id="file" name="file" accept=".doc,.docx,.pdf" required 
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition bg-white">
                                <p class="text-xs text-gray-500 mt-1">Accepted formats: .doc, .docx, .pdf (Max 10MB)</p>
                            </div>
                            
                            <div>
                                <label for="cover_letter" class="block text-sm font-medium text-gray-700 mb-1">Cover Letter</label>
                                <textarea id="cover_letter" name="cover_letter" rows="4" 
                                          class="input-field"
                                          placeholder="Add any additional information or cover letter"></textarea>
                            </div>
                            
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input type="checkbox" id="agreement" name="agreement" required 
                                           class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                </div>
                                <label for="agreement" class="ml-2 text-sm text-gray-600">
                                    I confirm that this manuscript is original and has not been published elsewhere.
                                    <span class="text-red-500">*</span>
                                </label>
                            </div>
                            
                            <button type="submit" class="btn-submit">
                                <i class="fas fa-paper-plane mr-2"></i> Submit Manuscript
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <i class="fas fa-check-circle text-6xl text-green-500 mb-4"></i>
                            <h3 class="text-xl font-semibold text-[#0b2b3f]">Submission Successful!</h3>
                            <p class="text-gray-600 mt-2">Your manuscript has been submitted for review.</p>
                            <a href="<?= SITE_URL ?>?page=dashboard" class="inline-block mt-4 bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                                <i class="fas fa-arrow-left mr-2"></i> Go to Dashboard
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- SIDEBAR -->
            <aside class="space-y-6">
                <!-- Author Portal -->
                <div class="bg-white rounded-xl shadow-card p-5 border border-gray-100/70">
                    <h4 class="font-semibold text-tirp flex items-center gap-2">
                        <i class="fas fa-pen-to-square text-indigo-500"></i> Author portal
                    </h4>
                    <p class="text-sm text-gray-500 mt-1">Submit, track, revise — all in one place.</p>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                        <a href="<?= SITE_URL ?>?page=submit" class="bg-tirp-light text-tirp font-medium py-2 rounded-lg text-center hover:bg-indigo-100 transition">New submission</a>
                        <a href="<?= SITE_URL ?>?page=dashboard" class="bg-gray-100 text-gray-600 py-2 rounded-lg text-center hover:bg-gray-200 transition">My drafts</a>
                        <a href="<?= SITE_URL ?>?page=dashboard" class="col-span-2 bg-white border border-gray-200 text-gray-600 py-2 rounded-lg text-center hover:bg-gray-50 transition">
                            <i class="fas fa-rotate-right mr-1"></i> Check status
                        </a>
                    </div>
                    <hr class="my-3 border-gray-100">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-400">
                            <i class="fas fa-user-circle mr-1"></i> 
                            <?= isLoggedIn() ? 'Welcome, ' . htmlspecialchars($_SESSION['user_name'] ?? 'User') : 'Welcome, Guest' ?>
                        </span>
                        <?php if (isLoggedIn()): ?>
                            <a href="<?= SITE_URL ?>?page=dashboard" class="text-indigo-600 font-medium">Dashboard</a>
                        <?php else: ?>
                            <a href="<?= SITE_URL ?>?page=login" class="text-indigo-600 font-medium">Login</a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Editorial Board -->
                <div class="bg-white rounded-xl shadow-card p-5 border border-gray-100/70">
                    <h4 class="font-semibold text-tirp flex items-center gap-2">
                        <i class="fas fa-users text-indigo-500"></i> Editorial board
                    </h4>
                    <ul class="mt-2 space-y-2 text-sm">
                        <?php if (!empty($editorialBoard)): ?>
                            <?php foreach ($editorialBoard as $member): ?>
                                <li class="flex justify-between">
                                    <span><?= htmlspecialchars($member['full_name'] ?? 'Unknown') ?></span>
                                    <span class="text-gray-400"><?= htmlspecialchars($member['position'] ?? 'Member') ?></span>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="flex justify-between"><span>Prof. A. M. Kilonzo</span> <span class="text-gray-400">Editor-in-Chief</span></li>
                            <li class="flex justify-between"><span>Dr. C. L. Mrema</span> <span class="text-gray-400">Managing Editor</span></li>
                            <li class="flex justify-between"><span>Prof. R. S. Ngowi</span> <span class="text-gray-400">Associate Editor</span></li>
                        <?php endif; ?>
                        <li class="text-xs text-indigo-600 pt-1">
                            <a href="<?= SITE_URL ?>?page=editorial" class="hover:underline">View full board <i class="fas fa-arrow-right ml-1"></i></a>
                        </li>
                    </ul>
                </div>

                <!-- Latest News Sidebar -->
                <div class="bg-white rounded-xl shadow-card p-5 border border-gray-100/70">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-semibold text-tirp flex items-center gap-2">
                            <i class="fas fa-newspaper text-indigo-500"></i> Latest News
                        </h4>
                        <a href="<?= SITE_URL ?>?page=news" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                            View All
                        </a>
                    </div>
                    
                    <?php if (empty($sidebarNews)): ?>
                        <p class="text-sm text-gray-400 text-center py-2">No news available.</p>
                    <?php else: ?>
                        <div class="space-y-3 max-h-80 overflow-y-auto pr-1">
                            <?php foreach ($sidebarNews as $item): ?>
                                <div class="news-item border-b border-gray-100 pb-2 last:border-0 last:pb-0 pl-2 hover:pl-3 transition-all">
                                    <a href="<?= SITE_URL ?>?page=news&id=<?= $item['id'] ?>" class="block hover:text-indigo-600 transition">
                                        <div class="flex items-start gap-2">
                                            <?php if ($item['is_featured']): ?>
                                                <i class="fas fa-star text-yellow-500 text-xs mt-1 flex-shrink-0"></i>
                                            <?php else: ?>
                                                <i class="fas fa-circle text-indigo-300 text-[6px] mt-1.5 flex-shrink-0"></i>
                                            <?php endif; ?>
                                            <div>
                                                <p class="text-sm font-medium text-gray-700 hover:text-indigo-600 transition leading-tight">
                                                    <?= htmlspecialchars($item['title']) ?>
                                                </p>
                                                <p class="text-xs text-gray-400 mt-0.5">
                                                    <?= formatDate($item['published_at'] ?? $item['created_at'], 'M d, Y') ?>
                                                </p>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Indexing -->
                <div class="bg-white rounded-xl shadow-card p-5 border border-gray-100/70 flex items-center justify-between flex-wrap gap-2">
                    <div><i class="fas fa-link text-tirp"></i> <span class="font-medium text-sm">Crossref DOI</span></div>
                    <div><i class="fas fa-google text-tirp"></i> <span class="font-medium text-sm">Google Scholar</span></div>
                    <div><i class="fas fa-database text-tirp"></i> <span class="font-medium text-sm">DOAJ</span></div>
                    <span class="text-xs bg-gray-100 px-3 py-1 rounded-full">Indexing ready</span>
                </div>
            </aside>
        </div>
    </div>
    
    <?php include INCLUDES_PATH . 'footer.php'; ?>
</body>
</html>
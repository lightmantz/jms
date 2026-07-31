<?php
// modules/about/index.php - About Page
require_once __DIR__ . '/../../includes/init.php';

// Get sidebar data
$editorialBoard = getEditorialBoard(4);
$currentUser = getCurrentUser();
$stats = getJournalStats();

// Get latest news for sidebar
$db = getDB();
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
    <title>About - <?= SITE_NAME ?></title>
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
                        <i class="fas fa-info-circle text-indigo-500"></i> About TIRP
                    </h1>
                    <div class="h-1 w-20 bg-indigo-200 rounded-full mt-2 mb-6"></div>
                    
                    <div class="prose max-w-none">
                        <h2 class="text-xl font-semibold text-[#0b2b3f] mt-6">Our Mission</h2>
                        <p class="text-gray-600">The Tanzania Journal of Rehabilitation Practice (TIRP) is a peer-reviewed, open-access journal dedicated to advancing rehabilitation science and practice in Tanzania and across the African continent.</p>
                        
                        <h2 class="text-xl font-semibold text-[#0b2b3f] mt-6">Our Vision</h2>
                        <p class="text-gray-600">To be the leading platform for rehabilitation research in Africa, promoting evidence-based practice and improving healthcare outcomes for people with disabilities and chronic conditions.</p>
                        
                        <h2 class="text-xl font-semibold text-[#0b2b3f] mt-6">Scope</h2>
                        <p class="text-gray-600">TIRP publishes original research, systematic reviews, case reports, clinical innovations, and professional perspectives in all areas of rehabilitation, including:</p>
                        <ul class="list-disc pl-6 text-gray-600 mt-2 space-y-1">
                            <li>Physical Therapy and Physiotherapy</li>
                            <li>Occupational Therapy</li>
                            <li>Speech and Language Therapy</li>
                            <li>Community-Based Rehabilitation</li>
                            <li>Disability Studies and Inclusion</li>
                            <li>Rehabilitation Technology and Assistive Devices</li>
                            <li>Mental Health Rehabilitation</li>
                            <li>Pediatric and Geriatric Rehabilitation</li>
                            <li>Sports Rehabilitation</li>
                        </ul>
                        
                        <h2 class="text-xl font-semibold text-[#0b2b3f] mt-6">Open Access Policy</h2>
                        <p class="text-gray-600">TIRP is committed to the principles of open access. All articles are freely available to read, download, and share under a Creative Commons license, ensuring maximum dissemination and impact of research.</p>
                        
                        <h2 class="text-xl font-semibold text-[#0b2b3f] mt-6">Publisher</h2>
                        <p class="text-gray-600">TIRP is published by the Tanzania Society of Rehabilitation in collaboration with Lightman Computers Tech.</p>
                    </div>
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
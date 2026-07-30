<?php
// modules/editorial/index.php - Editorial Board Page
require_once __DIR__ . '/../../includes/init.php';

// Get editorial board members
$boardMembers = getEditorialBoard();

// Group by position (optional)
$positions = [];
foreach ($boardMembers as $member) {
    $position = $member['position'] ?? 'Member';
    if (!isset($positions[$position])) {
        $positions[$position] = [];
    }
    $positions[$position][] = $member;
}

// If no positions defined, use a default
if (empty($positions)) {
    $positions['Editorial Board'] = $boardMembers;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editorial Board - <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>css/style.css">
</head>
<body class="antialiased text-gray-700 font-['Inter']" style="background: #f6f9fc;">
    <?php include INCLUDES_PATH . 'header.php'; ?>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-8 md:p-10">
            <h1 class="text-3xl font-bold text-[#0b2b3f] flex items-center gap-3">
                <i class="fas fa-users text-indigo-500"></i> Editorial Board
            </h1>
            <div class="h-1 w-20 bg-indigo-200 rounded-full mt-2 mb-6"></div>
            
            <?php if (empty($boardMembers)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-users text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-600">Editorial Board Coming Soon</h3>
                    <p class="text-gray-500">Our editorial board members will be listed here.</p>
                </div>
            <?php else: ?>
                <!-- Editorial Board Introduction -->
                <div class="mb-8">
                    <p class="text-gray-600 leading-relaxed">
                        The <strong>Tanzania Journal of Rehabilitation Practice (TIRP)</strong> is guided by a distinguished 
                        editorial board comprising experts in rehabilitation science from across the globe. 
                        Our board members provide strategic direction, maintain academic standards, and ensure 
                        the quality and integrity of our publications.
                    </p>
                </div>
                
                <!-- Board Members by Position -->
                <?php foreach ($positions as $position => $members): ?>
                    <div class="mb-10">
                        <h2 class="text-xl font-semibold text-[#0b2b3f] border-b border-gray-200 pb-3 mb-5">
                            <?= htmlspecialchars($position) ?>
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($members as $member): ?>
                                <div class="bg-gray-50 rounded-xl p-6 border border-gray-100 hover:shadow-md transition-shadow">
                                    <div class="flex items-start gap-4">
                                        <!-- Avatar -->
                                        <div class="flex-shrink-0">
                                            <?php if (!empty($member['avatar'])): ?>
                                                <img src="<?= SITE_URL . $member['avatar'] ?>" alt="<?= htmlspecialchars($member['full_name']) ?>" class="w-16 h-16 rounded-full object-cover">
                                            <?php else: ?>
                                                <div class="w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 text-xl font-semibold">
                                                    <?= strtoupper(substr($member['full_name'] ?? 'U', 0, 1)) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="flex-1 min-w-0">
                                            <h3 class="font-semibold text-[#0b2b3f] text-lg truncate">
                                                <?= htmlspecialchars($member['full_name'] ?? 'Unknown') ?>
                                            </h3>
                                            <?php if (!empty($member['position'])): ?>
                                                <p class="text-sm text-indigo-600 font-medium">
                                                    <?= htmlspecialchars($member['position']) ?>
                                                </p>
                                            <?php endif; ?>
                                            <?php if (!empty($member['institution'])): ?>
                                                <p class="text-sm text-gray-600 mt-1">
                                                    <i class="fas fa-university text-gray-400 mr-1"></i>
                                                    <?= htmlspecialchars($member['institution']) ?>
                                                </p>
                                            <?php endif; ?>
                                            <?php if (!empty($member['expertise'])): ?>
                                                <p class="text-xs text-gray-500 mt-2">
                                                    <span class="font-medium">Expertise:</span> 
                                                    <?= htmlspecialchars($member['expertise']) ?>
                                                </p>
                                            <?php endif; ?>
                                            <?php if (!empty($member['biography'])): ?>
                                                <p class="text-sm text-gray-600 mt-2 line-clamp-3">
                                                    <?= truncateText($member['biography'], 120) ?>
                                                </p>
                                            <?php endif; ?>
                                            <?php if (!empty($member['email'])): ?>
                                                <a href="mailto:<?= htmlspecialchars($member['email']) ?>" class="text-xs text-indigo-500 hover:text-indigo-700 mt-2 inline-block">
                                                    <i class="fas fa-envelope mr-1"></i> Email
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Join Editorial Board -->
                <div class="mt-10 p-6 bg-indigo-50 rounded-xl border border-indigo-100">
                    <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-[#0b2b3f] flex items-center gap-2">
                                <i class="fas fa-user-plus text-indigo-500"></i> 
                                Interested in Joining Our Editorial Board?
                            </h3>
                            <p class="text-gray-600 text-sm">
                                We welcome applications from qualified researchers and practitioners in rehabilitation sciences.
                            </p>
                        </div>
                        <a href="<?= SITE_URL ?>?page=contact" class="inline-flex items-center px-6 py-2.5 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors duration-200 whitespace-nowrap">
                            <i class="fas fa-paper-plane mr-2"></i> Contact Us
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include INCLUDES_PATH . 'footer.php'; ?>
</body>
</html>
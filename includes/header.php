<?php
// includes/header.php
if (!function_exists('isLoggedIn')) {
    require_once INCLUDES_PATH . 'functions.php';
    require_once INCLUDES_PATH . 'auth.php';
}

$currentUser = getCurrentUser();
$unreadCount = $currentUser ? getUnreadNotifications($currentUser['id']) : 0;
?>
<header class="bg-white border-b border-gray-200/80 sticky top-0 z-30 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16">
        <div class="flex items-center gap-2">
            <a href="<?= SITE_URL ?>?page=home" class="flex items-center gap-3">
                <!-- Logo -->
                <img src="<?= SITE_URL ?>resources/images/tjr.png" alt="TIRP Logo" class="h-10 w-auto object-contain">
                <div class="flex flex-col">
                    <span class="text-2xl font-bold text-[#0b2b3f] tracking-tight leading-none">TIRP</span>
                    <span class="hidden sm:inline text-xs font-medium text-gray-500 leading-tight">Tanzania Journal of Rehabilitation Practice</span>
                </div>
            </a>
        </div>
        <nav class="flex items-center gap-6 text-sm font-medium">
            <a href="<?= SITE_URL ?>?page=home" class="text-[#0b2b3f] hover:text-indigo-700 transition">Home</a>
            <a href="<?= SITE_URL ?>?page=about" class="text-gray-500 hover:text-[#0b2b3f] transition">About</a>
            <a href="<?= SITE_URL ?>?page=editorial" class="text-gray-500 hover:text-[#0b2b3f] transition">Editorial Board</a>
            <a href="<?= SITE_URL ?>?page=archive" class="text-gray-500 hover:text-[#0b2b3f] transition">Archive</a>
            <a href="<?= SITE_URL ?>?page=search" class="text-gray-500 hover:text-[#0b2b3f] transition"><i class="fas fa-search mr-1 text-xs"></i> Search</a>
            
            <?php if (isLoggedIn()): ?>
                <div class="relative group">
                    <button class="flex items-center gap-2 text-gray-700 hover:text-[#0b2b3f]">
                        <i class="fas fa-user-circle text-xl"></i>
                        <span><?= htmlspecialchars($currentUser['full_name']) ?></span>
                        <?php if ($unreadCount > 0): ?>
                            <span class="bg-red-500 text-white text-xs rounded-full px-2 py-0.5"><?= $unreadCount ?></span>
                        <?php endif; ?>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-100 hidden group-hover:block">
                        <?php 
                        $dashboardUrl = getDashboardUrl($currentUser);
                        ?>
                        <a href="<?= $dashboardUrl ?>" class="block px-4 py-2 text-sm hover:bg-gray-50">Dashboard</a>
                        <a href="<?= SITE_URL ?>?page=profile" class="block px-4 py-2 text-sm hover:bg-gray-50">Profile</a>
                        <a href="<?= SITE_URL ?>?page=notifications" class="block px-4 py-2 text-sm hover:bg-gray-50">
                            Notifications
                            <?php if ($unreadCount > 0): ?>
                                <span class="bg-red-500 text-white text-xs rounded-full px-2 py-0.5 ml-2"><?= $unreadCount ?></span>
                            <?php endif; ?>
                        </a>
                        <hr class="my-1">
                        <a href="<?= SITE_URL ?>modules/logout/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-50">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= SITE_URL ?>?page=login" class="text-gray-500 hover:text-[#0b2b3f] transition">Login</a>
                <a href="<?= SITE_URL ?>?page=register" class="bg-[#0b2b3f] text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-[#123a4f] transition shadow-sm">Register</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
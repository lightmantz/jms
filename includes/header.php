<?php
// includes/header.php
if (!function_exists('isLoggedIn')) {
    require_once INCLUDES_PATH . 'functions.php';
    require_once INCLUDES_PATH . 'auth.php';
}

$currentUser = getCurrentUser();
$unreadCount = $currentUser ? getUnreadNotifications($currentUser['id']) : 0;
$notifications = $currentUser ? getNotifications($currentUser['id'], 5) : [];
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
                <!-- Notifications Dropdown -->
                <div class="relative" id="notificationDropdown">
                    <button onclick="toggleDropdown('notificationDropdown')" class="relative text-gray-700 hover:text-[#0b2b3f] transition focus:outline-none">
                        <i class="fas fa-bell text-lg"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"><?= $unreadCount > 9 ? '9+' : $unreadCount ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-100 hidden z-50 max-h-96 overflow-y-auto">
                        <div class="p-3 border-b border-gray-100 flex justify-between items-center">
                            <span class="font-semibold text-[#0b2b3f]">Notifications</span>
                            <?php if ($unreadCount > 0): ?>
                                <a href="#" onclick="markAllRead(event)" class="text-xs text-indigo-600 hover:text-indigo-800">Mark all read</a>
                            <?php endif; ?>
                        </div>
                        <?php if (empty($notifications)): ?>
                            <div class="p-4 text-center text-gray-500 text-sm">
                                <i class="fas fa-bell-slash text-2xl text-gray-300 mb-2"></i>
                                <p>No notifications</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($notifications as $notif): ?>
                                <div class="border-b border-gray-100 hover:bg-gray-50 transition <?= $notif['is_read'] ? '' : 'bg-indigo-50' ?>">
                                    <a href="<?= $notif['link'] ?? '#' ?>" class="block p-3">
                                        <p class="text-sm font-medium text-[#0b2b3f]"><?= htmlspecialchars($notif['title']) ?></p>
                                        <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($notif['message']) ?></p>
                                        <p class="text-xs text-gray-400 mt-1"><?= timeAgo($notif['created_at']) ?></p>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <div class="p-2 border-t border-gray-100 text-center">
                            <a href="<?= SITE_URL ?>?page=notifications" class="text-xs text-indigo-600 hover:text-indigo-800">View all notifications</a>
                        </div>
                    </div>
                </div>

                <!-- User Menu Dropdown -->
                <div class="relative" id="userDropdown">
                    <button onclick="toggleDropdown('userDropdown')" class="flex items-center gap-2 text-gray-700 hover:text-[#0b2b3f] focus:outline-none">
                        <i class="fas fa-user-circle text-xl"></i>
                        <span><?= htmlspecialchars($currentUser['full_name']) ?></span>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    <div class="dropdown-menu absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-100 hidden z-50">
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

<script>
// Toggle dropdown visibility on click
function toggleDropdown(dropdownId) {
    const dropdown = document.getElementById(dropdownId);
    const menu = dropdown.querySelector('.dropdown-menu');
    
    // Close all other dropdowns first
    document.querySelectorAll('.dropdown-menu').forEach(function(el) {
        if (el !== menu) {
            el.classList.add('hidden');
        }
    });
    
    // Toggle the clicked dropdown
    menu.classList.toggle('hidden');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const isClickInsideDropdown = event.target.closest('.relative');
    
    if (!isClickInsideDropdown) {
        document.querySelectorAll('.dropdown-menu').forEach(function(el) {
            el.classList.add('hidden');
        });
    }
});

// Mark all notifications as read
function markAllRead(event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    fetch('<?= SITE_URL ?>modules/ajax/mark-notifications-read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    }).then(response => response.json())
      .then(data => {
          if (data.success) {
              location.reload();
          }
      });
}

// Close dropdowns when pressing Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        document.querySelectorAll('.dropdown-menu').forEach(function(el) {
            el.classList.add('hidden');
        });
    }
});

// Prevent dropdown from closing when clicking inside it
document.addEventListener('click', function(event) {
    const dropdownMenus = document.querySelectorAll('.dropdown-menu');
    dropdownMenus.forEach(function(menu) {
        if (menu.contains(event.target)) {
            event.stopPropagation();
        }
    });
});
</script>
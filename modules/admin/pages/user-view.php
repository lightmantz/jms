<?php
// modules/admin/pages/user-view.php - View User Details
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

requireRole(['admin']);

$db = getDB();
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($userId <= 0) {
    echo '<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">Invalid user ID.</div>';
    return;
}

// Get user details
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    echo '<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">User not found.</div>';
    return;
}

// Get user stats
$stmt = $db->prepare("SELECT COUNT(*) as count FROM manuscripts WHERE author_id = ? OR corresponding_author_id = ?");
$stmt->execute([$userId, $userId]);
$manuscriptCount = $stmt->fetch()['count'];

$stmt = $db->prepare("SELECT COUNT(*) as count FROM reviews WHERE reviewer_id = ?");
$stmt->execute([$userId]);
$reviewCount = $stmt->fetch()['count'];

$roleColors = [
    'admin' => 'bg-red-100 text-red-700',
    'editor' => 'bg-blue-100 text-blue-700',
    'reviewer' => 'bg-yellow-100 text-yellow-700',
    'author' => 'bg-green-100 text-green-700',
    'reader' => 'bg-gray-100 text-gray-700',
    'staff' => 'bg-purple-100 text-purple-700'
];
?>
<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">User Details</h2>
            <p class="text-gray-500 text-sm mt-1">View user information and activity</p>
        </div>
        <div class="flex gap-3">
            <a href="/jms/admin?action=users" class="text-indigo-600 hover:text-indigo-800 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back to Users
            </a>
            <button onclick="window.location.href='/jms/admin?action=users&edit=<?= $user['id'] ?>'" 
                    class="bg-[#0b2b3f] text-white px-4 py-2 rounded-lg hover:bg-[#123a4f] transition text-sm">
                <i class="fas fa-edit mr-1"></i> Edit User
            </button>
        </div>
    </div>
    <div class="h-1 w-20 bg-indigo-200 rounded-full mb-6"></div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Profile -->
        <div class="md:col-span-2">
            <div class="flex items-start gap-4 p-4 bg-gray-50 rounded-xl border border-gray-200">
                <div class="w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-2xl flex-shrink-0">
                    <?php 
                    $initials = '';
                    $nameParts = explode(' ', $user['full_name']);
                    foreach ($nameParts as $part) {
                        if (!empty($part)) {
                            $initials .= strtoupper(substr($part, 0, 1));
                        }
                    }
                    echo htmlspecialchars(substr($initials, 0, 2));
                    ?>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-[#0b2b3f]"><?= htmlspecialchars($user['full_name']) ?></h3>
                    <p class="text-sm text-gray-500"><?= htmlspecialchars($user['email']) ?></p>
                    <?php if ($user['institution']): ?>
                        <p class="text-sm text-gray-600"><i class="fas fa-university mr-1"></i> <?= htmlspecialchars($user['institution']) ?></p>
                    <?php endif; ?>
                    <div class="flex flex-wrap gap-2 mt-2">
                        <span class="px-2 py-1 rounded-full text-xs font-medium <?= $roleColors[$user['role']] ?? 'bg-gray-100 text-gray-700' ?>">
                            <?= ucfirst($user['role']) ?>
                        </span>
                        <?php if ($user['is_active']): ?>
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>
                        <?php else: ?>
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-700">Pending</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-3 gap-4 mt-4">
                <div class="bg-blue-50 rounded-xl p-4 text-center border border-blue-100">
                    <p class="text-2xl font-bold text-blue-700"><?= $manuscriptCount ?></p>
                    <p class="text-xs text-blue-600">Manuscripts</p>
                </div>
                <div class="bg-yellow-50 rounded-xl p-4 text-center border border-yellow-100">
                    <p class="text-2xl font-bold text-yellow-700"><?= $reviewCount ?></p>
                    <p class="text-xs text-yellow-600">Reviews</p>
                </div>
                <div class="bg-green-50 rounded-xl p-4 text-center border border-green-100">
                    <p class="text-2xl font-bold text-green-700"><?= formatDate($user['created_at'], 'M d, Y') ?></p>
                    <p class="text-xs text-green-600">Joined</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="space-y-4">
            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                <h4 class="font-semibold text-[#0b2b3f] mb-3">Quick Actions</h4>
                <div class="space-y-2">
                    <?php if (!$user['is_active']): ?>
                        <button onclick="approveUser(<?= $user['id'] ?>)" 
                                class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition text-sm font-medium">
                            <i class="fas fa-check-circle mr-2"></i> Approve User
                        </button>
                    <?php endif; ?>
                    <?php if ($user['id'] != $currentUser['id']): ?>
                        <button onclick="deleteUser(<?= $user['id'] ?>)" 
                                class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition text-sm font-medium">
                            <i class="fas fa-trash mr-2"></i> Delete User
                        </button>
                    <?php endif; ?>
                    <button onclick="window.location.href='/jms/admin?action=users&edit=<?= $user['id'] ?>'" 
                            class="w-full bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition text-sm font-medium">
                        <i class="fas fa-edit mr-2"></i> Edit User
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function approveUser(id) {
    if (confirm('Approve this user? They will be able to login and access the system.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="approve_user">
            <input type="hidden" name="user_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function deleteUser(id) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_user">
            <input type="hidden" name="user_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
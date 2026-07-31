<?php
// modules/admin/pages/users.php - Manage Users
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

requireRole(['admin']);

$db = getDB();
$message = '';
$error = '';
$currentUser = getCurrentUser();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_user') {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'author';
        $institution = trim($_POST['institution'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($full_name) || empty($email) || empty($password)) {
            $error = 'Please fill in all required fields.';
        } else {
            // Check if email exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email already exists.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("
                    INSERT INTO users (full_name, email, password_hash, role, institution, is_active, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                if ($stmt->execute([$full_name, $email, $hashedPassword, $role, $institution, $is_active])) {
                    $message = 'User added successfully!';
                    logAction($currentUser['id'], 'add_user', 'users', $db->lastInsertId());
                } else {
                    $error = 'Failed to add user.';
                }
            }
        }
    } elseif ($action === 'update_user_role') {
        $user_id = (int)$_POST['user_id'];
        $role = $_POST['role'] ?? 'author';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt = $db->prepare("UPDATE users SET role = ?, is_active = ? WHERE id = ?");
        if ($stmt->execute([$role, $is_active, $user_id])) {
            $message = 'User updated successfully!';
            logAction($currentUser['id'], 'update_user', 'users', $user_id);
        } else {
            $error = 'Failed to update user.';
        }
    } elseif ($action === 'approve_user') {
        $user_id = (int)$_POST['user_id'];
        
        $stmt = $db->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
        if ($stmt->execute([$user_id])) {
            $message = 'User approved successfully!';
            logAction($currentUser['id'], 'approve_user', 'users', $user_id);
        } else {
            $error = 'Failed to approve user.';
        }
    } elseif ($action === 'delete_user') {
        $user_id = (int)$_POST['user_id'];
        
        // Don't allow admin to delete themselves
        if ($user_id == $currentUser['id']) {
            $error = 'You cannot delete your own account.';
        } else {
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$user_id])) {
                $message = 'User deleted successfully!';
                logAction($currentUser['id'], 'delete_user', 'users', $user_id);
            } else {
                $error = 'Failed to delete user.';
            }
        }
    }
}

// Get the subaction
$subaction = $_GET['subaction'] ?? 'all';

// Define role mapping
$roleMap = [
    'authors' => 'author',
    'editors' => 'editor',
    'reviewers' => 'reviewer',
    'staff' => 'staff',
    'all' => null,
    'pending' => 'pending'
];

$role = $roleMap[$subaction] ?? null;

// Build query
$sql = "SELECT u.* FROM users u";
$params = [];

if ($subaction == 'pending') {
    // For pending users, check is_active = 0
    $sql .= " WHERE u.is_active = 0";
} elseif ($role) {
    $sql .= " WHERE u.role = ?";
    $params[] = $role;
} elseif ($subaction == 'staff') {
    $sql .= " WHERE u.role IN ('admin', 'staff')";
} elseif ($subaction == 'all') {
    $sql .= " WHERE u.role NOT IN ('admin')";
}

$sql .= " ORDER BY u.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get counts
$counts = [];
$stmt = $db->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
while ($row = $stmt->fetch()) {
    $counts[$row['role']] = $row['count'];
}

// Get pending users count (is_active = 0)
$stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE is_active = 0");
$pendingCount = $stmt->fetch()['count'] ?? 0;

$staffCount = ($counts['admin'] ?? 0) + ($counts['staff'] ?? 0);

// Get current label
$labels = [
    'all' => 'All Users',
    'authors' => 'Authors',
    'editors' => 'Editors',
    'reviewers' => 'Reviewers',
    'staff' => 'Staff',
    'pending' => 'Pending Approval'
];
$currentLabel = $labels[$subaction] ?? 'Users';

// Role colors for display
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
            <h2 class="text-2xl font-bold text-[#0b2b3f]"><?= htmlspecialchars($currentLabel) ?></h2>
            <p class="text-gray-500 text-sm mt-1">Manage <?= strtolower($currentLabel) ?></p>
        </div>
        <div class="flex gap-3">
            <a href="/jms/admin" class="text-indigo-600 hover:text-indigo-800 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
            <button onclick="openAddUserModal()" class="bg-[#0b2b3f] text-white px-4 py-2 rounded-lg hover:bg-[#123a4f] transition text-sm">
                <i class="fas fa-user-plus mr-1"></i> Add User
            </button>
        </div>
    </div>
    <div class="h-1 w-20 bg-indigo-200 rounded-full mb-6"></div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-6">
        <div class="bg-gray-50 rounded-xl p-4 text-center border border-gray-200">
            <p class="text-2xl font-bold text-gray-700"><?= array_sum($counts) ?></p>
            <p class="text-xs text-gray-600">Total Users</p>
        </div>
        <div class="bg-green-50 rounded-xl p-4 text-center border border-green-200">
            <p class="text-2xl font-bold text-green-700"><?= $counts['author'] ?? 0 ?></p>
            <p class="text-xs text-green-600">Authors</p>
        </div>
        <div class="bg-blue-50 rounded-xl p-4 text-center border border-blue-200">
            <p class="text-2xl font-bold text-blue-700"><?= $counts['editor'] ?? 0 ?></p>
            <p class="text-xs text-blue-600">Editors</p>
        </div>
        <div class="bg-yellow-50 rounded-xl p-4 text-center border border-yellow-200">
            <p class="text-2xl font-bold text-yellow-700"><?= $counts['reviewer'] ?? 0 ?></p>
            <p class="text-xs text-yellow-600">Reviewers</p>
        </div>
        <div class="bg-purple-50 rounded-xl p-4 text-center border border-purple-200">
            <p class="text-2xl font-bold text-purple-700"><?= $staffCount ?></p>
            <p class="text-xs text-purple-600">Staff</p>
        </div>
        <div class="bg-orange-50 rounded-xl p-4 text-center border border-orange-200">
            <p class="text-2xl font-bold text-orange-700"><?= $pendingCount ?></p>
            <p class="text-xs text-orange-600">Pending</p>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 pb-4">
        <a href="/jms/admin?action=users&subaction=all" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $subaction == 'all' ? 'bg-[#0b2b3f] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">
            <i class="fas fa-users mr-1"></i> All (<?= array_sum($counts) ?>)
        </a>
        <a href="/jms/admin?action=users&subaction=authors" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $subaction == 'authors' ? 'bg-green-600 text-white' : 'bg-green-50 text-green-600 hover:bg-green-100' ?>">
            <i class="fas fa-user-edit mr-1"></i> Authors (<?= $counts['author'] ?? 0 ?>)
        </a>
        <a href="/jms/admin?action=users&subaction=editors" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $subaction == 'editors' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-600 hover:bg-blue-100' ?>">
            <i class="fas fa-user-tie mr-1"></i> Editors (<?= $counts['editor'] ?? 0 ?>)
        </a>
        <a href="/jms/admin?action=users&subaction=reviewers" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $subaction == 'reviewers' ? 'bg-yellow-600 text-white' : 'bg-yellow-50 text-yellow-600 hover:bg-yellow-100' ?>">
            <i class="fas fa-user-graduate mr-1"></i> Reviewers (<?= $counts['reviewer'] ?? 0 ?>)
        </a>
        <a href="/jms/admin?action=users&subaction=staff" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $subaction == 'staff' ? 'bg-purple-600 text-white' : 'bg-purple-50 text-purple-600 hover:bg-purple-100' ?>">
            <i class="fas fa-user-cog mr-1"></i> Staff (<?= $staffCount ?>)
        </a>
        <a href="/jms/admin?action=users&subaction=pending" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $subaction == 'pending' ? 'bg-orange-600 text-white' : 'bg-orange-50 text-orange-600 hover:bg-orange-100' ?>">
            <i class="fas fa-clock mr-1"></i> Pending (<?= $pendingCount ?>)
        </a>
    </div>

    <?php if ($message): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm mb-6">
            <i class="fas fa-check-circle mr-2"></i> <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm mb-6">
            <i class="fas fa-exclamation-circle mr-2"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Search -->
    <div class="mb-6">
        <form method="GET" action="" class="flex flex-wrap gap-3">
            <input type="hidden" name="action" value="users">
            <input type="hidden" name="subaction" value="<?= htmlspecialchars($subaction) ?>">
            <div class="flex-1 min-w-[200px]">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input type="text" name="search" placeholder="Search by name, email, or institution..." 
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                           class="w-full pl-9 pr-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none text-sm">
                </div>
            </div>
            <button type="submit" class="bg-[#0b2b3f] text-white px-4 py-2 rounded-lg hover:bg-[#123a4f] transition text-sm">
                <i class="fas fa-search mr-1"></i> Search
            </button>
            <?php if (!empty($_GET['search'])): ?>
                <a href="/jms/admin?action=users&subaction=<?= $subaction ?>" 
                   class="text-sm text-gray-500 hover:text-[#0b2b3f]">
                    <i class="fas fa-times mr-1"></i> Clear
                </a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (empty($users)): ?>
        <div class="text-center py-12">
            <i class="fas fa-users text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No <?= strtolower($currentLabel) ?> found.</p>
            <button onclick="openAddUserModal()" class="mt-3 bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                <i class="fas fa-user-plus mr-2"></i> Add User
            </button>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">User</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Role</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Institution</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Joined</th>
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                        <td class="py-2 px-3">
                            <div>
                                <p class="font-medium text-[#0b2b3f] text-sm"><?= htmlspecialchars($user['full_name']) ?></p>
                                <p class="text-xs text-gray-400"><?= htmlspecialchars($user['email']) ?></p>
                            </div>
                        </td>
                        <td class="py-2 px-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium <?= $roleColors[$user['role']] ?? 'bg-gray-100 text-gray-700' ?>">
                                <?= ucfirst($user['role']) ?>
                            </span>
                        </td>
                        <td class="py-2 px-3 text-sm text-gray-600"><?= htmlspecialchars($user['institution'] ?? '-') ?></td>
                        <td class="py-2 px-3">
                            <?php if ($user['is_active']): ?>
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>
                            <?php else: ?>
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-700">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-2 px-3 text-sm text-gray-600"><?= formatDate($user['created_at']) ?></td>
                        <td class="py-2 px-3">
                            <div class="flex gap-1 flex-wrap">
                                <a href="/jms/admin?action=user-view&id=<?= $user['id'] ?>" 
                                   class="text-blue-600 hover:text-blue-800 text-sm" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button onclick="openEditUserModal(<?= htmlspecialchars(json_encode($user)) ?>)" 
                                        class="text-indigo-600 hover:text-indigo-800 text-sm" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if (!$user['is_active']): ?>
                                    <button onclick="approveUser(<?= $user['id'] ?>)" 
                                            class="text-green-600 hover:text-green-800 text-sm" title="Approve">
                                        <i class="fas fa-check-circle"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if ($user['id'] != $currentUser['id']): ?>
                                    <button onclick="deleteUser(<?= $user['id'] ?>)" 
                                            class="text-red-600 hover:text-red-800 text-sm" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-4 text-sm text-gray-400">
            Showing <?= count($users) ?> <?= strtolower($currentLabel) ?>
        </div>
    <?php endif; ?>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-[#0b2b3f]">Add New User</h3>
            <button onclick="closeAddUserModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add_user">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                    <input type="text" name="full_name" required 
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                    <input type="email" name="email" required 
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                    <input type="password" name="password" required 
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                    <p class="text-xs text-gray-400 mt-1">Minimum 6 characters</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
                    <select name="role" required class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                        <option value="author">Author</option>
                        <option value="editor">Editor</option>
                        <option value="reviewer">Reviewer</option>
                        <option value="staff">Staff</option>
                        <option value="reader">Reader</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Institution</label>
                    <input type="text" name="institution" 
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                </div>
                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" checked>
                        <span class="text-sm text-gray-700">Active</span>
                    </label>
                </div>
            </div>
            
            <div class="flex gap-3 mt-6">
                <button type="submit" class="bg-[#0b2b3f] text-white px-6 py-2 rounded-lg font-semibold hover:bg-[#123a4f] transition flex-1">
                    <i class="fas fa-save mr-2"></i> Add User
                </button>
                <button type="button" onclick="closeAddUserModal()" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg font-semibold hover:bg-gray-200 transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-[#0b2b3f]">Edit User</h3>
            <button onclick="closeEditUserModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="update_user_role">
            <input type="hidden" name="user_id" id="editUserId">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">User</label>
                    <p class="text-sm font-medium text-[#0b2b3f]" id="editUserName"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
                    <select name="role" id="editUserRole" required class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                        <option value="admin">Admin</option>
                        <option value="editor">Editor</option>
                        <option value="reviewer">Reviewer</option>
                        <option value="author">Author</option>
                        <option value="staff">Staff</option>
                        <option value="reader">Reader</option>
                    </select>
                </div>
                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" id="editUserActive">
                        <span class="text-sm text-gray-700">Active</span>
                    </label>
                </div>
            </div>
            
            <div class="flex gap-3 mt-6">
                <button type="submit" class="bg-[#0b2b3f] text-white px-6 py-2 rounded-lg font-semibold hover:bg-[#123a4f] transition flex-1">
                    <i class="fas fa-save mr-2"></i> Update
                </button>
                <button type="button" onclick="closeEditUserModal()" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg font-semibold hover:bg-gray-200 transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddUserModal() {
    document.getElementById('addUserModal').classList.remove('hidden');
}

function closeAddUserModal() {
    document.getElementById('addUserModal').classList.add('hidden');
}

function openEditUserModal(user) {
    document.getElementById('editUserId').value = user.id;
    document.getElementById('editUserName').textContent = user.full_name + ' (' + user.email + ')';
    document.getElementById('editUserRole').value = user.role;
    document.getElementById('editUserActive').checked = user.is_active == 1;
    document.getElementById('editUserModal').classList.remove('hidden');
}

function closeEditUserModal() {
    document.getElementById('editUserModal').classList.add('hidden');
}

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
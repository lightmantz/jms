<?php
// modules/admin/pages/roles.php - Manage Roles & Permissions
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

requireRole(['admin']);

$db = getDB();
$message = '';
$error = '';

// Define all roles and their permissions
$rolePermissions = [
    'admin' => [
        'name' => 'Administrator',
        'color' => 'red',
        'description' => 'Full system access with all permissions',
        'permissions' => [
            'dashboard' => 'Access Dashboard',
            'manage_users' => 'Manage Users',
            'manage_roles' => 'Manage Roles & Permissions',
            'manage_manuscripts' => 'Manage All Manuscripts',
            'manage_editorial' => 'Manage Editorial Board',
            'manage_reviewers' => 'Manage Reviewers',
            'manage_volumes' => 'Manage Volumes & Issues',
            'manage_settings' => 'Manage Settings',
            'publish_articles' => 'Publish Articles',
            'view_reports' => 'View Reports',
            'manage_content' => 'Manage Content Pages',
            'view_logs' => 'View System Logs',
            'manage_backups' => 'Manage Backups'
        ]
    ],
    'editor' => [
        'name' => 'Editor',
        'color' => 'blue',
        'description' => 'Manage submissions, reviews, and publishing workflow',
        'permissions' => [
            'dashboard' => 'Access Dashboard',
            'manage_manuscripts' => 'Manage Assigned Manuscripts',
            'manage_reviews' => 'Manage Reviews',
            'assign_reviewers' => 'Assign Reviewers',
            'make_decisions' => 'Make Editorial Decisions',
            'manage_volumes' => 'Manage Volumes & Issues',
            'publish_articles' => 'Publish Articles',
            'view_reports' => 'View Reports'
        ]
    ],
    'reviewer' => [
        'name' => 'Reviewer',
        'color' => 'yellow',
        'description' => 'Conduct peer reviews for assigned manuscripts',
        'permissions' => [
            'dashboard' => 'Access Dashboard',
            'view_assigned' => 'View Assigned Manuscripts',
            'submit_reviews' => 'Submit Reviews',
            'view_manuscripts' => 'View Manuscript Details'
        ]
    ],
    'author' => [
        'name' => 'Author',
        'color' => 'green',
        'description' => 'Submit and track manuscripts',
        'permissions' => [
            'dashboard' => 'Access Dashboard',
            'submit_manuscripts' => 'Submit Manuscripts',
            'track_manuscripts' => 'Track Manuscripts',
            'view_publications' => 'View Publications'
        ]
    ],
    'staff' => [
        'name' => 'Staff',
        'color' => 'purple',
        'description' => 'Support staff with limited administrative access',
        'permissions' => [
            'dashboard' => 'Access Dashboard',
            'manage_content' => 'Manage Content Pages',
            'manage_announcements' => 'Manage Announcements',
            'view_reports' => 'View Reports',
            'manage_users' => 'View Users'
        ]
    ],
    'reader' => [
        'name' => 'Reader',
        'color' => 'gray',
        'description' => 'Read and search published articles',
        'permissions' => [
            'view_publications' => 'View Publications',
            'search_articles' => 'Search Articles',
            'download_articles' => 'Download Articles'
        ]
    ]
];

// Get all roles from database for count
$stmt = $db->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$roleCounts = [];
while ($row = $stmt->fetch()) {
    $roleCounts[$row['role']] = $row['count'];
}

// Handle permission updates (simulated - in production you'd have a proper permission system)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_permissions'])) {
    // In a real system, you would update a permissions table
    // For now, we'll just show a success message
    $message = 'Permissions updated successfully! (Demo mode - permissions are static)';
    logAction($currentUser['id'], 'update_permissions', 'settings', 0);
}
?>
<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Roles & Permissions</h2>
            <p class="text-gray-500 text-sm mt-1">Manage user roles and their permissions</p>
        </div>
        <div class="flex gap-3">
            <a href="/jms/admin" class="text-indigo-600 hover:text-indigo-800 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
            <a href="/jms/admin?action=users" class="bg-[#0b2b3f] text-white px-4 py-2 rounded-lg hover:bg-[#123a4f] transition text-sm">
                <i class="fas fa-users mr-1"></i> Manage Users
            </a>
        </div>
    </div>
    <div class="h-1 w-20 bg-indigo-200 rounded-full mb-6"></div>

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

    <!-- Role Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($rolePermissions as $roleKey => $role): ?>
        <div class="border border-gray-200 rounded-xl p-4 hover:shadow-md transition">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <h3 class="font-semibold text-[#0b2b3f] text-lg"><?= htmlspecialchars($role['name']) ?></h3>
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-<?= $role['color'] ?>-100 text-<?= $role['color'] ?>-700">
                        <?= ucfirst($roleKey) ?>
                    </span>
                    <span class="text-xs text-gray-400 ml-2">
                        <?= $roleCounts[$roleKey] ?? 0 ?> users
                    </span>
                </div>
                <?php if ($roleKey != 'admin'): ?>
                <span class="text-xs text-blue-600 cursor-pointer" onclick="alert('Edit role: <?= ucfirst($roleKey) ?>')">
                    <i class="fas fa-edit"></i>
                </span>
                <?php endif; ?>
            </div>
            <p class="text-sm text-gray-500 mb-3"><?= htmlspecialchars($role['description']) ?></p>
            
            <div class="space-y-1">
                <?php foreach ($role['permissions'] as $permKey => $permLabel): ?>
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <i class="fas fa-check-circle text-green-500 text-xs"></i>
                    <span><?= htmlspecialchars($permLabel) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($roleKey != 'admin'): ?>
            <div class="mt-3 pt-3 border-t border-gray-100">
                <button onclick="openEditRoleModal('<?= $roleKey ?>')" 
                        class="w-full text-center text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                    <i class="fas fa-edit mr-1"></i> Edit Permissions
                </button>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Role Hierarchy -->
    <div class="mt-8 border-t border-gray-200 pt-6">
        <h3 class="font-semibold text-[#0b2b3f] mb-4">Role Hierarchy</h3>
        <div class="bg-gray-50 rounded-xl p-4">
            <div class="space-y-2">
                <div class="flex items-center gap-3 p-2 bg-red-50 rounded-lg border border-red-200">
                    <span class="font-bold text-red-700">Administrator</span>
                    <span class="text-xs text-gray-500">→</span>
                    <span class="text-sm text-gray-600">Full system access, all permissions</span>
                </div>
                <div class="flex items-center gap-3 p-2 bg-blue-50 rounded-lg border border-blue-200 ml-4">
                    <span class="font-bold text-blue-700">Editor</span>
                    <span class="text-xs text-gray-500">→</span>
                    <span class="text-sm text-gray-600">Manage submissions, reviews, publishing</span>
                </div>
                <div class="flex items-center gap-3 p-2 bg-yellow-50 rounded-lg border border-yellow-200 ml-8">
                    <span class="font-bold text-yellow-700">Reviewer</span>
                    <span class="text-xs text-gray-500">→</span>
                    <span class="text-sm text-gray-600">Conduct peer reviews</span>
                </div>
                <div class="flex items-center gap-3 p-2 bg-green-50 rounded-lg border border-green-200 ml-8">
                    <span class="font-bold text-green-700">Author</span>
                    <span class="text-xs text-gray-500">→</span>
                    <span class="text-sm text-gray-600">Submit and track manuscripts</span>
                </div>
                <div class="flex items-center gap-3 p-2 bg-purple-50 rounded-lg border border-purple-200 ml-4">
                    <span class="font-bold text-purple-700">Staff</span>
                    <span class="text-xs text-gray-500">→</span>
                    <span class="text-sm text-gray-600">Content management, support</span>
                </div>
                <div class="flex items-center gap-3 p-2 bg-gray-50 rounded-lg border border-gray-200 ml-8">
                    <span class="font-bold text-gray-700">Reader</span>
                    <span class="text-xs text-gray-500">→</span>
                    <span class="text-sm text-gray-600">Read and search articles</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Permission Matrix -->
    <div class="mt-8 border-t border-gray-200 pt-6">
        <h3 class="font-semibold text-[#0b2b3f] mb-4">Permission Matrix</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500 uppercase">Permission</th>
                        <?php foreach ($rolePermissions as $roleKey => $role): ?>
                        <th class="text-center py-2 px-3 text-xs font-semibold text-gray-500 uppercase">
                            <?= ucfirst($roleKey) ?>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Collect all unique permissions
                    $allPermissions = [];
                    foreach ($rolePermissions as $role) {
                        foreach ($role['permissions'] as $key => $label) {
                            $allPermissions[$key] = $label;
                        }
                    }
                    ?>
                    <?php foreach ($allPermissions as $permKey => $permLabel): ?>
                    <tr class="border-b border-gray-100">
                        <td class="py-2 px-3 text-gray-600"><?= htmlspecialchars($permLabel) ?></td>
                        <?php foreach ($rolePermissions as $roleKey => $role): ?>
                        <td class="text-center py-2 px-3">
                            <?php if (isset($role['permissions'][$permKey])): ?>
                                <i class="fas fa-check-circle text-green-500"></i>
                            <?php else: ?>
                                <i class="fas fa-circle text-gray-300"></i>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Info Box -->
    <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
        <p class="text-sm text-blue-700">
            <i class="fas fa-info-circle mr-2"></i>
            <strong>Note:</strong> Role permissions are predefined in the system. To modify permissions,
            you would need to update the role configuration in the system code.
        </p>
    </div>
</div>

<!-- Edit Role Modal -->
<div id="editRoleModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-[#0b2b3f]">Edit Role Permissions</h3>
            <button onclick="closeEditRoleModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <p class="text-sm font-medium text-[#0b2b3f]" id="editRoleName"></p>
                </div>
                <div class="space-y-2">
                    <p class="text-sm text-gray-500">Select permissions for this role:</p>
                    <div id="editRolePermissions" class="space-y-2">
                        <!-- Permissions will be loaded via JavaScript -->
                    </div>
                </div>
            </div>
            
            <div class="flex gap-3 mt-6">
                <button type="submit" name="update_permissions" class="bg-[#0b2b3f] text-white px-6 py-2 rounded-lg font-semibold hover:bg-[#123a4f] transition flex-1">
                    <i class="fas fa-save mr-2"></i> Update Permissions
                </button>
                <button type="button" onclick="closeEditRoleModal()" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg font-semibold hover:bg-gray-200 transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditRoleModal(roleKey) {
    const rolePermissions = <?= json_encode($rolePermissions) ?>;
    const role = rolePermissions[roleKey];
    
    document.getElementById('editRoleName').textContent = role.name + ' (' + roleKey + ')';
    
    // Build permission checkboxes
    const container = document.getElementById('editRolePermissions');
    container.innerHTML = '';
    
    // Get all unique permissions
    const allPerms = [];
    for (const r in rolePermissions) {
        for (const key in rolePermissions[r].permissions) {
            if (!allPerms.find(p => p.key === key)) {
                allPerms.push({key: key, label: rolePermissions[r].permissions[key]});
            }
        }
    }
    
    allPerms.forEach(perm => {
        const checked = role.permissions[perm.key] ? 'checked' : '';
        container.innerHTML += `
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="permissions[]" value="${perm.key}" ${checked}>
                ${perm.label}
            </label>
        `;
    });
    
    document.getElementById('editRoleModal').classList.remove('hidden');
}

function closeEditRoleModal() {
    document.getElementById('editRoleModal').classList.add('hidden');
}
</script>
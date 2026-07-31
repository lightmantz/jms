<?php
// modules/admin/pages/reviewers.php - Manage Reviewers
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

requireRole(['admin']);

$db = getDB();
$message = '';
$error = '';
$currentUser = getCurrentUser();

// Get reviewer stats
function getReviewerStats($reviewerId) {
    $db = getDB();
    
    // Pending reviews
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM reviews WHERE reviewer_id = ? AND status IN ('invited', 'accepted')");
    $stmt->execute([$reviewerId]);
    $pending = $stmt->fetch()['count'] ?? 0;
    
    // Completed reviews
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM reviews WHERE reviewer_id = ? AND status = 'completed'");
    $stmt->execute([$reviewerId]);
    $completed = $stmt->fetch()['count'] ?? 0;
    
    return ['pending' => $pending, 'completed' => $completed];
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_reviewer') {
        $user_id = (int)$_POST['user_id'];
        $expertise = trim($_POST['expertise'] ?? '');
        $affiliation = trim($_POST['affiliation'] ?? '');
        $max_assignments = (int)$_POST['max_assignments'] ?? 5;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Check if user is already a reviewer
        $stmt = $db->prepare("SELECT id FROM users WHERE id = ? AND role IN ('reviewer', 'admin')");
        $stmt->execute([$user_id]);
        if ($stmt->fetch()) {
            $error = 'This user is already a reviewer.';
        } else {
            $stmt = $db->prepare("UPDATE users SET role = 'reviewer', institution = ?, bio = ?, is_active = ? WHERE id = ?");
            if ($stmt->execute([$affiliation, $expertise, $is_active, $user_id])) {
                $message = 'Reviewer added successfully!';
                logAction($currentUser['id'], 'add_reviewer', 'users', $user_id);
            } else {
                $error = 'Failed to add reviewer.';
            }
        }
    } elseif ($action === 'update_reviewer') {
        $user_id = (int)$_POST['user_id'];
        $expertise = trim($_POST['expertise'] ?? '');
        $affiliation = trim($_POST['affiliation'] ?? '');
        $max_assignments = (int)$_POST['max_assignments'] ?? 5;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt = $db->prepare("UPDATE users SET institution = ?, bio = ?, is_active = ? WHERE id = ?");
        if ($stmt->execute([$affiliation, $expertise, $is_active, $user_id])) {
            $message = 'Reviewer updated successfully!';
            logAction($currentUser['id'], 'update_reviewer', 'users', $user_id);
        } else {
            $error = 'Failed to update reviewer.';
        }
    } elseif ($action === 'remove_reviewer') {
        $user_id = (int)$_POST['user_id'];
        
        // Check if reviewer has pending reviews
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM reviews WHERE reviewer_id = ? AND status IN ('invited', 'accepted')");
        $stmt->execute([$user_id]);
        $pending = $stmt->fetch()['count'];
        
        if ($pending > 0) {
            $error = 'Cannot remove reviewer with pending reviews.';
        } else {
            $stmt = $db->prepare("UPDATE users SET role = 'author', is_active = 0 WHERE id = ?");
            if ($stmt->execute([$user_id])) {
                $message = 'Reviewer removed successfully!';
                logAction($currentUser['id'], 'remove_reviewer', 'users', $user_id);
            } else {
                $error = 'Failed to remove reviewer.';
            }
        }
    }
}

// Get all reviewers
$reviewers = getReviewers();

// Add stats to each reviewer
foreach ($reviewers as &$reviewer) {
    $stats = getReviewerStats($reviewer['id']);
    $reviewer['pending_reviews'] = $stats['pending'];
    $reviewer['completed_reviews'] = $stats['completed'];
}

// Get available users (not already reviewers)
$stmt = $db->query("
    SELECT u.* FROM users u 
    WHERE u.role NOT IN ('reviewer', 'admin') 
    AND u.is_active = 1
    ORDER BY u.full_name
");
$availableUsers = $stmt->fetchAll();
?>
<!-- NO OUTER CONTAINER - The main dashboard provides it -->
<div class="space-y-6">
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

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Reviewers</h2>
            <p class="text-gray-500 text-sm mt-1">Manage journal reviewers and their assignments</p>
        </div>
        <button onclick="openCreateModal()" class="bg-[#0b2b3f] text-white px-4 py-2 rounded-lg hover:bg-[#123a4f] transition text-sm">
            <i class="fas fa-plus mr-1"></i> Add Reviewer
        </button>
    </div>
    <div class="h-1 w-20 bg-indigo-200 rounded-full"></div>

    <?php if (empty($reviewers)): ?>
        <div class="text-center py-12">
            <i class="fas fa-user-tie text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No reviewers added yet.</p>
            <button onclick="openCreateModal()" class="mt-3 bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                <i class="fas fa-plus mr-2"></i> Add First Reviewer
            </button>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($reviewers as $reviewer): ?>
            <div class="border border-gray-200 rounded-xl p-4 hover:shadow-md transition bg-white">
                <div class="flex items-start gap-3">
                    <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-lg flex-shrink-0">
                        <?php 
                        $initials = '';
                        $nameParts = explode(' ', $reviewer['full_name']);
                        foreach ($nameParts as $part) {
                            if (!empty($part)) {
                                $initials .= strtoupper(substr($part, 0, 1));
                            }
                        }
                        echo htmlspecialchars(substr($initials, 0, 2));
                        ?>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-start justify-between">
                            <div>
                                <h4 class="font-semibold text-[#0b2b3f]"><?= htmlspecialchars($reviewer['full_name']) ?></h4>
                                <p class="text-xs text-indigo-600 font-medium">Reviewer</p>
                            </div>
                            <div class="flex gap-1">
                                <button onclick="openEditModal(<?= htmlspecialchars(json_encode($reviewer)) ?>)" 
                                        class="text-indigo-600 hover:text-indigo-800 text-sm">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="removeReviewer(<?= $reviewer['id'] ?>)" 
                                        class="text-red-600 hover:text-red-800 text-sm">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <?php if ($reviewer['institution']): ?>
                            <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($reviewer['institution']) ?></p>
                        <?php endif; ?>
                        <?php if ($reviewer['bio']): ?>
                            <p class="text-xs text-gray-400 mt-1">Expertise: <?= htmlspecialchars(substr($reviewer['bio'], 0, 40)) ?><?= strlen($reviewer['bio']) > 40 ? '...' : '' ?></p>
                        <?php endif; ?>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $reviewer['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' ?>">
                                <?= $reviewer['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-50 text-yellow-600">
                                <i class="fas fa-clock mr-1"></i> <?= $reviewer['pending_reviews'] ?? 0 ?> Pending
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-600">
                                <i class="fas fa-check mr-1"></i> <?= $reviewer['completed_reviews'] ?? 0 ?> Completed
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-4 text-sm text-gray-400">
            Showing <?= count($reviewers) ?> reviewers
        </div>
    <?php endif; ?>
</div>

<!-- Create/Edit Modal -->
<div id="reviewerModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-[#0b2b3f]" id="modalTitle">Add Reviewer</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" id="reviewerForm">
            <input type="hidden" name="action" id="formAction" value="add_reviewer">
            <input type="hidden" name="user_id" id="formUserId" value="">
            
            <div class="space-y-4">
                <div id="userSelectWrapper">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Select User *</label>
                    <select name="user_id" id="reviewerUser" required class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                        <option value="">Select a user...</option>
                        <?php foreach ($availableUsers as $user): ?>
                        <option value="<?= $user['id'] ?>">
                            <?= htmlspecialchars($user['full_name']) ?> (<?= htmlspecialchars($user['email']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Expertise Areas</label>
                    <input type="text" name="expertise" id="reviewerExpertise" 
                           placeholder="e.g., Occupational Therapy, Mental Health"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Institution/Affiliation</label>
                    <input type="text" name="affiliation" id="reviewerAffiliation" 
                           placeholder="e.g., KCMC, University of Dar es Salaam"
                           class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none">
                </div>
                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" id="reviewerActive" checked>
                        <span class="text-sm text-gray-700">Active</span>
                    </label>
                </div>
            </div>
            
            <div class="flex gap-3 mt-6">
                <button type="submit" class="bg-[#0b2b3f] text-white px-6 py-2 rounded-lg font-semibold hover:bg-[#123a4f] transition flex-1">
                    <i class="fas fa-save mr-2"></i> Save
                </button>
                <button type="button" onclick="closeModal()" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg font-semibold hover:bg-gray-200 transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Add Reviewer';
    document.getElementById('formAction').value = 'add_reviewer';
    document.getElementById('formUserId').value = '';
    document.getElementById('reviewerUser').value = '';
    document.getElementById('reviewerExpertise').value = '';
    document.getElementById('reviewerAffiliation').value = '';
    document.getElementById('reviewerActive').checked = true;
    document.getElementById('userSelectWrapper').style.display = 'block';
    document.getElementById('reviewerModal').classList.remove('hidden');
}

function openEditModal(reviewer) {
    document.getElementById('modalTitle').textContent = 'Edit Reviewer';
    document.getElementById('formAction').value = 'update_reviewer';
    document.getElementById('formUserId').value = reviewer.id;
    document.getElementById('userSelectWrapper').style.display = 'none';
    document.getElementById('reviewerExpertise').value = reviewer.bio || '';
    document.getElementById('reviewerAffiliation').value = reviewer.institution || '';
    document.getElementById('reviewerActive').checked = reviewer.is_active == 1;
    document.getElementById('reviewerModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('reviewerModal').classList.add('hidden');
}

function removeReviewer(id) {
    if (confirm('Are you sure you want to remove this reviewer? This will deactivate their account.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="remove_reviewer">
            <input type="hidden" name="user_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
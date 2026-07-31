<?php
// modules/admin/pages/editorial-board.php - Manage Editorial Board
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

requireRole(['admin']);

$db = getDB();
$message = '';
$error = '';

// Handle reorder
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reorder'])) {
    $order = $_POST['order'] ?? [];
    foreach ($order as $index => $id) {
        $stmt = $db->prepare("UPDATE editorial_board SET display_order = ? WHERE id = ?");
        $stmt->execute([$index + 1, $id]);
    }
    $message = 'Board order updated successfully!';
    logAction($currentUser['id'], 'reorder_board', 'editorial_board', 0);
}

// Get all editorial board members with full details
$boardMembers = getEditorialBoard();

// Group by position for better display
$grouped = [];
foreach ($boardMembers as $member) {
    $position = $member['position'] ?? 'Other';
    if (!isset($grouped[$position])) {
        $grouped[$position] = [];
    }
    $grouped[$position][] = $member;
}
?>
<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Editorial Board</h2>
            <p class="text-gray-500 text-sm mt-1">Manage editorial board members and their positions</p>
        </div>
        <div class="flex gap-3">
            <a href="/jms/admin" class="text-indigo-600 hover:text-indigo-800 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
            <a href="/jms/admin?action=editors" class="bg-[#0b2b3f] text-white px-4 py-2 rounded-lg hover:bg-[#123a4f] transition text-sm">
                <i class="fas fa-user-plus mr-1"></i> Manage Editors
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

    <?php if (empty($boardMembers)): ?>
        <div class="text-center py-12">
            <i class="fas fa-users text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No editorial board members yet.</p>
            <a href="/jms/admin?action=editors" class="mt-3 inline-block bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                <i class="fas fa-user-plus mr-2"></i> Add Editors
            </a>
        </div>
    <?php else: ?>
        <!-- Board Statistics -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-indigo-50 rounded-xl p-4 text-center">
                <p class="text-2xl font-bold text-indigo-700"><?= count($boardMembers) ?></p>
                <p class="text-xs text-indigo-600">Total Members</p>
            </div>
            <div class="bg-blue-50 rounded-xl p-4 text-center">
                <p class="text-2xl font-bold text-blue-700"><?= count($grouped) ?></p>
                <p class="text-xs text-blue-600">Different Positions</p>
            </div>
            <div class="bg-green-50 rounded-xl p-4 text-center">
                <p class="text-2xl font-bold text-green-700">
                    <?= count(array_filter($boardMembers, function($m) { return $m['is_active'] == 1; })) ?>
                </p>
                <p class="text-xs text-green-600">Active Members</p>
            </div>
            <div class="bg-purple-50 rounded-xl p-4 text-center">
                <p class="text-2xl font-bold text-purple-700">
                    <?= count(array_filter($boardMembers, function($m) { return strpos(strtolower($m['position'] ?? ''), 'chief') !== false; })) ?>
                </p>
                <p class="text-xs text-purple-600">Chief Editors</p>
            </div>
        </div>

        <!-- Board Members by Position -->
        <div class="space-y-6">
            <?php foreach ($grouped as $position => $members): ?>
            <div>
                <h3 class="font-semibold text-[#0b2b3f] text-lg border-b border-gray-200 pb-2 mb-3">
                    <?= htmlspecialchars($position) ?>
                    <span class="text-sm text-gray-400 font-normal">(<?= count($members) ?>)</span>
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($members as $member): ?>
                    <div class="border border-gray-200 rounded-xl p-4 hover:shadow-md transition <?= !$member['is_active'] ? 'opacity-60' : '' ?>">
                        <div class="flex items-start gap-3">
                            <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-lg flex-shrink-0">
                                <?php 
                                $initials = '';
                                $nameParts = explode(' ', $member['full_name']);
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
                                        <h4 class="font-semibold text-[#0b2b3f] text-sm"><?= htmlspecialchars($member['full_name']) ?></h4>
                                        <p class="text-xs text-gray-500"><?= htmlspecialchars($member['email']) ?></p>
                                    </div>
                                    <div class="flex gap-1">
                                        <a href="/jms/admin?action=editors&edit=<?= $member['id'] ?>" 
                                           class="text-indigo-600 hover:text-indigo-800 text-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </div>
                                <?php if ($member['affiliation']): ?>
                                    <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($member['affiliation']) ?></p>
                                <?php endif; ?>
                                <?php if ($member['expertise']): ?>
                                    <p class="text-xs text-gray-400 mt-1">Expertise: <?= htmlspecialchars($member['expertise']) ?></p>
                                <?php endif; ?>
                                <div class="mt-2">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $member['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' ?>">
                                        <?= $member['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Reorder Notice -->
        <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
            <p class="text-sm text-blue-700">
                <i class="fas fa-info-circle mr-2"></i>
                Board members are displayed in order of their position. To change the order, 
                <a href="/jms/admin?action=editors" class="underline font-medium">edit the editor's position</a>.
            </p>
        </div>
    <?php endif; ?>
</div>
<?php
// modules/reviewer/pages/invitations.php - Reviewer Invitations
// This file is included by modules/reviewer/index.php when action=invitations

if (!defined('SITE_URL')) {
    require_once dirname(dirname(dirname(__DIR__))) . '/includes/init.php';
}

$db = getDB();
$currentUser = getCurrentUser();

// Get reviewer's invitations
$stmt = $db->prepare("
    SELECT 
        r.*,
        m.title as manuscript_title,
        m.abstract,
        m.submission_date,
        u.full_name as editor_name
    FROM reviewer_assignments r
    JOIN manuscripts m ON r.manuscript_id = m.id
    LEFT JOIN users u ON r.editor_id = u.id
    WHERE r.reviewer_id = ?
    ORDER BY r.invitation_date DESC
");
$stmt->execute([$currentUser['id']]);
$invitations = $stmt->fetchAll();

// Count by status
$pendingCount = 0;
$acceptedCount = 0;
$declinedCount = 0;
$completedCount = 0;

foreach ($invitations as $inv) {
    if ($inv['status'] === 'invited') $pendingCount++;
    elseif ($inv['status'] === 'accepted') $acceptedCount++;
    elseif ($inv['status'] === 'declined') $declinedCount++;
    elseif ($inv['status'] === 'completed') $completedCount++;
}
?>
<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-[#0b2b3f]">Reviewer Invitations</h2>
        <div class="flex gap-2 text-sm">
            <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full">Pending: <?= $pendingCount ?></span>
            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full">Accepted: <?= $acceptedCount ?></span>
            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full">Completed: <?= $completedCount ?></span>
        </div>
    </div>
    
    <?php if (empty($invitations)): ?>
        <div class="text-center py-12">
            <i class="fas fa-envelope-open-text text-6xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">You have no review invitations yet.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Manuscript</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Editor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invited</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($invitations as $inv): ?>
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <?= htmlspecialchars(substr($inv['manuscript_title'], 0, 40)) ?>...
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?= htmlspecialchars($inv['editor_name'] ?? 'Unknown') ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?= formatDate($inv['invitation_date'], 'M d, Y') ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?= $inv['due_date'] ? formatDate($inv['due_date'], 'M d, Y') : 'N/A' ?>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded-full 
                                <?= $inv['status'] === 'invited' ? 'bg-yellow-100 text-yellow-800' : 
                                   ($inv['status'] === 'accepted' ? 'bg-green-100 text-green-800' : 
                                   ($inv['status'] === 'declined' ? 'bg-red-100 text-red-800' : 
                                   ($inv['status'] === 'completed' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'))) ?>">
                                <?= ucfirst($inv['status']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <?php if ($inv['status'] === 'invited'): ?>
                                <a href="/jms/reviewer?action=accept-invitation&id=<?= $inv['id'] ?>" 
                                   class="text-green-600 hover:text-green-900 mr-2"
                                   onclick="return confirm('Accept this review invitation?')">
                                    <i class="fas fa-check"></i> Accept
                                </a>
                                <a href="/jms/reviewer?action=decline-invitation&id=<?= $inv['id'] ?>" 
                                   class="text-red-600 hover:text-red-900"
                                   onclick="return confirm('Decline this review invitation?')">
                                    <i class="fas fa-times"></i> Decline
                                </a>
                            <?php elseif ($inv['status'] === 'accepted'): ?>
                                <a href="/jms/reviewer?action=review-forms&id=<?= $inv['manuscript_id'] ?>" 
                                   class="text-indigo-600 hover:text-indigo-900">
                                    <i class="fas fa-file-alt"></i> Start Review
                                </a>
                            <?php elseif ($inv['status'] === 'completed'): ?>
                                <span class="text-gray-500">Completed</span>
                            <?php else: ?>
                                <span class="text-gray-500"><?= ucfirst($inv['status']) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
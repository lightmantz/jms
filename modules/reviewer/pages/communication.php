<?php
// modules/reviewer/pages/communication.php - Anonymous Communication
if (!defined('SITE_URL')) {
    require_once dirname(dirname(dirname(__DIR__))) . '/includes/init.php';
}

$db = getDB();
$currentUser = getCurrentUser();
$manuscriptId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get manuscripts the reviewer is assigned to
$stmt = $db->prepare("
    SELECT DISTINCT m.id, m.title
    FROM reviewer_assignments r
    JOIN manuscripts m ON r.manuscript_id = m.id
    WHERE r.reviewer_id = ? AND r.status IN ('accepted', 'completed')
    ORDER BY m.title
");
$stmt->execute([$currentUser['id']]);
$manuscripts = $stmt->fetchAll();

// Get communications
$communications = [];
if ($manuscriptId) {
    $stmt = $db->prepare("
        SELECT c.*, u.full_name as sender_name
        FROM communications c
        LEFT JOIN users u ON c.sender_id = u.id
        WHERE c.manuscript_id = ? AND (c.recipient_id = ? OR c.sender_id = ?)
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$manuscriptId, $currentUser['id'], $currentUser['id']]);
    $communications = $stmt->fetchAll();
}
?>
<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
    <h2 class="text-2xl font-bold text-[#0b2b3f] mb-6">Anonymous Communication</h2>
    
    <div class="mb-6">
        <label for="manuscript_select" class="block text-sm font-medium text-gray-700 mb-1">Select Manuscript</label>
        <select id="manuscript_select" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
                onchange="window.location.href='/jms/reviewer?action=communication&id=' + this.value">
            <option value="">Select a manuscript...</option>
            <?php foreach ($manuscripts as $m): ?>
                <option value="<?= $m['id'] ?>" <?= $manuscriptId == $m['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars(substr($m['title'], 0, 50)) ?>...
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <?php if ($manuscriptId): ?>
        <div class="space-y-4 max-h-96 overflow-y-auto border border-gray-200 rounded-lg p-4 bg-gray-50">
            <?php if (empty($communications)): ?>
                <p class="text-gray-500 text-center py-4">No communication history.</p>
            <?php else: ?>
                <?php foreach ($communications as $msg): ?>
                    <div class="p-3 rounded-lg <?= $msg['sender_id'] == $currentUser['id'] ? 'bg-indigo-50 ml-8' : 'bg-white mr-8 border border-gray-200' ?>">
                        <div class="flex justify-between text-xs text-gray-400">
                            <span><?= htmlspecialchars($msg['sender_name'] ?? 'Anonymous') ?></span>
                            <span><?= formatDate($msg['created_at'], 'M d, Y H:i') ?></span>
                        </div>
                        <p class="text-sm text-gray-700 mt-1"><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <form method="POST" action="" class="mt-4">
            <input type="hidden" name="manuscript_id" value="<?= $manuscriptId ?>">
            <div>
                <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Your Message</label>
                <textarea id="message" name="message" rows="3" 
                          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
                          placeholder="Type your message here..."></textarea>
            </div>
            <button type="submit" class="mt-3 bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition">
                <i class="fas fa-paper-plane mr-2"></i> Send Message
            </button>
        </form>
    <?php else: ?>
        <div class="text-center py-12">
            <i class="fas fa-comments text-6xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">Select a manuscript to view communication history.</p>
        </div>
    <?php endif; ?>
</div>
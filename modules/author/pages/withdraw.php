<?php
// modules/author/pages/withdraw.php - Withdraw Manuscript
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

$db = getDB();
$currentUser = getCurrentUser();
$message = '';
$error = '';

// Get manuscripts that can be withdrawn (not published or withdrawn)
$stmt = $db->prepare("
    SELECT id, title, status FROM manuscripts 
    WHERE corresponding_author_id = ? 
    AND status NOT IN ('published', 'withdrawn', 'draft')
    ORDER BY created_at DESC
");
$stmt->execute([$currentUser['id']]);
$manuscripts = $stmt->fetchAll();

// Handle withdrawal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdraw_manuscript'])) {
    $manuscript_id = (int)$_POST['manuscript_id'];
    $reason = trim($_POST['reason'] ?? '');
    
    if (empty($reason)) {
        $error = 'Please provide a reason for withdrawal.';
    } else {
        $stmt = $db->prepare("UPDATE manuscripts SET status = 'withdrawn', withdrawn_at = NOW(), withdrawal_reason = ? WHERE id = ? AND corresponding_author_id = ?");
        if ($stmt->execute([$reason, $manuscript_id, $currentUser['id']])) {
            $message = 'Manuscript withdrawn successfully!';
            logAction($currentUser['id'], 'withdraw_manuscript', 'manuscripts', $manuscript_id);
            
            // Notify editor
            $stmt = $db->prepare("SELECT title, editor_assigned_id FROM manuscripts WHERE id = ?");
            $stmt->execute([$manuscript_id]);
            $manuscript = $stmt->fetch();
            if ($manuscript && $manuscript['editor_assigned_id']) {
                createNotification(
                    $manuscript['editor_assigned_id'],
                    'withdrawn',
                    'Manuscript Withdrawn',
                    'A manuscript has been withdrawn by the author: ' . $manuscript['title'],
                    SITE_URL . '/editor?action=decision&id=' . $manuscript_id
                );
            }
            
            // Refresh data
            $stmt = $db->prepare("
                SELECT id, title, status FROM manuscripts 
                WHERE corresponding_author_id = ? 
                AND status NOT IN ('published', 'withdrawn', 'draft')
                ORDER BY created_at DESC
            ");
            $stmt->execute([$currentUser['id']]);
            $manuscripts = $stmt->fetchAll();
        } else {
            $error = 'Failed to withdraw manuscript. Please try again.';
        }
    }
}
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Withdraw Manuscript</h2>
            <p class="text-gray-500 text-sm mt-1">Withdraw your manuscript from consideration</p>
        </div>
        <a href="/jms/author" class="text-indigo-600 hover:text-indigo-800 text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back
        </a>
    </div>
    <div class="h-1 w-20 bg-indigo-200 rounded-full"></div>

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

    <?php if (empty($manuscripts)): ?>
        <div class="text-center py-12">
            <i class="fas fa-check-circle text-5xl text-green-300 mb-4"></i>
            <p class="text-gray-500">No manuscripts available for withdrawal.</p>
            <a href="/jms/author?action=track" class="mt-3 inline-block bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                <i class="fas fa-chart-line mr-2"></i> View All Submissions
            </a>
        </div>
    <?php else: ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-6">
            <p class="text-sm text-yellow-700">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <strong>Warning:</strong> Withdrawing a manuscript will permanently remove it from the review process. 
                This action cannot be undone.
            </p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">Withdraw Manuscript</h3>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Select Manuscript *</label>
                    <select name="manuscript_id" required class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none transition">
                        <option value="">Select a manuscript...</option>
                        <?php foreach ($manuscripts as $manuscript): ?>
                        <option value="<?= $manuscript['id'] ?>">
                            <?= htmlspecialchars(substr($manuscript['title'], 0, 60)) ?> (<?= ucfirst($manuscript['status']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Withdrawal *</label>
                    <textarea name="reason" rows="4" required
                              placeholder="Please explain why you are withdrawing this manuscript..."
                              class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition"></textarea>
                </div>
                <button type="submit" name="withdraw_manuscript" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition" 
                        onclick="return confirm('Are you sure you want to withdraw this manuscript? This action cannot be undone.')">
                    <i class="fas fa-times mr-2"></i> Withdraw Manuscript
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>
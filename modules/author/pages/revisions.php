<?php
// modules/author/pages/revisions.php - Submit Revision
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

$db = getDB();
$currentUser = getCurrentUser();
$message = '';
$error = '';

// Get manuscripts that need revisions
$stmt = $db->prepare("
    SELECT m.*, 
           (SELECT COUNT(*) FROM reviews WHERE manuscript_id = m.id AND status = 'completed') as completed_reviews
    FROM manuscripts m
    WHERE m.corresponding_author_id = ? AND m.status = 'revision_required'
    ORDER BY m.updated_at DESC
");
$stmt->execute([$currentUser['id']]);
$manuscripts = $stmt->fetchAll();

// Handle revision submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_revision'])) {
    $manuscript_id = (int)$_POST['manuscript_id'];
    $response_letter = trim($_POST['response_letter'] ?? '');
    $changes = trim($_POST['changes'] ?? '');
    
    if (empty($response_letter)) {
        $error = 'Please provide a response letter.';
    } elseif (empty($changes)) {
        $error = 'Please describe the changes made.';
    } else {
        // Update manuscript status
        $stmt = $db->prepare("UPDATE manuscripts SET status = 'under_review', updated_at = NOW() WHERE id = ? AND corresponding_author_id = ?");
        if ($stmt->execute([$manuscript_id, $currentUser['id']])) {
            // Save revision details
            $stmt = $db->prepare("
                INSERT INTO revisions (manuscript_id, user_id, revision_type, comments, submitted_at) 
                VALUES (?, ?, 'major', ?, NOW())
            ");
            $stmt->execute([$manuscript_id, $currentUser['id'], $response_letter . "\n\nChanges: " . $changes]);
            
            $message = 'Revision submitted successfully!';
            logAction($currentUser['id'], 'submit_revision', 'manuscripts', $manuscript_id);
            
            // Notify editor
            $stmt = $db->prepare("SELECT editor_assigned_id FROM manuscripts WHERE id = ?");
            $stmt->execute([$manuscript_id]);
            $manuscript = $stmt->fetch();
            if ($manuscript && $manuscript['editor_assigned_id']) {
                createNotification(
                    $manuscript['editor_assigned_id'],
                    'revision_submitted',
                    'Revision Submitted',
                    'A revision has been submitted for manuscript: ' . $manuscript['title'],
                    SITE_URL . '/editor?action=decision&id=' . $manuscript_id
                );
            }
            
            // Refresh data
            $stmt = $db->prepare("
                SELECT m.*, 
                       (SELECT COUNT(*) FROM reviews WHERE manuscript_id = m.id AND status = 'completed') as completed_reviews
                FROM manuscripts m
                WHERE m.corresponding_author_id = ? AND m.status = 'revision_required'
                ORDER BY m.updated_at DESC
            ");
            $stmt->execute([$currentUser['id']]);
            $manuscripts = $stmt->fetchAll();
        } else {
            $error = 'Failed to submit revision. Please try again.';
        }
    }
}
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Submit Revision</h2>
            <p class="text-gray-500 text-sm mt-1">Submit revised manuscripts for re-review</p>
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
            <p class="text-gray-500">No manuscripts require revisions at this time.</p>
            <a href="/jms/author?action=track" class="mt-3 inline-block bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                <i class="fas fa-chart-line mr-2"></i> View All Submissions
            </a>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($manuscripts as $manuscript): ?>
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h4 class="font-semibold text-[#0b2b3f]"><?= htmlspecialchars($manuscript['title']) ?></h4>
                        <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500 mt-1">
                            <span class="px-2 py-0.5 rounded-full <?= getStatusBadge($manuscript['status']) ?>">
                                <?= ucfirst(str_replace('_', ' ', $manuscript['status'])) ?>
                            </span>
                            <span>Submitted: <?= formatDate($manuscript['submission_date']) ?></span>
                            <span>Reviews: <?= $manuscript['completed_reviews'] ?? 0 ?></span>
                        </div>
                    </div>
                </div>

                <form method="POST" class="space-y-4">
                    <input type="hidden" name="manuscript_id" value="<?= $manuscript['id'] ?>">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Response Letter to Reviewers *</label>
                        <textarea name="response_letter" rows="4" required
                                  placeholder="Provide a detailed response to each reviewer comment..."
                                  class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition"></textarea>
                        <p class="text-xs text-gray-400 mt-1">Address each reviewer comment individually</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Changes Made *</label>
                        <textarea name="changes" rows="3" required
                                  placeholder="Describe the changes made to the manuscript..."
                                  class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Upload Revised Manuscript (Optional)</label>
                        <input type="file" name="revised_file" accept=".pdf"
                               class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none transition file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    </div>
                    
                    <button type="submit" name="submit_revision" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-paper-plane mr-2"></i> Submit Revision
                    </button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
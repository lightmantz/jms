<?php
// modules/reviewer/pages/communication.php - Anonymous Communication
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

$db = getDB();
$currentUser = getCurrentUser();
$message = '';
$error = '';

// Get reviewer's active reviews
$stmt = $db->prepare("
    SELECT r.*, m.title as manuscript_title,
           m.id as manuscript_id
    FROM reviews r
    JOIN manuscripts m ON r.manuscript_id = m.id
    WHERE r.reviewer_id = ? AND r.status IN ('accepted', 'completed')
    ORDER BY r.invitation_date DESC
");
$stmt->execute([$currentUser['id']]);
$reviews = $stmt->fetchAll();

// Handle communication
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $manuscript_id = (int)$_POST['manuscript_id'];
    $message_text = trim($_POST['message'] ?? '');
    $is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;
    
    if (empty($message_text)) {
        $error = 'Please enter a message.';
    } else {
        // Store communication - you'll need to create a communications table
        try {
            $stmt = $db->prepare("
                INSERT INTO communications (manuscript_id, user_id, message, is_anonymous, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $result = $stmt->execute([$manuscript_id, $currentUser['id'], $message_text, $is_anonymous]);
        } catch (PDOException $e) {
            // Create table if it doesn't exist
            if (strpos($e->getMessage(), 'Table') !== false) {
                $db->exec("
                    CREATE TABLE IF NOT EXISTS communications (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        manuscript_id INT NOT NULL,
                        user_id INT NOT NULL,
                        message TEXT NOT NULL,
                        is_anonymous BOOLEAN DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (manuscript_id) REFERENCES manuscripts(id) ON DELETE CASCADE,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                        INDEX idx_manuscript (manuscript_id)
                    )
                ");
                $stmt = $db->prepare("
                    INSERT INTO communications (manuscript_id, user_id, message, is_anonymous, created_at) 
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $result = $stmt->execute([$manuscript_id, $currentUser['id'], $message_text, $is_anonymous]);
            }
        }
        
        if ($result ?? false) {
            $message = 'Message sent successfully!';
            logAction($currentUser['id'], 'send_communication', 'communications', $manuscript_id);
        } else {
            $error = 'Failed to send message.';
        }
    }
}

// Get existing communications for selected manuscript
$selectedManuscript = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$communications = [];
if ($selectedManuscript > 0) {
    try {
        $stmt = $db->prepare("
            SELECT c.*, u.full_name as user_name
            FROM communications c
            JOIN users u ON c.user_id = u.id
            WHERE c.manuscript_id = ?
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([$selectedManuscript]);
        $communications = $stmt->fetchAll();
    } catch (PDOException $e) {
        $communications = [];
    }
}
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Anonymous Communication</h2>
            <p class="text-gray-500 text-sm mt-1">Communicate with editors anonymously</p>
        </div>
        <a href="/jms/reviewer" class="text-indigo-600 hover:text-indigo-800 text-sm">
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

    <div class="grid md:grid-cols-3 gap-6">
        <!-- Manuscript List -->
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <h3 class="font-semibold text-[#0b2b3f] mb-3">Your Reviews</h3>
            <?php if (empty($reviews)): ?>
                <p class="text-sm text-gray-500">No active reviews.</p>
            <?php else: ?>
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    <?php foreach ($reviews as $review): ?>
                    <a href="/jms/reviewer?action=communication&id=<?= $review['manuscript_id'] ?>" 
                       class="block p-2 rounded-lg <?= $selectedManuscript == $review['manuscript_id'] ? 'bg-indigo-50 border border-indigo-200' : 'hover:bg-gray-50' ?>">
                        <p class="text-sm font-medium text-[#0b2b3f]"><?= htmlspecialchars(substr($review['manuscript_title'], 0, 30)) ?>...</p>
                        <p class="text-xs text-gray-500">Status: <?= ucfirst($review['status']) ?></p>
                    </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Communication Thread -->
        <div class="md:col-span-2">
            <?php if ($selectedManuscript > 0): ?>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <h3 class="font-semibold text-[#0b2b3f] mb-3">Communication Thread</h3>
                    
                    <!-- Messages -->
                    <div class="space-y-3 max-h-96 overflow-y-auto mb-4">
                        <?php if (empty($communications)): ?>
                            <p class="text-sm text-gray-500 text-center py-4">No messages yet.</p>
                        <?php else: ?>
                            <?php foreach ($communications as $comm): ?>
                            <div class="flex <?= $comm['user_id'] == $currentUser['id'] ? 'justify-end' : 'justify-start' ?>">
                                <div class="max-w-[80%] <?= $comm['user_id'] == $currentUser['id'] ? 'bg-indigo-100' : 'bg-gray-100' ?> rounded-lg p-3">
                                    <p class="text-sm"><?= nl2br(htmlspecialchars($comm['message'])) ?></p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        <?php if ($comm['is_anonymous']): ?>
                                            <span class="text-gray-500">Anonymous</span>
                                        <?php else: ?>
                                            <?= htmlspecialchars($comm['user_name']) ?>
                                        <?php endif; ?>
                                        · <?= timeAgo($comm['created_at']) ?>
                                    </p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Send Message -->
                    <form method="POST" class="border-t border-gray-200 pt-4">
                        <input type="hidden" name="manuscript_id" value="<?= $selectedManuscript ?>">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                            <textarea name="message" rows="3" required
                                      placeholder="Type your message here..."
                                      class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition"></textarea>
                        </div>
                        <div class="flex items-center justify-between mt-2">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="is_anonymous" checked>
                                <span class="text-sm text-gray-700">Send anonymously</span>
                            </label>
                            <button type="submit" name="send_message" class="bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                                <i class="fas fa-paper-plane mr-2"></i> Send
                            </button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-xl border border-gray-200 p-8 text-center">
                    <i class="fas fa-comments text-5xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500">Select a manuscript from the list to view communication.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
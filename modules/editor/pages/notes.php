<?php
// modules/editor/pages/notes.php - Internal Notes
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

$db = getDB();
$currentUser = getCurrentUser();
$message = '';
$error = '';

// Get all manuscripts
$stmt = $db->query("
    SELECT m.id, m.title, u.full_name as author_name,
           m.status, m.submission_date
    FROM manuscripts m
    LEFT JOIN users u ON m.corresponding_author_id = u.id
    WHERE m.status NOT IN ('draft', 'withdrawn')
    ORDER BY m.submission_date DESC
");
$manuscripts = $stmt->fetchAll();

// Handle note saving
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_note'])) {
    $manuscript_id = (int)$_POST['manuscript_id'];
    $note = trim($_POST['note'] ?? '');
    
    if (empty($note)) {
        $error = 'Please enter a note.';
    } else {
        // Check if notes table exists, if not create it
        try {
            $stmt = $db->prepare("
                INSERT INTO internal_notes (manuscript_id, user_id, note, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $result = $stmt->execute([$manuscript_id, $currentUser['id'], $note]);
        } catch (PDOException $e) {
            // Create table if it doesn't exist
            if (strpos($e->getMessage(), 'Table') !== false) {
                $db->exec("
                    CREATE TABLE IF NOT EXISTS internal_notes (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        manuscript_id INT NOT NULL,
                        user_id INT NOT NULL,
                        note TEXT NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (manuscript_id) REFERENCES manuscripts(id) ON DELETE CASCADE,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                        INDEX idx_manuscript (manuscript_id)
                    )
                ");
                $stmt = $db->prepare("
                    INSERT INTO internal_notes (manuscript_id, user_id, note, created_at) 
                    VALUES (?, ?, ?, NOW())
                ");
                $result = $stmt->execute([$manuscript_id, $currentUser['id'], $note]);
            }
        }
        
        if ($result ?? false) {
            $message = 'Note saved successfully!';
            logAction($currentUser['id'], 'add_internal_note', 'internal_notes', $manuscript_id);
        } else {
            $error = 'Failed to save note.';
        }
    }
}
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Internal Notes</h2>
            <p class="text-gray-500 text-sm mt-1">Add and manage internal notes for manuscripts</p>
        </div>
        <a href="/jms/editor" class="text-indigo-600 hover:text-indigo-800 text-sm">
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

    <div class="grid md:grid-cols-2 gap-6">
        <!-- Add Note -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">Add Note</h3>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Select Manuscript</label>
                    <select name="manuscript_id" required class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none transition">
                        <option value="">Select a manuscript...</option>
                        <?php foreach ($manuscripts as $manuscript): ?>
                        <option value="<?= $manuscript['id'] ?>">
                            <?= htmlspecialchars(substr($manuscript['title'], 0, 50)) ?> 
                            (<?= ucfirst($manuscript['status']) ?>) - <?= htmlspecialchars($manuscript['author_name'] ?? 'Unknown') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
                    <textarea name="note" rows="5" required
                              placeholder="Add internal notes about this manuscript..."
                              class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition"></textarea>
                </div>
                <button type="submit" name="save_note" class="bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                    <i class="fas fa-save mr-2"></i> Save Note
                </button>
            </form>
        </div>

        <!-- Recent Notes -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">Recent Notes</h3>
            <?php
            // Get recent notes
            try {
                $stmt = $db->query("
                    SELECT n.*, m.title as manuscript_title, u.full_name as user_name
                    FROM internal_notes n
                    JOIN manuscripts m ON n.manuscript_id = m.id
                    JOIN users u ON n.user_id = u.id
                    ORDER BY n.created_at DESC
                    LIMIT 10
                ");
                $notes = $stmt->fetchAll();
            } catch (PDOException $e) {
                $notes = [];
            }
            ?>
            <?php if (empty($notes)): ?>
                <p class="text-sm text-gray-500">No notes yet.</p>
            <?php else: ?>
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    <?php foreach ($notes as $note): ?>
                    <div class="border-b border-gray-100 pb-3 last:border-0">
                        <p class="text-sm text-gray-600"><?= nl2br(htmlspecialchars($note['note'])) ?></p>
                        <div class="flex items-center gap-2 text-xs text-gray-400 mt-1">
                            <span><?= htmlspecialchars($note['user_name']) ?></span>
                            <span>·</span>
                            <span><?= timeAgo($note['created_at']) ?></span>
                            <span>·</span>
                            <span class="text-indigo-600"><?= htmlspecialchars(substr($note['manuscript_title'], 0, 30)) ?>...</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
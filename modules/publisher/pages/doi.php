<?php
// modules/publisher/pages/doi.php - DOI Assignment
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}

$db = getDB();
$currentUser = getCurrentUser();
$message = '';
$error = '';

$manuscriptId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($manuscriptId > 0) {
    $manuscript = getManuscript($manuscriptId);
}

// Get manuscripts without DOI
$stmt = $db->query("
    SELECT m.*, u.full_name as author_name
    FROM manuscripts m
    LEFT JOIN users u ON m.corresponding_author_id = u.id
    WHERE (m.doi IS NULL OR m.doi = '') AND m.status IN ('accepted', 'published')
    ORDER BY m.accepted_at ASC
");
$missingDoi = $stmt->fetchAll();

// Get manuscripts with DOI
$stmt = $db->query("
    SELECT m.*, u.full_name as author_name
    FROM manuscripts m
    LEFT JOIN users u ON m.corresponding_author_id = u.id
    WHERE m.doi IS NOT NULL AND m.doi != '' AND m.status IN ('accepted', 'published')
    ORDER BY m.publication_date DESC
    LIMIT 10
");
$withDoi = $stmt->fetchAll();

// Handle DOI assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['generate_doi'])) {
        $manuscript_id = (int)$_POST['manuscript_id'];
        $doi = generateDOI();
        
        $stmt = $db->prepare("UPDATE manuscripts SET doi = ? WHERE id = ?");
        if ($stmt->execute([$doi, $manuscript_id])) {
            $message = 'DOI generated successfully: ' . $doi;
            logAction($currentUser['id'], 'generate_doi', 'manuscripts', $manuscript_id);
            // Refresh data
            if ($manuscriptId > 0) {
                $manuscript = getManuscript($manuscriptId);
            }
            $stmt = $db->query("
                SELECT m.*, u.full_name as author_name
                FROM manuscripts m
                LEFT JOIN users u ON m.corresponding_author_id = u.id
                WHERE (m.doi IS NULL OR m.doi = '') AND m.status IN ('accepted', 'published')
                ORDER BY m.accepted_at ASC
            ");
            $missingDoi = $stmt->fetchAll();
        } else {
            $error = 'Failed to generate DOI.';
        }
    } elseif (isset($_POST['update_doi'])) {
        $manuscript_id = (int)$_POST['manuscript_id'];
        $doi = trim($_POST['doi']);
        
        if (empty($doi)) {
            $error = 'Please enter a DOI.';
        } else {
            $stmt = $db->prepare("UPDATE manuscripts SET doi = ? WHERE id = ?");
            if ($stmt->execute([$doi, $manuscript_id])) {
                $message = 'DOI updated successfully!';
                logAction($currentUser['id'], 'update_doi', 'manuscripts', $manuscript_id);
                // Refresh data
                if ($manuscriptId > 0) {
                    $manuscript = getManuscript($manuscriptId);
                }
            } else {
                $error = 'Failed to update DOI.';
            }
        }
    }
}
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">DOI Assignment</h2>
            <p class="text-gray-500 text-sm mt-1">Assign Digital Object Identifiers to manuscripts</p>
        </div>
        <a href="/jms/publisher" class="text-indigo-600 hover:text-indigo-800 text-sm">
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

    <?php if ($manuscriptId > 0 && isset($manuscript)): ?>
        <!-- Edit DOI for Specific Manuscript -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">
                Manage DOI: <?= htmlspecialchars(substr($manuscript['title'], 0, 50)) ?>...
            </h3>
            
            <div class="grid md:grid-cols-2 gap-4 mb-4">
                <div>
                    <p class="text-sm text-gray-500">Author</p>
                    <p class="font-medium"><?= htmlspecialchars($manuscript['author_name'] ?? 'Unknown') ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Current DOI</p>
                    <p class="font-medium <?= empty($manuscript['doi']) ? 'text-red-500' : 'text-green-600' ?>">
                        <?= htmlspecialchars($manuscript['doi'] ?? 'Not assigned') ?>
                    </p>
                </div>
            </div>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="manuscript_id" value="<?= $manuscriptId ?>">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">DOI</label>
                    <div class="flex gap-3">
                        <input type="text" name="doi" 
                               value="<?= htmlspecialchars($manuscript['doi'] ?? '') ?>"
                               placeholder="10.1016/tirp.2026.0001"
                               class="flex-1 px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] outline-none transition">
                        <button type="submit" name="update_doi" class="bg-[#0b2b3f] text-white px-6 py-2 rounded-lg hover:bg-[#123a4f] transition">
                            <i class="fas fa-save mr-2"></i> Update
                        </button>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Format: 10.1016/tirp.YEAR.NUMBER</p>
                </div>
            </form>
            
            <?php if (empty($manuscript['doi'])): ?>
                <form method="POST" class="mt-4">
                    <input type="hidden" name="manuscript_id" value="<?= $manuscriptId ?>">
                    <button type="submit" name="generate_doi" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition">
                        <i class="fas fa-magic mr-2"></i> Auto-Generate DOI
                    </button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="grid md:grid-cols-2 gap-6">
        <!-- Missing DOIs -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">
                Missing DOIs (<?= count($missingDoi) ?>)
            </h3>
            <?php if (empty($missingDoi)): ?>
                <p class="text-sm text-green-600">All manuscripts have DOIs assigned.</p>
            <?php else: ?>
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    <?php foreach ($missingDoi as $manuscript): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                        <div>
                            <p class="font-medium text-[#0b2b3f]"><?= htmlspecialchars(substr($manuscript['title'], 0, 30)) ?>...</p>
                            <p class="text-xs text-gray-500"><?= htmlspecialchars($manuscript['author_name'] ?? 'Unknown') ?></p>
                        </div>
                        <div class="flex gap-2">
                            <form method="POST" class="inline">
                                <input type="hidden" name="manuscript_id" value="<?= $manuscript['id'] ?>">
                                <button type="submit" name="generate_doi" class="bg-green-600 text-white px-3 py-1 rounded-lg hover:bg-green-700 transition text-xs">
                                    <i class="fas fa-magic mr-1"></i> Generate
                                </button>
                            </form>
                            <a href="/jms/publisher?action=doi&id=<?= $manuscript['id'] ?>" 
                               class="bg-[#0b2b3f] text-white px-3 py-1 rounded-lg hover:bg-[#123a4f] transition text-xs">
                                <i class="fas fa-edit mr-1"></i> Edit
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- With DOIs -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">
                Recent DOIs Assigned
            </h3>
            <?php if (empty($withDoi)): ?>
                <p class="text-sm text-gray-500">No DOIs assigned yet.</p>
            <?php else: ?>
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    <?php foreach ($withDoi as $manuscript): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                        <div>
                            <p class="font-medium text-[#0b2b3f]"><?= htmlspecialchars(substr($manuscript['title'], 0, 25)) ?>...</p>
                            <p class="text-xs text-indigo-600"><?= htmlspecialchars($manuscript['doi']) ?></p>
                        </div>
                        <a href="/jms/publisher?action=doi&id=<?= $manuscript['id'] ?>" 
                           class="text-indigo-600 hover:text-indigo-800 text-sm">
                            <i class="fas fa-edit"></i>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
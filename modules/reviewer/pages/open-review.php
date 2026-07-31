<?php
// modules/reviewer/pages/open-review.php - Open Review
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../../includes/functions.php';
    require_once __DIR__ . '/../../../includes/auth.php';
}
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-[#0b2b3f]">Open Review</h2>
            <p class="text-gray-500 text-sm mt-1">Both author and reviewer identities are known to each other</p>
        </div>
        <a href="/jms/reviewer" class="text-indigo-600 hover:text-indigo-800 text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back
        </a>
    </div>
    <div class="h-1 w-20 bg-indigo-200 rounded-full"></div>

    <div class="grid md:grid-cols-3 gap-6">
        <div class="md:col-span-2 space-y-6">
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">About Open Review</h3>
                <div class="space-y-4 text-gray-600">
                    <p>In an open review process:</p>
                    <ul class="list-disc list-inside space-y-2 ml-4">
                        <li>Both author and reviewer identities are known</li>
                        <li>Promotes transparency and accountability</li>
                        <li>Encourages constructive dialogue</li>
                        <li>Reviewer reports are often published alongside the article</li>
                    </ul>
                    
                    <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                        <h4 class="font-semibold text-blue-700">Reviewer Responsibilities</h4>
                        <ul class="list-disc list-inside text-sm text-blue-600 mt-2">
                            <li>Be professional and constructive</li>
                            <li>Provide transparent feedback</li>
                            <li>Be prepared for your name to be associated with the review</li>
                            <li>Maintain academic integrity</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Active Open Reviews -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-[#0b2b3f] mb-4">Your Open Reviews</h3>
                <?php
                $stmt = $db->prepare("
                    SELECT r.*, m.title as manuscript_title,
                           u.full_name as author_name
                    FROM reviews r
                    JOIN manuscripts m ON r.manuscript_id = m.id
                    LEFT JOIN users u ON m.corresponding_author_id = u.id
                    WHERE r.reviewer_id = ? AND r.review_type = 'open_review'
                    ORDER BY r.invitation_date DESC
                ");
                $stmt->execute([$currentUser['id']]);
                $reviews = $stmt->fetchAll();
                ?>
                <?php if (empty($reviews)): ?>
                    <p class="text-sm text-gray-500">No open reviews assigned.</p>
                <?php else: ?>
                    <div class="space-y-2">
                        <?php foreach ($reviews as $review): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <div>
                                <p class="font-medium text-[#0b2b3f]"><?= htmlspecialchars(substr($review['manuscript_title'], 0, 40)) ?>...</p>
                                <p class="text-xs text-gray-500">Author: <?= htmlspecialchars($review['author_name'] ?? 'Unknown') ?></p>
                            </div>
                            <a href="/jms/reviewer?action=review-forms&id=<?= $review['manuscript_id'] ?>" 
                               class="text-indigo-600 hover:text-indigo-800 text-sm">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h4 class="font-semibold text-[#0b2b3f] mb-3">Open Review Guidelines</h4>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check-circle text-green-500 mt-0.5"></i>
                        <span>Be respectful and professional</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check-circle text-green-500 mt-0.5"></i>
                        <span>Provide constructive feedback</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check-circle text-green-500 mt-0.5"></i>
                        <span>Your review will be public</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
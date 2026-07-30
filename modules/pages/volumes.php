<?php
// modules/admin/pages/volumes.php - Volumes Management
// This file is included by admin/index.php when action=volumes

$db = getDB();
$volumes = getVolumes();
?>

<div class="bg-white rounded-xl shadow-card p-6 border border-gray-100/70">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-[#0b2b3f]">Volumes</h2>
        <a href="/jms/admin?action=create-volume" class="bg-[#0b2b3f] text-white px-4 py-2 rounded-lg hover:bg-[#123a4f] transition">
            <i class="fas fa-plus mr-2"></i> Add Volume
        </a>
    </div>
    
    <?php if (empty($volumes)): ?>
        <div class="text-center py-12">
            <i class="fas fa-layer-group text-6xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No volumes found. Create your first volume!</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Volume</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Year</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issues</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($volumes as $volume): ?>
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">Volume <?= $volume['volume_number'] ?></td>
                        <td class="px-6 py-4 text-sm text-gray-500"><?= $volume['year'] ?></td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?php 
                            $issues = getIssuesByVolume($volume['id']);
                            echo count($issues);
                            ?>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded-full <?= $volume['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                <?= $volume['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <a href="/jms/admin?action=edit-volume&id=<?= $volume['id'] ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                            <a href="/jms/admin?action=delete-volume&id=<?= $volume['id'] ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
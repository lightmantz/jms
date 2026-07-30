<?php
// modules/pages/publication-ethics.php - Publication Ethics
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../includes/functions.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publication Ethics - <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="/jms/css/style.css">
</head>
<body class="antialiased text-gray-700 font-['Inter']" style="background: #f6f9fc;">
    <?php include './includes/header.php'; ?>
    
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-8 md:p-10">
            <h1 class="text-3xl font-bold text-[#0b2b3f] flex items-center gap-3">
                <i class="fas fa-shield-alt text-indigo-500"></i> Publication Ethics
            </h1>
            <div class="h-1 w-20 bg-indigo-200 rounded-full mt-2 mb-6"></div>
            
            <div class="prose max-w-none text-gray-600 space-y-6">
                <p class="text-lg font-medium text-[#0b2b3f]">TIRP is committed to maintaining the highest standards of publication ethics.</p>
                
                <p>We adhere to the guidelines and policies set forth by the <strong>Committee on Publication Ethics (COPE)</strong> and follow best practices in scholarly publishing.</p>
                
                <h2 class="text-xl font-semibold text-[#0b2b3f] mt-6">Editorial Responsibilities</h2>
                <ul class="list-disc pl-6 space-y-2">
                    <li><strong>Editorial Independence:</strong> Editors make decisions based on the academic merit of submissions, free from commercial or political influence.</li>
                    <li><strong>Confidentiality:</strong> Editors and editorial staff maintain strict confidentiality regarding submitted manuscripts.</li>
                    <li><strong>Fairness:</strong> All manuscripts are evaluated based on their scholarly merit, without discrimination based on race, gender, sexual orientation, religious belief, ethnic origin, citizenship, or political philosophy of the authors.</li>
                    <li><strong>Conflict of Interest:</strong> Editors recuse themselves from handling manuscripts where they have conflicts of interest.</li>
                </ul>
                
                <h2 class="text-xl font-semibold text-[#0b2b3f] mt-6">Author Responsibilities</h2>
                <ul class="list-disc pl-6 space-y-2">
                    <li><strong>Originality:</strong> Authors must ensure that their work is original and has not been published elsewhere.</li>
                    <li><strong>Acknowledgment of Sources:</strong> Proper acknowledgment of the work of others must always be given.</li>
                    <li><strong>Authorship:</strong> All listed authors must have made significant contributions to the research.</li>
                    <li><strong>Data Access and Retention:</strong> Authors should be prepared to provide raw data for editorial review if requested.</li>
                    <li><strong>Conflicts of Interest:</strong> All potential conflicts of interest must be disclosed.</li>
                </ul>
                
                <h2 class="text-xl font-semibold text-[#0b2b3f] mt-6">Reviewer Responsibilities</h2>
                <ul class="list-disc pl-6 space-y-2">
                    <li><strong>Confidentiality:</strong> Reviewers must maintain confidentiality of the review process.</li>
                    <li><strong>Constructive Feedback:</strong> Reviews should be objective, constructive, and respectful.</li>
                    <li><strong>Timeliness:</strong> Reviewers should respond to invitations promptly and meet deadlines.</li>
                    <li><strong>Conflict of Interest:</strong> Any conflicts of interest must be disclosed immediately.</li>
                </ul>
                
                <h2 class="text-xl font-semibold text-[#0b2b3f] mt-6">Plagiarism and Misconduct</h2>
                <p>TIRP uses plagiarism detection software to screen all submissions. Cases of plagiarism, data fabrication, or other misconduct will be investigated according to COPE guidelines.</p>
                
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-4">
                    <p class="text-sm text-yellow-800">
                        <i class="fas fa-info-circle mr-2"></i>
                        For any ethical concerns, please contact the editorial office at 
                        <a href="mailto:ethics@tirp.org" class="text-indigo-600 hover:underline">ethics@tirp.org</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <?php include './includes/footer.php'; ?>
</body>
</html>
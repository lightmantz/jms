<?php
// modules/pages/author-guidelines.php - Author Guidelines
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../includes/functions.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Author Guidelines - <?= SITE_NAME ?></title>
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
                <i class="fas fa-user-edit text-indigo-500"></i> Author Guidelines
            </h1>
            <div class="h-1 w-20 bg-indigo-200 rounded-full mt-2 mb-6"></div>
            
            <div class="prose max-w-none text-gray-600 space-y-6">
                <p class="text-lg font-medium text-[#0b2b3f]">Welcome to TIRP. Please read these guidelines carefully before submitting your manuscript.</p>
                
                <h2 class="text-xl font-semibold text-[#0b2b3f] mt-6">Types of Articles</h2>
                <p>TIRP accepts the following types of articles:</p>
                <ul class="list-disc pl-6 space-y-2">
                    <li><strong>Original Research:</strong> Full-length reports of original research (3,000-5,000 words)</li>
                    <li><strong>Review Articles:</strong> Comprehensive reviews of a topic (4,000-8,000 words)</li>
                    <li><strong>Case Reports:</strong> Detailed reports of clinical cases (1,500-2,500 words)</li>
                    <li><strong>Editorials:</strong> Opinion pieces from invited authors (1,000-2,000 words)</li>
                    <li><strong>Letters to the Editor:</strong> Brief communications (500-1,000 words)</li>
                    <li><strong>Commentaries:</strong> Perspective pieces on current topics (1,500-3,000 words)</li>
                </ul>
                
                <h2 class="text-xl font-semibold text-[#0b2b3f] mt-6">Manuscript Preparation</h2>
                <p>All manuscripts should be prepared according to the following guidelines:</p>
                <ul class="list-disc pl-6 space-y-2">
                    <li><strong>Language:</strong> English (British or American spelling accepted)</li>
                    <li><strong>Format:</strong> Microsoft Word or LaTeX</li>
                    <li><strong>Font:</strong> Times New Roman, 12pt</li>
                    <li><strong>Spacing:</strong> Double-spaced</li>
                    <li><strong>Margins:</strong> 1 inch (2.54 cm) on all sides</li>
                    <li><strong>File Format:</strong> .doc, .docx, or .pdf</li>
                </ul>
                
                <h2 class="text-xl font-semibold text-[#0b2b3f] mt-6">Manuscript Structure</h2>
                <p>Original research articles should follow this structure:</p>
                <ol class="list-decimal pl-6 space-y-2">
                    <li><strong>Title Page:</strong> Title, author names, affiliations, corresponding author contact</li>
                    <li><strong>Abstract:</strong> 250 words maximum, structured (Background, Methods, Results, Conclusion)</li>
                    <li><strong>Keywords:</strong> 4-6 keywords for indexing</li>
                    <li><strong>Introduction:</strong> Background and study objectives</li>
                    <li><strong>Methods:</strong> Study design, participants, interventions, statistical analysis</li>
                    <li><strong>Results:</strong> Study findings with tables and figures</li>
                    <li><strong>Discussion:</strong> Interpretation of findings, limitations, conclusions</li>
                    <li><strong>References:</strong> Vancouver style</li>
                    <li><strong>Tables and Figures:</strong> Each on separate pages with captions</li>
                </ol>
                
                <h2 class="text-xl font-semibold text-[#0b2b3f] mt-6">Submission Process</h2>
                <ol class="list-decimal pl-6 space-y-2">
                    <li>Create an account on the TIRP submission system</li>
                    <li>Click on "New Submission"</li>
                    <li>Upload your manuscript files</li>
                    <li>Enter author information and metadata</li>
                    <li>Submit for review</li>
                </ol>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-4">
                    <p class="text-sm text-blue-800">
                        <i class="fas fa-info-circle mr-2"></i>
                        For detailed formatting instructions, please download our 
                        <a href="#" class="text-indigo-600 hover:underline">Author Template</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <?php include './includes/footer.php'; ?>
</body>
</html>
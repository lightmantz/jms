<?php
// modules/pages/reviewer-guidelines.php - Reviewer Guidelines
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../includes/functions.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviewer Guidelines - <?= SITE_NAME ?></title>
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
                <i class="fas fa-user-graduate text-indigo-500"></i> Reviewer Guidelines
            </h1>
            <div class="h-1 w-20 bg-indigo-200 rounded-full mt-2 mb-6"></div>
            
            <div class="prose max-w-none text-gray-600 space-y-6">
                <p class="text-lg font-medium text-[#0b2b3f]">Thank you for agreeing to review for TIRP. Your contribution is essential to maintaining the quality of our journal.</p>
                
                <h2 class="text-xl font-semibold text-[#0b2b3f] mt-6">Reviewer Responsibilities</h2>
                <ul class="list-disc pl-6 space-y-2">
                    <li><strong>Confidentiality:</strong> The review process is strictly confidential. Do not share the manuscript or discuss it with others.</li>
                    <li><strong>Objectivity:</strong> Evaluate the manuscript objectively and provide constructive feedback.</li>
                    <li><strong>Timeliness:</strong> Complete your review within the specified timeframe.</li>
                    <li><strong>Conflict of Interest:</strong> Disclose any potential conflicts of interest immediately.</li>
                    <li><strong>Professionalism:</strong> Maintain a respectful and professional tone in your review.</li>
                </ul>
                
                <h2 class="text-xl font-semibold text-[#0b2b3f] mt-6">Review Process</h2>
                <ol class="list-decimal pl-6 space-y-2">
                    <li><strong>Invitation:</strong> You will receive an email invitation to review a manuscript.</li>
                    <li><strong>Acceptance:</strong> Accept the invitation if you can complete the review on time.</li>
                    <li><strong>Manuscript Review:</strong> Read the manuscript thoroughly and complete the review form.</li>
                    <li><strong>Recommendation:</strong> Provide your recommendation (Accept, Minor Revision, Major Revision, Reject).</li>
                    <li><strong>Submission:</strong> Submit your review through the online system.</li>
                </ol>
                
                <h2 class="text-xl font-semibold text-[#0b2b3f] mt-6">Review Criteria</h2>
                <p>When reviewing a manuscript, please consider the following criteria:</p>
                <ul class="list-disc pl-6 space-y-2">
                    <li><strong>Originality:</strong> Is the research novel and significant?</li>
                    <li><strong>Methodology:</strong> Are the methods appropriate and well-described?</li>
                    <li><strong>Results:</strong> Are the results clearly presented and supported by evidence?</li>
                    <li><strong>Discussion:</strong> Is the discussion balanced and well-referenced?</li>
                    <li><strong>Conclusion:</strong> Are the conclusions supported by the data?</li>
                    <li><strong>References:</strong> Are the references appropriate and up-to-date?</li>
                    <li><strong>Writing Quality:</strong> Is the manuscript well-written and organized?</li>
                </ul>
                
                <h2 class="text-xl font-semibold text-[#0b2b3f] mt-6">Writing Your Review</h2>
                <p>A good review should include:</p>
                <ul class="list-disc pl-6 space-y-2">
                    <li><strong>Summary:</strong> A brief summary of the manuscript</li>
                    <li><strong>Strengths:</strong> What are the strengths of the work?</li>
                    <li><strong>Weaknesses:</strong> What are the limitations or concerns?</li>
                    <li><strong>Recommendations:</strong> Specific suggestions for improvement</li>
                    <li><strong>Confidential Comments:</strong> Any confidential comments to the editor</li>
                </ul>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-4">
                    <p class="text-sm text-blue-800">
                        <i class="fas fa-info-circle mr-2"></i>
                        Need assistance? Contact the editorial office at 
                        <a href="mailto:reviewers@tirp.org" class="text-indigo-600 hover:underline">reviewers@tirp.org</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <?php include './includes/footer.php'; ?>
</body>
</html>
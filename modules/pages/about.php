<?php
// modules/about/index.php - About Page
require_once __DIR__ . '/../../includes/init.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>css/style.css">
</head>
<body class="antialiased text-gray-700 font-['Inter']" style="background: #f6f9fc;">
    <?php include INCLUDES_PATH . 'header.php'; ?>
    
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-8 md:p-10">
            <h1 class="text-3xl font-bold text-[#0b2b3f] flex items-center gap-3">
                <i class="fas fa-info-circle text-indigo-500"></i> About TIRP
            </h1>
            <div class="h-1 w-20 bg-indigo-200 rounded-full mt-2 mb-6"></div>
            
            <div class="prose max-w-none">
                <h2 class="text-xl font-semibold text-[#0b2b3f] mt-6">Our Mission</h2>
                <p class="text-gray-600">The Tanzania Journal of Rehabilitation Practice (TIRP) is a peer-reviewed, open-access journal dedicated to advancing rehabilitation science and practice in Tanzania and across the African continent.</p>
                
                <h2 class="text-xl font-semibold text-[#0b2b3f] mt-6">Our Vision</h2>
                <p class="text-gray-600">To be the leading platform for rehabilitation research in Africa, promoting evidence-based practice and improving healthcare outcomes for people with disabilities and chronic conditions.</p>
                
                <h2 class="text-xl font-semibold text-[#0b2b3f] mt-6">Scope</h2>
                <p class="text-gray-600">TIRP publishes original research, systematic reviews, case reports, clinical innovations, and professional perspectives in all areas of rehabilitation, including:</p>
                <ul class="list-disc pl-6 text-gray-600 mt-2 space-y-1">
                    <li>Physical Therapy and Physiotherapy</li>
                    <li>Occupational Therapy</li>
                    <li>Speech and Language Therapy</li>
                    <li>Community-Based Rehabilitation</li>
                    <li>Disability Studies and Inclusion</li>
                    <li>Rehabilitation Technology and Assistive Devices</li>
                    <li>Mental Health Rehabilitation</li>
                    <li>Pediatric and Geriatric Rehabilitation</li>
                    <li>Sports Rehabilitation</li>
                </ul>
                
                <h2 class="text-xl font-semibold text-[#0b2b3f] mt-6">Open Access Policy</h2>
                <p class="text-gray-600">TIRP is committed to the principles of open access. All articles are freely available to read, download, and share under a Creative Commons license, ensuring maximum dissemination and impact of research.</p>
            </div>
        </div>
    </div>
    
    <?php include INCLUDES_PATH . 'footer.php'; ?>
</body>
</html>
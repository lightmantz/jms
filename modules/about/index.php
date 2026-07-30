<?php
// modules/about/index.php
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
    <link rel="stylesheet" href="/jms/css/style.css">
</head>
<body class="antialiased text-gray-700 font-['Inter']" style="background: #f6f9fc;">
    <?php include './includes/header.php'; ?>
    
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-8 md:p-10">
            <h2 class="text-3xl font-bold text-[#0b2b3f] flex items-center gap-3">
                <i class="fas fa-info-circle text-indigo-500 text-2xl"></i> About the Journal
            </h2>
            <div class="h-1 w-20 bg-indigo-200 rounded-full mt-2 mb-6"></div>
            
            <div class="prose prose-lg max-w-none text-gray-600 space-y-4">
                <p><strong class="text-[#0b2b3f]">Tanzania Journal of Rehabilitation Practice (TIRP)</strong> is a peer-reviewed, open-access journal dedicated to advancing the field of rehabilitation science and practice in Tanzania and across the African continent.</p>
                
                <p>Published by the <strong>Tanzania Society of Rehabilitation</strong> in collaboration with <strong>Lightman Computers Tech</strong>, TIRP provides a platform for researchers, clinicians, and educators to share evidence-based findings, innovative interventions, and policy-relevant research in occupational therapy, physiotherapy, speech therapy, and community-based rehabilitation.</p>
                
                <div class="grid sm:grid-cols-2 gap-6 mt-6">
                    <div class="bg-indigo-50/50 rounded-xl p-5">
                        <i class="fas fa-bullseye text-2xl text-[#0b2b3f]"></i>
                        <h4 class="font-semibold mt-2">Aims & Scope</h4>
                        <p class="text-sm text-gray-500">We publish original research, reviews, case studies, and perspectives that address rehabilitation challenges, health systems, and disability inclusion in resource-limited settings.</p>
                    </div>
                    <div class="bg-indigo-50/50 rounded-xl p-5">
                        <i class="fas fa-globe-africa text-2xl text-[#0b2b3f]"></i>
                        <h4 class="font-semibold mt-2">Open Access</h4>
                        <p class="text-sm text-gray-500">All articles are freely available under a Creative Commons license. No publication fees for authors from low-income countries.</p>
                    </div>
                </div>
                
                <div class="mt-6 p-5 bg-gray-50 rounded-xl border border-gray-100">
                    <p class="text-sm"><i class="fas fa-check-circle text-emerald-500 mr-2"></i> <strong>Indexing:</strong> TIRP is currently indexed in Google Scholar and is under evaluation for DOAJ, Crossref, and Scopus. All articles receive a DOI.</p>
                    <p class="text-sm mt-2"><i class="fas fa-check-circle text-emerald-500 mr-2"></i> <strong>Frequency:</strong> Quarterly (March, June, September, December) with continuous online publication.</p>
                </div>
            </div>
        </div>
    </div>
    
    <?php include './includes/footer.php'; ?>
</body>
</html>
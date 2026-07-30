<?php
// modules/pages/faq.php - Frequently Asked Questions
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../includes/functions.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ - <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="/jms/css/style.css">
    <style>
        .faq-item details {
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 0;
        }
        .faq-item summary {
            cursor: pointer;
            font-weight: 600;
            color: #0b2b3f;
            padding: 0.5rem 0;
            list-style: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .faq-item summary:hover {
            color: #4f46e5;
        }
        .faq-item summary::-webkit-details-marker {
            display: none;
        }
        .faq-item summary::after {
            content: '\f078';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            color: #4f46e5;
            font-size: 0.875rem;
            transition: transform 0.3s ease;
        }
        .faq-item details[open] summary::after {
            transform: rotate(180deg);
        }
        .faq-item details p {
            padding: 0.5rem 0 0.25rem 0;
        }
        .faq-section-title {
            position: relative;
            padding-left: 1rem;
        }
        .faq-section-title::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: #4f46e5;
            border-radius: 4px;
        }
        .search-box:focus {
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
            border-color: #4f46e5;
        }
        .faq-highlight {
            background-color: #fef3c7;
            padding: 0 0.25rem;
            border-radius: 2px;
        }
    </style>
</head>
<body class="antialiased text-gray-700 font-['Inter']" style="background: #f6f9fc;">
    <?php include '../../includes/header.php'; ?>
    
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-8 md:p-10">
            <h1 class="text-3xl font-bold text-[#0b2b3f] flex items-center gap-3">
                <i class="fas fa-question-circle text-indigo-500"></i> Frequently Asked Questions
            </h1>
            <div class="h-1 w-20 bg-indigo-200 rounded-full mt-2 mb-6"></div>
            
            <!-- Search Bar -->
            <div class="mb-8">
                <div class="relative">
                    <input type="text" id="faqSearch" placeholder="Search for answers..." 
                           class="search-box w-full px-4 py-3 pl-12 border border-gray-300 rounded-xl focus:outline-none focus:border-indigo-500 transition-all duration-200">
                    <i class="fas fa-search absolute left-4 top-3.5 text-gray-400"></i>
                    <button onclick="clearSearch()" id="clearSearchBtn" class="absolute right-4 top-3 text-gray-400 hover:text-gray-600 hidden">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>
            </div>
            
            <div class="faq-item space-y-4" id="faqContainer">
                <!-- General Questions -->
                <h2 class="text-xl font-semibold text-[#0b2b3f] mt-6 mb-4 faq-section-title">General Questions</h2>
                
                <details data-keywords="tirp what journal rehabilitation practice africa">
                    <summary>What is TIRP?</summary>
                    <p class="text-gray-600 mt-2">TIRP (Tanzania Journal of Rehabilitation Practice) is a peer-reviewed, open-access journal dedicated to advancing rehabilitation science and practice in Tanzania and across the African continent.</p>
                </details>
                
                <details data-keywords="open access free read download share creative commons license">
                    <summary>Is TIRP an open-access journal?</summary>
                    <p class="text-gray-600 mt-2">Yes, TIRP is an open-access journal. All articles are freely available to read, download, and share under a Creative Commons license.</p>
                </details>
                
                <details data-keywords="frequency publication quarterly march june september december continuous online">
                    <summary>What is the publication frequency?</summary>
                    <p class="text-gray-600 mt-2">TIRP is published quarterly (March, June, September, December) with continuous online publication.</p>
                </details>
                
                <details data-keywords="charges fees publication cost apc article processing charge low-income countries">
                    <summary>Does TIRP charge publication fees?</summary>
                    <p class="text-gray-600 mt-2">No, TIRP does not charge publication fees for authors from low-income countries. For other authors, a nominal APC (Article Processing Charge) may apply.</p>
                </details>
                
                <!-- Submission Questions -->
                <h2 class="text-xl font-semibold text-[#0b2b3f] mt-8 mb-4 faq-section-title">Submission Questions</h2>
                
                <details data-keywords="submit manuscript submission system account new submission upload metadata">
                    <summary>How do I submit a manuscript?</summary>
                    <p class="text-gray-600 mt-2">Create an account on our submission system, click on "New Submission," and follow the step-by-step process to upload your manuscript and enter metadata.</p>
                </details>
                
                <details data-keywords="file formats accepted word doc docx pdf supplementary materials xls ppt txt zip images">
                    <summary>What file formats are accepted?</summary>
                    <p class="text-gray-600 mt-2">We accept Microsoft Word (.doc, .docx) and PDF files. For supplementary materials, we accept PDF, DOC, XLS, PPT, TXT, ZIP, and common image formats.</p>
                </details>
                
                <details data-keywords="review process duration time first decision 28 days publication timeline months">
                    <summary>How long does the review process take?</summary>
                    <p class="text-gray-600 mt-2">The average time from submission to first decision is approximately 28 days. The total time from submission to publication is typically 2-4 months.</p>
                </details>
                
                <details data-keywords="original work duplicate publication previously published elsewhere plagiarism">
                    <summary>Can I submit a manuscript that has been published elsewhere?</summary>
                    <p class="text-gray-600 mt-2">No, TIRP only considers original work that has not been previously published. All submissions are checked for plagiarism using industry-standard software.</p>
                </details>
                
                <details data-keywords="author guidelines formatting style citation reference apa vancouver">
                    <summary>What are the formatting requirements?</summary>
                    <p class="text-gray-600 mt-2">Manuscripts should follow the uniform requirements for manuscripts submitted to biomedical journals. We accept both APA and Vancouver referencing styles. Detailed guidelines are available in our Author Guidelines section.</p>
                </details>
                
                <details data-keywords="co-authors multiple authors corresponding author authorship criteria contribution">
                    <summary>How do I add co-authors to my submission?</summary>
                    <p class="text-gray-600 mt-2">During the submission process, you can add co-authors by providing their email addresses. Co-authors will receive an email to confirm their authorship and complete their profiles.</p>
                </details>
                
                <!-- Review Process Questions -->
                <h2 class="text-xl font-semibold text-[#0b2b3f] mt-8 mb-4 faq-section-title">Review Process Questions</h2>
                
                <details data-keywords="peer review double-blind reviewers anonymity">
                    <summary>What type of peer review does TIRP use?</summary>
                    <p class="text-gray-600 mt-2">TIRP uses a double-blind peer review process, where both the reviewers and authors remain anonymous to each other to ensure unbiased evaluation.</p>
                </details>
                
                <details data-keywords="reviewer criteria expertise qualifications become reviewer register">
                    <summary>How can I become a reviewer?</summary>
                    <p class="text-gray-600 mt-2">You can register as a reviewer by creating an account and selecting "Reviewer" as your role. Our editorial team will review your qualifications and expertise area.</p>
                </details>
                
                <details data-keywords="reviewer guidelines evaluation criteria scoring rubric">
                    <summary>What are the reviewer guidelines?</summary>
                    <p class="text-gray-600 mt-2">Reviewers evaluate manuscripts based on originality, scientific merit, methodology, significance, clarity, and relevance to the journal's scope. Detailed guidelines are available in your reviewer dashboard.</p>
                </details>
                
                <details data-keywords="revision revisions major minor decision resubmit deadline">
                    <summary>What happens after I receive a revision request?</summary>
                    <p class="text-gray-600 mt-2">When revisions are requested, you'll receive a decision letter with reviewer comments. You'll need to revise your manuscript accordingly and submit a revised version with a point-by-point response to reviewer comments within the given timeframe.</p>
                </details>
                
                <details data-keywords="rejection appeal decision reconsideration">
                    <summary>Can I appeal a rejection decision?</summary>
                    <p class="text-gray-600 mt-2">Yes, if you believe your manuscript was rejected unfairly, you can submit an appeal with a detailed justification. The appeal will be reviewed by the Editor-in-Chief.</p>
                </details>
                
                <!-- Publication Questions -->
                <h2 class="text-xl font-semibold text-[#0b2b3f] mt-8 mb-4 faq-section-title">Publication Questions</h2>
                
                <details data-keywords="publication timeline production process proofreading galley proofs">
                    <summary>How long after acceptance is my article published?</summary>
                    <p class="text-gray-600 mt-2">After acceptance, articles are typically published online within 2-3 weeks. The production process includes copyediting, typesetting, and proofreading.</p>
                </details>
                
                <details data-keywords="indexing databases scopus web of science google scholar doaj pubmed">
                    <summary>Is TIRP indexed in major databases?</summary>
                    <p class="text-gray-600 mt-2">Yes, TIRP is indexed in Google Scholar, DOAJ (Directory of Open Access Journals), and is in the process of being indexed in Scopus and Web of Science.</p>
                </details>
                
                <details data-keywords="copyright ownership license creative commons retain rights">
                    <summary>Who holds the copyright of published articles?</summary>
                    <p class="text-gray-600 mt-2">Authors retain copyright of their work. Articles are published under a Creative Commons Attribution License (CC BY) that allows others to distribute, remix, and build upon the work, provided credit is given to the authors.</p>
                </details>
                
                <details data-keywords="reprints copies offprints printed version">
                    <summary>Can I order reprints of my article?</summary>
                    <p class="text-gray-600 mt-2">Yes, authors can order high-quality reprints of their articles. A reprint order form is available in the author dashboard after publication.</p>
                </details>
                
                <!-- Technical Questions -->
                <h2 class="text-xl font-semibold text-[#0b2b3f] mt-8 mb-4 faq-section-title">Technical Questions</h2>
                
                <details data-keywords="forgot password reset account login issue">
                    <summary>I forgot my password. How can I reset it?</summary>
                    <p class="text-gray-600 mt-2">Click on the "Forgot Password" link on the login page. Enter your registered email address, and we'll send you instructions to reset your password.</p>
                </details>
                
                <details data-keywords="change email update profile account settings">
                    <summary>How can I update my profile information?</summary>
                    <p class="text-gray-600 mt-2">Log in to your account, go to the "My Profile" section, and you can update your personal information, email address, institutional affiliation, and research interests.</p>
                </details>
                
                <details data-keywords="upload error technical issue file size limit submission problem">
                    <summary>I'm having trouble uploading my manuscript. What should I do?</summary>
                    <p class="text-gray-600 mt-2">Check that your file size does not exceed 10MB. Supported file formats include .doc, .docx, and .pdf. If you continue to experience issues, clear your browser cache or try a different browser. Contact technical support at support@tirpjournal.org for assistance.</p>
                </details>
                
                <details data-keywords="browser compatibility system requirements recommended browsers">
                    <summary>Which browsers are supported?</summary>
                    <p class="text-gray-600 mt-2">Our system is optimized for the latest versions of Chrome, Firefox, Safari, and Edge. For the best experience, we recommend using Google Chrome.</p>
                </details>
            </div>
            
            <!-- Still Have Questions Section -->
            <div class="mt-12 p-6 bg-indigo-50 rounded-xl border border-indigo-100">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-[#0b2b3f] flex items-center gap-2">
                            <i class="fas fa-headset text-indigo-500"></i> Still Have Questions?
                        </h3>
                        <p class="text-gray-600 text-sm">Our support team is here to help you</p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <a href="mailto:support@tirpjournal.org" class="inline-flex items-center px-6 py-2.5 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors duration-200">
                            <i class="fas fa-envelope mr-2"></i> Email Support
                        </a>
                        <a href="/jms/modules/pages/contact.php" class="inline-flex items-center px-6 py-2.5 bg-white text-indigo-600 font-medium rounded-lg border border-indigo-300 hover:bg-indigo-50 transition-colors duration-200">
                            <i class="fas fa-comment mr-2"></i> Contact Form
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    
    <script>
        // Search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('faqSearch');
            const clearBtn = document.getElementById('clearSearchBtn');
            const faqItems = document.querySelectorAll('#faqContainer details');
            const sectionTitles = document.querySelectorAll('.faq-section-title');
            
            function filterFAQs(searchTerm) {
                const term = searchTerm.toLowerCase().trim();
                let hasVisibleItems = false;
                
                // Reset all items first
                faqItems.forEach(item => {
                    const summary = item.querySelector('summary');
                    const content = item.querySelector('p');
                    const keywords = item.getAttribute('data-keywords') || '';
                    const text = (summary.textContent + ' ' + content.textContent + ' ' + keywords).toLowerCase();
                    
                    if (term === '') {
                        item.style.display = 'block';
                        hasVisibleItems = true;
                    } else if (text.includes(term)) {
                        item.style.display = 'block';
                        item.open = true;
                        hasVisibleItems = true;
                        // Highlight matching text
                        if (content) {
                            const textContent = content.textContent;
                            const regex = new RegExp(`(${term})`, 'gi');
                            content.innerHTML = textContent.replace(regex, '<span class="faq-highlight">$1</span>');
                        }
                    } else {
                        item.style.display = 'none';
                        item.open = false;
                        if (item.querySelector('p')) {
                            item.querySelector('p').innerHTML = item.querySelector('p').textContent;
                        }
                    }
                });
                
                // Show/hide section titles
                sectionTitles.forEach(title => {
                    const nextDetails = title.nextElementSibling;
                    let hasVisible = false;
                    let sibling = nextDetails;
                    while (sibling && sibling.tagName === 'DETAILS') {
                        if (sibling.style.display !== 'none') {
                            hasVisible = true;
                            break;
                        }
                        sibling = sibling.nextElementSibling;
                    }
                    title.style.display = hasVisible || term === '' ? 'block' : 'none';
                });
                
                // Show clear button
                clearBtn.classList.toggle('hidden', term === '');
            }
            
            searchInput.addEventListener('input', function() {
                filterFAQs(this.value);
            });
            
            window.clearSearch = function() {
                searchInput.value = '';
                filterFAQs('');
                searchInput.focus();
            };
            
            // Toggle icon rotation
            document.querySelectorAll('#faqContainer details').forEach(details => {
                details.addEventListener('toggle', function() {
                    const summary = this.querySelector('summary');
                    if (this.open) {
                        summary.setAttribute('aria-expanded', 'true');
                    } else {
                        summary.setAttribute('aria-expanded', 'false');
                    }
                });
            });
        });
    </script>
</body>
</html>
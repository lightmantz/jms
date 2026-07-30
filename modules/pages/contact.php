<?php
// modules/pages/contact.php - Contact Page
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../includes/functions.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="/jms/css/style.css">
</head>
<body class="antialiased text-gray-700 font-['Inter']" style="background: #f6f9fc;">
    <?php include '../../includes/header.php'; ?>
    
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-8 md:p-10">
            <h1 class="text-3xl font-bold text-[#0b2b3f] flex items-center gap-3">
                <i class="fas fa-envelope text-indigo-500"></i> Contact Us
            </h1>
            <div class="h-1 w-20 bg-indigo-200 rounded-full mt-2 mb-6"></div>
            
            <div class="grid md:grid-cols-2 gap-8">
                <!-- Contact Information -->
                <div>
                    <h2 class="text-lg font-semibold text-[#0b2b3f] mb-4">Get in Touch</h2>
                    
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-map-marker-alt text-indigo-500 mt-1"></i>
                            <div>
                                <p class="font-medium text-gray-700">Address</p>
                                <p class="text-gray-500 text-sm">P.O. Box 1541, KCMC, Moshi, Tanzania</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3">
                            <i class="fas fa-phone text-indigo-500 mt-1"></i>
                            <div>
                                <p class="font-medium text-gray-700">Phone</p>
                                <p class="text-gray-500 text-sm">+255 763 872 771</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3">
                            <i class="fas fa-envelope text-indigo-500 mt-1"></i>
                            <div>
                                <p class="font-medium text-gray-700">Email</p>
                                <p class="text-gray-500 text-sm">info@lightmantz.com</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3">
                            <i class="fas fa-globe text-indigo-500 mt-1"></i>
                            <div>
                                <p class="font-medium text-gray-700">Website</p>
                                <p class="text-gray-500 text-sm">https://tirp.lightmantz.com</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3">
                            <i class="fas fa-clock text-indigo-500 mt-1"></i>
                            <div>
                                <p class="font-medium text-gray-700">Office Hours</p>
                                <p class="text-gray-500 text-sm">Monday - Friday: 8:00 AM - 5:00 PM EAT</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <h3 class="font-semibold text-[#0b2b3f] mb-2">Connect With Us</h3>
                        <div class="flex gap-3">
                            <a href="#" class="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center hover:bg-blue-700 transition">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="w-10 h-10 bg-blue-400 text-white rounded-full flex items-center justify-center hover:bg-blue-500 transition">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="#" class="w-10 h-10 bg-blue-700 text-white rounded-full flex items-center justify-center hover:bg-blue-800 transition">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                            <a href="#" class="w-10 h-10 bg-red-600 text-white rounded-full flex items-center justify-center hover:bg-red-700 transition">
                                <i class="fab fa-youtube"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Form -->
                <div>
                    <h2 class="text-lg font-semibold text-[#0b2b3f] mb-4">Send Us a Message</h2>
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Your Name *</label>
                            <input type="text" name="name" required 
                                   class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                            <input type="email" name="email" required 
                                   class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Subject *</label>
                            <input type="text" name="subject" required 
                                   class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Message *</label>
                            <textarea name="message" rows="4" required
                                      class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0b2b3f] focus:ring-2 focus:ring-indigo-100 outline-none transition"></textarea>
                        </div>
                        <button type="submit" 
                                class="w-full bg-[#0b2b3f] text-white py-2 rounded-lg font-semibold hover:bg-[#123a4f] transition shadow-sm">
                            <i class="fas fa-paper-plane mr-2"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>
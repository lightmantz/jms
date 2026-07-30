<?php
// modules/pages/contact.php - Contact Page
require_once __DIR__ . '/../../includes/init.php';
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
    <link rel="stylesheet" href="<?= SITE_URL ?>css/style.css">
    <style>
        .logo-container {
            width: 120px;
            height: 120px;
            margin: 0 auto 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            border-radius: 50%;
            padding: 12px;
            border: 2px solid #e2e8f0;
        }
        .logo-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .contact-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            border: 1px solid #f0f0f0;
        }
        .input-field {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            transition: all 0.2s;
            font-size: 14px;
        }
        .input-field:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        .btn-send {
            background: #4f46e5;
            color: white;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            width: 100%;
        }
        .btn-send:hover {
            background: #4338ca;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }
        .social-icon {
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: white;
            transition: all 0.2s;
        }
        .social-icon:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body class="antialiased text-gray-700 font-['Inter']" style="background: #f6f9fc;">
    <?php include INCLUDES_PATH . 'header.php'; ?>
    
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="contact-card p-8 md:p-10">
            <!-- Logo -->
            <div class="text-center mb-6">
                <div class="logo-container">
                    <img src="<?= SITE_URL ?>resources/images/tjr.png" alt="TIRP Logo">
                </div>
                <h1 class="text-3xl font-bold text-[#0b2b3f] flex items-center justify-center gap-3">
                    <i class="fas fa-envelope text-indigo-500"></i> Contact Us
                </h1>
                <div class="h-1 w-20 bg-indigo-200 rounded-full mx-auto mt-2"></div>
                <p class="text-gray-500 text-sm mt-3">Have questions or feedback? We'd love to hear from you.</p>
            </div>
            
            <div class="grid md:grid-cols-2 gap-8 mt-6">
                <!-- Contact Information -->
                <div>
                    <h2 class="text-lg font-semibold text-[#0b2b3f] mb-4 flex items-center gap-2">
                        <i class="fas fa-info-circle text-indigo-500"></i> Get in Touch
                    </h2>
                    
                    <div class="space-y-4">
                        <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <i class="fas fa-map-marker-alt text-indigo-500 mt-1"></i>
                            <div>
                                <p class="font-medium text-gray-700 text-sm">Address</p>
                                <p class="text-gray-500 text-sm">P.O. Box 1541, KCMC, Moshi, Tanzania</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <i class="fas fa-phone text-indigo-500 mt-1"></i>
                            <div>
                                <p class="font-medium text-gray-700 text-sm">Phone</p>
                                <p class="text-gray-500 text-sm">+255 763 872 771</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <i class="fas fa-envelope text-indigo-500 mt-1"></i>
                            <div>
                                <p class="font-medium text-gray-700 text-sm">Email</p>
                                <p class="text-gray-500 text-sm">info@lightmantz.com</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <i class="fas fa-globe text-indigo-500 mt-1"></i>
                            <div>
                                <p class="font-medium text-gray-700 text-sm">Website</p>
                                <p class="text-gray-500 text-sm">https://tirp.lightmantz.com</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <i class="fas fa-clock text-indigo-500 mt-1"></i>
                            <div>
                                <p class="font-medium text-gray-700 text-sm">Office Hours</p>
                                <p class="text-gray-500 text-sm">Monday - Friday: 8:00 AM - 5:00 PM EAT</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <h3 class="font-semibold text-[#0b2b3f] mb-3 flex items-center gap-2">
                            <i class="fas fa-share-alt text-indigo-500"></i> Connect With Us
                        </h3>
                        <div class="flex gap-3">
                            <a href="#" class="social-icon bg-blue-600 hover:bg-blue-700">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="social-icon bg-blue-400 hover:bg-blue-500">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="#" class="social-icon bg-blue-700 hover:bg-blue-800">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                            <a href="#" class="social-icon bg-red-600 hover:bg-red-700">
                                <i class="fab fa-youtube"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Form -->
                <div>
                    <h2 class="text-lg font-semibold text-[#0b2b3f] mb-4 flex items-center gap-2">
                        <i class="fas fa-paper-plane text-indigo-500"></i> Send Us a Message
                    </h2>
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Your Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" required 
                                   class="input-field"
                                   placeholder="John Doe">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email Address <span class="text-red-500">*</span></label>
                            <input type="email" name="email" required 
                                   class="input-field"
                                   placeholder="you@example.com">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Subject <span class="text-red-500">*</span></label>
                            <input type="text" name="subject" required 
                                   class="input-field"
                                   placeholder="Subject of your message">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Message <span class="text-red-500">*</span></label>
                            <textarea name="message" rows="4" required
                                      class="input-field"
                                      placeholder="Your message here..."></textarea>
                        </div>
                        <button type="submit" class="btn-send">
                            <i class="fas fa-paper-plane mr-2"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php include INCLUDES_PATH . 'footer.php'; ?>
</body>
</html>
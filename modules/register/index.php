<?php
// modules/register/index.php - Register Page
require_once __DIR__ . '/../../includes/init.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: ' . SITE_URL . '?page=home');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Registration logic here
    // ...
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>css/style.css">
</head>
<body class="antialiased text-gray-700 font-['Inter']" style="background: #f6f9fc;">
    <?php include INCLUDES_PATH . 'header.php'; ?>
    
    <div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-8">
            <div class="text-center mb-6">
                <i class="fas fa-user-plus text-4xl text-indigo-500"></i>
                <h1 class="text-2xl font-bold text-[#0b2b3f] mt-2">Create Account</h1>
                <p class="text-gray-500 text-sm">Register for a new account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">
                    <i class="fas fa-check-circle mr-2"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-4">
                    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <div class="relative">
                        <i class="fas fa-user absolute left-3 top-3.5 text-gray-400"></i>
                        <input type="text" id="full_name" name="full_name" required 
                               class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
                               placeholder="John Doe">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-3 top-3.5 text-gray-400"></i>
                        <input type="email" id="email" name="email" required 
                               class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
                               placeholder="you@example.com">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="institution" class="block text-sm font-medium text-gray-700 mb-1">Institution</label>
                    <div class="relative">
                        <i class="fas fa-building absolute left-3 top-3.5 text-gray-400"></i>
                        <input type="text" id="institution" name="institution" 
                               class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
                               placeholder="Your institution">
                    </div>
                </div>
                
                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-3 top-3.5 text-gray-400"></i>
                        <input type="password" id="password" name="password" required 
                               class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
                               placeholder="Create a password (min 8 characters)">
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Password must be at least 8 characters long</p>
                </div>
                
                <button type="submit" class="w-full bg-indigo-600 text-white py-2.5 rounded-lg font-semibold hover:bg-indigo-700 transition shadow-sm">
                    Create Account
                </button>
            </form>
            
            <p class="text-center text-sm text-gray-600 mt-6">
                Already have an account? 
                <a href="<?= SITE_URL ?>?page=login" class="text-indigo-600 hover:text-indigo-800 font-medium">Sign in here</a>
            </p>
        </div>
    </div>
    
    <?php include INCLUDES_PATH . 'footer.php'; ?>
</body>
</html>
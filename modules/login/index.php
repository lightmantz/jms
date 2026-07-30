<?php
// modules/login/index.php - Login Page
require_once __DIR__ . '/../../includes/init.php';

// If already logged in, redirect to dashboard
if (function_exists('isLoggedIn') && isLoggedIn()) {
    $currentUser = getCurrentUser();
    if ($currentUser) {
        $dashboardUrl = getDashboardUrl($currentUser);
        header('Location: ' . $dashboardUrl);
        exit;
    }
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = isset($_POST['remember']) ? true : false;
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        // Validate email without filter_var to avoid PCRE JIT warning
        if (!preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $email)) {
            $error = 'Please enter a valid email address.';
        } else {
            if (function_exists('authenticateUser')) {
                $user = authenticateUser($email, $password);
                if ($user) {
                    // Login successful
                    loginUser($user);
                    
                    // Redirect to dashboard
                    $dashboardUrl = getDashboardUrl($user);
                    header('Location: ' . $dashboardUrl);
                    exit;
                } else {
                    $error = 'Invalid email or password. Please try again.';
                }
            } else {
                $error = 'Authentication system is not properly configured. Please contact support.';
            }
        }
    }
}

// Check for logout message
if (isset($_GET['message']) && $_GET['message'] === 'logged_out') {
    $success = 'You have been successfully logged out.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>css/style.css">
    <style>
        .login-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            border: 1px solid #f0f0f0;
        }
        .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }
        .input-field {
            width: 100%;
            padding: 10px 12px 10px 40px;
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
        .btn-primary {
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
        .btn-primary:hover {
            background: #4338ca;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }
        .btn-primary:active {
            transform: translateY(0);
        }
    </style>
</head>
<body class="antialiased text-gray-700 font-['Inter']" style="background: linear-gradient(135deg, #f6f9fc 0%, #eef2f7 100%); min-height: 100vh;">
    <?php include INCLUDES_PATH . 'header.php'; ?>
    
    <div class="max-w-md mx-auto px-4 sm:px-6 py-12">
        <div class="login-card p-8">
            <!-- Logo/Icon -->
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-sign-in-alt text-2xl text-indigo-600"></i>
                </div>
                <h1 class="text-2xl font-bold text-[#0b2b3f]">Welcome Back</h1>
                <p class="text-gray-500 text-sm mt-1">Sign in to your TIRP account</p>
            </div>
            
            <!-- Success Message -->
            <?php if ($success): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">
                    <i class="fas fa-check-circle mr-2"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <!-- Error Message -->
            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form method="POST" action="" class="space-y-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <div class="relative">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" required 
                               class="input-field"
                               placeholder="you@example.com"
                               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                    </div>
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" required 
                               class="input-field"
                               placeholder="Enter your password">
                        <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <i id="passwordToggleIcon" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input type="checkbox" id="remember" name="remember" class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                        <label for="remember" class="ml-2 text-sm text-gray-600">Remember me</label>
                    </div>
                    <a href="<?= SITE_URL ?>?page=forgot-password" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                        Forgot password?
                    </a>
                </div>
                
                <button type="submit" class="btn-primary">
                    <i class="fas fa-sign-in-alt mr-2"></i> Sign In
                </button>
            </form>
            
            <!-- Register Link -->
            <p class="text-center text-sm text-gray-600 mt-6">
                Don't have an account? 
                <a href="<?= SITE_URL ?>?page=register" class="text-indigo-600 hover:text-indigo-800 font-medium">
                    Create one now
                </a>
            </p>
        </div>
        
        <!-- Demo Credentials (Development Only) -->
        <div class="text-center mt-6 text-xs text-gray-400">
            <p class="mb-1">Demo credentials:</p>
            <p>Admin: admin@jms.com / admin123</p>
            <p>Admin: admin@tirp.com / admin123</p>
            <p>Author: author@jms.com / admin123</p>
            <p>Reviewer: reviewer@jms.com / admin123</p>
        </div>
    </div>
    
    <?php include INCLUDES_PATH . 'footer.php'; ?>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const icon = document.getElementById('passwordToggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }
        
        // Add animation to the card
        document.addEventListener('DOMContentLoaded', function() {
            const card = document.querySelector('.login-card');
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(function() {
                card.style.transition = 'all 0.5s ease-out';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>
</html>
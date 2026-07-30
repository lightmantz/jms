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
    <style>
        /* Background Image */
        body {
            background-image: url('<?= SITE_URL ?>resources/images/login.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
            position: relative;
        }
        
        /* Overlay for better readability */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(11, 43, 63, 0.7);
            z-index: 0;
        }
        
        /* Ensure content is above overlay */
        .register-wrapper {
            position: relative;
            z-index: 1;
        }
        
        .register-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
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
            background: rgba(255, 255, 255, 0.9);
        }
        
        .input-field:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            background: white;
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
        
        .logo-container {
            width: 150px;
            height: 150px;
            margin: 0 auto 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            padding: 12px;
            border: 3px solid rgba(79, 70, 229, 0.3);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .logo-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .register-title {
            color: #0b2b3f;
            text-shadow: 0 2px 4px rgba(255, 255, 255, 0.1);
        }
        
        .register-subtitle {
            color: #64748b;
        }
    </style>
</head>
<body>
    <?php include INCLUDES_PATH . 'header.php'; ?>
    
    <div class="register-wrapper max-w-md mx-auto px-4 sm:px-6 py-12">
        <div class="register-card p-8">
            <!-- Logo -->
            <div class="text-center mb-6">
                <div class="logo-container">
                    <img src="<?= SITE_URL ?>resources/images/tjr.png" alt="TIRP Logo">
                </div>
                <h1 class="text-2xl font-bold register-title">Create Account</h1>
                <p class="text-gray-500 text-sm mt-1 register-subtitle">Register for a new TJRP account</p>
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
            
            <form method="POST" action="" class="space-y-4">
                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <div class="relative">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" id="full_name" name="full_name" required 
                               class="input-field"
                               placeholder="John Doe">
                    </div>
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <div class="relative">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" required 
                               class="input-field"
                               placeholder="you@example.com">
                    </div>
                </div>
                
                <div>
                    <label for="institution" class="block text-sm font-medium text-gray-700 mb-1">Institution</label>
                    <div class="relative">
                        <i class="fas fa-building input-icon"></i>
                        <input type="text" id="institution" name="institution" 
                               class="input-field"
                               placeholder="Your institution">
                    </div>
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" required 
                               class="input-field"
                               placeholder="Create a password (min 8 characters)">
                        <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <i id="passwordToggleIcon" class="fas fa-eye"></i>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Password must be at least 8 characters long</p>
                </div>
                
                <button type="submit" class="btn-primary">
                    <i class="fas fa-user-plus mr-2"></i> Create Account
                </button>
            </form>
            
            <p class="text-center text-sm text-gray-600 mt-6">
                Already have an account? 
                <a href="<?= SITE_URL ?>?page=login" class="text-indigo-600 hover:text-indigo-800 font-medium">Sign in here</a>
            </p>
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
            const card = document.querySelector('.register-card');
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
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

// Check for redirect parameter
$redirectUrl = isset($_GET['redirect']) ? $_GET['redirect'] : null;

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
                    
                    // Redirect to dashboard or the intended page
                    if ($redirectUrl) {
                        header('Location: ' . urldecode($redirectUrl));
                    } else {
                        $dashboardUrl = getDashboardUrl($user);
                        header('Location: ' . $dashboardUrl);
                    }
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

// Get sidebar data for right panel
$editorialBoard = getEditorialBoard(3);
$stats = getJournalStats();

// Get latest news for sidebar
$db = getDB();
$stmt = $db->query("
    SELECT n.*, u.full_name as author_name
    FROM news n
    LEFT JOIN users u ON n.author_id = u.id
    WHERE n.status = 'published'
    ORDER BY n.is_featured DESC, n.published_at DESC, n.created_at DESC
    LIMIT 4
");
$sidebarNews = $stmt->fetchAll();
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
        .login-wrapper {
            position: relative;
            z-index: 1;
        }
        
        .login-card {
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
            width: 120px;
            height: 120px;
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
        
        .login-title {
            color: #0b2b3f;
            text-shadow: 0 2px 4px rgba(255, 255, 255, 0.1);
        }
        
        .login-subtitle {
            color: #64748b;
        }
        
        /* Demo credentials styling */
        .demo-credentials {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(5px);
            border-radius: 12px;
            padding: 12px 16px;
            color: #1a1a1a;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .demo-credentials .label {
            color: #4a4a4a;
            font-weight: 500;
        }
        
        .demo-credentials .value {
            color: #1a1a1a;
            font-weight: 400;
        }
        
        .demo-credentials .title {
            color: #333333;
            font-weight: 600;
        }
        
        /* Sidebar Panel Styles */
        .info-panel {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
        }
        
        .info-panel .info-item {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 0.75rem 0;
            transition: all 0.2s ease;
        }
        
        .info-panel .info-item:last-child {
            border-bottom: none;
        }
        
        .info-panel .info-item:hover {
            padding-left: 0.5rem;
        }
        
        .info-panel .info-item a {
            color: rgba(255, 255, 255, 0.8);
            transition: all 0.2s ease;
        }
        
        .info-panel .info-item a:hover {
            color: #ffffff;
        }
        
        .info-panel .info-title {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .info-panel .info-value {
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.85rem;
        }

        @media (max-width: 1024px) {
            .login-sidebar {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <?php include INCLUDES_PATH . 'header.php'; ?>
    
    <div class="login-wrapper max-w-6xl mx-auto px-4 sm:px-6 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
            <!-- Login Form -->
            <div class="lg:col-span-3">
                <div class="login-card p-8">
                    <!-- Logo -->
                    <div class="text-center mb-6">
                        <div class="logo-container">
                            <img src="<?= SITE_URL ?>resources/images/tjr.png" alt="TIRP Logo">
                        </div>
                        <h1 class="text-2xl font-bold login-title">Welcome Back</h1>
                        <p class="text-gray-500 text-sm mt-1 login-subtitle">Sign in to your TJRP account</p>
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
                    
                    <!-- Demo Credentials (Development Only) -->
                    <div class="mt-6 demo-credentials">
                        <p class="text-xs font-semibold title mb-2 text-center">Demo Credentials</p>
                        <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs">
                            <div class="label">Admin:</div>
                            <div class="value">admin@jms.com / admin123</div>
                            <div class="label">Author:</div>
                            <div class="value">author@jms.com / author123</div>
                            <div class="label">Reviewer:</div>
                            <div class="value">reviewer@jms.com / reviewer123</div>
                            <div class="label">Publisher:</div>
                            <div class="value">publisher@jms.com / publisher123</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel - Info Sidebar -->
            <div class="lg:col-span-2 login-sidebar">
                <div class="info-panel p-6 space-y-6">
                    <div>
                        <h4 class="text-white font-semibold text-lg flex items-center gap-2">
                            <i class="fas fa-info-circle text-indigo-300"></i> Quick Info
                        </h4>
                    </div>

                    <!-- Stats -->
                    <div>
                        <p class="info-title">Journal Statistics</p>
                        <div class="grid grid-cols-1 gap-3 mt-2">
                            <div class="bg-white/5 rounded-lg p-3 text-center">
                                <p class="text-2xl font-bold text-white"><?= $stats['total_articles'] ?? 0 ?></p>
                                <p class="text-xs text-white/60">Total Articles</p>
                            </div>
                            <div class="bg-white/5 rounded-lg p-3 text-center">
                                <p class="text-2xl font-bold text-white"><?= number_format($stats['total_views'] ?? 0) ?></p>
                                <p class="text-xs text-white/60">Total Views</p>
                            </div>
                            <div class="bg-white/5 rounded-lg p-3 text-center">
                                <p class="text-2xl font-bold text-white"><?= $stats['submissions_this_month'] ?? 0 ?></p>
                                <p class="text-xs text-white/60">Submissions (This Month)</p>
                            </div>
                        </div>
                    </div>

                    <!-- Editorial Board -->
                    <div>
                        <p class="info-title">Editorial Board</p>
                        <div class="space-y-2 mt-2">
                            <?php if (!empty($editorialBoard)): ?>
                                <?php foreach ($editorialBoard as $member): ?>
                                    <div class="info-item">
                                        <p class="info-value"><?= htmlspecialchars($member['full_name'] ?? 'Unknown') ?></p>
                                        <p class="text-xs text-white/50"><?= htmlspecialchars($member['position'] ?? 'Member') ?></p>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="info-item">
                                    <p class="info-value">Prof. A. M. Kilonzo</p>
                                    <p class="text-xs text-white/50">Editor-in-Chief</p>
                                </div>
                                <div class="info-item">
                                    <p class="info-value">Dr. C. L. Mrema</p>
                                    <p class="text-xs text-white/50">Managing Editor</p>
                                </div>
                            <?php endif; ?>
                            <div class="pt-1">
                                <a href="<?= SITE_URL ?>?page=editorial" class="text-xs text-indigo-300 hover:text-white transition">
                                    View full board <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Latest News -->
                    <div>
                        <p class="info-title">Latest News</p>
                        <div class="space-y-2 mt-2">
                            <?php if (empty($sidebarNews)): ?>
                                <div class="info-item">
                                    <p class="info-value text-white/50">No news available.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($sidebarNews as $item): ?>
                                    <div class="info-item">
                                        <a href="<?= SITE_URL ?>?page=news&id=<?= $item['id'] ?>" class="info-value hover:text-white transition">
                                            <?= htmlspecialchars($item['title']) ?>
                                        </a>
                                        <p class="text-xs text-white/50"><?= formatDate($item['published_at'] ?? $item['created_at'], 'M d, Y') ?></p>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <div class="pt-1">
                                <a href="<?= SITE_URL ?>?page=news" class="text-xs text-indigo-300 hover:text-white transition">
                                    View all news <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Open Access -->
                    <div class="bg-white/5 rounded-lg p-3 text-center">
                        <i class="fas fa-unlock-alt text-2xl text-emerald-300 mb-1"></i>
                        <p class="text-sm font-medium text-white">Open Access</p>
                        <p class="text-xs text-white/60">All articles freely available</p>
                    </div>
                </div>
            </div>
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
            
            // Animate info panel
            const panel = document.querySelector('.info-panel');
            if (panel) {
                panel.style.opacity = '0';
                panel.style.transform = 'translateX(20px)';
                setTimeout(function() {
                    panel.style.transition = 'all 0.6s ease-out';
                    panel.style.opacity = '1';
                    panel.style.transform = 'translateX(0)';
                }, 300);
            }
        });
    </script>
</body>
</html>
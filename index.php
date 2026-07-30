<?php
// index.php - Main entry point / router
require_once __DIR__ . '/includes/init.php';

// Get the requested page
$page = isset($_GET['page']) ? $_GET['page'] : '';

// If no page parameter, check if the URL path indicates a module
if (empty($page)) {
    $requestUri = $_SERVER['REQUEST_URI'];
    // Parse the URL path
    $path = parse_url($requestUri, PHP_URL_PATH);
    $pathSegments = explode('/', trim($path, '/'));
    
    // Check if the first segment is a module (admin, author, reviewer, editor, publisher)
    if (isset($pathSegments[1])) {
        $module = $pathSegments[1];
        $validModules = ['admin', 'author', 'reviewer', 'editor', 'publisher'];
        if (in_array($module, $validModules)) {
            $page = $module . '-dashboard';
        }
    }
}

// If still empty, default to home
if (empty($page)) {
    $page = 'home';
}

// Security: Prevent directory traversal
$page = str_replace(['..', '/', '\\'], '', $page);

// Map page names to their actual paths
$pageMap = [
    // Pages in their own directories (with index.php)
    'home' => 'home/index.php',
    'about' => 'about/index.php',  // FIXED: Points to /modules/about/index.php
    'editorial' => 'editorial/index.php',
    'register' => 'register/index.php',
    'login' => 'login/index.php',
    'archive' => 'archive/index.php',
    'search' => 'search/index.php',
    
    // Pages in /modules/pages/
    'faq' => 'pages/faq.php',
    'contact' => 'pages/contact.php',
    'submit' => 'pages/submit.php',
    'author-guidelines' => 'pages/author-guidelines.php',
    'reviewer-guidelines' => 'pages/reviewer-guidelines.php',
    'reviewer-faq' => 'pages/reviewer-faq.php',
    'publication-ethics' => 'pages/publication-ethics.php',
    'privacy-policy' => 'pages/privacy-policy.php',
    'terms' => 'pages/terms.php',
    'notifications' => 'pages/notifications.php',
    'profile' => 'pages/profile.php',
    'forgot-password' => 'pages/forgot-password.php',
    'maintenance' => 'pages/maintenance.php',
    'settings' => 'pages/settings.php',
    'users' => 'pages/users.php',
    'issues' => 'pages/issues.php',
    'manuscript' => 'pages/manuscript.php',
    'review' => 'pages/review.php',
    '403' => 'pages/403.php',
    '404' => 'pages/404.php',
    'article' => 'pages/article.php',
    'download' => 'pages/download.php',
    
    // Dashboard pages - Role-specific dashboards
    'admin-dashboard' => 'admin/index.php',
    'author-dashboard' => 'author/index.php',
    'reviewer-dashboard' => 'reviewer/index.php',
    'editor-dashboard' => 'editor/index.php',
    'publisher-dashboard' => 'publisher/index.php',
    
    // Direct access to role modules (for URL routing)
    'admin' => 'admin/index.php',
    'author' => 'author/index.php',
    'reviewer' => 'reviewer/index.php',
    'editor' => 'editor/index.php',
    'publisher' => 'publisher/index.php',
];

// Check if page exists in map
if (isset($pageMap[$page])) {
    $pageFile = MODULES_PATH . $pageMap[$page];
    
    if (file_exists($pageFile)) {
        // Define the current page for the included file
        define('CURRENT_PAGE', $page);
        require_once $pageFile;
        exit;
    }
}

// If page not found, show 404
http_response_code(404);
if (file_exists(PAGES_PATH . '404.php')) {
    require_once PAGES_PATH . '404.php';
} else {
    // Fallback 404 page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>404 - Page Not Found</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                text-align: center;
                padding: 50px;
                background: #f6f9fc;
                color: #0b2b3f;
            }
            h1 {
                font-size: 72px;
                margin: 0;
                color: #4f46e5;
            }
            .container {
                max-width: 500px;
                margin: 0 auto;
                background: white;
                padding: 40px;
                border-radius: 16px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            }
            a {
                display: inline-block;
                margin-top: 20px;
                padding: 12px 24px;
                background: #4f46e5;
                color: white;
                text-decoration: none;
                border-radius: 8px;
            }
            a:hover {
                background: #4338ca;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>404</h1>
            <h2>Page Not Found</h2>
            <p>The page you are looking for could not be found.</p>
            <a href="<?= SITE_URL ?>?page=home">Go Home</a>
        </div>
    </body>
    </html>
    <?php
}
?>
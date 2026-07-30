<?php
// includes/init.php - Initialize paths and constants

// Disable PCRE JIT to fix the warning
ini_set('pcre.jit', '0');

// Define base paths
if (!defined('SITE_URL')) {
    define('SITE_URL', '/jms/');
}

if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'Tanzania Journal of Rehabilitation Practice');
}

// Define absolute path to the root directory
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__)); // /jms/
}

// Define include paths
if (!defined('INCLUDES_PATH')) {
    define('INCLUDES_PATH', BASE_PATH . '/includes/');
}

if (!defined('MODULES_PATH')) {
    define('MODULES_PATH', BASE_PATH . '/modules/');
}

if (!defined('PAGES_PATH')) {
    define('PAGES_PATH', MODULES_PATH . '/pages/');
}

if (!defined('CONFIG_PATH')) {
    define('CONFIG_PATH', BASE_PATH . '/config/');
}

if (!defined('UPLOADS_PATH')) {
    define('UPLOADS_PATH', BASE_PATH . '/uploads/');
}

if (!defined('RESOURCES_PATH')) {
    define('RESOURCES_PATH', BASE_PATH . '/resources/');
}

if (!defined('IMAGES_PATH')) {
    define('IMAGES_PATH', RESOURCES_PATH . 'images/');
}

if (!defined('CSS_PATH')) {
    define('CSS_PATH', BASE_PATH . '/css/');
}

if (!defined('JS_PATH')) {
    define('JS_PATH', BASE_PATH . '/js/');
}

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('Africa/Dar_es_Salaam');

// Include functions first (this has isLoggedIn AND getDB)
if (file_exists(INCLUDES_PATH . 'functions.php')) {
    require_once INCLUDES_PATH . 'functions.php';
}

// Then include auth (which uses functions from functions.php)
if (file_exists(INCLUDES_PATH . 'auth.php')) {
    require_once INCLUDES_PATH . 'auth.php';
}

// Set default charset
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
?>
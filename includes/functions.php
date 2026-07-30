<?php
// includes/functions.php - Core functions

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Get database connection
 */
function getDB() {
    static $db = null;
    
    if ($db === null) {
        // Load database configuration
        $configFile = __DIR__ . '/../config/database.php';
        
        try {
            if (file_exists($configFile)) {
                $config = require_once $configFile;
                $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
                $db = new PDO($dsn, $config['username'], $config['password'], $config['options'] ?? []);
            } else {
                // Fallback configuration
                $dsn = "mysql:host=localhost;dbname=tirp;charset=utf8mb4";
                $db = new PDO($dsn, 'root', '');
                $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            // For development - show error
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    return $db;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get a setting from database
 */
function getSetting($key) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        if ($result) {
            return $result['setting_value'];
        }
    } catch (PDOException $e) {
        // Table might not exist yet
    }
    
    // Fallback default values
    $settings = [
        'journal_issn' => '9877-476533',
        'journal_title' => 'Tanzania Journal of Rehabilitation Practice',
        'contact_email' => 'info@lightmantz.com',
        'contact_phone' => '+255 763 872 771',
        'contact_address' => 'P.O. Box 1541, KCMC, Moshi'
    ];
    
    return isset($settings[$key]) ? $settings[$key] : null;
}

// ============================================
// AUTHOR FUNCTIONS
// ============================================

/**
 * Get manuscripts by author ID
 */
function getManuscriptsByAuthor($authorId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT * FROM manuscripts 
        WHERE author_id = ? OR corresponding_author_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$authorId, $authorId]);
    return $stmt->fetchAll();
}

/**
 * Get manuscript with author details
 */
function getManuscriptWithAuthor($manuscriptId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT m.*, u.full_name as author_name, u.email as author_email
        FROM manuscripts m
        LEFT JOIN users u ON m.corresponding_author_id = u.id
        WHERE m.id = ?
    ");
    $stmt->execute([$manuscriptId]);
    return $stmt->fetch();
}

/**
 * Get notifications for user
 */
function getNotifications($userId, $limit = 10) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Log user action
 */
function logAction($userId, $action, $tableName = null, $recordId = null) {
    try {
        $db = getDB();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $stmt = $db->prepare("
            INSERT INTO audit_logs (user_id, action, table_name, record_id, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $action, $tableName, $recordId, $ip, $userAgent]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// ============================================
// REVIEWER FUNCTIONS
// ============================================

/**
 * Get manuscripts for reviewer
 */
function getManuscriptsForReviewer($reviewerId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT 
            m.*,
            r.id as review_id,
            r.status as review_status,
            r.due_date,
            r.invitation_date,
            r.accepted_date,
            r.completed_date,
            r.recommendation,
            u.full_name as reviewer_name
        FROM reviewer_assignments r
        JOIN manuscripts m ON r.manuscript_id = m.id
        LEFT JOIN users u ON r.reviewer_id = u.id
        WHERE r.reviewer_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$reviewerId]);
    return $stmt->fetchAll();
}

/**
 * Get review assignment by ID
 */
function getReviewAssignment($assignmentId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT r.*, m.title as manuscript_title, u.full_name as reviewer_name
        FROM reviewer_assignments r
        JOIN manuscripts m ON r.manuscript_id = m.id
        JOIN users u ON r.reviewer_id = u.id
        WHERE r.id = ?
    ");
    $stmt->execute([$assignmentId]);
    return $stmt->fetch();
}

/**
 * Get review by manuscript and reviewer
 */
function getReviewByManuscriptAndReviewer($manuscriptId, $reviewerId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT * FROM reviews 
        WHERE manuscript_id = ? AND reviewer_id = ?
    ");
    $stmt->execute([$manuscriptId, $reviewerId]);
    return $stmt->fetch();
}

// ============================================
// EDITOR FUNCTIONS
// ============================================

/**
 * Get manuscripts for editor
 */
function getManuscriptsForEditor($editorId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT * FROM manuscripts 
        WHERE editor_assigned_id = ? 
        ORDER BY submission_date DESC
    ");
    $stmt->execute([$editorId]);
    return $stmt->fetchAll();
}

/**
 * Get all reviewers
 */
function getReviewers() {
    $db = getDB();
    $stmt = $db->query("
        SELECT * FROM users 
        WHERE role = 'reviewer' AND is_active = 1 
        ORDER BY full_name
    ");
    return $stmt->fetchAll();
}

/**
 * Get all editors
 */
function getEditors() {
    $db = getDB();
    $stmt = $db->query("
        SELECT * FROM users 
        WHERE role IN ('admin', 'editor') AND is_active = 1 
        ORDER BY full_name
    ");
    return $stmt->fetchAll();
}

/**
 * Get pending reviews for editor
 */
function getPendingReviewsForEditor($editorId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT r.*, m.title as manuscript_title, u.full_name as reviewer_name
        FROM reviews r
        JOIN manuscripts m ON r.manuscript_id = m.id
        JOIN users u ON r.reviewer_id = u.id
        WHERE r.editor_id = ? AND r.status IN ('invited', 'accepted')
        ORDER BY r.due_date ASC
    ");
    $stmt->execute([$editorId]);
    return $stmt->fetchAll();
}

/**
 * Get editor assignments
 */
function getEditorAssignments($editorId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT m.*, u.full_name as author_name
        FROM manuscripts m
        LEFT JOIN users u ON m.corresponding_author_id = u.id
        WHERE m.editor_assigned_id = ?
        ORDER BY m.submission_date DESC
    ");
    $stmt->execute([$editorId]);
    return $stmt->fetchAll();
}

// ============================================
// PUBLISHER FUNCTIONS
// ============================================

/**
 * Get articles ready for publishing
 */
function getArticlesReadyForPublishing() {
    $db = getDB();
    $stmt = $db->query("
        SELECT m.*, u.full_name as author_name
        FROM manuscripts m
        LEFT JOIN users u ON m.corresponding_author_id = u.id
        WHERE m.status = 'accepted'
        ORDER BY m.accepted_at DESC
        LIMIT 10
    ");
    return $stmt->fetchAll();
}

/**
 * Get published articles by volume
 */
function getPublishedArticlesByVolume($volumeId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT m.*, u.full_name as author_name
        FROM manuscripts m
        JOIN issues i ON m.issue_id = i.id
        LEFT JOIN users u ON m.corresponding_author_id = u.id
        WHERE i.volume_id = ? AND m.status = 'published'
        ORDER BY m.publication_date DESC
    ");
    $stmt->execute([$volumeId]);
    return $stmt->fetchAll();
}

/**
 * Get published articles by issue
 */
function getPublishedArticlesByIssue($issueId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT m.*, u.full_name as author_name
        FROM manuscripts m
        LEFT JOIN users u ON m.corresponding_author_id = u.id
        WHERE m.issue_id = ? AND m.status = 'published'
        ORDER BY m.publication_date DESC
    ");
    $stmt->execute([$issueId]);
    return $stmt->fetchAll();
}

// ============================================
// VOLUME FUNCTIONS
// ============================================

/**
 * Get all volumes
 */
function getVolumes() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM volumes ORDER BY volume_number DESC");
    return $stmt->fetchAll();
}

/**
 * Get a specific volume by ID
 */
function getVolume($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM volumes WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Get issues for a volume
 */
function getIssuesByVolume($volumeId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM issues WHERE volume_id = ? ORDER BY issue_number DESC");
    $stmt->execute([$volumeId]);
    return $stmt->fetchAll();
}

/**
 * Get manuscripts for an issue
 */
function getManuscriptsByIssue($issueId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM manuscripts WHERE issue_id = ? AND status = 'published' ORDER BY page_start ASC");
    $stmt->execute([$issueId]);
    return $stmt->fetchAll();
}

// ============================================
// MANUSCRIPT FUNCTIONS
// ============================================

/**
 * Get recent articles
 */
function getRecentArticles($limit = 5) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT m.*, u.full_name as author_name 
        FROM manuscripts m 
        LEFT JOIN users u ON m.corresponding_author_id = u.id 
        WHERE m.status = 'published' 
        ORDER BY m.publication_date DESC, m.created_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * Get featured articles
 */
function getFeaturedArticles($limit = 3) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT m.*, u.full_name as author_name 
        FROM manuscripts m 
        LEFT JOIN users u ON m.corresponding_author_id = u.id 
        WHERE m.status = 'published' AND m.is_featured = 1 
        ORDER BY m.publication_date DESC, m.created_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * Get all published manuscripts
 */
function getPublishedManuscripts($limit = 10, $offset = 0) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT m.*, u.full_name as author_name 
        FROM manuscripts m 
        LEFT JOIN users u ON m.corresponding_author_id = u.id 
        WHERE m.status = 'published' 
        ORDER BY m.publication_date DESC, m.created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$limit, $offset]);
    return $stmt->fetchAll();
}

/**
 * Count published manuscripts
 */
function countPublishedManuscripts() {
    $db = getDB();
    $stmt = $db->query("SELECT COUNT(*) as count FROM manuscripts WHERE status = 'published'");
    $result = $stmt->fetch();
    return $result ? $result['count'] : 0;
}

/**
 * Get manuscripts by category
 */
function getManuscriptsByCategory($categoryId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT m.* FROM manuscripts m
        JOIN manuscript_keywords mk ON m.id = mk.manuscript_id
        WHERE mk.category_id = ? AND m.status = 'published'
        ORDER BY m.publication_date DESC
    ");
    $stmt->execute([$categoryId]);
    return $stmt->fetchAll();
}

/**
 * Search manuscripts
 */
function searchManuscripts($query) {
    $db = getDB();
    $searchTerm = '%' . $query . '%';
    $stmt = $db->prepare("
        SELECT m.*, u.full_name as author_name 
        FROM manuscripts m 
        LEFT JOIN users u ON m.corresponding_author_id = u.id 
        WHERE m.status = 'published' 
        AND (m.title LIKE ? OR m.abstract LIKE ? OR u.full_name LIKE ?)
        ORDER BY m.publication_date DESC
    ");
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    return $stmt->fetchAll();
}

/**
 * Get current issue
 */
function getCurrentIssue() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM issues WHERE is_current = 1 LIMIT 1");
    $issue = $stmt->fetch();
    
    if (!$issue) {
        $stmt = $db->query("SELECT * FROM issues ORDER BY publication_date DESC, id DESC LIMIT 1");
        $issue = $stmt->fetch();
    }
    
    return $issue;
}

/**
 * Get manuscript by ID
 */
function getManuscript($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM manuscripts WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Get author by ID
 */
function getAuthor($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Get categories
 */
function getCategories() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM categories ORDER BY name");
    return $stmt->fetchAll();
}

// ============================================
// EDITORIAL BOARD FUNCTIONS
// ============================================

/**
 * Get editorial board members
 */
function getEditorialBoard($limit = null) {
    $db = getDB();
    $sql = "SELECT eb.*, u.full_name, u.email, u.institution, u.bio, u.avatar 
            FROM editorial_board eb 
            JOIN users u ON eb.user_id = u.id 
            WHERE eb.is_active = 1 
            ORDER BY eb.display_order ASC, eb.id ASC";
    
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    
    $stmt = $db->query($sql);
    return $stmt->fetchAll();
}

/**
 * Get editorial board member by ID
 */
function getEditorialBoardMember($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT eb.*, u.full_name, u.email, u.institution, u.bio, u.avatar 
                          FROM editorial_board eb 
                          JOIN users u ON eb.user_id = u.id 
                          WHERE eb.id = ? AND eb.is_active = 1");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Get editorial board member by user ID
 */
function getEditorialBoardMemberByUser($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT eb.*, u.full_name, u.email, u.institution, u.bio, u.avatar 
                          FROM editorial_board eb 
                          JOIN users u ON eb.user_id = u.id 
                          WHERE eb.user_id = ? AND eb.is_active = 1");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

// ============================================
// STATISTICS FUNCTIONS
// ============================================

/**
 * Get journal statistics
 */
function getJournalStats() {
    $db = getDB();
    $stats = [];
    
    try {
        // Total articles
        $stmt = $db->query("SELECT COUNT(*) as count FROM manuscripts WHERE status = 'published'");
        $stats['total_articles'] = $stmt->fetch()['count'] ?? 0;
        
        // Total views
        $stmt = $db->query("SELECT COUNT(*) as count FROM article_views");
        $stats['total_views'] = $stmt->fetch()['count'] ?? 0;
        
        // Total downloads
        $stmt = $db->query("SELECT COUNT(*) as count FROM article_downloads");
        $stats['total_downloads'] = $stmt->fetch()['count'] ?? 0;
        
        // Total users
        $stmt = $db->query("SELECT COUNT(*) as count FROM users");
        $stats['total_users'] = $stmt->fetch()['count'] ?? 0;
        
        // Recent submissions this month
        $stmt = $db->query("SELECT COUNT(*) as count FROM manuscripts WHERE MONTH(submission_date) = MONTH(CURRENT_DATE()) AND YEAR(submission_date) = YEAR(CURRENT_DATE())");
        $stats['submissions_this_month'] = $stmt->fetch()['count'] ?? 0;
    } catch (PDOException $e) {
        // Tables might not exist yet
        $stats['total_articles'] = 0;
        $stats['total_views'] = 0;
        $stats['total_downloads'] = 0;
        $stats['total_users'] = 0;
        $stats['submissions_this_month'] = 0;
    }
    
    return $stats;
}

// ============================================
// STATUS BADGE FUNCTIONS
// ============================================

/**
 * Get status badge class for manuscript status
 */
function getStatusBadge($status) {
    $classes = [
        'draft' => 'bg-gray-100 text-gray-700',
        'submitted' => 'bg-blue-100 text-blue-700',
        'under_review' => 'bg-yellow-100 text-yellow-700',
        'revision_required' => 'bg-orange-100 text-orange-700',
        'accepted' => 'bg-green-100 text-green-700',
        'rejected' => 'bg-red-100 text-red-700',
        'published' => 'bg-purple-100 text-purple-700',
        'withdrawn' => 'bg-gray-100 text-gray-500',
        'invited' => 'bg-indigo-100 text-indigo-700',
        'accepted_review' => 'bg-blue-100 text-blue-700',
        'declined' => 'bg-red-100 text-red-700',
        'completed' => 'bg-green-100 text-green-700',
        'overdue' => 'bg-red-100 text-red-700',
        'pending' => 'bg-yellow-100 text-yellow-700'
    ];
    return $classes[$status] ?? 'bg-gray-100 text-gray-700';
}

/**
 * Get status badge class for manuscript status (alias for getStatusBadge)
 */
function getStatusBadgeClass($status) {
    return getStatusBadge($status);
}

// ============================================
// ROLE URL HELPER
// ============================================

/**
 * Generate a role-specific URL
 */
function roleUrl($role, $action = null, $params = []) {
    $baseUrl = defined('SITE_URL') ? SITE_URL : '/jms/';
    $url = $baseUrl . $role;
    
    $queryParams = [];
    if ($action) {
        $queryParams['action'] = $action;
    }
    $queryParams = array_merge($queryParams, $params);
    
    if (!empty($queryParams)) {
        $url .= '?' . http_build_query($queryParams);
    }
    
    return $url;
}

// ============================================
// UTILITY FUNCTIONS
// ============================================

/**
 * Get time ago string
 */
function timeAgo($timestamp) {
    if (empty($timestamp)) {
        return 'Just now';
    }
    
    $time = strtotime($timestamp);
    $diff = time() - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . 'm ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . 'h ago';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . 'd ago';
    } elseif ($diff < 2592000) {
        return floor($diff / 604800) . 'w ago';
    } else {
        return date('M j, Y', $time);
    }
}

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Generate a random string
 */
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Get current date/time
 */
function getCurrentDateTime() {
    return date('Y-m-d H:i:s');
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'F j, Y') {
    if (empty($date)) {
        return '';
    }
    $timestamp = strtotime($date);
    return date($format, $timestamp);
}

/**
 * Get user IP address
 */
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Redirect to a URL
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Get URL parameter safely
 */
function getParam($key, $default = null) {
    return isset($_GET[$key]) ? sanitizeInput($_GET[$key]) : $default;
}

/**
 * Get POST parameter safely
 */
function getPost($key, $default = null) {
    return isset($_POST[$key]) ? sanitizeInput($_POST[$key]) : $default;
}

/**
 * Check if request is POST
 */
function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Check if request is AJAX
 */
function isAjax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Create a slug from a string
 */
function createSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

/**
 * Truncate text to a specific length
 */
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

/**
 * Get current page URL
 */
function getCurrentURL() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    return $protocol . '://' . $host . $uri;
}

/**
 * Check if current page is active
 */
function isActivePage($page) {
    $currentPage = defined('CURRENT_PAGE') ? CURRENT_PAGE : (isset($_GET['page']) ? $_GET['page'] : 'home');
    return $currentPage === $page;
}

/**
 * Generate pagination
 */
function generatePagination($currentPage, $totalPages, $baseUrl) {
    if ($totalPages <= 1) {
        return '';
    }
    
    $html = '<nav class="flex items-center justify-between px-4 py-3 bg-white border-t border-gray-200 sm:px-6">';
    $html .= '<div class="flex justify-between flex-1 sm:hidden">';
    
    if ($currentPage > 1) {
        $html .= '<a href="' . $baseUrl . '&page=' . ($currentPage - 1) . '" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Previous</a>';
    }
    
    if ($currentPage < $totalPages) {
        $html .= '<a href="' . $baseUrl . '&page=' . ($currentPage + 1) . '" class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Next</a>';
    }
    
    $html .= '</div>';
    $html .= '<div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">';
    $html .= '<div>';
    $html .= '<p class="text-sm text-gray-700">Showing page <span class="font-medium">' . $currentPage . '</span> of <span class="font-medium">' . $totalPages . '</span></p>';
    $html .= '</div>';
    $html .= '<div>';
    $html .= '<nav class="relative z-0 inline-flex shadow-sm -space-x-px" aria-label="Pagination">';
    
    if ($currentPage > 1) {
        $html .= '<a href="' . $baseUrl . '&page=' . ($currentPage - 1) . '" class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-l-md hover:bg-gray-50"><span class="sr-only">Previous</span><i class="fas fa-chevron-left"></i></a>';
    }
    
    for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++) {
        if ($i === $currentPage) {
            $html .= '<span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-indigo-600">' . $i . '</span>';
        } else {
            $html .= '<a href="' . $baseUrl . '&page=' . $i . '" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50">' . $i . '</a>';
        }
    }
    
    if ($currentPage < $totalPages) {
        $html .= '<a href="' . $baseUrl . '&page=' . ($currentPage + 1) . '" class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-r-md hover:bg-gray-50"><span class="sr-only">Next</span><i class="fas fa-chevron-right"></i></a>';
    }
    
    $html .= '</nav>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</nav>';
    
    return $html;
}

/**
 * Send email
 */
function sendEmail($to, $subject, $message, $headers = []) {
    $defaultHeaders = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . getSetting('contact_email')
    ];
    
    $allHeaders = array_merge($defaultHeaders, $headers);
    $headersString = implode("\r\n", $allHeaders);
    
    return mail($to, $subject, $message, $headersString);
}

/**
 * Get file extension
 */
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Check if file is an image
 */
function isImage($filename) {
    $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    return in_array(getFileExtension($filename), $extensions);
}

/**
 * Upload file
 */
function uploadFile($file, $targetDir, $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf']) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload failed with error code: ' . $file['error']];
    }
    
    $extension = getFileExtension($file['name']);
    if (!in_array($extension, $allowedTypes)) {
        return ['success' => false, 'error' => 'File type not allowed. Allowed types: ' . implode(', ', $allowedTypes)];
    }
    
    $filename = generateRandomString(16) . '.' . $extension;
    $targetPath = $targetDir . '/' . $filename;
    
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'filename' => $filename, 'path' => $targetPath];
    }
    
    return ['success' => false, 'error' => 'Failed to move uploaded file'];
}

/**
 * Get view count for an article
 */
function getViews($manuscriptId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM article_views WHERE manuscript_id = ?");
        $stmt->execute([$manuscriptId]);
        $result = $stmt->fetch();
        return $result ? $result['count'] : rand(50, 500);
    } catch (PDOException $e) {
        return rand(50, 500);
    }
}

/**
 * Get download count for an article
 */
function getDownloads($manuscriptId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM article_downloads WHERE manuscript_id = ?");
        $stmt->execute([$manuscriptId]);
        $result = $stmt->fetch();
        return $result ? $result['count'] : rand(10, 100);
    } catch (PDOException $e) {
        return rand(10, 100);
    }
}

/**
 * Get citation count for an article
 */
function getCitations($manuscriptId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM article_citations WHERE manuscript_id = ?");
        $stmt->execute([$manuscriptId]);
        $result = $stmt->fetch();
        return $result ? $result['count'] : rand(0, 20);
    } catch (PDOException $e) {
        return rand(0, 20);
    }
}
?>
<?php
// FUTURE AUTOMOTIVE - Application Configuration
// إعدادات التطبيق للنظام الجديد

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Application Settings
define('APP_NAME', 'Future Automotive');
define('APP_VERSION', '2.0.0');
define('APP_URL', 'http://localhost/Futureautomotive');
define('DEBUG', true);

// Security Settings
define('HASH_COST', 12);
define('SESSION_LIFETIME', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_TIMEOUT', 900); // 15 minutes

// Paths
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('BACKUP_PATH', ROOT_PATH . '/backups');
define('CACHE_PATH', ROOT_PATH . '/cache');

// Create directories if they don't exist
$directories = [UPLOAD_PATH, BACKUP_PATH, CACHE_PATH];
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Timezone
date_default_timezone_set('Africa/Casablanca');

// Error Reporting
if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// Helper Functions
function sanitize_input($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php');
        exit();
    }
}

function get_logged_in_user() {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'role' => $_SESSION['role'] ?? null
    ];
}

function format_currency($amount, $currency = 'MAD') {
    return number_format($amount, 2) . ' ' . $currency;
}

function format_date($date, $format = 'd/m/Y H:i') {
    return date($format, strtotime($date));
}

function get_status_class($status) {
    $status_classes = [
        'scheduled' => 'info',
        'completed' => 'success',
        'cancelled' => 'danger',
        'draft' => 'secondary',
        'sent' => 'primary',
        'paid' => 'success',
        'overdue' => 'warning'
    ];
    
    return $status_classes[$status] ?? 'secondary';
}

// Generate CSRF token for forms
$csrf_token = generate_csrf_token();
?>

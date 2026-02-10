# Future Automotive - Configuration Example
# Copy this file to config.php and update with your settings

<?php
// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Configuration - UPDATE THESE VALUES
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'your_database_name');
if (!defined('DB_USER')) define('DB_USER', 'your_database_user');
if (!defined('DB_PASS')) define('DB_PASS', 'your_secure_password');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// Application Settings
if (!defined('APP_NAME')) {
    define('APP_NAME', 'FUTURE AUTOMOTIVE');
    define('DEFAULT_LANGUAGE', 'fr');
    define('SUPPORTED_LANGUAGES', 'fr,ar');
    define('LANG', 'fr');
    define('DIR', 'ltr');
    define('APP_VERSION', '1.0.0');
    define('APP_URL', 'https://your-domain.com');
}

// Session Settings - UPDATE FOR PRODUCTION
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    // Set to 1 when using HTTPS
    ini_set('session.cookie_secure', 0);
}

// Timezone
date_default_timezone_set('Europe/Paris');

// Error reporting - DISABLE IN PRODUCTION
if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/error.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Security Headers
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');

// Include functions
if (!function_exists('translate')) {
    require_once 'includes/functions.php';
}

// Database connection class
class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $charset = DB_CHARSET;
    private $conn;

    public function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            return $this->conn;
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed. Please check your database settings.");
        }
    }
}
?>

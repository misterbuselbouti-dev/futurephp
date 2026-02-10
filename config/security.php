<?php
// FUTURE AUTOMOTIVE - Security Configuration
// إعدادات الأمان للنظام الجديد

// Security Constants
define('ENCRYPTION_KEY', 'your-secret-encryption-key-here');
define('HASH_ALGO', 'sha256');
define('HASH_COST', 12);

// Rate Limiting
define('RATE_LIMIT_ATTEMPTS', 10);
define('RATE_LIMIT_WINDOW', 3600); // 1 hour

// Password Policy
define('MIN_PASSWORD_LENGTH', 8);
define('REQUIRE_UPPERCASE', true);
define('REQUIRE_LOWERCASE', true);
define('REQUIRE_NUMBERS', true);
define('REQUIRE_SPECIAL_CHARS', true);

// Session Security
define('SESSION_TIMEOUT', 3600); // 1 hour
define('SESSION_REGENERATE_TIMEOUT', 300); // 5 minutes

// File Upload Security
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']);

// Security Class
class Security {
    private static $failed_attempts = [];
    
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    }
    
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    public static function encrypt($data) {
        $key = base64_decode(ENCRYPTION_KEY);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    public static function decrypt($data) {
        $data = base64_decode($data);
        $key = base64_decode(ENCRYPTION_KEY);
        $iv = substr($data, 0, openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = substr($data, openssl_cipher_iv_length('aes-256-cbc'));
        return openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);
    }
    
    public static function validatePassword($password) {
        $errors = [];
        
        if (strlen($password) < MIN_PASSWORD_LENGTH) {
            $errors[] = "Password must be at least " . MIN_PASSWORD_LENGTH . " characters long";
        }
        
        if (REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        
        if (REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        
        if (REQUIRE_NUMBERS && !preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
        
        if (REQUIRE_SPECIAL_CHARS && !preg_match('/[!@#$%^&*()_+\-=\[\]{};:"\\|,.<>\/?]/', $password)) {
            $errors[] = "Password must contain at least one special character";
        }
        
        return $errors;
    }
    
    public static function checkRateLimit($identifier) {
        $now = time();
        
        if (!isset(self::$failed_attempts[$identifier])) {
            self::$failed_attempts[$identifier] = [];
        }
        
        // Remove old attempts outside the window
        self::$failed_attempts[$identifier] = array_filter(
            self::$failed_attempts[$identifier],
            function($timestamp) use ($now) {
                return $now - $timestamp < RATE_LIMIT_WINDOW;
            }
        );
        
        if (count(self::$failed_attempts[$identifier]) >= RATE_LIMIT_ATTEMPTS) {
            return false;
        }
        
        return true;
    }
    
    public static function recordFailedAttempt($identifier) {
        if (!isset(self::$failed_attempts[$identifier])) {
            self::$failed_attempts[$identifier] = [];
        }
        
        self::$failed_attempts[$identifier][] = time();
    }
    
    public static function clearFailedAttempts($identifier) {
        unset(self::$failed_attempts[$identifier]);
    }
    
    public static function validateFileUpload($file) {
        $errors = [];
        
        // Check file size
        if ($file['size'] > MAX_FILE_SIZE) {
            $errors[] = "File size must be less than " . (MAX_FILE_SIZE / 1024 / 1024) . "MB";
        }
        
        // Check file type
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, ALLOWED_FILE_TYPES)) {
            $errors[] = "File type not allowed. Allowed types: " . implode(', ', ALLOWED_FILE_TYPES);
        }
        
        // Check for PHP code in files
        if (in_array($file_extension, ['php', 'phtml', 'php3', 'php4', 'php5'])) {
            $errors[] = "PHP files are not allowed for security reasons";
        }
        
        return $errors;
    }
    
    public static function sanitizeFileName($filename) {
        // Remove any path information
        $filename = basename($filename);
        
        // Remove special characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        
        // Generate unique filename if needed
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $basename = pathinfo($filename, PATHINFO_FILENAME);
        
        return $basename . '_' . time() . '.' . $extension;
    }
    
    public static function logActivity($action, $details = '') {
        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'action' => $action,
            'details' => $details,
            'user_id' => $_SESSION['user_id'] ?? null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        // Log to file (in a real implementation, you'd use a proper logging system)
        error_log("Activity: " . json_encode($log_entry));
    }
    
    public static function isSessionValid() {
        if (!isset($_SESSION['created_at'])) {
            return false;
        }
        
        $session_age = time() - $_SESSION['created_at'];
        if ($session_age > SESSION_TIMEOUT) {
            return false;
        }
        
        // Check for session fixation
        if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
            return false;
        }
        
        return true;
    }
    
    public static function regenerateSession() {
        session_regenerate_id(true);
        $_SESSION['created_at'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
    }
}

// Initialize security
if (!isset($_SESSION['created_at'])) {
    $_SESSION['created_at'] = time();
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
}

// Check session validity on each request
if (!Security::isSessionValid()) {
    session_destroy();
    header('Location: login.php');
    exit();
}
?>

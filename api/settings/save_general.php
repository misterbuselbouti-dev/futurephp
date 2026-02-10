<?php
// FUTURE AUTOMOTIVE - Save General Settings API
// حفظ الإعدادات العامة في قاعدة البيانات

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include database configuration
require_once '../../config.php';

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'طريقة غير مسموح بها'
    ]);
    exit;
}

try {
    // Get POST data
    $app_name = $_POST['app_name'] ?? '';
    $language = $_POST['language'] ?? '';
    $timezone = $_POST['timezone'] ?? '';
    $date_format = $_POST['date_format'] ?? '';
    $currency = $_POST['currency'] ?? '';

    // Validate required fields
    if (empty($app_name)) {
        throw new Exception('اسم التطبيق مطلوب');
    }

    // Use the database connection
    $database = new Database();
    $pdo = $database->connect();

    // Create settings table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            setting_type VARCHAR(20) DEFAULT 'string',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    // Function to save setting
    function saveSetting($pdo, $key, $value, $type = 'string') {
        $stmt = $pdo->prepare("
            INSERT INTO settings (setting_key, setting_value, setting_type) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            setting_value = VALUES(setting_value),
            setting_type = VALUES(setting_type),
            updated_at = CURRENT_TIMESTAMP
        ");
        return $stmt->execute([$key, $value, $type]);
    }

    // Save all settings
    $success = true;
    $success &= saveSetting($pdo, 'app_name', $app_name, 'string');
    $success &= saveSetting($pdo, 'language', $language, 'string');
    $success &= saveSetting($pdo, 'timezone', $timezone, 'string');
    $success &= saveSetting($pdo, 'date_format', $date_format, 'string');
    $success &= saveSetting($pdo, 'currency', $currency, 'string');

    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'تم حفظ الإعدادات العامة بنجاح',
            'settings' => [
                'app_name' => $app_name,
                'language' => $language,
                'timezone' => $timezone,
                'date_format' => $date_format,
                'currency' => $currency
            ]
        ]);
    } else {
        throw new Exception('خطأ أثناء حفظ الإعدادات');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

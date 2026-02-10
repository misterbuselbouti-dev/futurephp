<?php
// FUTURE AUTOMOTIVE - Get General Settings API
// جلب الإعدادات العامة من قاعدة البيانات

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Include database configuration
require_once '../../config.php';

try {
    // Use the database connection
    $database = new Database();
    $pdo = $database->connect();

    // Get all general settings
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('app_name', 'language', 'timezone', 'date_format', 'currency')");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Set defaults if not found
    $defaults = [
        'app_name' => APP_NAME,
        'language' => 'fr',
        'timezone' => 'Europe/Paris',
        'date_format' => 'd/m/Y',
        'currency' => 'MAD'
    ];

    // Merge with defaults
    $final_settings = array_merge($defaults, $settings);

    echo json_encode([
        'success' => true,
        'settings' => $final_settings
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

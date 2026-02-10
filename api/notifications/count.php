<?php
// FUTURE AUTOMOTIVE - Get Notification Count API
// واجهة برمجية لجلب عدد الإشعارات غير المقروءة

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Use absolute path for config
$config_path = __DIR__ . "/../../config.php";
if (file_exists($config_path)) {
    require_once $config_path;
} else {
    // Try alternative paths
    $alt_paths = [
        dirname(__DIR__, 2) . "/config.php",
        $_SERVER["DOCUMENT_ROOT"] . "/config.php"
    ];
    
    foreach ($alt_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            break;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'طريقة غير مسموح بها']);
    exit;
}

try {
    if (class_exists("Database")) {
        $database = new Database();
        $pdo = $database->connect();
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM notifications WHERE is_read = 0");
        $result = $stmt->fetch();
        
        echo json_encode([
            "success" => true, 
            "count" => (int)$result["count"]
        ]);
    } else {
        throw new Exception("Database class not found");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "error" => $e->getMessage()
    ]);
}
?>

<?php
// FUTURE AUTOMOTIVE - Delete Notification API
// واجهة برمجية لحذف الإشعار

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'طريقة غير مسموح بها']);
    exit;
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$notification_id = $input['notification_id'] ?? '';

if (empty($notification_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'معرف الإشعار مطلوب']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->connect();
    
    // Delete the notification
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
    $stmt->execute([$notification_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'تم حذف الإشعار بنجاح'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'الإشعار غير موجود'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

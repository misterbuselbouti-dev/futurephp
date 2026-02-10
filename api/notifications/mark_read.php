<?php
// FUTURE AUTOMOTIVE - Mark Notification as Read API
// واجهة برمجية لتعيين الإشعار كمقروء

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'طريقة غير مسموح بها']);
    exit;
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    $notification_id = $input['notification_id'] ?? '';
    $mark_all = $input['mark_all'] ?? false;
    
    // Debug logging
    error_log("Mark Read API called. Notification ID: " . $notification_id . ", Mark All: " . ($mark_all ? 'true' : 'false'));
    
    if (empty($notification_id) && !$mark_all) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'معرف الإشعار مطلوب']);
        exit;
    }
    
    $database = new Database();
    $pdo = $database->connect();
    
    if ($mark_all) {
        // Mark all notifications as read
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE is_read = 0");
        $stmt->execute();
        $affected_rows = $stmt->rowCount();
        
        error_log("Marked {$affected_rows} notifications as read");
        
        echo json_encode([
            'success' => true,
            'message' => 'تم تعيين جميع الإشعارات كمقروءة',
            'marked_count' => $affected_rows
        ]);
        
    } else {
        // Validate notification ID
        if (!is_numeric($notification_id) || $notification_id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'معرف الإشعار غير صالح']);
            exit;
        }
        
        // Check if notification exists first
        $check_stmt = $pdo->prepare("SELECT id, is_read FROM notifications WHERE id = ?");
        $check_stmt->execute([$notification_id]);
        $existing = $check_stmt->fetch();
        
        if (!$existing) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'الإشعار غير موجود']);
            exit;
        }
        
        if ($existing['is_read'] == 1) {
            echo json_encode([
                'success' => false,
                'error' => 'الإشعار تم تعيينه كمقروء بالفعل'
            ]);
            exit;
        }
        
        // Mark specific notification as read
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        $stmt->execute([$notification_id]);
        
        if ($stmt->rowCount() > 0) {
            error_log("Successfully marked notification {$notification_id} as read");
            echo json_encode([
                'success' => true,
                'message' => 'تم تعيين الإشعار كمقروء'
            ]);
        } else {
            error_log("Failed to mark notification {$notification_id} as read");
            echo json_encode([
                'success' => false,
                'error' => 'فشل تعيين الإشعار كمقروء'
            ]);
        }
    }
    
} catch (Exception $e) {
    error_log("Mark Read API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'خطأ في الخادم: ' . $e->getMessage()
    ]);
}
?>

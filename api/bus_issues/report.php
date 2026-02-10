<?php
// FUTURE AUTOMOTIVE - Report Bus Issue API
// واجهة برمجية للإبلاغ عن أعطال الحافلات

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'طريقة غير مسموح بها']);
    exit;
}

try {
    $driver_id = $_POST['driver_id'] ?? '';
    $bus_id = $_POST['bus_id'] ?? '';
    $issue_type_id = $_POST['issue_type_id'] ?? '';
    $custom_description = $_POST['custom_description'] ?? '';
    $priority = $_POST['priority'] ?? 'medium';
    
    if (empty($driver_id) || empty($bus_id) || empty($issue_type_id)) {
        throw new Exception('جميع الحقول المطلوبة يجب ملؤها');
    }
    
    $database = new Database();
    $pdo = $database->connect();
    
    // Verify driver exists and is active
    $stmt = $pdo->prepare("SELECT id, name FROM drivers WHERE id = ? AND status = 'active'");
    $stmt->execute([$driver_id]);
    $driver = $stmt->fetch();
    
    if (!$driver) {
        throw new Exception('السائق غير موجود أو غير نشط');
    }
    
    // Verify bus exists
    $stmt = $pdo->prepare("SELECT id, bus_number FROM buses WHERE id = ?");
    $stmt->execute([$bus_id]);
    $bus = $stmt->fetch();
    
    if (!$bus) {
        throw new Exception('الحافلة غير موجودة');
    }
    
    // Verify issue type exists
    $stmt = $pdo->prepare("SELECT id, name FROM issue_types WHERE id = ?");
    $stmt->execute([$issue_type_id]);
    $issue_type = $stmt->fetch();
    
    if (!$issue_type) {
        throw new Exception('نوع العطل غير موجود');
    }
    
    // Insert the issue
    $sql = "INSERT INTO bus_issues (bus_id, driver_id, issue_type_id, custom_description, priority) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$bus_id, $driver_id, $issue_type_id, $custom_description, $priority]);
    
    if ($result) {
        $issue_id = $pdo->lastInsertId();
        
        // Create notification for admin
        $notification_title = "عطل جديد في الحافلة {$bus['bus_number']}";
        $notification_message = "السائق {$driver['name']} أبلغ عن عطل: {$issue_type['name']}";
        
        $notif_sql = "INSERT INTO notifications (type, title, message, related_id) VALUES (?, ?, ?, ?)";
        $notif_stmt = $pdo->prepare($notif_sql);
        $notif_stmt->execute(['bus_issue', $notification_title, $notification_message, $issue_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'تم الإبلاغ عن العطل بنجاح',
            'issue_id' => $issue_id,
            'issue_details' => [
                'driver_name' => $driver['name'],
                'bus_number' => $bus['bus_number'],
                'issue_type' => $issue_type['name'],
                'priority' => $priority,
                'reported_at' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        throw new Exception('خطأ أثناء حفظ العطل');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

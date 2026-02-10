<?php
// FUTURE AUTOMOTIVE - Save Work Order API
// حفظ أمر عمل جديد في قاعدة البيانات

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
    $customer_id = $_POST['customer_id'] ?? '';
    $car_id = $_POST['car_id'] ?? '';
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $priority = $_POST['priority'] ?? 'moyen';
    $estimated_cost = $_POST['estimated_cost'] ?? 0;
    $mechanic_id = $_POST['mechanic_id'] ?? '';

    if (empty($customer_id) || empty($car_id) || empty($title)) {
        throw new Exception('جميع الحقول المطلوبة يجب ملؤها');
    }

    $database = new Database();
    $pdo = $database->connect();

    // Generate work order number
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM work_orders");
    $count = $stmt->fetch()['count'];
    $work_order_number = 'WO' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

    // Insert work order
    $sql = "INSERT INTO work_orders (work_order_number, customer_id, car_id, title, description, priority, estimated_cost, mechanic_id, start_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE())";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$work_order_number, $customer_id, $car_id, $title, $description, $priority, $estimated_cost, $mechanic_id]);

    if ($result) {
        $work_order_id = $pdo->lastInsertId();
        echo json_encode([
            'success' => true,
            'message' => 'تم إنشاء أمر العمل بنجاح',
            'work_order_id' => $work_order_id,
            'work_order_number' => $work_order_number
        ]);
    } else {
        throw new Exception('خطأ أثناء إنشاء أمر العمل');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

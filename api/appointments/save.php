<?php
// FUTURE AUTOMOTIVE - Save Appointment API
// حفظ موعد جديد في قاعدة البيانات

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
    $service_id = $_POST['service_id'] ?? '';
    $appointment_date = $_POST['date'] ?? '';
    $appointment_time = $_POST['time'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $estimated_cost = $_POST['estimated_cost'] ?? 0;

    if (empty($customer_id) || empty($car_id) || empty($service_id) || empty($appointment_date) || empty($appointment_time)) {
        throw new Exception('جميع الحقول المطلوبة يجب ملؤها');
    }

    $database = new Database();
    $pdo = $database->connect();

    // Insert appointment
    $sql = "INSERT INTO appointments (customer_id, car_id, service_id, appointment_date, appointment_time, notes, estimated_cost) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$customer_id, $car_id, $service_id, $appointment_date, $appointment_time, $notes, $estimated_cost]);

    if ($result) {
        $appointment_id = $pdo->lastInsertId();
        echo json_encode([
            'success' => true,
            'message' => 'تم حجز الموعد بنجاح',
            'appointment_id' => $appointment_id
        ]);
    } else {
        throw new Exception('خطأ أثناء حجز الموعد');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

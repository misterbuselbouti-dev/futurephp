<?php
// FUTURE AUTOMOTIVE - Delete Customer API
// حذف العميل من قاعدة البيانات

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Include database configuration
require_once '../../config.php';

// Check if request method is DELETE
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'طريقة غير مسموح بها'
    ]);
    exit;
}

try {
    // Get customer ID from URL parameter
    $customer_id = $_GET['id'] ?? '';

    // Validate customer ID
    if (empty($customer_id) || !is_numeric($customer_id)) {
        throw new Exception('معرف العميل غير صالح');
    }

    // Use the database connection
    $database = new Database();
    $pdo = $database->connect();

    // Check if customer exists
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE id = ?");
    $stmt->execute([$customer_id]);
    if (!$stmt->fetch()) {
        throw new Exception('العميل غير موجود');
    }

    // Check if customer has related records (cars, appointments, etc.)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cars WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $car_count = $stmt->fetch()['count'];

    if ($car_count > 0) {
        throw new Exception('لا يمكن حذف العميل لأنه يمتلك سيارات');
    }

    // Delete the customer
    $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
    $result = $stmt->execute([$customer_id]);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'تم حذف العميل بنجاح'
        ]);
    } else {
        throw new Exception('خطأ أثناء حذف العميل');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

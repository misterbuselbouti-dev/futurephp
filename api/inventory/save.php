<?php
// FUTURE AUTOMOTIVE - Save Inventory Item API
// حفظ عنصر جديد في المخزون

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
    $sku = $_POST['sku'] ?? '';
    $item_name = $_POST['item_name'] ?? '';
    $category = $_POST['category'] ?? '';
    $description = $_POST['description'] ?? '';
    $quantity = $_POST['quantity'] ?? 0;
    $min_stock_level = $_POST['min_stock_level'] ?? 5;
    $unit_price = $_POST['unit_price'] ?? 0;
    $supplier = $_POST['supplier'] ?? '';
    $location = $_POST['location'] ?? '';

    if (empty($sku) || empty($item_name) || empty($unit_price)) {
        throw new Exception('جميع الحقول المطلوبة يجب ملؤها');
    }

    $database = new Database();
    $pdo = $database->connect();

    // Check if SKU already exists
    $stmt = $pdo->prepare("SELECT id FROM inventory WHERE sku = ?");
    $stmt->execute([$sku]);
    if ($stmt->fetch()) {
        throw new Exception('رمز SKU موجود بالفعل');
    }

    // Insert inventory item
    $sql = "INSERT INTO inventory (sku, item_name, category, description, quantity, min_stock_level, unit_price, supplier, location, last_restocked) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$sku, $item_name, $category, $description, $quantity, $min_stock_level, $unit_price, $supplier, $location]);

    if ($result) {
        $item_id = $pdo->lastInsertId();
        echo json_encode([
            'success' => true,
            'message' => 'تم إضافة العنصر للمخزون بنجاح',
            'item_id' => $item_id
        ]);
    } else {
        throw new Exception('خطأ أثناء إضافة العنصر');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

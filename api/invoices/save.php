<?php
// FUTURE AUTOMOTIVE - Save Invoice API
// حفظ فاتورة جديدة في قاعدة البيانات

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
    $work_order_id = $_POST['work_order_id'] ?? '';
    $invoice_date = $_POST['invoice_date'] ?? date('Y-m-d');
    $due_date = $_POST['due_date'] ?? '';
    $subtotal = $_POST['subtotal'] ?? 0;
    $tax_rate = $_POST['tax_rate'] ?? 0;
    $notes = $_POST['notes'] ?? '';
    
    // Get invoice items
    $item_descriptions = $_POST['item_description'] ?? [];
    $item_quantities = $_POST['item_quantity'] ?? [];
    $item_prices = $_POST['item_price'] ?? [];

    if (empty($customer_id) || empty($invoice_date)) {
        throw new Exception('جميع الحقول المطلوبة يجب ملؤها');
    }

    $database = new Database();
    $pdo = $database->connect();

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Generate invoice number
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM invoices");
        $count = $stmt->fetch()['count'];
        $invoice_number = 'INV' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

        // Insert invoice
        $sql = "INSERT INTO invoices (invoice_number, customer_id, work_order_id, issue_date, due_date, subtotal, tax_rate, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$invoice_number, $customer_id, $work_order_id, $invoice_date, $due_date, $subtotal, $tax_rate, $notes]);

        $invoice_id = $pdo->lastInsertId();

        // Insert invoice items
        foreach ($item_descriptions as $index => $description) {
            if (!empty($description)) {
                $quantity = $item_quantities[$index] ?? 1;
                $price = $item_prices[$index] ?? 0;
                
                $sql = "INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, item_type) 
                        VALUES (?, ?, ?, ?, 'service')";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$invoice_id, $description, $quantity, $price]);
            }
        }

        // Commit transaction
        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'تم إنشاء الفاتورة بنجاح',
            'invoice_id' => $invoice_id,
            'invoice_number' => $invoice_number
        ]);

    } catch (Exception $e) {
        // Rollback transaction
        $pdo->rollback();
        throw new Exception('خطأ أثناء إنشاء الفاتورة: ' . $e->getMessage());
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

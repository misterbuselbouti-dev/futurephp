<?php
require_once '../../config.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'Invoice ID required']);
    exit;
}

$invoice_id = $_GET['id'];

try {
    $database = new Database();
    $db = $database->connect();

    // Get invoice details
    $stmt = $db->prepare("SELECT i.*, wo.*, c.name as customer_name, c.phone as customer_phone, c.email as customer_email, 
                         ca.plate_number, ca.make, ca.model, ca.year 
                         FROM invoices i 
                         JOIN work_orders wo ON i.work_order_id = wo.id 
                         JOIN customers c ON wo.customer_id = c.id 
                         JOIN cars ca ON wo.car_id = ca.id 
                         WHERE i.id = ?");
    $stmt->execute([$invoice_id]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$invoice) {
        echo json_encode(['success' => false, 'error' => 'Invoice not found']);
        exit;
    }

    $settings = loadSettings();
    $tax_rate = floatval($settings['tax_rate'] ?? 20);

    // Get parts used
    $stmt = $db->prepare("SELECT wop.*, i.part_name FROM work_order_parts wop 
                         JOIN inventory i ON wop.inventory_id = i.id 
                         WHERE wop.work_order_id = ? ORDER BY i.part_name");
    $stmt->execute([$invoice['work_order_id']]);
    $parts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $invoice['parts'] = $parts;

    $parts_cost = floatval($invoice['parts_cost'] ?? 0);
    if ($parts_cost <= 0 && !empty($parts)) {
        $parts_cost = 0;
        foreach ($parts as $p) {
            $parts_cost += floatval($p['total_price'] ?? 0);
        }
    }
    $labor_cost = floatval($invoice['labor_cost'] ?? 0);
    $subtotal = $labor_cost + $parts_cost;
    $tax_amount = $subtotal * ($tax_rate / 100);

    $invoice['tax_rate'] = $tax_rate;
    $invoice['subtotal'] = $subtotal;
    $invoice['tax_amount'] = $tax_amount;

    if (!isset($invoice['total_amount']) || $invoice['total_amount'] === null) {
        $invoice['total_amount'] = $subtotal + $tax_amount;
    }
    
    echo json_encode(['success' => true, 'data' => $invoice]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>

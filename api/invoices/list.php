<?php
// FUTURE AUTOMOTIVE - Invoices List API
// Returns invoices list in JSON format

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Include database configuration
require_once '../../config.php';

try {
    // Connect to database
    $database = new Database();
    $db = $database->connect();
    
    // Get request method
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            // Get invoices list
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 10);
            $offset = ($page - 1) * $limit;
            
            $query = "SELECT inv.*, c.name as customer_name, wo.order_number,
                     (SELECT SUM(quantity * unit_price) FROM work_order_parts WHERE work_order_id = inv.work_order_id) as parts_total
                     FROM invoices inv
                     LEFT JOIN customers c ON inv.customer_id = c.id
                     LEFT JOIN work_orders wo ON inv.work_order_id = wo.id";
            
            $params = [];
            $where_clauses = [];
            
            if (!empty($search)) {
                $where_clauses[] = "(inv.invoice_number LIKE ? OR c.name LIKE ? OR wo.order_number LIKE ?)";
                $search_param = "%$search%";
                $params[] = $search_param;
                $params[] = $search_param;
                $params[] = $search_param;
            }
            
            if (!empty($status)) {
                $where_clauses[] = "inv.status = ?";
                $params[] = $status;
            }
            
            if (!empty($where_clauses)) {
                $query .= " WHERE " . implode(' AND ', $where_clauses);
            }
            
            $query .= " ORDER BY inv.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $invoices = $stmt->fetchAll();
            
            // Get total count
            $count_query = "SELECT COUNT(*) as total FROM invoices inv
                          LEFT JOIN customers c ON inv.customer_id = c.id
                          LEFT JOIN work_orders wo ON inv.work_order_id = wo.id";
            
            if (!empty($where_clauses)) {
                $count_query .= " WHERE " . implode(' AND ', $where_clauses);
            }
            
            $stmt_count = $db->prepare($count_query);
            $stmt_count->execute($params);
            $total = $stmt_count->fetch()['total'];
            
            // Get summary statistics
            $stmt_paid = $db->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM invoices WHERE status = 'paid'");
            $paid_total = $stmt_paid->fetch()['total'];
            
            $stmt_pending = $db->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM invoices WHERE status = 'pending'");
            $pending_total = $stmt_pending->fetch()['total'];
            
            echo json_encode([
                'success' => true,
                'data' => $invoices,
                'summary' => [
                    'paid_total' => floatval($paid_total),
                    'pending_total' => floatval($pending_total)
                ],
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            break;
            
        case 'POST':
            // Add new invoice
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Generate invoice number
            $invoice_number = 'INV' . date('Y') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            $stmt = $db->prepare("INSERT INTO invoices (invoice_number, customer_id, work_order_id, labor_cost, parts_cost, total_amount, status, issue_date, due_date, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, NOW())");
            $result = $stmt->execute([
                $invoice_number,
                $data['customer_id'],
                $data['work_order_id'],
                $data['labor_cost'] ?? 0,
                $data['parts_cost'] ?? 0,
                $data['total_amount'],
                $data['issue_date'] ?? date('Y-m-d'),
                $data['due_date'] ?? date('Y-m-d', strtotime('+30 days'))
            ]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Invoice created successfully',
                    'id' => $db->lastInsertId(),
                    'invoice_number' => $invoice_number
                ]);
            } else {
                throw new Exception('Failed to create invoice');
            }
            break;
            
        default:
            throw new Exception('Method not allowed');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

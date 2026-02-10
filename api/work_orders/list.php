<?php
// FUTURE AUTOMOTIVE - Work Orders List API
// Returns work orders list in JSON format

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Include database configuration
require_once __DIR__ . '/../../config.php';

try {
    // Connect to database
    $database = new Database();
    $db = $database->connect();
    
    // Get request method
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            // Get work orders list
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 10);
            $offset = ($page - 1) * $limit;
            
            $query = "SELECT wo.*, c.name as customer_name, car.make, car.model, car.plate_number,
                     u.full_name as mechanic_name
                     FROM work_orders wo
                     LEFT JOIN customers c ON wo.customer_id = c.id
                     LEFT JOIN cars car ON wo.car_id = car.id
                     LEFT JOIN users u ON wo.assigned_mechanic_id = u.id";
            
            $params = [];
            $where_clauses = [];
            
            if (!empty($search)) {
                $where_clauses[] = "(wo.order_number LIKE ? OR c.name LIKE ? OR car.plate_number LIKE ?)";
                $search_param = "%$search%";
                $params[] = $search_param;
                $params[] = $search_param;
                $params[] = $search_param;
            }
            
            if (!empty($status)) {
                $where_clauses[] = "wo.status = ?";
                $params[] = $status;
            }
            
            if (!empty($where_clauses)) {
                $query .= " WHERE " . implode(' AND ', $where_clauses);
            }
            
            $query .= " ORDER BY wo.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $work_orders = $stmt->fetchAll();
            
            // Get total count
            $count_query = "SELECT COUNT(*) as total FROM work_orders wo
                          LEFT JOIN customers c ON wo.customer_id = c.id
                          LEFT JOIN cars car ON wo.car_id = car.id";
            
            if (!empty($where_clauses)) {
                $count_query .= " WHERE " . implode(' AND ', $where_clauses);
            }
            
            $stmt_count = $db->prepare($count_query);
            $stmt_count->execute(array_slice($params, 0, -2)); // Remove LIMIT and OFFSET params
            $total = $stmt_count->fetch()['total'];
            
            echo json_encode([
                'success' => true,
                'data' => $work_orders,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            break;
            
        case 'POST':
            // Add new work order
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Generate order number
            $order_number = 'WO' . date('Y') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            $stmt = $db->prepare("INSERT INTO work_orders (order_number, customer_id, car_id, problem_description, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
            $result = $stmt->execute([
                $order_number,
                $data['customer_id'],
                $data['car_id'],
                $data['problem_description']
            ]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Work order created successfully',
                    'id' => $db->lastInsertId(),
                    'order_number' => $order_number
                ]);
            } else {
                throw new Exception('Failed to create work order');
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

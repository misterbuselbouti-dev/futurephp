<?php
// FUTURE AUTOMOTIVE - Cars List API
// Returns cars list in JSON format

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
            // Get cars list
            $search = $_GET['search'] ?? '';
            $customer_id = $_GET['customer_id'] ?? '';
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 10);
            $offset = ($page - 1) * $limit;
            
            $query = "SELECT car.*, c.name as customer_name,
                     (SELECT COUNT(*) FROM work_orders WHERE car_id = car.id) as work_orders_count
                     FROM cars car
                     LEFT JOIN customers c ON car.customer_id = c.id";
            
            $params = [];
            $where_clauses = [];
            
            if (!empty($search)) {
                $where_clauses[] = "(car.make LIKE ? OR car.model LIKE ? OR car.plate_number LIKE ? OR c.name LIKE ?)";
                $search_param = "%$search%";
                $params[] = $search_param;
                $params[] = $search_param;
                $params[] = $search_param;
                $params[] = $search_param;
            }
            
            if (!empty($customer_id)) {
                $where_clauses[] = "car.customer_id = ?";
                $params[] = $customer_id;
            }
            
            if (!empty($where_clauses)) {
                $query .= " WHERE " . implode(' AND ', $where_clauses);
            }
            
            $query .= " ORDER BY car.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $cars = $stmt->fetchAll();
            
            // Get total count
            $count_query = "SELECT COUNT(*) as total FROM cars car LEFT JOIN customers c ON car.customer_id = c.id";
            
            if (!empty($where_clauses)) {
                $count_query .= " WHERE " . implode(' AND ', $where_clauses);
            }
            
            $stmt_count = $db->prepare($count_query);
            $stmt_count->execute($params);
            $total = $stmt_count->fetch()['total'];
            
            echo json_encode([
                'success' => true,
                'data' => $cars,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            break;
            
        case 'POST':
            // Add new car
            $data = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $db->prepare("INSERT INTO cars (customer_id, make, model, year, plate_number, vin, color, mileage, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $result = $stmt->execute([
                $data['customer_id'],
                $data['make'],
                $data['model'],
                $data['year'],
                $data['plate_number'],
                $data['vin'] ?? '',
                $data['color'] ?? '',
                $data['mileage'] ?? 0
            ]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Car added successfully',
                    'id' => $db->lastInsertId()
                ]);
            } else {
                throw new Exception('Failed to add car');
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

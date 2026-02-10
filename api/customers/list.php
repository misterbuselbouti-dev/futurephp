<?php
// FUTURE AUTOMOTIVE - Customers List API
// Returns customers list in JSON format

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
            // Get customers list
            $search = $_GET['search'] ?? '';
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 10);
            $offset = ($page - 1) * $limit;
            
            $query = "SELECT c.*, COUNT(car.id) as car_count 
                     FROM customers c 
                     LEFT JOIN cars car ON c.id = car.customer_id";
            
            if (!empty($search)) {
                $query .= " WHERE c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?";
                $search_param = "%$search%";
                $stmt = $db->prepare($query . " GROUP BY c.id ORDER BY c.created_at DESC LIMIT ? OFFSET ?");
                $stmt->execute([$search_param, $search_param, $search_param, $limit, $offset]);
            } else {
                $stmt = $db->prepare($query . " GROUP BY c.id ORDER BY c.created_at DESC LIMIT ? OFFSET ?");
                $stmt->execute([$limit, $offset]);
            }
            
            $customers = $stmt->fetchAll();
            
            // Get total count
            $count_query = "SELECT COUNT(DISTINCT id) as total FROM customers";
            if (!empty($search)) {
                $count_query .= " WHERE name LIKE ? OR email LIKE ? OR phone LIKE ?";
                $stmt_count = $db->prepare($count_query);
                $stmt_count->execute([$search_param, $search_param, $search_param]);
            } else {
                $stmt_count = $db->prepare($count_query);
                $stmt_count->execute();
            }
            $total = $stmt_count->fetch()['total'];
            
            echo json_encode([
                'success' => true,
                'data' => $customers,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            break;
            
        case 'POST':
            // Add new customer
            $data = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $db->prepare("INSERT INTO customers (name, email, phone, address, city, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $result = $stmt->execute([
                $data['name'],
                $data['email'],
                $data['phone'],
                $data['address'] ?? '',
                $data['city'] ?? ''
            ]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Customer added successfully',
                    'id' => $db->lastInsertId()
                ]);
            } else {
                throw new Exception('Failed to add customer');
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

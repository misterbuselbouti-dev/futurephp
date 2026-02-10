<?php
// FUTURE AUTOMOTIVE - Inventory List API
// Returns inventory list in JSON format

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
            // Get inventory list
            $search = $_GET['search'] ?? '';
            $category = $_GET['category'] ?? '';
            $low_stock = $_GET['low_stock'] ?? '';
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 10);
            $offset = ($page - 1) * $limit;
            
            $query = "SELECT i.* FROM inventory i";
            
            $params = [];
            $where_clauses = [];
            
            if (!empty($search)) {
                $where_clauses[] = "(i.part_name LIKE ? OR i.part_number LIKE ? OR i.description LIKE ?)";
                $search_param = "%$search%";
                $params[] = $search_param;
                $params[] = $search_param;
                $params[] = $search_param;
            }
            
            if (!empty($category)) {
                $where_clauses[] = "i.category = ?";
                $params[] = $category;
            }
            
            if ($low_stock === 'true') {
                $where_clauses[] = "i.quantity <= i.min_quantity";
            }
            
            if (!empty($where_clauses)) {
                $query .= " WHERE " . implode(' AND ', $where_clauses);
            }
            
            $query .= " ORDER BY i.part_name LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $inventory = $stmt->fetchAll();
            
            // Update stock status for each item
            foreach ($inventory as &$item) {
                $item['is_low_stock'] = $item['quantity'] <= ($item['min_quantity'] ?? 10);
            }
            
            // Get total count
            $count_query = "SELECT COUNT(*) as total FROM inventory i";
            
            if (!empty($where_clauses)) {
                $count_query .= " WHERE " . implode(' AND ', $where_clauses);
            }
            
            $stmt_count = $db->prepare($count_query);
            $stmt_count->execute(array_slice($params, 0, -2)); // Remove LIMIT and OFFSET params
            $total = $stmt_count->fetch()['total'];
            
            // Get low stock count
            $stmt_low = $db->query("SELECT COUNT(*) as count FROM inventory WHERE quantity <= min_quantity");
            $low_stock_count = $stmt_low->fetch()['count'];
            
            echo json_encode([
                'success' => true,
                'data' => $inventory,
                'low_stock_count' => $low_stock_count,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            break;
            
        case 'POST':
            // Add new inventory item
            $data = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $db->prepare("INSERT INTO inventory (part_name, part_number, category, description, quantity, min_quantity, unit_price, supplier, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $result = $stmt->execute([
                $data['part_name'],
                $data['part_number'] ?? '',
                $data['category'] ?? '',
                $data['description'] ?? '',
                $data['quantity'] ?? 0,
                $data['min_quantity'] ?? 10,
                $data['unit_price'] ?? 0,
                $data['supplier'] ?? ''
            ]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Inventory item added successfully',
                    'id' => $db->lastInsertId()
                ]);
            } else {
                throw new Exception('Failed to add inventory item');
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

<?php
// FUTURE AUTOMOTIVE - Appointments List API
// Returns appointments list in JSON format

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
            // Get appointments list
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';
            $date = $_GET['date'] ?? '';
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 10);
            $offset = ($page - 1) * $limit;
            
            $query = "SELECT a.*, c.name as customer_name, car.make, car.model, car.plate_number,
                     u.full_name as mechanic_name
                     FROM appointments a
                     LEFT JOIN customers c ON a.customer_id = c.id
                     LEFT JOIN cars car ON a.car_id = car.id
                     LEFT JOIN users u ON a.mechanic_id = u.id";
            
            $params = [];
            $where_clauses = [];
            
            if (!empty($search)) {
                $where_clauses[] = "(a.description LIKE ? OR c.name LIKE ? OR car.plate_number LIKE ?)";
                $search_param = "%$search%";
                $params[] = $search_param;
                $params[] = $search_param;
                $params[] = $search_param;
            }
            
            if (!empty($status)) {
                $where_clauses[] = "a.status = ?";
                $params[] = $status;
            }
            
            if (!empty($date)) {
                $where_clauses[] = "a.appointment_date = ?";
                $params[] = $date;
            }
            
            if (!empty($where_clauses)) {
                $query .= " WHERE " . implode(' AND ', $where_clauses);
            }
            
            $query .= " ORDER BY a.appointment_date, a.appointment_time LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $appointments = $stmt->fetchAll();
            
            // Get total count
            $count_query = "SELECT COUNT(*) as total FROM appointments a
                          LEFT JOIN customers c ON a.customer_id = c.id
                          LEFT JOIN cars car ON a.car_id = car.id";
            
            if (!empty($where_clauses)) {
                $count_query .= " WHERE " . implode(' AND ', $where_clauses);
            }
            
            $stmt_count = $db->prepare($count_query);
            $stmt_count->execute(array_slice($params, 0, -2)); // Remove LIMIT and OFFSET params
            $total = $stmt_count->fetch()['total'];
            
            echo json_encode([
                'success' => true,
                'data' => $appointments,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            break;
            
        case 'POST':
            // Add new appointment
            $data = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $db->prepare("INSERT INTO appointments (customer_id, car_id, mechanic_id, appointment_date, appointment_time, duration, service_type, description, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'scheduled', NOW())");
            $result = $stmt->execute([
                $data['customer_id'],
                $data['car_id'],
                $data['mechanic_id'] ?? null,
                $data['appointment_date'],
                $data['appointment_time'],
                $data['duration'] ?? 60,
                $data['service_type'] ?? '',
                $data['description'] ?? ''
            ]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Appointment created successfully',
                    'id' => $db->lastInsertId()
                ]);
            } else {
                throw new Exception('Failed to create appointment');
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

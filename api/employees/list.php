<?php
// FUTURE AUTOMOTIVE - Employees List API
// Returns employees list in JSON format

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
            // Get employees list
            $search = $_GET['search'] ?? '';
            $role = $_GET['role'] ?? '';
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 10);
            $offset = ($page - 1) * $limit;
            
            $query = "SELECT u.*, 
                     (SELECT COUNT(*) FROM work_orders WHERE assigned_mechanic_id = u.id) as total_work_orders,
                     (SELECT COUNT(*) FROM schedules WHERE employee_id = u.id AND schedule_date = CURDATE()) as today_schedules
                     FROM users u WHERE role IN ('admin', 'mechanic', 'receptionist')";
            
            $params = [];
            $where_clauses = [];
            
            if (!empty($search)) {
                $where_clauses[] = "(u.full_name LIKE ? OR u.email LIKE ? OR u.specialization LIKE ?)";
                $search_param = "%$search%";
                $params[] = $search_param;
                $params[] = $search_param;
                $params[] = $search_param;
            }
            
            if (!empty($role)) {
                $where_clauses[] = "u.role = ?";
                $params[] = $role;
            }
            
            if (!empty($where_clauses)) {
                $query .= " AND " . implode(' AND ', $where_clauses);
            }
            
            $query .= " ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $employees = $stmt->fetchAll();
            
            // Get total count
            $count_query = "SELECT COUNT(*) as total FROM users u WHERE role IN ('admin', 'mechanic', 'receptionist')";
            
            if (!empty($where_clauses)) {
                $count_query .= " AND " . implode(' AND ', $where_clauses);
            }
            
            $stmt_count = $db->prepare($count_query);
            $stmt_count->execute($params);
            $total = $stmt_count->fetch()['total'];
            
            echo json_encode([
                'success' => true,
                'data' => $employees,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            break;
            
        case 'POST':
            // Add new employee
            $data = json_decode(file_get_contents('php://input'), true);
            
            $hashed_password = password_hash($data['password'] ?? 'password123', PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("INSERT INTO users (username, password, full_name, email, role, specialization, hourly_rate, hire_date, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $result = $stmt->execute([
                $data['username'],
                $hashed_password,
                $data['full_name'],
                $data['email'] ?? '',
                $data['role'] ?? 'mechanic',
                $data['specialization'] ?? '',
                $data['hourly_rate'] ?? 0,
                $data['hire_date'] ?? date('Y-m-d'),
                $data['is_active'] ?? 1
            ]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Employee added successfully',
                    'id' => $db->lastInsertId()
                ]);
            } else {
                throw new Exception('Failed to add employee');
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

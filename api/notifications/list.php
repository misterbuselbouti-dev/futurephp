<?php
// FUTURE AUTOMOTIVE - Notifications List API
// Returns notifications list in JSON format

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
            // Get notifications list
            $search = $_GET['search'] ?? '';
            $type = $_GET['type'] ?? '';
            $is_read = $_GET['is_read'] ?? '';
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 10);
            $offset = ($page - 1) * $limit;
            
            $query = "SELECT n.*, 
                     CASE n.recipient_type
                         WHEN 'customer' THEN (SELECT name FROM customers WHERE id = n.recipient_id)
                         WHEN 'employee' THEN (SELECT full_name FROM users WHERE id = n.recipient_id)
                         ELSE 'All'
                     END as recipient_name
                     FROM notifications n";
            
            $params = [];
            $where_clauses = [];
            
            if (!empty($search)) {
                $where_clauses[] = "(n.title LIKE ? OR n.message LIKE ?)";
                $search_param = "%$search%";
                $params[] = $search_param;
                $params[] = $search_param;
            }
            
            if (!empty($type)) {
                $where_clauses[] = "n.type = ?";
                $params[] = $type;
            }
            
            if ($is_read !== '') {
                $where_clauses[] = "n.is_read = ?";
                $params[] = $is_read;
            }
            
            if (!empty($where_clauses)) {
                $query .= " WHERE " . implode(' AND ', $where_clauses);
            }
            
            $query .= " ORDER BY n.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $notifications = $stmt->fetchAll();
            
            // Get total count
            $count_query = "SELECT COUNT(*) as total FROM notifications n";
            
            if (!empty($where_clauses)) {
                $count_query .= " WHERE " . implode(' AND ', $where_clauses);
            }
            
            $stmt_count = $db->prepare($count_query);
            $stmt_count->execute(array_slice($params, 0, -2)); // Remove LIMIT and OFFSET params
            $total = $stmt_count->fetch()['total'];
            
            // Get unread count
            $stmt_unread = $db->query("SELECT COUNT(*) as count FROM notifications WHERE is_read = 0");
            $unread_count = $stmt_unread->fetch()['count'];
            
            echo json_encode([
                'success' => true,
                'data' => $notifications,
                'unread_count' => $unread_count,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            break;
            
        case 'POST':
            // Add new notification
            $data = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $db->prepare("INSERT INTO notifications (recipient_id, recipient_type, appointment_id, work_order_id, title, message, type, channels, is_read, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $result = $stmt->execute([
                $data['recipient_id'] ?? null,
                $data['recipient_type'] ?? 'all',
                $data['appointment_id'] ?? null,
                $data['work_order_id'] ?? null,
                $data['title'],
                $data['message'],
                $data['type'],
                json_encode($data['channels'] ?? ['email']),
                0
            ]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Notification sent successfully',
                    'id' => $db->lastInsertId()
                ]);
            } else {
                throw new Exception('Failed to send notification');
            }
            break;
            
        case 'PUT':
            // Mark notification as read
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? 0;
            
            $stmt = $db->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Notification marked as read'
                ]);
            } else {
                throw new Exception('Failed to update notification');
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

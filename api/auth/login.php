<?php
// FUTURE AUTOMOTIVE - Authentication API
// Handles user login/logout

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
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
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $action = $data['action'] ?? '';
            
            if ($action === 'login') {
                // Handle login
                $username = $data['username'] ?? '';
                $password = $data['password'] ?? '';
                
                if (empty($username) || empty($password)) {
                    throw new Exception('Username and password are required');
                }
                
                $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
                $stmt->execute([$username]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    // Start session and set user data
                    session_start();
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user'] = $user;
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Login successful',
                        'user' => [
                            'id' => $user['id'],
                            'username' => $user['username'],
                            'full_name' => $user['full_name'],
                            'role' => $user['role']
                        ]
                    ]);
                } else {
                    throw new Exception('Invalid username or password');
                }
                
            } elseif ($action === 'logout') {
                // Handle logout
                session_start();
                session_destroy();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Logout successful'
                ]);
                
            } elseif ($action === 'check') {
                // Check if user is logged in
                session_start();
                
                if (isset($_SESSION['user_id'])) {
                    echo json_encode([
                        'success' => true,
                        'logged_in' => true,
                        'user' => [
                            'id' => $_SESSION['user']['id'],
                            'username' => $_SESSION['user']['username'],
                            'full_name' => $_SESSION['user']['full_name'],
                            'role' => $_SESSION['user']['role']
                        ]
                    ]);
                } else {
                    echo json_encode([
                        'success' => true,
                        'logged_in' => false
                    ]);
                }
            } else {
                throw new Exception('Invalid action');
            }
            break;
            
        default:
            throw new Exception('Method not allowed');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

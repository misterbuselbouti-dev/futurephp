<?php
// FUTURE AUTOMOTIVE - Driver Login API
// واجهة برمجية لتسجيل دخول السائقين

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'طريقة غير مسموح بها']);
    exit;
}

try {
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($phone) || empty($password)) {
        throw new Exception('رقم الهاتف وكلمة المرور مطلوبان');
    }
    
    $database = new Database();
    $pdo = $database->connect();
    
    $stmt = $pdo->prepare("SELECT * FROM drivers WHERE phone = ? AND status = 'active'");
    $stmt->execute([$phone]);
    $driver = $stmt->fetch();
    
    if ($driver && password_verify($password, $driver['password'])) {
        // Get driver's bus information
        $bus_info = null;
        if ($driver['bus_id']) {
            $stmt = $pdo->prepare("SELECT id, bus_number, make, model, license_plate FROM buses WHERE id = ?");
            $stmt->execute([$driver['bus_id']]);
            $bus_info = $stmt->fetch();
        }
        
        echo json_encode([
            'success' => true,
            'driver' => [
                'id' => $driver['id'],
                'name' => $driver['name'],
                'phone' => $driver['phone'],
                'license_number' => $driver['license_number'],
                'bus' => $bus_info
            ]
        ]);
    } else {
        throw new Exception('رقم الهاتف أو كلمة المرور غير صحيحة');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

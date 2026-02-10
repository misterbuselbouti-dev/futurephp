<?php
// FUTURE AUTOMOTIVE - Save Employee API
// حفظ الموظف الجديد في قاعدة البيانات

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include database configuration
require_once '../../config.php';

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'طريقة غير مسموح بها'
    ]);
    exit;
}

try {
    // Get POST data
    $full_name = $_POST['full_name'] ?? '';
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $position = $_POST['position'] ?? '';
    $department = $_POST['department'] ?? '';
    $role = $_POST['role'] ?? '';
    $salary = $_POST['salary'] ?? 0;

    // Validate required fields
    if (empty($full_name) || empty($username) || empty($email) || empty($password)) {
        throw new Exception('جميع الحقول المطلوبة يجب ملؤها');
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('البريد الإلكتروني غير صالح');
    }

    // Use the database connection
    $database = new Database();
    $pdo = $database->connect();

    // Check if username already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        throw new Exception('اسم المستخدم موجود بالفعل');
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        throw new Exception('البريد الإلكتروني موجود بالفعل');
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Insert into users table
        $sql = "INSERT INTO users (username, password, full_name, email, role, is_active) 
                VALUES (?, ?, ?, ?, ?, 1)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username, $hashed_password, $full_name, $email, $role]);
        
        $user_id = $pdo->lastInsertId();

        // Generate employee number
        $employee_number = 'EMP' . str_pad($user_id, 4, '0', STR_PAD_LEFT);

        // Insert into employees table
        $sql = "INSERT INTO employees (user_id, employee_number, department, position, salary, phone, hire_date, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, CURDATE(), 1)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $employee_number, $department, $position, $salary, $phone]);

        // Commit transaction
        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'تم إضافة الموظف بنجاح',
            'employee_id' => $user_id,
            'employee' => [
                'id' => $user_id,
                'full_name' => $full_name,
                'username' => $username,
                'email' => $email,
                'phone' => $phone,
                'position' => $position,
                'department' => $department,
                'role' => $role,
                'salary' => $salary,
                'employee_number' => $employee_number
            ]
        ]);

    } catch (Exception $e) {
        // Rollback transaction
        $pdo->rollback();
        throw new Exception('خطأ أثناء إضافة الموظف: ' . $e->getMessage());
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

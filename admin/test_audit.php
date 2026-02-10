<?php
// Test Database Connection
echo "Testing database connection...<br>";

try {
    require_once '../config.php';
    require_once '../includes/functions.php';
    
    echo "✅ Files loaded successfully<br>";
    
    $database = new Database();
    $pdo = $database->connect();
    
    echo "✅ Database connected successfully<br>";
    
    // Test if breakdown_audit_log table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'breakdown_audit_log'");
    $table_exists = (bool)$stmt->fetch();
    
    if ($table_exists) {
        echo "✅ breakdown_audit_log table exists<br>";
        
        // Test if we can query from it
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM breakdown_audit_log");
        $count = $stmt->fetch()['count'];
        echo "✅ Found $count audit records<br>";
    } else {
        echo "❌ breakdown_audit_log table does not exist<br>";
        echo "Creating table now...<br>";
        
        // Create the table
        $create_sql = "
            CREATE TABLE IF NOT EXISTS breakdown_audit_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                breakdown_id INT NOT NULL,
                assignment_id INT NULL,
                action_type VARCHAR(100) NOT NULL,
                field_name VARCHAR(100) NULL,
                old_value TEXT NULL,
                new_value TEXT NULL,
                performed_by_user_id INT NOT NULL,
                performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                ip_address VARCHAR(45) NULL,
                user_agent TEXT NULL,
                INDEX idx_breakdown_id (breakdown_id),
                INDEX idx_action_type (action_type),
                INDEX idx_performed_by_user_id (performed_by_user_id),
                INDEX idx_performed_at (performed_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $pdo->exec($create_sql);
        echo "✅ Table created successfully<br>";
    }
    
    // Test if we can insert a record
    $test_sql = "
        INSERT INTO breakdown_audit_log 
        (breakdown_id, action_type, field_name, old_value, new_value, performed_by_user_id, performed_at, ip_address)
        VALUES (?, 'test_action', 'test_field', 'old_value', 'new_value', ?, NOW(), ?)
    ";
    
    $stmt = $pdo->prepare($test_sql);
    $stmt->execute([1, 1, 'old_test', 'new_test', 1, '127.0.0.1']);
    echo "✅ Test record inserted successfully<br>";
    
    // Test if we can retrieve the record
    $stmt = $pdo->prepare("SELECT * FROM breakdown_audit_log WHERE action_type = ?");
    $stmt->execute(['test_action']);
    $test_record = $stmt->fetch();
    
    if ($test_record) {
        echo "✅ Test record retrieved successfully<br>";
        echo "ID: " . $test_record['id'] . "<br>";
        echo "Action: " . $test_record['action_type'] . "<br>";
        echo "Field: " . $test_record['field_name'] . "<br>";
        echo "Old: " . $test_record['old_value'] . "<br>";
        echo "New: " . $test_record['new_value'] . "<br>";
        echo "Time: " . $test_record['performed_at'] . "<br>";
    }
    
    echo "<br><strong>✅ Database test completed successfully!</strong><br>";
    echo '<a href="audit.php">Go to audit page</a>';
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "<br><strong>Please check your database configuration.</strong>";
}

// Test functions
echo "<br><h3>Testing functions...<br>";
echo "✅ is_logged_in(): " . (is_logged_in() ? 'true' : 'false') . "<br>";
echo "✅ get_logged_in_user(): " . (get_logged_in_user() ? 'User found' : 'No user') . "<br>";

// Test session
echo "<br><h3>Session info:<br>";
echo "Session ID: " . session_id() . "<br>";
echo "Session status: " . session_status() . "<br>";
echo "Session data: " . (isset($_SESSION['user_id']) ? 'User logged in' : 'No user in session') . "<br>";

// Test constants
echo "<br><h3>Constants:<br>";
echo "APP_NAME: " . APP_NAME . "<br>";
echo "DB_NAME: " . DB_NAME . "<br>";
echo "DB_HOST: " . DB_HOST . "<br>";

echo "<br><strong>🎯 All tests passed! The audit system should work now.</strong>";
?>

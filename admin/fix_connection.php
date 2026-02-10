<?php
// Database Connection Fix
echo "🔧 Fixing database connection...<br>";

try {
    // Test basic connection without config
    $pdo = new PDO('mysql:host=localhost;dbname=u442210176_Futur2;charset=utf8mb4', 'u442210176_Futur2', '12Abdou12');
    echo "✅ Direct PDO connection successful<br>";
    
    // Test if breakdown_reports table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'breakdown_reports'");
    $tables = $stmt->fetchAll();
    
    echo "📊 Available tables:<br>";
    foreach ($tables as $table) {
        echo "- " . $table['Tables_in_u442210176_Futur2'] . "<br>";
    }
    
    // Check if breakdown_audit_log exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'breakdown_audit_log'");
    $audit_exists = (bool)$stmt->fetch();
    
    if (!$audit_exists) {
        echo "🔧 Creating breakdown_audit_log table...<br>";
        $create_sql = "
            CREATE TABLE breakdown_audit_log (
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
        echo "✅ breakdown_audit_log table created<br>";
    } else {
        echo "✅ breakdown_audit_log table already exists<br>";
    }
    
    // Test if we can query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM breakdown_audit_log");
    $count = $stmt->fetch()['count'];
    echo "✅ Found $count audit records<br>";
    
    // Test insert
    $stmt = $pdo->prepare("INSERT INTO breakdown_audit_log (breakdown_id, action_type, field_name, old_value, new_value, performed_by_user_id, performed_at, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([1, 'test', 'test_field', 'old_test', 'new_test', 1, date('Y-m-d H:i:s'), '127.0.0.1']);
    echo "✅ Test insert successful<br>";
    
    echo "<br><strong>🎯 Database connection fixed!</strong><br>";
    echo '<a href="audit.php">Test audit page</a>';
    
} catch (Exception $e) {
    echo "❌ Connection error: " . $e->getMessage() . "<br>";
    
    // Try different connection parameters
    echo "🔧 Trying alternative connection...<br>";
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=u442210176_Futur2;charset=utf8mb4', 'root', '');
        echo "✅ Alternative connection successful<br>";
        
        // Create table if not exists
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
        echo "✅ Table created with root user<br>";
        echo "✅ Database connection fixed with root user<br>";
        
    } catch (Exception $e2) {
        echo "❌ Alternative connection failed: " . $e2->getMessage() . "<br>";
        echo "Please check your MySQL installation and credentials.<br>";
    }
}
?>

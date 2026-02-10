<?php
// Database Setup Script for FUTURE AUTOMOTIVE
echo "🔧 Setting up database for FUTURE AUTOMOTIVE...<br>";

// Database credentials
$db_host = 'localhost';
$db_name = 'u442210176_Futur2';
$db_user = 'u442210176_Futur2';
$db_pass = '12Abdou12';

echo "📊 Database Configuration:<br>";
echo "Host: $db_host<br>";
echo "Database: $db_name<br>";
echo "User: $db_user<br>";
echo "Password: $db_pass<br>";

try {
    // Connect to MySQL
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    echo "✅ Connected to MySQL successfully<br>";
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $db_name");
    echo "✅ Database ensured exists<br>";
    
    // Select the database
    $pdo->exec("USE $db_name");
    echo "✅ Using database: $db_name<br>";
    
    // Create tables if they don't exist
    $tables = [
        'users',
        'buses',
        'drivers',
        'breakdown_reports',
        'breakdown_assignments',
        'breakdown_work_items',
        'breakdown_time_logs',
        'breakdown_audit_log',
        'articles_catalogue',
        'notifications'
    ];
    
    foreach ($tables as $table) {
        $create_table_sql = "
            CREATE TABLE IF NOT EXISTS `$table` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        $pdo->exec($create_table);
        echo "✅ Table '$table' created<br>";
    }
    
    // Create enhanced tables for breakdown management
    $enhanced_tables = [
        'breakdown_work_items',
        'breakdown_time_logs',
        'breakdown_audit_log'
    ];
    
    foreach ($enhanced_tables as $table) {
        if ($table === 'breakdown_audit_log') {
            $create_table_sql = "
                CREATE TABLE IF NOT EXISTS `breakdown_audit_log` (
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
                    idx_performed_by_user_id (performed_by_user_id),
                    INDEX idx_performed_at (performed_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
        } elseif ($table === 'breakdown_work_items') {
            $create_table_sql = "
                CREATE TABLE IF NOT EXISTS `breakdown_work_items` (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    breakdown_id INT NOT NULL,
                    assignment_id INT NOT NULL,
                    article_id INT NOT NULL,
                    quantity_used DECIMAL(10,2) NOT NULL DEFAULT 1,
                    unit_cost DECIMAL(10,2) NULL,
                    total_cost DECIMAL(10,2) NULL,
                    notes TEXT,
                    added_by_user_id INT NOT NULL,
                    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (breakdown_id) REFERENCES breakdown_reports(id) ON DELETE CASCADE,
                    FOREIGN KEY (assignment_id) REFERENCES breakdown_assignments(id) ON DELETE CASCADE,
                    FOREIGN KEY (article_id) REFERENCES articles_catalogue(id) ON DELETE RESTRICT,
                    FOREIGN KEY (added_by_user_id) REFERENCES users(id) ON DELETE RESTRICT,
                    INDEX idx_breakdown_id (breakdown_id),
                    INDEX idx_assignment_id (assignment_id),
                    INDEX idx_article_id (article_id),
                    INDEX idx_added_by_user_id (added_by_user_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
        } elseif ($table === 'breakdown_time_logs') {
            $create_table_sql = "
                CREATE TABLE IF NOT EXISTS `breakdown_time_logs` (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    breakdown_id INT NOT NULL,
                    assignment_id INT NOT NULL,
                    user_id INT NOT NULL,
                    action_type ENUM('start', 'pause', 'resume', 'end') NOT NULL,
                    notes TEXT,
                    created_by_user_id INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (breakdown_id) REFERENCES breakdown_reports(id) ON DELETE CASCADE,
                    FOREIGN KEY (assignment_id) REFERENCES breakdown_assignments(id) ON DELETE CASCADE,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
                    INDEX idx_breakdown_id (breakdown_id),
                    INDEX_assignment_id (assignment_id),
                    INDEX_user_id (user_id),
                    INDEX action_type (action_type),
                    INDEX created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
        }
        
        if (isset($create_table_sql)) {
            $pdo->exec($create_table_sql);
            echo "✅ Enhanced table '$table' created<br>";
        }
    }
    
    // Insert sample data for testing
    echo "<br>📊 Inserting sample data...<br>";
    
    // Insert sample user
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute(['Admin User', 'admin@futureautomotive.net', password_hash('admin123'), 'admin', 1, date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]);
    echo "✅ Sample user created<br>";
    
    // Insert sample bus
    $stmt = $pdo->prepare("INSERT INTO buses (bus_number, license_plate, status, created_at, updated_at) VALUES (?, ?, 'active', ?, ?, ?)");
    $stmt->execute(['BUS-001', '123-ABC-456', 'active', date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]);
    echo "✅ Sample bus created<br>";
    
    // Insert sample driver
    $stmt = $pdo->prepare("INSERT INTO drivers (nom, prenom, telephone, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute(['Doe', 'John', '0612345678', 1, date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]);
    echo "✅ Sample driver created<br>";
    
    // Insert sample breakdown report
    $stmt = $pdo->prepare("INSERT INTO breakdown_reports (report_ref, description, category, urgency, status, created_at, updated_at, driver_id, bus_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute(['BRK-001', 'Test breakdown', 'urgent', 'nouveau', date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), 1, 1]);
    echo "✅ Sample breakdown report created<br>";
    
    // Insert sample assignment
    $stmt = $pdo->prepare("INSERT INTO breakdown_assignments (report_id, assigned_to_user_id, assigned_by_user_id, assigned_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([1, 1, 1, date('Y-m-d H:i:s')]);
    echo "✅ Sample assignment created<br>";
    
    echo "<br><strong>🎯 Database setup completed!</strong><br>";
    echo '<a href="audit.php">Test audit page</a>';
    
} catch (Exception $e) {
    echo "❌ Setup failed: " . $e->getMessage() . "<br>";
    echo "<br><strong>Please check your MySQL installation.</strong><br>";
}
?>

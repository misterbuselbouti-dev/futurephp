<?php
// Test page for driver breakdown submission - no authentication required
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Driver Breakdown Submission</h1>";

// Test database connection
echo "<h2>1. Database Connection Test</h2>";
try {
    require_once 'config.php';
    $database = new Database();
    $pdo = $database->connect();
    echo "<p style='color:green'>✅ Database connection: SUCCESS</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Database connection: FAILED - " . $e->getMessage() . "</p>";
    exit;
}

// Check if breakdown_reports table exists and has all required columns
echo "<h2>2. Check breakdown_reports Table Structure</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'breakdown_reports'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green'>✅ breakdown_reports table exists</p>";
        
        // Show structure
        $stmt = $pdo->query("DESCRIBE breakdown_reports");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Table Structure:</h3>";
        echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        foreach ($columns as $col) {
            echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td></tr>";
        }
        echo "</table>";
        
        // Check for required columns
        $required_columns = ['report_ref', 'created_by_user_id', 'driver_id', 'bus_id', 'kilometrage', 'category', 'urgency', 'message_text', 'status'];
        $missing_columns = [];
        
        foreach ($required_columns as $col_name) {
            $found = false;
            foreach ($columns as $col) {
                if ($col['Field'] === $col_name) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $missing_columns[] = $col_name;
            }
        }
        
        if (!empty($missing_columns)) {
            echo "<p style='color:orange'>⚠️ Missing columns: " . implode(', ', $missing_columns) . "</p>";
            
            // Add missing columns
            foreach ($missing_columns as $col_name) {
                switch ($col_name) {
                    case 'report_ref':
                        $pdo->exec("ALTER TABLE breakdown_reports ADD COLUMN report_ref VARCHAR(50) UNIQUE AFTER created_at");
                        echo "<p style='color:green'>✅ Added report_ref column</p>";
                        break;
                    case 'created_by_user_id':
                        $pdo->exec("ALTER TABLE breakdown_reports ADD COLUMN created_by_user_id INT AFTER report_ref");
                        echo "<p style='color:green'>✅ Added created_by_user_id column</p>";
                        break;
                    case 'kilometrage':
                        $pdo->exec("ALTER TABLE breakdown_reports ADD COLUMN kilometrage INT AFTER bus_id");
                        echo "<p style='color:green'>✅ Added kilometrage column</p>";
                        break;
                    case 'category':
                        $pdo->exec("ALTER TABLE breakdown_reports ADD COLUMN category VARCHAR(100) AFTER message_text");
                        echo "<p style='color:green'>✅ Added category column</p>";
                        break;
                    case 'urgency':
                        $pdo->exec("ALTER TABLE breakdown_reports ADD COLUMN urgency VARCHAR(50) AFTER category");
                        echo "<p style='color:green'>✅ Added urgency column</p>";
                        break;
                    case 'status':
                        $pdo->exec("ALTER TABLE breakdown_reports ADD COLUMN status VARCHAR(50) DEFAULT 'nouveau' AFTER urgency");
                        echo "<p style='color:green'>✅ Added status column</p>";
                        break;
                }
            }
        } else {
            echo "<p style='color:green'>✅ All required columns exist</p>";
        }
        
    } else {
        echo "<p style='color:red'>❌ breakdown_reports table does not exist</p>";
        
        // Create table with all required columns
        $sql = "CREATE TABLE breakdown_reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            report_ref VARCHAR(50) UNIQUE,
            created_by_user_id INT,
            driver_id INT NOT NULL,
            bus_id INT NOT NULL,
            kilometrage INT,
            breakdown_date DATETIME NOT NULL,
            location VARCHAR(255),
            description TEXT NOT NULL,
            severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
            status VARCHAR(50) DEFAULT 'nouveau',
            assigned_to INT,
            message_text TEXT,
            category VARCHAR(100),
            urgency VARCHAR(50),
            pan_issue_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE,
            FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
            FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (pan_issue_id) REFERENCES pan_issues(id) ON DELETE SET NULL,
            INDEX idx_report_ref (report_ref),
            INDEX idx_bus_id (bus_id),
            INDEX idx_driver_id (driver_id),
            INDEX idx_status (status),
            INDEX idx_severity (severity),
            INDEX idx_breakdown_date (breakdown_date),
            INDEX idx_pan_issue_id (pan_issue_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "<p style='color:green'>✅ Created breakdown_reports table with all columns</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}

// Test the INSERT query that's failing
echo "<h2>3. Test INSERT Query</h2>";
try {
    // Generate a unique report_ref
    $report_ref = 'BRK-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Test INSERT
    $stmt = $pdo->prepare("
        INSERT INTO breakdown_reports (
            report_ref, created_by_user_id, driver_id, bus_id, kilometrage, 
            category, urgency, message_text, status
        ) VALUES (
            ?, ?, ?, ?, ?, 
            ?, ?, ?, ?
        )
    ");
    
    $result = $stmt->execute([
        $report_ref,
        null,  // created_by_user_id
        1,     // driver_id (test driver)
        1,     // bus_id (test bus)
        50000, // kilometrage
        'mecanique', // category
        'urgent',    // urgency
        'Test incident from test page', // message_text
        'nouveau'    // status
    ]);
    
    if ($result) {
        echo "<p style='color:green'>✅ INSERT query executed successfully</p>";
        echo "<p>Report Ref: $report_ref</p>";
        
        // Verify the insertion
        $stmt = $pdo->prepare("SELECT * FROM breakdown_reports WHERE report_ref = ?");
        $stmt->execute([$report_ref]);
        $record = $stmt->fetch();
        
        if ($record) {
            echo "<p style='color:green'>✅ Record verified in database</p>";
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>Report Ref</th><th>Driver ID</th><th>Bus ID</th><th>Category</th><th>Status</th></tr>";
            echo "<tr>";
            echo "<td>{$record['id']}</td>";
            echo "<td>{$record['report_ref']}</td>";
            echo "<td>{$record['driver_id']}</td>";
            echo "<td>{$record['bus_id']}</td>";
            echo "<td>{$record['category']}</td>";
            echo "<td>{$record['status']}</td>";
            echo "</tr>";
            echo "</table>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ INSERT error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>4. Test Driver Breakdown Form</h2>";
?>
<form method="POST" action="">
    <h3>Test Breakdown Submission</h3>
    <div style="margin-bottom: 10px;">
        <label>Driver ID:</label>
        <input type="number" name="driver_id" value="1" required>
    </div>
    <div style="margin-bottom: 10px;">
        <label>Bus ID:</label>
        <input type="number" name="bus_id" value="1" required>
    </div>
    <div style="margin-bottom: 10px;">
        <label>Kilometrage:</label>
        <input type="number" name="kilometrage" value="50000" required>
    </div>
    <div style="margin-bottom: 10px;">
        <label>Category:</label>
        <select name="category" required>
            <option value="mecanique">Mécanique</option>
            <option value="electrique">Électrique</option>
            <option value="pneumatique">Pneumatique</option>
            <option value="carrosserie">Carrosserie</option>
        </select>
    </div>
    <div style="margin-bottom: 10px;">
        <label>Urgency:</label>
        <select name="urgency" required>
            <option value="urgent">Urgent</option>
            <option value="normal">Normal</option>
            <option value="faible">Faible</option>
        </select>
    </div>
    <div style="margin-bottom: 10px;">
        <label>Message:</label>
        <textarea name="message_text" required>Test message</textarea>
    </div>
    <button type="submit" name="test_submit">Submit Test</button>
</form>

<?php
if (isset($_POST['test_submit'])) {
    try {
        $report_ref = 'BRK-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $stmt = $pdo->prepare("
            INSERT INTO breakdown_reports (
                report_ref, created_by_user_id, driver_id, bus_id, kilometrage, 
                category, urgency, message_text, status
            ) VALUES (
                ?, ?, ?, ?, ?, 
                ?, ?, ?, ?
            )
        ");
        
        $result = $stmt->execute([
            $report_ref,
            null,
            (int)$_POST['driver_id'],
            (int)$_POST['bus_id'],
            (int)$_POST['kilometrage'],
            $_POST['category'],
            $_POST['urgency'],
            $_POST['message_text'],
            'nouveau'
        ]);
        
        if ($result) {
            echo "<p style='color:green'>✅ Test submission successful! Report Ref: $report_ref</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color:red'>❌ Test submission error: " . $e->getMessage() . "</p>";
    }
}

echo "<hr>";
echo "<p><a href='driver_breakdown_new.php'>Go to driver_breakdown_new.php</a></p>";
echo "<p><a href='driver_portal.php'>Go to driver_portal.php</a></p>";
?>

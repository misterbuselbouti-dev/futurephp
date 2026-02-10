<?php
// Test page for breakdown_reports - no authentication required
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Breakdown Reports</h1>";

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

// Check if breakdown_reports table exists
echo "<h2>2. Check breakdown_reports Table</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'breakdown_reports'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green'>✅ breakdown_reports table exists</p>";
        
        // Show structure
        $stmt = $pdo->query("DESCRIBE breakdown_reports");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Table Structure:</h3>";
        echo "<table border='1'><tr><th>Field</th><th>Type</th></tr>";
        foreach ($columns as $col) {
            echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td></tr>";
        }
        echo "</table>";
        
        // Check if pan_issue_id column exists
        $has_pan_issue_id = false;
        $has_message_text = false;
        $has_report_ref = false;
        foreach ($columns as $col) {
            if ($col['Field'] === 'pan_issue_id') {
                $has_pan_issue_id = true;
            }
            if ($col['Field'] === 'message_text') {
                $has_message_text = true;
            }
            if ($col['Field'] === 'report_ref') {
                $has_report_ref = true;
            }
        }
        
        if (!$has_pan_issue_id) {
            echo "<p style='color:orange'>⚠️ pan_issue_id column missing. Adding it...</p>";
            $pdo->exec("ALTER TABLE breakdown_reports ADD COLUMN pan_issue_id INT AFTER description");
            echo "<p style='color:green'>✅ Added pan_issue_id column</p>";
        }
        
        if (!$has_message_text) {
            echo "<p style='color:orange'>⚠️ message_text column missing. Adding it...</p>";
            $pdo->exec("ALTER TABLE breakdown_reports ADD COLUMN message_text TEXT AFTER description");
            echo "<p style='color:green'>✅ Added message_text column</p>";
        }
        
        if (!$has_report_ref) {
            echo "<p style='color:orange'>⚠️ report_ref column missing. Adding it...</p>";
            $pdo->exec("ALTER TABLE breakdown_reports ADD COLUMN report_ref VARCHAR(50) UNIQUE AFTER created_at");
            echo "<p style='color:green'>✅ Added report_ref column</p>";
        }
        
        // Check if pan_issues table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'pan_issues'");
        $has_pan_issues = $stmt->rowCount() > 0;
        echo "<p>Pan issues table exists: " . ($has_pan_issues ? 'YES' : 'NO') . "</p>";
        
        if (!$has_pan_issues) {
            echo "<p style='color:orange'>⚠️ pan_issues table missing. Creating it...</p>";
            
            // Create pan_issues table
            $sql = "CREATE TABLE pan_issues (
                id INT AUTO_INCREMENT PRIMARY KEY,
                pan_code VARCHAR(20) UNIQUE NOT NULL,
                label_fr VARCHAR(255) NOT NULL,
                label_ar VARCHAR(255) DEFAULT NULL,
                description TEXT DEFAULT NULL,
                category VARCHAR(100) DEFAULT NULL,
                priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_pan_code (pan_code),
                INDEX idx_category (category),
                INDEX idx_priority (priority),
                INDEX idx_is_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($sql);
            echo "<p style='color:green'>✅ Created pan_issues table</p>";
            
            // Add sample PAN issues
            $sample_pan_issues = [
                ['PAN001', 'Moteur surchauffé', 'محرك سخان', 'Température du moteur supérieure à la normale', 'Moteur', 'high'],
                ['PAN002', 'Freinage défaillant', 'فرامل معطل', 'Système de freinage ne fonctionne pas correctement', 'Freinage', 'critical'],
                ['PAN003', 'Pneu crevé', 'إطار منفجر', 'Pneu avant droit crevé ou endommagé', 'Pneumatique', 'medium'],
                ['PAN004', 'Batterie faible', 'بطارية ضعيفة', 'Tension de la batterie inférieure à 12V', 'Électrique', 'medium'],
                ['PAN005', 'Fuite d\'huile', 'تسرب زيت', 'Perte d\'huile moteur visible', 'Moteur', 'high']
            ];
            
            foreach ($sample_pan_issues as $issue) {
                $stmt = $pdo->prepare("INSERT INTO pan_issues (pan_code, label_fr, label_ar, description, category, priority) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute($issue);
            }
            
            echo "<p style='color:green'>✅ Added 5 sample PAN issues</p>";
        }
        
        // Add foreign key if both tables exist
        if ($has_pan_issues) {
            try {
                $pdo->exec("ALTER TABLE breakdown_reports ADD CONSTRAINT fk_breakdown_pan_issues FOREIGN KEY (pan_issue_id) REFERENCES pan_issues(id) ON DELETE SET NULL");
                echo "<p style='color:green'>✅ Added foreign key constraint</p>";
            } catch (Exception $e) {
                echo "<p style='color:orange'>⚠️ Foreign key already exists or error: " . $e->getMessage() . "</p>";
            }
        }
        
    } else {
        echo "<p style='color:red'>❌ breakdown_reports table does not exist</p>";
        
        // Create table
        $sql = "CREATE TABLE breakdown_reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            bus_id INT NOT NULL,
            driver_id INT NOT NULL,
            breakdown_date DATETIME NOT NULL,
            location VARCHAR(255),
            description TEXT NOT NULL,
            severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
            status ENUM('reported', 'assigned', 'in_progress', 'resolved', 'closed') DEFAULT 'reported',
            assigned_to INT,
            report_ref VARCHAR(50) UNIQUE,
            category VARCHAR(100),
            urgency VARCHAR(50),
            kilometrage INT,
            message_text TEXT,
            pan_issue_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE,
            FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
            FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (pan_issue_id) REFERENCES pan_issues(id) ON DELETE SET NULL,
            INDEX idx_bus_id (bus_id),
            INDEX idx_driver_id (driver_id),
            INDEX idx_status (status),
            INDEX idx_severity (severity),
            INDEX idx_breakdown_date (breakdown_date),
            INDEX idx_pan_issue_id (pan_issue_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "<p style='color:green'>✅ Created breakdown_reports table</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}

// Test the problematic query
echo "<h2>3. Test Query from breakdown_reports</h2>";
try {
    $stmt = $pdo->query("
        SELECT br.*, b.bus_number, b.license_plate, 
               CONCAT(d.prenom, ' ', d.nom) AS driver_name, d.phone AS driver_phone,
               pi.pan_code, pi.label_fr
        FROM breakdown_reports br
        LEFT JOIN buses b ON br.bus_id = b.id
        LEFT JOIN drivers d ON br.driver_id = d.id
        LEFT JOIN pan_issues pi ON br.pan_issue_id = pi.id
        ORDER BY br.created_at DESC
        LIMIT 3
    ");
    
    $reports = $stmt->fetchAll();
    
    echo "<p>Query executed successfully. Found " . count($reports) . " breakdown reports</p>";
    
    if (count($reports) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Ref</th><th>Bus</th><th>Driver</th><th>PAN Code</th><th>Description</th></tr>";
        foreach ($reports as $r) {
            echo "<tr>";
            echo "<td>{$r['id']}</td>";
            echo "<td>" . ($r['report_ref'] ?? '-') . "</td>";
            echo "<td>" . ($r['bus_number'] ?? 'No bus') . "</td>";
            echo "<td>" . ($r['driver_name'] ?? 'No driver') . "</td>";
            echo "<td>" . ($r['pan_code'] ?? '-') . "</td>";
            echo "<td>" . ($r['label_fr'] ?? '-') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Query error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='admin_breakdowns.php'>Go to admin_breakdowns.php</a></p>";
?>

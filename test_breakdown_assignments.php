<?php
// Test page for breakdown_assignments table - no authentication required
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Breakdown Assignments</h1>";

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

// Check if breakdown_assignments table exists
echo "<h2>2. Check breakdown_assignments Table</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'breakdown_assignments'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green'>✅ breakdown_assignments table exists</p>";
        
        // Show structure
        $stmt = $pdo->query("DESCRIBE breakdown_assignments");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Table Structure:</h3>";
        echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        foreach ($columns as $col) {
            echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td></tr>";
        }
        echo "</table>";
        
        // Show data
        $stmt = $pdo->query("SELECT * FROM breakdown_assignments");
        $assignments = $stmt->fetchAll();
        echo "<h3>Current Data (" . count($assignments) . " records):</h3>";
        if (count($assignments) > 0) {
            echo "<table border='1'><tr><th>ID</th><th>Breakdown ID</th><th>Assigned To</th><th>Status</th><th>Assigned At</th></tr>";
            foreach ($assignments as $a) {
                echo "<tr>";
                echo "<td>{$a['id']}</td>";
                echo "<td>{$a['breakdown_id']}</td>";
                echo "<td>{$a['assigned_to']}</td>";
                echo "<td>{$a['status']}</td>";
                echo "<td>{$a['assigned_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No data found. Adding sample data...</p>";
            
            // Add sample data
            $stmt = $pdo->prepare("
                INSERT INTO breakdown_assignments (
                    breakdown_id, assigned_to, assigned_by, status, notes
                ) VALUES (?, ?, ?, ?, ?)
            ");
            
            $sample_data = [
                [1, 1, 1, 'assigned', 'Initial assignment'],
                [2, 1, 1, 'in_progress', 'Work started'],
                [3, 2, 1, 'completed', 'Issue resolved']
            ];
            
            foreach ($sample_data as $data) {
                try {
                    $stmt->execute($data);
                    echo "<p style='color:green'>✅ Added sample assignment: Breakdown {$data[0]} -> User {$data[1]}</p>";
                } catch (Exception $e) {
                    echo "<p style='color:orange'>⚠️ Error adding sample data: " . $e->getMessage() . "</p>";
                }
            }
        }
        
    } else {
        echo "<p style='color:red'>❌ breakdown_assignments table does not exist</p>";
        
        // Create table
        $sql = "CREATE TABLE breakdown_assignments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            breakdown_id INT NOT NULL,
            assigned_to INT NOT NULL,
            assigned_by INT NOT NULL,
            assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('assigned', 'in_progress', 'completed', 'cancelled') DEFAULT 'assigned',
            notes TEXT DEFAULT NULL,
            completed_at TIMESTAMP NULL DEFAULT NULL,
            completion_notes TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (breakdown_id) REFERENCES breakdown_reports(id) ON DELETE CASCADE,
            FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_breakdown_id (breakdown_id),
            INDEX idx_assigned_to (assigned_to),
            INDEX idx_assigned_by (assigned_by),
            INDEX idx_status (status),
            INDEX idx_assigned_at (assigned_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "<p style='color:green'>✅ Created breakdown_assignments table</p>";
        
        // Add sample data
        $stmt = $pdo->prepare("
            INSERT INTO breakdown_assignments (
                breakdown_id, assigned_to, assigned_by, status, notes
            ) VALUES (?, ?, ?, ?, ?)
        ");
        
        $sample_data = [
            [1, 1, 1, 'assigned', 'Initial assignment'],
            [2, 1, 1, 'in_progress', 'Work started'],
            [3, 2, 1, 'completed', 'Issue resolved']
        ];
        
        foreach ($sample_data as $data) {
            try {
                $stmt->execute($data);
                echo "<p style='color:green'>✅ Added sample assignment: Breakdown {$data[0]} -> User {$data[1]}</p>";
            } catch (Exception $e) {
                echo "<p style='color:orange'>⚠️ Error adding sample data: " . $e->getMessage() . "</p>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}

// Test the problematic query
echo "<h2>3. Test Query from breakdown_assignments</h2>";
try {
    $stmt = $pdo->query("
        SELECT ba.*, br.report_ref, br.description AS breakdown_description,
               u1.username AS assigned_to_name, u2.username AS assigned_by_name
        FROM breakdown_assignments ba
        LEFT JOIN breakdown_reports br ON ba.breakdown_id = br.id
        LEFT JOIN users u1 ON ba.assigned_to = u1.id
        LEFT JOIN users u2 ON ba.assigned_by = u2.id
        ORDER BY ba.assigned_at DESC
        LIMIT 5
    ");
    
    $assignments = $stmt->fetchAll();
    
    echo "<p>Query executed successfully. Found " . count($assignments) . " assignments</p>";
    
    if (count($assignments) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Report Ref</th><th>Description</th><th>Assigned To</th><th>Assigned By</th><th>Status</th></tr>";
        foreach ($assignments as $a) {
            echo "<tr>";
            echo "<td>{$a['id']}</td>";
            echo "<td>" . ($a['report_ref'] ?? '-') . "</td>";
            echo "<td>" . substr($a['breakdown_description'] ?? '-', 0, 30) . "...</td>";
            echo "<td>" . ($a['assigned_to_name'] ?? 'User ' . $a['assigned_to']) . "</td>";
            echo "<td>" . ($a['assigned_by_name'] ?? 'User ' . $a['assigned_by']) . "</td>";
            echo "<td>{$a['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Query error: " . $e->getMessage() . "</p>";
}

// Check related tables
echo "<h2>4. Check Related Tables</h2>";

// Check breakdown_reports
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM breakdown_reports");
    $count = $stmt->fetch()['count'];
    echo "<p>breakdown_reports: $count records</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ breakdown_reports table error: " . $e->getMessage() . "</p>";
}

// Check users
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $count = $stmt->fetch()['count'];
    echo "<p>users: $count records</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ users table error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='admin_breakdowns.php'>Go to admin_breakdowns.php</a></p>";
echo "<p><a href='driver_breakdown_new.php'>Go to driver_breakdown_new.php</a></p>";
?>

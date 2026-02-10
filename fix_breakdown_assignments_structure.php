<?php
// Fix breakdown_assignments table structure to match PHP code
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Fix Breakdown Assignments Structure</h1>";

// Database connection
try {
    require_once 'config.php';
    $database = new Database();
    $pdo = $database->connect();
    echo "<p style='color:green'>✅ Database connection: SUCCESS</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Database connection: FAILED - " . $e->getMessage() . "</p>";
    exit;
}

// Check current breakdown_assignments structure
echo "<h2>1. Current breakdown_assignments Structure</h2>";
try {
    $stmt = $pdo->query("DESCRIBE breakdown_assignments");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td></tr>";
    }
    echo "</table>";
    
    // Check what columns exist
    $existing_columns = array_column($columns, 'Field');
    
    echo "<h3>Column Analysis:</h3>";
    echo "<p><strong>PHP code expects:</strong> report_id, assigned_to_user_id, assigned_by_user_id, started_at</p>";
    echo "<p><strong>Table currently has:</strong> " . implode(', ', $existing_columns) . "</p>";
    
    // Check for missing columns
    $required_columns = ['report_id', 'assigned_to_user_id', 'assigned_by_user_id', 'started_at'];
    $missing_columns = [];
    
    foreach ($required_columns as $col) {
        if (!in_array($col, $existing_columns)) {
            $missing_columns[] = $col;
        }
    }
    
    if (!empty($missing_columns)) {
        echo "<p style='color:orange'>⚠️ Missing columns: " . implode(', ', $missing_columns) . "</p>";
        
        // Add missing columns
        foreach ($missing_columns as $col) {
            try {
                switch ($col) {
                    case 'report_id':
                        $pdo->exec("ALTER TABLE breakdown_assignments ADD COLUMN report_id INT AFTER breakdown_id");
                        echo "<p style='color:green'>✅ Added report_id column</p>";
                        break;
                    case 'assigned_to_user_id':
                        $pdo->exec("ALTER TABLE breakdown_assignments ADD COLUMN assigned_to_user_id INT AFTER assigned_to");
                        echo "<p style='color:green'>✅ Added assigned_to_user_id column</p>";
                        break;
                    case 'assigned_by_user_id':
                        $pdo->exec("ALTER TABLE breakdown_assignments ADD COLUMN assigned_by_user_id INT AFTER assigned_by");
                        echo "<p style='color:green'>✅ Added assigned_by_user_id column</p>";
                        break;
                    case 'started_at':
                        $pdo->exec("ALTER TABLE breakdown_assignments ADD COLUMN started_at TIMESTAMP NULL DEFAULT NULL AFTER assigned_at");
                        echo "<p style='color:green'>✅ Added started_at column</p>";
                        break;
                }
            } catch (Exception $e) {
                echo "<p style='color:red'>❌ Error adding $col: " . $e->getMessage() . "</p>";
            }
        }
    } else {
        echo "<p style='color:green'>✅ All required columns exist</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error getting table structure: " . $e->getMessage() . "</p>";
}

// Populate new columns with data from existing columns
echo "<h2>2. Synchronizing Column Data</h2>";
try {
    // Update report_id from breakdown_id
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM breakdown_assignments WHERE report_id IS NULL AND breakdown_id IS NOT NULL");
    $count = $stmt->fetch()['count'];
    
    if ($count > 0) {
        $pdo->exec("UPDATE breakdown_assignments SET report_id = breakdown_id WHERE report_id IS NULL AND breakdown_id IS NOT NULL");
        echo "<p style='color:green'>✅ Updated $count records: report_id = breakdown_id</p>";
    } else {
        echo "<p style='color:blue'>ℹ️ report_id already synchronized</p>";
    }
    
    // Update assigned_to_user_id from assigned_to
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM breakdown_assignments WHERE assigned_to_user_id IS NULL AND assigned_to IS NOT NULL");
    $count = $stmt->fetch()['count'];
    
    if ($count > 0) {
        $pdo->exec("UPDATE breakdown_assignments SET assigned_to_user_id = assigned_to WHERE assigned_to_user_id IS NULL AND assigned_to IS NOT NULL");
        echo "<p style='color:green'>✅ Updated $count records: assigned_to_user_id = assigned_to</p>";
    } else {
        echo "<p style='color:blue'>ℹ️ assigned_to_user_id already synchronized</p>";
    }
    
    // Update assigned_by_user_id from assigned_by
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM breakdown_assignments WHERE assigned_by_user_id IS NULL AND assigned_by IS NOT NULL");
    $count = $stmt->fetch()['count'];
    
    if ($count > 0) {
        $pdo->exec("UPDATE breakdown_assignments SET assigned_by_user_id = assigned_by WHERE assigned_by_user_id IS NULL AND assigned_by IS NOT NULL");
        echo "<p style='color:green'>✅ Updated $count records: assigned_by_user_id = assigned_by</p>";
    } else {
        echo "<p style='color:blue'>ℹ️ assigned_by_user_id already synchronized</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error synchronizing data: " . $e->getMessage() . "</p>";
}

// Test the PHP queries
echo "<h2>3. Testing PHP Queries</h2>";

$test_queries = [
    [
        'name' => 'technician_breakdowns.php line 43',
        'sql' => "SELECT id FROM breakdown_assignments WHERE report_id = ? AND assigned_to_user_id = ?",
        'params' => [1, 1]
    ],
    [
        'name' => 'technician_breakdowns.php line 60',
        'sql' => "SELECT id, started_at FROM breakdown_assignments WHERE report_id = ? AND assigned_to_user_id = ?",
        'params' => [1, 1]
    ],
    [
        'name' => 'admin_breakdown_view.php line 121',
        'sql' => "SELECT * FROM breakdown_assignments WHERE report_id = ? ORDER BY assigned_at DESC LIMIT 1",
        'params' => [1]
    ]
];

foreach ($test_queries as $query) {
    try {
        $stmt = $pdo->prepare($query['sql']);
        $stmt->execute($query['params']);
        $result = $stmt->fetchAll();
        
        echo "<p style='color:green'>✅ {$query['name']}: SUCCESS (" . count($result) . " records)</p>";
        
        if (count($result) > 0) {
            echo "<table border='1' style='font-size: 12px;'>";
            echo "<tr>";
            foreach (array_keys($result[0]) as $key) {
                echo "<th>$key</th>";
            }
            echo "</tr>";
            foreach ($result as $row) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . ($value ?? '-') . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color:red'>❌ {$query['name']}: " . $e->getMessage() . "</p>";
    }
}

// Show final structure
echo "<h2>4. Final Table Structure</h2>";
try {
    $stmt = $pdo->query("DESCRIBE breakdown_assignments");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td></tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error getting final structure: " . $e->getMessage() . "</p>";
}

// Show sample data
echo "<h2>5. Sample Data</h2>";
try {
    $stmt = $pdo->query("SELECT * FROM breakdown_assignments LIMIT 5");
    $data = $stmt->fetchAll();
    
    if (count($data) > 0) {
        echo "<table border='1' style='font-size: 12px;'>";
        echo "<tr>";
        foreach (array_keys($data[0]) as $key) {
            echo "<th>$key</th>";
        }
        echo "</tr>";
        foreach ($data as $row) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . ($value ?? '-') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No data found in breakdown_assignments table</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error getting sample data: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>6. Summary</h2>";
echo "<p><strong>Problem:</strong> PHP code uses different column names than the table structure</p>";
echo "<p><strong>Solution:</strong> Added the columns PHP code expects and synchronized data</p>";
echo "<p><strong>Result:</strong> Both old and new column names work (backward compatibility)</p>";

echo "<hr>";
echo "<p><a href='technician_breakdowns.php'>Test technician_breakdowns.php</a></p>";
echo "<p><a href='admin_breakdown_view.php'>Test admin_breakdown_view.php</a></p>";
?>

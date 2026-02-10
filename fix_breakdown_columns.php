<?php
// Fix breakdown_reports columns - simple version without information_schema
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Fix Breakdown Reports Columns</h1>";

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

// List of columns to add
$columns_to_add = [
    'report_ref' => 'VARCHAR(50) UNIQUE AFTER created_at',
    'created_by_user_id' => 'INT AFTER report_ref',
    'kilometrage' => 'INT AFTER bus_id',
    'message_text' => 'TEXT AFTER description',
    'category' => 'VARCHAR(100) AFTER message_text',
    'urgency' => 'VARCHAR(50) AFTER category',
    'status' => "VARCHAR(50) DEFAULT 'nouveau' AFTER urgency",
    'pan_issue_id' => 'INT AFTER status',
    'audio_path' => 'VARCHAR(255) AFTER pan_issue_id'
];

// Get current table structure
echo "<h2>1. Current Table Structure</h2>";
try {
    $stmt = $pdo->query("DESCRIBE breakdown_reports");
    $current_columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    echo "<table border='1'><tr><th>Current Columns</th></tr>";
    foreach ($current_columns as $col) {
        echo "<tr><td>$col</td></tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error getting table structure: " . $e->getMessage() . "</p>";
    exit;
}

// Add missing columns
echo "<h2>2. Adding Missing Columns</h2>";
$added_columns = [];
$already_exists_columns = [];

foreach ($columns_to_add as $column_name => $column_definition) {
    if (!in_array($column_name, $current_columns)) {
        try {
            $sql = "ALTER TABLE breakdown_reports ADD COLUMN $column_name $column_definition";
            $pdo->exec($sql);
            $added_columns[] = $column_name;
            echo "<p style='color:green'>✅ Added column: $column_name</p>";
        } catch (Exception $e) {
            echo "<p style='color:red'>❌ Error adding column $column_name: " . $e->getMessage() . "</p>";
        }
    } else {
        $already_exists_columns[] = $column_name;
        echo "<p style='color:blue'>ℹ️ Column already exists: $column_name</p>";
    }
}

// Show updated structure
echo "<h2>3. Updated Table Structure</h2>";
try {
    $stmt = $pdo->query("DESCRIBE breakdown_reports");
    $updated_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($updated_columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>" . ($col['Default'] ?: '-') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error getting updated structure: " . $e->getMessage() . "</p>";
}

// Test INSERT query
echo "<h2>4. Test INSERT Query</h2>";
try {
    // Generate a unique report_ref
    $report_ref = 'BRK-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Test INSERT
    $stmt = $pdo->prepare("
        INSERT INTO breakdown_reports (
            report_ref, created_by_user_id, driver_id, bus_id, kilometrage, 
            category, urgency, message_text, status, audio_path
        ) VALUES (
            ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, ?
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
        'Test incident from fix script', // message_text
        'nouveau',   // status
        null         // audio_path
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

// Summary
echo "<h2>5. Summary</h2>";
echo "<p><strong>Added columns:</strong> " . implode(', ', $added_columns) . "</p>";
echo "<p><strong>Already existed:</strong> " . implode(', ', $already_exists_columns) . "</p>";

echo "<hr>";
echo "<p><a href='driver_breakdown_new.php'>Test driver breakdown submission</a></p>";
echo "<p><a href='driver_portal.php'>Go to driver portal</a></p>";
?>

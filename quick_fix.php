<?php
// QUICK FIX - Resolve foreign key constraint violations immediately
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🚀 QUICK FIX - Foreign Key Issues</h1>";

// Database connection
try {
    require_once 'config.php';
    $database = new Database();
    $pdo = $database->connect();
    echo "<p style='color:green'>✅ Database connected</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Step 1: Ensure we have breakdown_reports
echo "<h2>Step 1: Ensure breakdown_reports exist</h2>";
$stmt = $pdo->query("SELECT COUNT(*) as count FROM breakdown_reports");
$count = $stmt->fetch()['count'];

if ($count == 0) {
    echo "<p style='color:orange'>⚠️ No breakdown_reports found. Creating one...</p>";
    $pdo->exec("INSERT INTO breakdown_reports (report_ref, driver_id, bus_id, kilometrage, category, urgency, description, status) VALUES ('BRK-QUICKFIX-001', 1, 1, 50000, 'mecanique', 'urgent', 'Quick fix breakdown report', 'nouveau')");
    echo "<p style='color:green'>✅ Created sample breakdown report</p>";
} else {
    echo "<p style='color:green'>✅ Found $count breakdown_reports</p>";
}

// Step 2: Ensure we have users
echo "<h2>Step 2: Ensure users exist</h2>";
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
$count = $stmt->fetch()['count'];

if ($count == 0) {
    echo "<p style='color:orange'>⚠️ No users found. Creating one...</p>";
    $pdo->exec("INSERT INTO users (username, email, password, role, is_active) VALUES ('admin', 'admin@quickfix.com', 'admin123', 'admin', 1)");
    echo "<p style='color:green'>✅ Created sample user</p>";
} else {
    echo "<p style='color:green'>✅ Found $count users</p>";
}

// Step 3: Clean up invalid assignments
echo "<h2>Step 3: Clean up invalid assignments</h2>";

// Delete assignments with invalid breakdown_id
$stmt = $pdo->query("
    SELECT COUNT(*) as count 
    FROM breakdown_assignments ba
    LEFT JOIN breakdown_reports br ON ba.breakdown_id = br.id
    WHERE br.id IS NULL
");
$invalid_count = $stmt->fetch()['count'];

if ($invalid_count > 0) {
    $pdo->exec("
        DELETE ba FROM breakdown_assignments ba
        LEFT JOIN breakdown_reports br ON ba.breakdown_id = br.id
        WHERE br.id IS NULL
    ");
    echo "<p style='color:green'>✅ Deleted $invalid_count assignments with invalid breakdown_id</p>";
} else {
    echo "<p style='color:green'>✅ No assignments with invalid breakdown_id</p>";
}

// Delete assignments with invalid assigned_to
$stmt = $pdo->query("
    SELECT COUNT(*) as count 
    FROM breakdown_assignments ba
    LEFT JOIN users u ON ba.assigned_to = u.id
    WHERE u.id IS NULL
");
$invalid_count = $stmt->fetch()['count'];

if ($invalid_count > 0) {
    $pdo->exec("
        DELETE ba FROM breakdown_assignments ba
        LEFT JOIN users u ON ba.assigned_to = u.id
        WHERE u.id IS NULL
    ");
    echo "<p style='color:green'>✅ Deleted $invalid_count assignments with invalid assigned_to</p>";
} else {
    echo "<p style='color:green'>✅ No assignments with invalid assigned_to</p>";
}

// Delete assignments with invalid assigned_by
$stmt = $pdo->query("
    SELECT COUNT(*) as count 
    FROM breakdown_assignments ba
    LEFT JOIN users u ON ba.assigned_by = u.id
    WHERE u.id IS NULL
");
$invalid_count = $stmt->fetch()['count'];

if ($invalid_count > 0) {
    $pdo->exec("
        DELETE ba FROM breakdown_assignments ba
        LEFT JOIN users u ON ba.assigned_by = u.id
        WHERE u.id IS NULL
    ");
    echo "<p style='color:green'>✅ Deleted $invalid_count assignments with invalid assigned_by</p>";
} else {
    echo "<p style='color:green'>✅ No assignments with invalid assigned_by</p>";
}

// Step 4: Add a valid assignment if table is empty
echo "<h2>Step 4: Ensure we have at least one valid assignment</h2>";
$stmt = $pdo->query("SELECT COUNT(*) as count FROM breakdown_assignments");
$count = $stmt->fetch()['count'];

if ($count == 0) {
    echo "<p style='color:orange'>⚠️ No assignments found. Creating one...</p>";
    
    // Get valid IDs
    $stmt = $pdo->query("SELECT MIN(id) as id FROM breakdown_reports");
    $breakdown_id = $stmt->fetch()['id'];
    
    $stmt = $pdo->query("SELECT MIN(id) as id FROM users");
    $user_id = $stmt->fetch()['id'];
    
    if ($breakdown_id && $user_id) {
        $pdo->exec("
            INSERT INTO breakdown_assignments (breakdown_id, assigned_to, assigned_by, status, notes)
            VALUES ($breakdown_id, $user_id, $user_id, 'assigned', 'Quick fix assignment')
        ");
        echo "<p style='color:green'>✅ Created sample assignment</p>";
    }
} else {
    echo "<p style='color:green'>✅ Found $count assignments</p>";
}

// Step 5: Test adding a new assignment
echo "<h2>Step 5: Test adding new assignment</h2>";
try {
    $stmt = $pdo->query("SELECT MIN(id) as id FROM breakdown_reports");
    $breakdown_id = $stmt->fetch()['id'];
    
    $stmt = $pdo->query("SELECT MIN(id) as id FROM users");
    $user_id = $stmt->fetch()['id'];
    
    if ($breakdown_id && $user_id) {
        $stmt = $pdo->prepare("
            INSERT INTO breakdown_assignments (breakdown_id, assigned_to, assigned_by, status, notes)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $breakdown_id,
            $user_id,
            $user_id,
            'assigned',
            'Test assignment from quick fix'
        ]);
        
        if ($result) {
            echo "<p style='color:green'>✅ Successfully added test assignment!</p>";
            echo "<p>breakdown_id: $breakdown_id, assigned_to: $user_id</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error adding test assignment: " . $e->getMessage() . "</p>";
}

// Step 6: Show final status
echo "<h2>🎯 FINAL STATUS</h2>";
$stmt = $pdo->query("SELECT COUNT(*) as count FROM breakdown_reports");
echo "<p>📊 breakdown_reports: " . $stmt->fetch()['count'] . " records</p>";

$stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
echo "<p>👥 users: " . $stmt->fetch()['count'] . " records</p>";

$stmt = $pdo->query("SELECT COUNT(*) as count FROM breakdown_assignments");
echo "<p>📋 breakdown_assignments: " . $stmt->fetch()['count'] . " records</p>";

echo "<hr>";
echo "<h2>🚀 READY TO TEST</h2>";
echo "<p><a href='technician_breakdowns.php' style='background:green;color:white;padding:10px;text-decoration:none;'>Test technician_breakdowns.php</a></p>";
echo "<p><a href='admin_breakdown_view.php' style='background:blue;color:white;padding:10px;text-decoration:none;'>Test admin_breakdown_view.php</a></p>";
echo "<p><a href='driver_breakdown_new.php' style='background:orange;color:white;padding:10px;text-decoration:none;'>Test driver_breakdown_new.php</a></p>";

echo "<hr>";
echo "<p style='color:green'><strong>✅ All foreign key issues should be resolved!</strong></p>";
?>

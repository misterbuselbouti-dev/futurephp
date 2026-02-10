<?php
// Fix breakdown_assignments data integrity issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Fix Breakdown Assignments Data Integrity</h1>";

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

// Check breakdown_reports data
echo "<h2>1. Check breakdown_reports Data</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM breakdown_reports");
    $breakdown_count = $stmt->fetch()['count'];
    echo "<p>breakdown_reports: $breakdown_count records</p>";
    
    if ($breakdown_count > 0) {
        $stmt = $pdo->query("SELECT id, report_ref FROM breakdown_reports ORDER BY id LIMIT 10");
        $reports = $stmt->fetchAll();
        
        echo "<h3>Sample breakdown_reports:</h3>";
        echo "<table border='1'><tr><th>ID</th><th>Report Ref</th></tr>";
        foreach ($reports as $r) {
            echo "<tr><td>{$r['id']}</td><td>" . ($r['report_ref'] ?? '-') . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange'>⚠️ No breakdown_reports found. Creating sample data...</p>";
        
        // Create sample breakdown reports
        $sample_reports = [
            ['BRK-20250207-0001', 1, 1, 50000, 'mecanique', 'urgent', 'Engine overheating issue', 'nouveau'],
            ['BRK-20250207-0002', 2, 2, 75000, 'electrique', 'normal', 'Electrical system failure', 'nouveau'],
            ['BRK-20250207-0003', 1, 3, 120000, 'pneumatique', 'urgent', 'Tire burst incident', 'nouveau']
        ];
        
        foreach ($sample_reports as $report) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO breakdown_reports (
                        report_ref, driver_id, bus_id, kilometrage, 
                        category, urgency, description, status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute($report);
                echo "<p style='color:green'>✅ Added sample report: {$report[0]}</p>";
            } catch (Exception $e) {
                echo "<p style='color:red'>❌ Error adding sample report: " . $e->getMessage() . "</p>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error checking breakdown_reports: " . $e->getMessage() . "</p>";
}

// Check users data
echo "<h2>2. Check users Data</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $user_count = $stmt->fetch()['count'];
    echo "<p>users: $user_count records</p>";
    
    if ($user_count > 0) {
        $stmt = $pdo->query("SELECT id, username FROM users ORDER BY id LIMIT 5");
        $users = $stmt->fetchAll();
        
        echo "<h3>Sample users:</h3>";
        echo "<table border='1'><tr><th>ID</th><th>Username</th></tr>";
        foreach ($users as $u) {
            echo "<tr><td>{$u['id']}</td><td>{$u['username']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange'>⚠️ No users found. Creating sample user...</p>";
        
        // Create sample user
        try {
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password, role, is_active) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute(['admin', 'admin@example.com', password_hash('admin', PASSWORD_DEFAULT), 'admin', 1]);
            echo "<p style='color:green'>✅ Added sample admin user</p>";
        } catch (Exception $e) {
            echo "<p style='color:red'>❌ Error adding sample user: " . $e->getMessage() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error checking users: " . $e->getMessage() . "</p>";
}

// Check breakdown_assignments data
echo "<h2>3. Check breakdown_assignments Data</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM breakdown_assignments");
    $assignment_count = $stmt->fetch()['count'];
    echo "<p>breakdown_assignments: $assignment_count records</p>";
    
    if ($assignment_count > 0) {
        // Check for invalid foreign keys
        echo "<h3>Checking for invalid foreign keys...</h3>";
        
        // Check invalid breakdown_id
        $stmt = $pdo->query("
            SELECT ba.id, ba.breakdown_id 
            FROM breakdown_assignments ba
            LEFT JOIN breakdown_reports br ON ba.breakdown_id = br.id
            WHERE br.id IS NULL
        ");
        $invalid_breakdowns = $stmt->fetchAll();
        
        if (!empty($invalid_breakdowns)) {
            echo "<p style='color:red'>❌ Found " . count($invalid_breakdowns) . " assignments with invalid breakdown_id</p>";
            echo "<table border='1'><tr><th>Assignment ID</th><th>Invalid breakdown_id</th></tr>";
            foreach ($invalid_breakdowns as $inv) {
                echo "<tr><td>{$inv['id']}</td><td>{$inv['breakdown_id']}</td></tr>";
            }
            echo "</table>";
            
            // Option 1: Delete invalid assignments
            echo "<h4>Option 1: Delete invalid assignments</h4>";
            foreach ($invalid_breakdowns as $inv) {
                try {
                    $pdo->exec("DELETE FROM breakdown_assignments WHERE id = {$inv['id']}");
                    echo "<p style='color:green'>✅ Deleted invalid assignment {$inv['id']}</p>";
                } catch (Exception $e) {
                    echo "<p style='color:red'>❌ Error deleting assignment {$inv['id']}: " . $e->getMessage() . "</p>";
                }
            }
            
            // Option 2: Update to valid breakdown_id
            echo "<h4>Option 2: Update to valid breakdown_id (alternative)</h4>";
            echo "<p>You could update invalid breakdown_id to a valid one instead of deleting.</p>";
        }
        
        // Check invalid assigned_to
        $stmt = $pdo->query("
            SELECT ba.id, ba.assigned_to 
            FROM breakdown_assignments ba
            LEFT JOIN users u ON ba.assigned_to = u.id
            WHERE u.id IS NULL
        ");
        $invalid_users = $stmt->fetchAll();
        
        if (!empty($invalid_users)) {
            echo "<p style='color:red'>❌ Found " . count($invalid_users) . " assignments with invalid assigned_to</p>";
            
            foreach ($invalid_users as $inv) {
                try {
                    // Update to first valid user
                    $stmt = $pdo->query("SELECT id FROM users LIMIT 1");
                    $first_user = $stmt->fetch();
                    if ($first_user) {
                        $pdo->exec("UPDATE breakdown_assignments SET assigned_to = {$first_user['id']} WHERE id = {$inv['id']}");
                        echo "<p style='color:green'>✅ Updated assignment {$inv['id']} to valid user {$first_user['id']}</p>";
                    }
                } catch (Exception $e) {
                    echo "<p style='color:red'>❌ Error updating assignment {$inv['id']}: " . $e->getMessage() . "</p>";
                }
            }
        }
        
        // Check invalid assigned_by
        $stmt = $pdo->query("
            SELECT ba.id, ba.assigned_by 
            FROM breakdown_assignments ba
            LEFT JOIN users u ON ba.assigned_by = u.id
            WHERE u.id IS NULL
        ");
        $invalid_assigned_by = $stmt->fetchAll();
        
        if (!empty($invalid_assigned_by)) {
            echo "<p style='color:red'>❌ Found " . count($invalid_assigned_by) . " assignments with invalid assigned_by</p>";
            
            foreach ($invalid_assigned_by as $inv) {
                try {
                    // Update to first valid user
                    $stmt = $pdo->query("SELECT id FROM users LIMIT 1");
                    $first_user = $stmt->fetch();
                    if ($first_user) {
                        $pdo->exec("UPDATE breakdown_assignments SET assigned_by = {$first_user['id']} WHERE id = {$inv['id']}");
                        echo "<p style='color:green'>✅ Updated assignment {$inv['id']} assigned_by to valid user {$first_user['id']}</p>";
                    }
                } catch (Exception $e) {
                    echo "<p style='color:red'>❌ Error updating assignment {$inv['id']}: " . $e->getMessage() . "</p>";
                }
            }
        }
        
    } else {
        echo "<p style='color:blue'>ℹ️ No breakdown_assignments found. Creating sample data...</p>";
        
        // Create sample assignments
        try {
            $stmt = $pdo->query("SELECT id FROM breakdown_reports LIMIT 3");
            $breakdown_ids = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            
            $stmt = $pdo->query("SELECT id FROM users LIMIT 2");
            $user_ids = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            
            if (!empty($breakdown_ids) && !empty($user_ids)) {
                foreach ($breakdown_ids as $i => $breakdown_id) {
                    $assigned_to = $user_ids[$i % count($user_ids)];
                    $assigned_by = $user_ids[0];
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO breakdown_assignments (
                            breakdown_id, assigned_to, assigned_by, status, notes
                        ) VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$breakdown_id, $assigned_to, $assigned_by, 'assigned', 'Initial assignment']);
                    echo "<p style='color:green'>✅ Added sample assignment: breakdown $breakdown_id -> user $assigned_to</p>";
                }
            }
        } catch (Exception $e) {
            echo "<p style='color:red'>❌ Error creating sample assignments: " . $e->getMessage() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error checking breakdown_assignments: " . $e->getMessage() . "</p>";
}

// Test adding a new assignment
echo "<h2>4. Test Adding New Assignment</h2>";
try {
    // Get valid IDs
    $stmt = $pdo->query("SELECT id FROM breakdown_reports LIMIT 1");
    $breakdown = $stmt->fetch();
    
    $stmt = $pdo->query("SELECT id FROM users LIMIT 1");
    $user = $stmt->fetch();
    
    if ($breakdown && $user) {
        $stmt = $pdo->prepare("
            INSERT INTO breakdown_assignments (
                breakdown_id, assigned_to, assigned_by, status, notes
            ) VALUES (?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $breakdown['id'],
            $user['id'],
            $user['id'],
            'assigned',
            'Test assignment from fix script'
        ]);
        
        if ($result) {
            echo "<p style='color:green'>✅ Successfully added test assignment</p>";
            echo "<p>breakdown_id: {$breakdown['id']}, assigned_to: {$user['id']}</p>";
        }
    } else {
        echo "<p style='color:orange'>⚠️ No valid breakdown_reports or users found for testing</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error adding test assignment: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>5. Summary</h2>";
echo "<ul>";
echo "<li><strong>Problem:</strong> Foreign key constraint violations in breakdown_assignments</li>";
echo "<li><strong>Cause:</strong> Invalid breakdown_id, assigned_to, or assigned_by values</li>";
echo "<li><strong>Solution:</strong> Clean up invalid data or create valid reference data</li>";
echo "</ul>";

echo "<hr>";
echo "<p><a href='technician_breakdowns.php'>Test technician_breakdowns.php</a></p>";
echo "<p><a href='admin_breakdown_view.php'>Test admin_breakdown_view.php</a></p>";
?>

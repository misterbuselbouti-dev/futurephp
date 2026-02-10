<?php
// Fix technician access issue
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Fix Technician Access Issue</h1>";

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

// Check current user
echo "<h2>1. Current User Status</h2>";
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    echo "<p>Logged in user ID: $user_id</p>";
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "<table border='1'>";
            echo "<tr><th>Username</th><td>{$user['username']}</td></tr>";
            echo "<tr><th>Role</th><td><strong>{$user['role']}</strong></td></tr>";
            echo "<tr><th>Email</th><td>{$user['email']}</td></tr>";
            echo "<tr><th>Active</th><td>" . ($user['is_active'] ? 'Yes' : 'No') . "</td></tr>";
            echo "</table>";
            
            echo "<h3>🔍 Problem Analysis:</h3>";
            echo "<p><strong>Current role:</strong> {$user['role']}</p>";
            echo "<p><strong>Required role for technician_breakdowns.php:</strong> technician</p>";
            
            if ($user['role'] !== 'technician') {
                echo "<p style='color:red'>❌ Role mismatch! Current role '{$user['role']}' != required 'technician'</p>";
                
                // Solution options
                echo "<h3>💡 Solutions:</h3>";
                
                echo "<h4>Option 1: Change user role to 'technician' (Recommended)</h4>";
                echo "<p>This will give you access to technician_breakdowns.php</p>";
                
                if (isset($_POST['change_role'])) {
                    try {
                        $stmt = $pdo->prepare("UPDATE users SET role = 'technician' WHERE id = ?");
                        $stmt->execute([$user_id]);
                        
                        // Update session
                        $_SESSION['role'] = 'technician';
                        
                        echo "<p style='color:green'>✅ Role changed to 'technician' successfully!</p>";
                        echo "<p><a href='technician_breakdowns.php' style='background:green;color:white;padding:10px;text-decoration:none;'>🔧 Test technician_breakdowns.php</a></p>";
                        
                        // Refresh user data
                        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                        $stmt->execute([$user_id]);
                        $user = $stmt->fetch();
                        
                    } catch (Exception $e) {
                        echo "<p style='color:red'>❌ Error changing role: " . $e->getMessage() . "</p>";
                    }
                } else {
                    echo "<form method='POST'>";
                    echo "<input type='hidden' name='change_role' value='1'>";
                    echo "<input type='submit' value='🔄 Change Role to technician' style='background:blue;color:white;padding:10px;border:none;cursor:pointer;'>";
                    echo "</form>";
                }
                
                echo "<h4>Option 2: Modify technician_breakdowns.php to accept 'admin' role</h4>";
                echo "<p>This will allow admin users to access technician pages</p>";
                
                if (isset($_POST['modify_file'])) {
                    try {
                        $file_path = __DIR__ . '/technician_breakdowns.php';
                        $content = file_get_contents($file_path);
                        
                        // Replace the role check
                        $old_check = "if (\$role !== 'technician') {";
                        $new_check = "if (!in_array(\$role, ['technician', 'admin'])) {";
                        
                        $content = str_replace($old_check, $new_check, $content);
                        
                        file_put_contents($file_path, $content);
                        
                        echo "<p style='color:green'>✅ Modified technician_breakdowns.php to accept admin users!</p>";
                        echo "<p><a href='technician_breakdowns.php' style='background:green;color:white;padding:10px;text-decoration:none;'>🔧 Test technician_breakdowns.php</a></p>";
                        
                    } catch (Exception $e) {
                        echo "<p style='color:red'>❌ Error modifying file: " . $e->getMessage() . "</p>";
                    }
                } else {
                    echo "<form method='POST'>";
                    echo "<input type='hidden' name='modify_file' value='1'>";
                    echo "<input type='submit' value='📝 Modify File to Accept Admin' style='background:orange;color:white;padding:10px;border:none;cursor:pointer;'>";
                    echo "</form>";
                }
                
                echo "<h4>Option 3: Create a separate technician user</h4>";
                echo "<p>Create a new user with technician role</p>";
                
                if (isset($_POST['create_technician'])) {
                    try {
                        $hashed_password = password_hash('technician', PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("
                            INSERT INTO users (username, email, password, role, is_active) 
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmt->execute(['technician', 'technician@example.com', $hashed_password, 'technician', 1]);
                        
                        echo "<p style='color:green'>✅ Created technician user!</p>";
                        echo "<p><strong>Username:</strong> technician</p>";
                        echo "<p><strong>Password:</strong> technician</p>";
                        echo "<p><a href='quick_login.php'>Login as technician</a></p>";
                        
                    } catch (Exception $e) {
                        echo "<p style='color:red'>❌ Error creating technician user: " . $e->getMessage() . "</p>";
                    }
                } else {
                    echo "<form method='POST'>";
                    echo "<input type='hidden' name='create_technician' value='1'>";
                    echo "<input type='submit' value='👤 Create Technician User' style='background:purple;color:white;padding:10px;border:none;cursor:pointer;'>";
                    echo "</form>";
                }
                
            } else {
                echo "<p style='color:green'>✅ Role is correct! Should have access.</p>";
                echo "<p><a href='technician_breakdowns.php' style='background:green;color:white;padding:10px;text-decoration:none;'>🔧 Test technician_breakdowns.php</a></p>";
            }
            
        } else {
            echo "<p style='color:red'>❌ User not found in database</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color:red'>❌ Error getting user info: " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<p style='color:red'>❌ Not logged in</p>";
    echo "<p><a href='quick_login.php'>Login first</a></p>";
}

// Show technician_breakdowns.php requirements
echo "<h2>2. technician_breakdowns.php Requirements</h2>";
echo "<h3>Code Analysis:</h3>";
echo "<pre style='background:#f5f5f5;padding:10px;border:1px solid #ccc;'>";
echo "Line 5: require_login();";
echo "Line 6: \$user = get_logged_in_user();";
echo "Line 7: \$role = \$user['role'] ?? '';";
echo "Line 8: if (\$role !== 'technician') {";
echo "Line 9:     http_response_code(403);";
echo "Line 10:    echo 'Accès refusé.';";
echo "Line 11:    exit;";
echo "Line 12: }";
echo "</pre>";

echo "<h3>What this means:</h3>";
echo "<ul>";
echo "<li>✅ User must be logged in (require_login())</li>";
echo "<li>✅ User role must be EXACTLY 'technician'</li>";
echo "<li>❌ 'admin' role is NOT accepted</li>";
echo "<li>❌ Any other role is rejected</li>";
echo "</ul>";

echo "<hr>";
echo "<h2>🎯 Recommended Solution</h2>";
echo "<p><strong>Option 1 (Recommended):</strong> Change your role to 'technician'</p>";
echo "<p><strong>Option 2:</strong> Modify the file to accept both 'technician' and 'admin'</p>";
echo "<p><strong>Option 3:</strong> Create a separate technician user</p>";

echo "<hr>";
echo "<p><small>Note: After applying any solution, refresh the page and test technician_breakdowns.php</small></p>";
?>

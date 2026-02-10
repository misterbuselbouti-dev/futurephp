<?php
// Test authentication and access issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔐 Test Authentication & Access</h1>";

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

// Check session status
echo "<h2>1. Session Status</h2>";
session_start();
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "</p>";

echo "<h3>Current Session Data:</h3>";
if (!empty($_SESSION)) {
    echo "<table border='1'><tr><th>Key</th><th>Value</th></tr>";
    foreach ($_SESSION as $key => $value) {
        echo "<tr><td>$key</td><td>" . (is_scalar($value) ? $value : gettype($value)) . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:orange'>⚠️ No session data found</p>";
}

// Check users table
echo "<h2>2. Users Table</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $count = $stmt->fetch()['count'];
    echo "<p>Total users: $count</p>";
    
    if ($count > 0) {
        $stmt = $pdo->query("SELECT id, username, role, is_active FROM users LIMIT 5");
        $users = $stmt->fetchAll();
        
        echo "<h3>Sample Users:</h3>";
        echo "<table border='1'><tr><th>ID</th><th>Username</th><th>Role</th><th>Active</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['username']}</td>";
            echo "<td>{$user['role']}</td>";
            echo "<td>" . ($user['is_active'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange'>⚠️ No users found. Creating admin user...</p>";
        
        // Create admin user
        $hashed_password = password_hash('admin', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, role, is_active) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute(['admin', 'admin@example.com', $hashed_password, 'admin', 1]);
        echo "<p style='color:green'>✅ Created admin user (username: admin, password: admin)</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error checking users: " . $e->getMessage() . "</p>";
}

// Check login.php functionality
echo "<h2>3. Login System Test</h2>";

// Test login functionality
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if ($username && $password) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['is_logged_in'] = true;
                
                echo "<p style='color:green'>✅ Login successful! Welcome {$user['username']}</p>";
                echo "<p>Role: {$user['role']}</p>";
                echo "<p><a href='?refresh=1'>Refresh page</a></p>";
            } else {
                echo "<p style='color:red'>❌ Invalid username or password</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color:red'>❌ Login error: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color:orange'>⚠️ Please enter username and password</p>";
    }
}

// Show login form if not logged in
if (!isset($_SESSION['is_logged_in']) || !$_SESSION['is_logged_in']) {
    echo "<h3>Test Login Form:</h3>";
    echo "<form method='POST'>";
    echo "<p>Username: <input type='text' name='username' value='admin' required></p>";
    echo "<p>Password: <input type='password' name='password' value='admin' required></p>";
    echo "<p><input type='submit' name='test_login' value='Test Login'></p>";
    echo "</form>";
} else {
    echo "<h3>Logged In User Info:</h3>";
    echo "<p><strong>Username:</strong> {$_SESSION['username']}</p>";
    echo "<p><strong>Role:</strong> {$_SESSION['role']}</p>";
    echo "<p><strong>User ID:</strong> {$_SESSION['user_id']}</p>";
    
    echo "<p><a href='?logout=1'>Logout</a></p>";
    
    if (isset($_GET['logout'])) {
        session_destroy();
        echo "<p style='color:blue'>ℹ️ Logged out. <a href='?'>Refresh</a></p>";
    }
}

// Check technician_breakdowns.php access requirements
echo "<h2>4. technician_breakdowns.php Access Requirements</h2>";
echo "<h3>What this page typically requires:</h3>";
echo "<ul>";
echo "<li>✅ User must be logged in</li>";
echo "<li>✅ User role should be 'technician' or 'admin'</li>";
echo "<li>✅ Session must contain user_id</li>";
echo "</ul>";

// Check if current user meets requirements
if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in']) {
    $user_role = $_SESSION['role'] ?? '';
    echo "<p><strong>Current user role:</strong> $user_role</p>";
    
    if (in_array($user_role, ['admin', 'technician'])) {
        echo "<p style='color:green'>✅ User has access to technician_breakdowns.php</p>";
    } else {
        echo "<p style='color:orange'>⚠️ User role '$user_role' may not have access to technician_breakdowns.php</p>";
        echo "<p>Suggestion: Update user role to 'technician' or 'admin'</p>";
        
        // Update user role
        try {
            $stmt = $pdo->prepare("UPDATE users SET role = 'technician' WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            echo "<p style='color:green'>✅ Updated user role to 'technician'</p>";
            $_SESSION['role'] = 'technician';
        } catch (Exception $e) {
            echo "<p style='color:red'>❌ Error updating role: " . $e->getMessage() . "</p>";
        }
    }
} else {
    echo "<p style='color:red'>❌ User not logged in - cannot access technician_breakdowns.php</p>";
}

// Check admin_breakdown_view.php access requirements
echo "<h2>5. admin_breakdown_view.php Access Requirements</h2>";
echo "<h3>What this page typically requires:</h3>";
echo "<ul>";
echo "<li>✅ User must be logged in</li>";
echo "<li>✅ User role should be 'admin'</li>";
echo "<li>✅ ID parameter in URL (e.g., ?id=1)</li>";
echo "</ul>";

// Test ID parameter
echo "<h3>ID Parameter Test:</h3>";
$id = $_GET['id'] ?? $_POST['id'] ?? null;
echo "<p>Current ID parameter: " . ($id ?? 'None') . "</p>";

if (!$id) {
    echo "<p style='color:red'>❌ ID parameter missing - this causes 'ID manquant' error</p>";
    
    // Get a valid ID from breakdown_reports
    try {
        $stmt = $pdo->query("SELECT MIN(id) as id FROM breakdown_reports");
        $valid_id = $stmt->fetch()['id'];
        
        if ($valid_id) {
            echo "<p style='color:green'>✅ Found valid ID: $valid_id</p>";
            echo "<p><a href='?id=$valid_id'>Test with ID $valid_id</a></p>";
        } else {
            echo "<p style='color:orange'>⚠️ No breakdown reports found</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>❌ Error getting valid ID: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:green'>✅ ID parameter present: $id</p>";
    
    // Verify ID exists in breakdown_reports
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM breakdown_reports WHERE id = ?");
        $stmt->execute([$id]);
        $count = $stmt->fetch()['count'];
        
        if ($count > 0) {
            echo "<p style='color:green'>✅ ID $id exists in breakdown_reports</p>";
        } else {
            echo "<p style='color:red'>❌ ID $id does not exist in breakdown_reports</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>❌ Error checking ID: " . $e->getMessage() . "</p>";
    }
}

// Test direct access
echo "<h2>6. Direct Access Test</h2>";
if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in']) {
    echo "<h3>Test Links:</h3>";
    
    // technician_breakdowns.php test
    $tech_link = "technician_breakdowns.php";
    echo "<p><a href='$tech_link' target='_blank' style='background:blue;color:white;padding:5px;'>Test technician_breakdowns.php</a></p>";
    
    // admin_breakdown_view.php test
    if ($id) {
        $admin_link = "admin_breakdown_view.php?id=$id";
        echo "<p><a href='$admin_link' target='_blank' style='background:green;color:white;padding:5px;'>Test admin_breakdown_view.php with ID $id</a></p>";
    } else {
        echo "<p style='color:orange'>⚠️ Cannot test admin_breakdown_view.php - no valid ID</p>";
    }
} else {
    echo "<p style='color:red'>❌ Please login first to test direct access</p>";
}

echo "<hr>";
echo "<h2>🎯 SUMMARY</h2>";
echo "<ul>";
echo "<li><strong>technician_breakdowns.php:</strong> Requires login + technician/admin role</li>";
echo "<li><strong>admin_breakdown_view.php:</strong> Requires login + admin role + ID parameter</li>";
echo "<li><strong>Solution:</strong> Login with admin user and ensure ID parameter is present</li>";
echo "</ul>";

if (isset($_GET['refresh'])) {
    echo "<script>window.location.href='?';</script>";
}
?>

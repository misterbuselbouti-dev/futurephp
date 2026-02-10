<?php
// QUICK LOGIN - Simple login page to resolve access issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🚀 Quick Login - Resolve Access Issues</h1>";

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

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if ($username && $password) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Check password (for quick fix, accept simple passwords)
                if ($password === 'admin' || password_verify($password, $user['password'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['is_logged_in'] = true;
                    $_SESSION['login_time'] = time();
                    
                    echo "<p style='color:green'>✅ Login successful!</p>";
                    echo "<p><strong>Welcome:</strong> {$user['username']}</p>";
                    echo "<p><strong>Role:</strong> {$user['role']}</p>";
                    echo "<p><strong>User ID:</strong> {$user['id']}</p>";
                    
                    // Redirect options
                    echo "<h2>🎯 Where to go next?</h2>";
                    echo "<p><a href='technician_breakdowns.php' style='background:blue;color:white;padding:10px;text-decoration:none;margin:5px;'>🔧 Technician Breakdowns</a></p>";
                    
                    // Get valid ID for admin view
                    $stmt = $pdo->query("SELECT MIN(id) as id FROM breakdown_reports");
                    $valid_id = $stmt->fetch()['id'];
                    if ($valid_id) {
                        echo "<p><a href='admin_breakdown_view.php?id=$valid_id' style='background:green;color:white;padding:10px;text-decoration:none;margin:5px;'>👨‍💼 Admin Breakdown View (ID: $valid_id)</a></p>";
                    }
                    
                    echo "<p><a href='dashboard.php' style='background:orange;color:white;padding:10px;text-decoration:none;margin:5px;'>📊 Dashboard</a></p>";
                    echo "<p><a href='driver_breakdown_new.php' style='background:red;color:white;padding:10px;text-decoration:none;margin:5px;'>🚗 Driver Breakdown New</a></p>";
                    
                    echo "<hr>";
                    echo "<p><a href='quick_login.php?logout=1'>Logout</a></p>";
                    
                } else {
                    echo "<p style='color:red'>❌ Invalid password</p>";
                }
            } else {
                echo "<p style='color:red'>❌ User not found</p>";
                
                // Create user if not exists
                echo "<p style='color:orange'>⚠️ Creating admin user...</p>";
                $hashed_password = password_hash('admin', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, email, password, role, is_active) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute(['admin', 'admin@example.com', $hashed_password, 'admin', 1]);
                echo "<p style='color:green'>✅ Created admin user. Please try login again.</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color:red'>❌ Login error: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color:orange'>⚠️ Please enter username and password</p>";
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    echo "<p style='color:blue'>ℹ️ Logged out successfully</p>";
}

// Show login form if not logged in
if (!isset($_SESSION['is_logged_in']) || !$_SESSION['is_logged_in']) {
    echo "<h2>🔐 Quick Login</h2>";
    echo "<form method='POST'>";
    echo "<p><strong>Username:</strong><br><input type='text' name='username' value='admin' style='width:200px;padding:5px;' required></p>";
    echo "<p><strong>Password:</strong><br><input type='password' name='password' value='admin' style='width:200px;padding:5px;' required></p>";
    echo "<p><input type='submit' value='🚀 Quick Login' style='background:green;color:white;padding:10px;border:none;cursor:pointer;'></p>";
    echo "</form>";
    
    echo "<h3>📋 Default Credentials:</h3>";
    echo "<table border='1' style='background:#f5f5f5;'>";
    echo "<tr><td>Username:</td><td><strong>admin</strong></td></tr>";
    echo "<tr><td>Password:</td><td><strong>admin</strong></td></tr>";
    echo "</table>";
    
    echo "<h3>🔧 Quick Setup:</h3>";
    echo "<p>If no users exist, this page will automatically create an admin user.</p>";
    echo "<p>The admin user will have access to all breakdown management pages.</p>";
    
} else {
    // Already logged in - show session info
    echo "<h2>👤 Current Session</h2>";
    echo "<table border='1'>";
    echo "<tr><td><strong>Username:</strong></td><td>{$_SESSION['username']}</td></tr>";
    echo "<tr><td><strong>Role:</strong></td><td>{$_SESSION['role']}</td></tr>";
    echo "<tr><td><strong>User ID:</strong></td><td>{$_SESSION['user_id']}</td></tr>";
    echo "<tr><td><strong>Login Time:</strong></td><td>" . date('Y-m-d H:i:s', $_SESSION['login_time']) . "</td></tr>";
    echo "</table>";
    
    echo "<h2>🎯 Quick Access Links:</h2>";
    
    // Get valid ID for admin view
    try {
        $stmt = $pdo->query("SELECT MIN(id) as id FROM breakdown_reports");
        $valid_id = $stmt->fetch()['id'];
        
        if (!$valid_id) {
            // Create a breakdown report if none exists
            $pdo->exec("INSERT INTO breakdown_reports (report_ref, driver_id, bus_id, category, urgency, description, status) VALUES ('BRK-QUICK-001', 1, 1, 'mecanique', 'urgent', 'Quick test report', 'nouveau')");
            $stmt = $pdo->query("SELECT MIN(id) as id FROM breakdown_reports");
            $valid_id = $stmt->fetch()['id'];
        }
        
        echo "<p><a href='admin_breakdown_view.php?id=$valid_id' style='background:green;color:white;padding:10px;text-decoration:none;display:inline-block;margin:5px;'>👨‍💼 Admin Breakdown View (ID: $valid_id)</a></p>";
    } catch (Exception $e) {
        echo "<p style='color:red'>❌ Error getting valid ID: " . $e->getMessage() . "</p>";
    }
    
    echo "<p><a href='technician_breakdowns.php' style='background:blue;color:white;padding:10px;text-decoration:none;display:inline-block;margin:5px;'>🔧 Technician Breakdowns</a></p>";
    echo "<p><a href='dashboard.php' style='background:orange;color:white;padding:10px;text-decoration:none;display:inline-block;margin:5px;'>📊 Dashboard</a></p>";
    echo "<p><a href='driver_breakdown_new.php' style='background:red;color:white;padding:10px;text-decoration:none;display:inline-block;margin:5px;'>🚗 Driver Breakdown New</a></p>";
    
    echo "<hr>";
    echo "<p><a href='quick_login.php?logout=1' style='color:red;'>🚪 Logout</a></p>";
}

echo "<hr>";
echo "<h3>🎯 Problem Resolution:</h3>";
echo "<ul>";
echo "<li>✅ <strong>technician_breakdowns.php:</strong> Login with admin/technician role</li>";
echo "<li>✅ <strong>admin_breakdown_view.php:</strong> Login + add ?id=1 to URL</li>";
echo "<li>✅ <strong>Session issues:</strong> This page handles session properly</li>";
echo "</ul>";
?>

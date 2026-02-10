<?php
// FUTURE AUTOMOTIVE - Advanced Login Debug
// Detailed debugging for login issues

echo "<h1>🔍 Advanced Login Debug</h1>";

// Step 1: Test database connection with exact credentials
echo "<h2>📊 Step 1: Database Connection Test</h2>";

$db_host = 'localhost';
$db_name = 'u442210176_Futur2';
$db_user = 'u442210176_Futur2';
$db_pass = '12Abdou12';

echo "🔗 Host: " . $db_host . "<br>";
echo "📁 Database: " . $db_name . "<br>";
echo "👤 User: " . $db_user . "<br>";
echo "🔑 Password: " . str_repeat('*', strlen($db_pass)) . "<br><br>";

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connection successful<br><br>";
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br><br>";
    exit;
}

// Step 2: Check if users table exists
echo "<h2>📋 Step 2: Check Users Table</h2>";

try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $table_exists = $stmt->rowCount() > 0;
    
    if ($table_exists) {
        echo "✅ Users table exists<br>";
        
        // Check table structure
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        foreach ($columns as $col) {
            echo "<tr><td>" . $col['Field'] . "</td><td>" . $col['Type'] . "</td><td>" . $col['Null'] . "</td><td>" . $col['Key'] . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "❌ Users table does not exist<br>";
        exit;
    }
} catch (PDOException $e) {
    echo "❌ Error checking users table: " . $e->getMessage() . "<br>";
}

// Step 3: Check admin user specifically
echo "<h2>👤 Step 3: Check Admin User</h2>";

$email = 'admin@futureautomotive.net';
echo "🔍 Looking for email: " . $email . "<br>";

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✅ User found<br>";
        echo "📊 User data:<br>";
        echo "<pre>";
        print_r($user);
        echo "</pre>";
        
        // Check if user is active
        $is_active = isset($user['is_active']) ? $user['is_active'] : 1;
        echo "🟢 Active status: " . ($is_active ? 'YES' : 'NO') . "<br>";
        
        if (!$is_active) {
            echo "❌ User is not active! Trying to activate...<br>";
            $activate_stmt = $pdo->prepare("UPDATE users SET is_active = 1 WHERE email = ?");
            $activate_stmt->execute([$email]);
            echo "✅ User activated<br>";
        }
        
    } else {
        echo "❌ User not found! Creating user...<br>";
        
        // Create admin user
        $hashed_password = password_hash('Admin1234', PASSWORD_DEFAULT);
        $create_stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, 1, NOW(), NOW())");
        $result = $create_stmt->execute(['Admin User', $email, $hashed_password, 'admin']);
        
        if ($result) {
            echo "✅ Admin user created successfully<br>";
            
            // Get the created user
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            echo "❌ Failed to create admin user<br>";
            exit;
        }
    }
} catch (PDOException $e) {
    echo "❌ Error checking admin user: " . $e->getMessage() . "<br>";
}

// Step 4: Test password verification
echo "<h2>🔐 Step 4: Password Verification Test</h2>";

$password = 'Admin1234';
echo "🔑 Testing password: " . $password . "<br>";

if ($user) {
    $stored_password = $user['password'];
    echo "🔒 Stored password: " . substr($stored_password, 0, 20) . "...<br>";
    
    // Test 1: Direct comparison
    if ($password === $stored_password) {
        echo "✅ Direct comparison: SUCCESS<br>";
    } else {
        echo "❌ Direct comparison: FAILED<br>";
    }
    
    // Test 2: MD5 comparison
    if (md5($password) === $stored_password) {
        echo "✅ MD5 comparison: SUCCESS<br>";
    } else {
        echo "❌ MD5 comparison: FAILED<br>";
    }
    
    // Test 3: password_verify
    if (password_verify($password, $stored_password)) {
        echo "✅ password_verify: SUCCESS<br>";
    } else {
        echo "❌ password_verify: FAILED<br>";
    }
    
    // If all tests fail, update password
    if (!($password === $stored_password) && !md5($password) === $stored_password && !password_verify($password, $stored_password)) {
        echo "🔄 All password tests failed. Updating password...<br>";
        
        $new_hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $update_result = $update_stmt->execute([$new_hashed_password, $email]);
        
        if ($update_result) {
            echo "✅ Password updated successfully<br>";
            
            // Test again
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $user['password'])) {
                echo "✅ New password verification: SUCCESS<br>";
            } else {
                echo "❌ New password verification: FAILED<br>";
            }
        } else {
            echo "❌ Failed to update password<br>";
        }
    }
}

// Step 5: Test login process
echo "<h2>🚪 Step 5: Login Process Test</h2>";

if ($user) {
    session_start();
    
    // Create session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user'] = $user;
    $_SESSION['email'] = $user['email'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];
    
    echo "✅ Session created<br>";
    echo "📊 Session data:<br>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    
    // Test redirect
    echo "🔗 <a href='dashboard.php'>Go to Dashboard</a><br>";
    echo "🔗 <a href='simple_login.php'>Try Simple Login</a><br>";
}

echo "<h2>📋 Summary</h2>";
echo "📧 Email: admin@futureautomotive.net<br>";
echo "🔑 Password: Admin1234<br>";
echo "🟢 Status: User should be active and ready<br>";
echo "🔗 <a href='simple_login.php'>Test Login</a> | <a href='dashboard.php'>Dashboard</a><br>";
?>

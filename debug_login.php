<?php
// FUTURE AUTOMOTIVE - Debug Login
// Simple test page to check database connection and user data

echo "<h1>🔍 Debug Login - Future Automotive</h1>";

// Test database connection
echo "<h2>📊 اختبار الاتصال بقاعدة البيانات</h2>";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=u442210176_Futur2;charset=utf8mb4", "u442210176_Futur2", "12Abdou12");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ الاتصال بقاعدة البيانات ناجح<br>";
} catch (PDOException $e) {
    echo "❌ خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage() . "<br>";
    exit;
}

// Test users table
echo "<h2>👥 اختبار جدول المستخدمين</h2>";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "📊 عدد المستخدمين: " . $count . "<br>";
    
    if ($count > 0) {
        $stmt = $pdo->query("SELECT id, full_name, email, role, is_active, password FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Full Name</th><th>Email</th><th>Role</th><th>Active</th><th>Password Type</th></tr>";
        
        foreach ($users as $user) {
            $password_type = "Unknown";
            if (password_verify('Admin1234', $user['password'])) {
                $password_type = "Hashed (✓)";
            } elseif ($user['password'] === 'Admin1234') {
                $password_type = "Plain Text (✓)";
            } elseif (md5('Admin1234') === $user['password']) {
                $password_type = "MD5 (✓)";
            }
            
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
            echo "<td>" . ($user['is_active'] ? '✅' : '❌') . "</td>";
            echo "<td>" . $password_type . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "❌ خطأ في قراءة جدول المستخدمين: " . $e->getMessage() . "<br>";
}

// Test login with admin user
echo "<h2>🔐 اختبار تسجيل الدخول</h2>";

$email = 'admin@futureautomotive.net';
$password = 'Admin1234';

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✅ تم العثور على المستخدم<br>";
        echo "📧 البريد: " . htmlspecialchars($user['email']) . "<br>";
        echo "👤 الاسم: " . htmlspecialchars($user['full_name']) . "<br>";
        echo "🔑 الدور: " . htmlspecialchars($user['role']) . "<br>";
        
        // Test password
        $password_valid = false;
        $password_method = "";
        
        if (password_verify($password, $user['password'])) {
            $password_valid = true;
            $password_method = "password_verify (Hashed)";
        } elseif ($password === $user['password']) {
            $password_valid = true;
            $password_method = "Direct comparison (Plain text)";
        } elseif (md5($password) === $user['password']) {
            $password_valid = true;
            $password_method = "MD5 comparison";
        }
        
        if ($password_valid) {
            echo "✅ كلمة المرور صحيحة (الطريقة: " . $password_method . ")<br>";
            
            // Test session
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user'] = $user;
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            echo "✅ تم إنشاء الجلسة بنجاح<br>";
            echo "🔗 <a href='dashboard.php'>اذهب إلى لوحة التحكم</a><br>";
        } else {
            echo "❌ كلمة المرور غير صحيحة<br>";
            echo "🔍 كلمة المرور في قاعدة البيانات: " . substr($user['password'], 0, 20) . "...<br>";
        }
    } else {
        echo "❌ لم يتم العثور على المستخدم أو غير نشط<br>";
    }
} catch (PDOException $e) {
    echo "❌ خطأ في اختبار تسجيل الدخول: " . $e->getMessage() . "<br>";
}

// Test password update
echo "<h2>🔄 تحديث كلمة المرور</h2>";

try {
    $new_password = 'Admin1234';
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
    $result = $stmt->execute([$hashed_password, $email]);
    
    if ($result) {
        echo "✅ تم تحديث كلمة المرور بنجاح<br>";
        echo "🔑 كلمة المرور الجديدة: " . $new_password . "<br>";
        echo "🔗 <a href='login_fixed.php'>جرب تسجيل الدخول الآن</a><br>";
    } else {
        echo "❌ فشل تحديث كلمة المرور<br>";
    }
} catch (PDOException $e) {
    echo "❌ خطأ في تحديث كلمة المرور: " . $e->getMessage() . "<br>";
}

echo "<h2>📋 ملخص</h2>";
echo "📧 البريد: admin@futureautomotive.net<br>";
echo "🔑 كلمة المرور: Admin1234<br>";
echo "🔗 <a href='login_fixed.php'>صفحة تسجيل الدخول المصححة</a><br>";
echo "🔗 <a href='dashboard.php'>لوحة التحكم</a><br>";
?>

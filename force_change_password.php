<?php
// FUTURE AUTOMOTIVE - Force Change Admin Password
// This file will force change the admin password

// Include database configuration
$db_host = 'localhost';
$db_name = 'u442210176_Futur2';
$db_user = 'u442210176_Futur2';
$db_pass = '12Abdou12';

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // New password
    $new_password = 'Admin1234';
    
    // Hash the password using PHP's password_hash
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update the admin user password
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
    $result = $stmt->execute([$hashed_password, 'admin@futureautomotive.net']);
    
    if ($result) {
        echo "✅ تم تغيير كلمة المرور بنجاح!";
        echo "<br><br>";
        echo "📧 البريد الإلكتروني: admin@futureautomotive.net";
        echo "<br>";
        echo "🔑 كلمة المرور الجديدة: " . $new_password;
        echo "<br><br>";
        echo "🔗 <a href='login.php'>اضغط هنا لتسجيل الدخول</a>";
        echo "<br><br>";
        echo "⚠️ ملاحظة: احذف هذا الملف بعد الاستخدام لأسباب أمنية!";
        
        // Verify the change
        $verify = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $verify->execute(['admin@futureautomotive.net']);
        $user = $verify->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($new_password, $user['password'])) {
            echo "<br><br>";
            echo "✅ تم التحقق من كلمة المرور بنجاح!";
        } else {
            echo "<br><br>";
            echo "❌ فشل التحقق من كلمة المرور!";
        }
    } else {
        echo "❌ حدث خطأ أثناء تحديث كلمة المرور";
    }
    
} catch (PDOException $e) {
    echo "❌ خطأ في قاعدة البيانات: " . $e->getMessage();
    echo "<br><br>";
    echo "🔧 المحاولة البديلة...";
    
    // Try alternative method
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Try MD5 as fallback
        $md5_password = md5($new_password);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $result = $stmt->execute([$md5_password, 'admin@futureautomotive.net']);
        
        if ($result) {
            echo "<br><br>";
            echo "✅ تم تغيير كلمة المرور باستخدام MD5!";
            echo "<br>";
            echo "📧 البريد الإلكتروني: admin@futureautomotive.net";
            echo "<br>";
            echo "🔑 كلمة المرور الجديدة: " . $new_password;
        }
    } catch (Exception $e2) {
        echo "<br><br>";
        echo "❌ فشلت جميع المحاولات: " . $e2->getMessage();
    }
}
?>

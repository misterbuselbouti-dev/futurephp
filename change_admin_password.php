<?php
// FUTURE AUTOMOTIVE - Change Admin Password
// This file will change the admin password

require_once 'config.php';

try {
    // New password
    $new_password = 'Admin1234';
    
    // Hash the password using PHP's password_hash
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update the admin user password
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
    $result = $stmt->execute([$hashed_password, 'admin@futureautomotive.net']);
    
    if ($result) {
        echo "✅ تم تغيير كلمة المرور بنجاح!";
        echo "<br>";
        echo "📧 البريد الإلكتروني: admin@futureautomotive.net";
        echo "<br>";
        echo "🔑 كلمة المرور الجديدة: " . $new_password;
        echo "<br>";
        echo "<br>";
        echo "🔗 <a href='login.php'>اضغط هنا لتسجيل الدخول</a>";
        echo "<br>";
        echo "<br>";
        echo "⚠️ ملاحظة: احذف هذا الملف بعد الاستخدام لأسباب أمنية!";
    } else {
        echo "❌ حدث خطأ أثناء تحديث كلمة المرور";
    }
    
} catch (PDOException $e) {
    echo "❌ خطأ في قاعدة البيانات: " . $e->getMessage();
}
?>

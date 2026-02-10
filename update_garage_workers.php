<?php
// FUTURE AUTOMOTIVE - Garage Workers Database Update
// تحديث قاعدة البيانات لإضافة العاملين في الكاراج

require_once 'config.php';

try {
    $database = new Database();
    $pdo = $database->connect();
    
    echo "<h2>تحديث قاعدة البيانات للعاملين في الكاراج</h2>";
    
    // 1. تحديث جدول users لإضافة أدوار جديدة
    echo "<h3>1. تحديث أدوار المستخدمين</h3>";
    
    // التحقق من الأدوار الحالية
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
    $roleColumn = $stmt->fetch();
    
    if ($roleColumn) {
        echo "<p>عمود role موجود حالياً</p>";
        
        // تحديث ENUM لإضافة الأدوار الجديدة
        $alterSql = "ALTER TABLE users MODIFY COLUMN role ENUM(
            'admin', 'mecanicien', 'electricien', 'tolier', 'peintre', 
            'chef_atelier', 'receptionniste', 'technician', 'agent', 'maintenance_manager'
        ) DEFAULT 'mecanicien'";
        
        try {
            $pdo->exec($alterSql);
            echo "<p style='color: green;'>✅ تم تحديث أدوار المستخدمين بنجاح</p>";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ قد تكون الأدوار موجودة مسبقاً: " . $e->getMessage() . "</p>";
        }
    }
    
    // 2. إنشاء جدول التخصصات
    echo "<h3>2. إنشاء جدول التخصصات</h3>";
    
    $createSpecialtiesTable = "
        CREATE TABLE IF NOT EXISTS garage_specialties (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE,
            name_fr VARCHAR(50) NOT NULL UNIQUE,
            description TEXT,
            color VARCHAR(7) DEFAULT '#007bff',
            icon VARCHAR(50) DEFAULT 'fa-wrench',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";
    
    $pdo->exec($createSpecialtiesTable);
    echo "<p style='color: green;'>✅ تم إنشاء جدول التخصصات بنجاح</p>";
    
    // 3. إدخال التخصصات
    echo "<h3>3. إدخال التخصصات</h3>";
    
    $specialties = [
        ['mecanicien', 'Mécanicien', 'Réparation mécanique générale', '#28a745', 'fa-wrench'],
        ['electricien', 'Électricien', 'Systèmes électriques et électroniques', '#ffc107', 'fa-bolt'],
        ['tolier', 'Tôlier', 'Carrosserie et châssis', '#17a2b8', 'fa-hammer'],
        ['peintre', 'Peintre', 'Peinture et finition', '#dc3545', 'fa-paint-brush'],
        ['chef_atelier', 'Chef d\'Atelier', 'Supervision et coordination', '#6f42c1', 'fa-user-tie']
    ];
    
    foreach ($specialties as $specialty) {
        $insertSql = "INSERT IGNORE INTO garage_specialties (name, name_fr, description, color, icon) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($insertSql);
        $stmt->execute($specialty);
        echo "<p style='color: green;'>✅ تم إضافة التخصص: {$specialty[1]}</p>";
    }
    
    // 4. تحديث المستخدمين الحاليين لإضافة تخصصات
    echo "<h3>4. تحديث المستخدمين الحاليين</h3>";
    
    // جلب المستخدمين الحاليين
    $stmt = $pdo->query("SELECT id, role FROM users");
    $users = $stmt->fetchAll();
    
    foreach ($users as $user) {
        // تحديث المستخدمين الذين لديهم دور ميكانيكي ليكونوا أكثر تحديداً
        if ($user['role'] === 'mecanicien') {
            echo "<p>المستخدم {$user['id']} بالفعل ميكانيكي ✅</p>";
        }
    }
    
    // 5. إضافة عمال نموذجيين إذا كان الجدول فارغاً
    echo "<h3>5. إضافة عمال نموذجيين</h3>";
    
    $countSql = "SELECT COUNT(*) as count FROM users WHERE role IN ('electricien', 'tolier', 'peintre', 'chef_atelier')";
    $stmt = $pdo->query($countSql);
    $specializedCount = $stmt->fetch()['count'];
    
    if ($specializedCount == 0) {
        $sampleWorkers = [
            ['electricien1', '123456', 'Ahmed Électricien', 'electricien', '0661234567'],
            ['tolier1', '123456', 'Mohammed Tôlier', 'tolier', '0662345678'],
            ['peintre1', '123456', 'Youssef Peintre', 'peintre', '0663456789'],
            ['chef_atelier1', '123456', 'Karim Chef Atelier', 'chef_atelier', '0664567890']
        ];
        
        foreach ($sampleWorkers as $worker) {
            $insertSql = "INSERT IGNORE INTO users (username, password, full_name, role, phone, is_active) VALUES (?, ?, ?, ?, ?, 1)";
            $stmt = $pdo->prepare($insertSql);
            $stmt->execute($worker);
            echo "<p style='color: green;'>✅ تم إضافة عامل: {$worker[2]}</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ️ يوجد بالفعل $specializedCount عامل متخصصون</p>";
    }
    
    echo "<h3 style='color: green;'>🎉 اكتمل تحديث قاعدة البيانات بنجاح!</h3>";
    
    // عرض التخصصات المتاحة
    echo "<h3>التخصصات المتاحة:</h3>";
    $stmt = $pdo->query("SELECT * FROM garage_specialties ORDER BY name");
    $specialties = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>التخصص</th><th>الاسم الفرنسي</th><th>الوصف</th><th>اللون</th></tr>";
    
    foreach ($specialties as $specialty) {
        echo "<tr>";
        echo "<td>{$specialty['name']}</td>";
        echo "<td>{$specialty['name_fr']}</td>";
        echo "<td>{$specialty['description']}</td>";
        echo "<td><span style='color: {$specialty['color']};'>●</span> {$specialty['color']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ حدث خطأ:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>

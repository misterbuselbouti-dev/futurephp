<?php
// FUTURE AUTOMOTIVE - Add ICE and RC columns to suppliers table
// إضافة أعمدة ICE و RC لجدول الموردين

echo "<h2>🔧 إضافة أعمدة ICE و RC لجدول الموردين</h2>";

try {
    $database_achat = new DatabaseAchat();
    $pdo = $database_achat->connect();
    
    echo "<h3>1. التحقق من وجود الأعمدة الحالية</h3>";
    
    // Check current columns
    $stmt = $pdo->query("SHOW COLUMNS FROM suppliers");
    $current_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p>الأعمدة الحالية: " . implode(', ', $current_columns) . "</p>";
    
    $has_ice = in_array('ice', $current_columns);
    $has_rc = in_array('rc', $current_columns);
    
    echo "<p>ICE موجود: " . ($has_ice ? '✅ نعم' : '❌ غير موجود') . "</p>";
    echo "<p>RC موجود: " . ($has_rc ? '✅ نعم' : '❌ غير موجود') . "</p>";
    
    // Add ICE column if not exists
    if (!$has_ice) {
        echo "<h3>2. إضافة عمود ICE</h3>";
        try {
            $pdo->exec("ALTER TABLE suppliers ADD COLUMN ice VARCHAR(30) NULL COMMENT 'Numéro ICE'");
            echo "<p style='color: green;'>✅ تم إضافة عمود ICE بنجاح</p>";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ قد يكون العمود موجود بالفعل: " . $e->getMessage() . "</p>";
        }
    }
    
    // Add RC column if not exists
    if (!$has_rc) {
        echo "<h3>3. إضافة عمود RC</h3>";
        try {
            $pdo->exec("ALTER TABLE suppliers ADD COLUMN rc VARCHAR(30) NULL COMMENT 'Numéro RC'");
            echo "<p style='color: green;'>✅ تم إضافة عمود RC بنجاح</p>";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ قد يكون العمود موجود بالفعل: " . $e->getMessage() . "</p>";
        }
    }
    
    // Add indexes for uniqueness
    echo "<h3>4. إضافة فهارس فريدة</h3>";
    
    try {
        if (!$has_ice) {
            $pdo->exec("CREATE UNIQUE INDEX idx_suppliers_ice ON suppliers(ice)");
            echo "<p style='color: green;'>✅ تم إضافة فهرس فريد لـ ICE</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ قد يكون الفهرس موجود بالفعل: " . $e->getMessage() . "</p>";
    }
    
    try {
        if (!$has_rc) {
            $pdo->exec("CREATE UNIQUE INDEX idx_suppliers_rc ON suppliers(rc)");
            echo "<p style='color: green;'>✅ تم إضافة فهرس فريد لـ RC</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ قد يكون الفهرس موجود بالفعل: " . $e->getMessage() . "</p>";
    }
    
    // Show updated table structure
    echo "<h3>5. هيكل الجدول المحدث</h3>";
    $stmt = $pdo->query("SHOW COLUMNS FROM suppliers");
    $updated_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>الأعمدة المحدثة: " . implode(', ', $updated_columns) . "</p>";
    
    // Show sample data
    echo "<h3>6. بيانات الموردين الحاليين</h3>";
    $stmt = $pdo->query("SELECT * FROM suppliers ORDER BY nom_fournisseur LIMIT 5");
    $suppliers = $stmt->fetchAll();
    
    if (!empty($suppliers)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        foreach ($updated_columns as $col) {
            echo "<th>" . htmlspecialchars($col) . "</th>";
        }
        echo "</tr>";
        
        foreach ($suppliers as $supplier) {
            echo "<tr>";
            foreach ($updated_columns as $col) {
                echo "<td>" . htmlspecialchars($supplier[$col] ?? '-') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>لا توجد بيانات حالياً</p>";
    }
    
    echo "<h3 style='color: green;'>🎉 اكتمل تحديث هيكل الجدول بنجاح!</h3>";
    echo "<div class='alert alert-success'>";
    echo "<strong>✅ تم بنجاح:</strong><br>";
    echo "• إضافة أعمدة ICE (15 رقم)<br>";
    echo "• إضافة عمود RC (15 رقم)<br>";
    echo "• فهارس فريدة لمنع التكرار<br>";
    echo "• تحديث هيكل الجدول<br>";
    echo "• التوافق مع التيم البسيط";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ حدث خطأ:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
?>

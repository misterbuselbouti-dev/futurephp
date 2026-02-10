<?php
// FUTURE AUTOMOTIVE - Archive System Database Setup
// إعداد نظام الأرشيف الذكي للمعاملات الشرائية

require_once 'config.php';
require_once 'config_achat_hostinger.php';

echo "<h2>🗄️ إعداد نظام الأرشيف الذكي</h2>";

try {
    // الاتصال بقاعدتي البيانات
    $database = new Database();
    $pdo_main = $database->connect();
    
    $database_achat = new DatabaseAchat();
    $pdo_achat = $database_achat->connect();
    
    echo "<h3>1. التحقق من الاتصال بقواعد البيانات</h3>";
    echo "<p style='color: green;'>✅ الاتصال بقاعدة البيانات الرئيسية: نجح</p>";
    echo "<p style='color: green;'>✅ الاتصال بقاعدة بيانات المشتريات: نجح</p>";
    
    // إنشاء جداول الأرشيف
    echo "<h3>2. إنشاء جداول الأرشيف</h3>";
    
    // جدول الملخصات الشهرية
    $createMonthlySummary = "
        CREATE TABLE IF NOT EXISTS monthly_transactions_summary (
            id INT AUTO_INCREMENT PRIMARY KEY,
            year_month VARCHAR(7) NOT NULL COMMENT 'Format: YYYY-MM',
            supplier_id INT,
            da_count INT DEFAULT 0,
            dp_count INT DEFAULT 0,
            bc_count INT DEFAULT 0,
            be_count INT DEFAULT 0,
            total_amount DECIMAL(12,2) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_year_month (year_month),
            INDEX idx_supplier_month (supplier_id, year_month),
            INDEX idx_year_supplier (year_month, supplier_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo_achat->exec($createMonthlySummary);
    echo "<p style='color: green;'>✅ تم إنشاء جدول الملخصات الشهرية</p>";
    
    // جدول الأرشيف الرئيسي
    $createArchiveTable = "
        CREATE TABLE IF NOT EXISTS transaction_archive (
            id INT AUTO_INCREMENT PRIMARY KEY,
            transaction_type ENUM('DA', 'DP', 'BC', 'BE') NOT NULL,
            transaction_id INT NOT NULL,
            reference VARCHAR(50) NOT NULL,
            supplier_id INT,
            amount DECIMAL(12,2) DEFAULT 0,
            transaction_date DATE NOT NULL,
            year_month VARCHAR(7) NOT NULL COMMENT 'Format: YYYY-MM',
            status VARCHAR(50) DEFAULT 'archived',
            archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            archived_by VARCHAR(100),
            notes TEXT,
            INDEX idx_type_month (transaction_type, year_month),
            INDEX idx_supplier_month (supplier_id, year_month),
            INDEX idx_reference (reference),
            INDEX idx_transaction (transaction_type, transaction_id),
            INDEX idx_date (transaction_date),
            UNIQUE KEY unique_transaction (transaction_type, transaction_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo_achat->exec($createArchiveTable);
    echo "<p style='color: green;'>✅ تم إنشاء جدول الأرشيف الرئيسي</p>";
    
    // جدول إعدادات الأرشفة
    $createSettingsTable = "
        CREATE TABLE IF NOT EXISTS archive_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT NOT NULL,
            description TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo_achat->exec($createSettingsTable);
    echo "<p style='color: green;'>✅ تم إنشاء جدول إعدادات الأرشفة</p>";
    
    // إدخال الإعدادات الافتراضية
    $defaultSettings = [
        'auto_archive_enabled' => 'true',
        'auto_archive_months' => '6',
        'archive_retention_years' => '5',
        'enable_compression' => 'true',
        'last_archive_date' => null,
        'archive_batch_size' => '1000'
    ];
    
    foreach ($defaultSettings as $key => $value) {
        $insertSetting = "INSERT IGNORE INTO archive_settings (setting_key, setting_value, description) VALUES (?, ?, ?)";
        $stmt = $pdo_achat->prepare($insertSetting);
        $descriptions = [
            'auto_archive_enabled' => 'تفعيل الأرشفة التلقائية',
            'auto_archive_months' => 'عدد الأشهر للأرشفة التلقائية',
            'archive_retention_years' => 'فترة الاحتفاظ بالأرشيف بالسنوات',
            'enable_compression' => 'تفعيل ضغط البيانات',
            'last_archive_date' => 'تاريخ آخر عملية أرشفة',
            'archive_batch_size' => 'حجم الدفعة للأرشفة'
        ];
        $stmt->execute([$key, $value, $descriptions[$key]]);
    }
    echo "<p style='color: green;'>✅ تم إدخال الإعدادات الافتراضية</p>";
    
    echo "<h3>3. تحسين الجداول الحالية</h3>";
    
    // التحقق من وجود أعمدة الشهر في الجداول
    $tablesToCheck = [
        'demandes_achat' => 'date_creation',
        'demandes_prix' => 'date_envoi',
        'bons_commande' => 'date_commande',
        'bons_entree' => 'reception_date'
    ];
    
    foreach ($tablesToCheck as $table => $dateColumn) {
        try {
            // التحقق من وجود عمود year_month
            $checkColumn = $pdo_achat->query("SHOW COLUMNS FROM $table LIKE 'year_month'")->fetch();
            
            if (!$checkColumn) {
                // إضافة عمود year_month
                $addColumn = "ALTER TABLE $table ADD COLUMN year_month VARCHAR(7) GENERATED ALWAYS AS (DATE_FORMAT($dateColumn, '%Y-%m')) STORED";
                $pdo_achat->exec($addColumn);
                echo "<p style='color: green;'>✅ تم إضافة عمود year_month لجدول $table</p>";
            } else {
                echo "<p style='color: blue;'>ℹ️ عمود year_month موجود بالفعل في جدول $table</p>";
            }
            
            // التحقق من وجود عمود is_archived
            $checkArchiveColumn = $pdo_achat->query("SHOW COLUMNS FROM $table LIKE 'is_archived'")->fetch();
            
            if (!$checkArchiveColumn) {
                // إضافة عمود is_archived
                $addArchiveColumn = "ALTER TABLE $table ADD COLUMN is_archived BOOLEAN DEFAULT FALSE";
                $pdo_achat->exec($addArchiveColumn);
                echo "<p style='color: green;'>✅ تم إضافة عمود is_archived لجدول $table</p>";
            } else {
                echo "<p style='color: blue;'>ℹ️ عمود is_archived موجود بالفعل في جدول $table</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ خطأ في تحديث جدول $table: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h3>4. إنشاء الفهارس المحسنة</h3>";
    
    // فهارس مركبة للبحث السريع
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_da_composite ON demandes_achat(year_month, statut, is_archived)",
        "CREATE INDEX IF NOT EXISTS idx_dp_composite ON demandes_prix(year_month, statut, is_archived, fournisseur_id)",
        "CREATE INDEX IF NOT EXISTS idx_bc_composite ON bons_commande(year_month, statut, is_archived, dp_id)",
        "CREATE INDEX IF NOT EXISTS idx_be_composite ON bons_entree(year_month, statut, is_archived, bc_id)",
        "CREATE INDEX IF NOT EXISTS idx_da_supplier ON demandes_achat(year_month, demandeur)",
        "CREATE INDEX IF NOT EXISTS idx_dp_supplier ON demandes_prix(year_month, fournisseur_id)"
    ];
    
    foreach ($indexes as $index) {
        try {
            $pdo_achat->exec($index);
            echo "<p style='color: green;'>✅ تم إنشاء فهرس محسن</p>";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ قد يكون الفهرس موجوداً: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h3>5. إنشاء إجراءات الأرشفة</h3>";
    
    // إنشاء إجراء الأرشفة الشهرية
    $createArchiveProcedure = "
        DELIMITER $$
        CREATE PROCEDURE IF NOT EXISTS ArchiveMonthlyTransactions(IN target_year_month VARCHAR(7))
        BEGIN
            DECLARE done INT DEFAULT FALSE;
            DECLARE transaction_count INT DEFAULT 0;
            
            -- أرشفة طلبات الشراء (DA)
            INSERT INTO transaction_archive (transaction_type, transaction_id, reference, amount, transaction_date, year_month, archived_by)
            SELECT 'DA', da.id, da.ref_da, 
                   COALESCE(SUM(pi.total_ligne), 0) as amount,
                   da.date_creation, da.year_month, 'system'
            FROM demandes_achat da
            LEFT JOIN purchase_items pi ON da.id = pi.parent_id AND pi.parent_type = 'DA'
            WHERE da.year_month = target_year_month AND da.is_archived = FALSE
            GROUP BY da.id
            ON DUPLICATE KEY UPDATE 
                amount = VALUES(amount),
                archived_at = CURRENT_TIMESTAMP;
            
            SET transaction_count = transaction_count + ROW_COUNT();
            
            -- تحديث حالة الأرشفة لـ DA
            UPDATE demandes_achat SET is_archived = TRUE 
            WHERE year_month = target_year_month AND is_archived = FALSE;
            
            -- أرشفة طلبات الأسعار (DP)
            INSERT INTO transaction_archive (transaction_type, transaction_id, reference, amount, transaction_date, year_month, archived_by)
            SELECT 'DP', dp.id, dp.ref_dp,
                   COALESCE(SUM(pi.total_ligne), 0) as amount,
                   dp.date_envoi, dp.year_month, 'system'
            FROM demandes_prix dp
            LEFT JOIN purchase_items pi ON dp.id = pi.parent_id AND pi.parent_type = 'DP'
            WHERE dp.year_month = target_year_month AND dp.is_archived = FALSE
            GROUP BY dp.id
            ON DUPLICATE KEY UPDATE 
                amount = VALUES(amount),
                archived_at = CURRENT_TIMESTAMP;
            
            SET transaction_count = transaction_count + ROW_COUNT();
            
            -- تحديث حالة الأرشفة لـ DP
            UPDATE demandes_prix SET is_archived = TRUE 
            WHERE year_month = target_year_month AND is_archived = FALSE;
            
            -- أرشفة أوامر الشراء (BC)
            INSERT INTO transaction_archive (transaction_type, transaction_id, reference, amount, transaction_date, year_month, archived_by)
            SELECT 'BC', bc.id, bc.ref_bc, bc.total_ttc, bc.date_commande, bc.year_month, 'system'
            FROM bons_commande bc
            WHERE bc.year_month = target_year_month AND bc.is_archived = FALSE
            ON DUPLICATE KEY UPDATE 
                amount = VALUES(amount),
                archived_at = CURRENT_TIMESTAMP;
            
            SET transaction_count = transaction_count + ROW_COUNT();
            
            -- تحديث حالة الأرشفة لـ BC
            UPDATE bons_commande SET is_archived = TRUE 
            WHERE year_month = target_year_month AND is_archived = FALSE;
            
            -- أرشفة إيصالات الاستلام (BE)
            INSERT INTO transaction_archive (transaction_type, transaction_id, reference, amount, transaction_date, year_month, archived_by)
            SELECT 'BE', be.id, be.ref_be,
                   COALESCE(SUM(bei.quantite_recue * bei.unit_price), 0) as amount,
                   be.reception_date, be.year_month, 'system'
            FROM bons_entree be
            LEFT JOIN be_items bei ON be.id = bei.be_id
            WHERE be.year_month = target_year_month AND be.is_archived = FALSE
            GROUP BY be.id
            ON DUPLICATE KEY UPDATE 
                amount = VALUES(amount),
                archived_at = CURRENT_TIMESTAMP;
            
            SET transaction_count = transaction_count + ROW_COUNT();
            
            -- تحديث حالة الأرشفة لـ BE
            UPDATE bons_entree SET is_archived = TRUE 
            WHERE year_month = target_year_month AND is_archived = FALSE;
            
            -- تحديث الملخص الشهري
            INSERT INTO monthly_transactions_summary (year_month, supplier_id, da_count, dp_count, bc_count, be_count, total_amount)
            SELECT target_year_month, s.id,
                   COUNT(DISTINCT da.id) as da_count,
                   COUNT(DISTINCT dp.id) as dp_count,
                   COUNT(DISTINCT bc.id) as bc_count,
                   COUNT(DISTINCT be.id) as be_count,
                   COALESCE(SUM(bc.total_ttc), 0) + COALESCE(SUM(bei.quantite_recue * bei.unit_price), 0) as total_amount
            FROM suppliers s
            LEFT JOIN demandes_prix dp ON s.id = dp.fournisseur_id AND dp.year_month = target_year_month
            LEFT JOIN demandes_achat da ON dp.da_id = da.id
            LEFT JOIN bons_commande bc ON dp.id = bc.dp_id
            LEFT JOIN bons_entree be ON bc.id = be.bc_id
            LEFT JOIN be_items bei ON be.id = bei.be_id
            WHERE dp.year_month = target_year_month OR bc.year_month = target_year_month OR be.year_month = target_year_month
            GROUP BY s.id
            ON DUPLICATE KEY UPDATE
                da_count = VALUES(da_count),
                dp_count = VALUES(dp_count),
                bc_count = VALUES(bc_count),
                be_count = VALUES(be_count),
                total_amount = VALUES(total_amount),
                updated_at = CURRENT_TIMESTAMP;
            
            SELECT transaction_count as archived_count;
        END$$
        DELIMITER ;
    ";
    
    try {
        $pdo_achat->exec($createArchiveProcedure);
        echo "<p style='color: green;'>✅ تم إنشاء إجراء الأرشفة الشهرية</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ قد يكون الإجراء موجوداً: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>6. اختبار النظام</h3>";
    
    // اختبار استعلام بسيط
    $testQuery = $pdo_achat->query("SELECT COUNT(*) as total FROM monthly_transactions_summary");
    $result = $testQuery->fetch();
    echo "<p style='color: green;'>✅ اختبار جدول الملخصات: {$result['total']} سجل</p>";
    
    $testArchive = $pdo_achat->query("SELECT COUNT(*) as total FROM transaction_archive");
    $archiveResult = $testArchive->fetch();
    echo "<p style='color: green;'>✅ اختبار جدول الأرشيف: {$archiveResult['total']} سجل</p>";
    
    echo "<h3 style='color: green;'>🎉 اكتمل إعداد نظام الأرشيف بنجاح!</h3>";
    
    echo "<div class='alert alert-info'>";
    echo "<h4>الخطوات التالية:</h4>";
    echo "<ul>";
    echo "<li>1. تشغيل أرشفة البيانات التاريخية</li>";
    echo "<li>2. إنشاء واجهات الأرشيف</li>";
    echo "<li>3. إعداد الأرشفة التلقائية</li>";
    echo "<li>4. إنشاء التقارير والإحصائيات</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ حدث خطأ:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
?>

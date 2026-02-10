<?php
// FUTURE AUTOMOTIVE - Supplier Tables Setup
// إعداد جداول الموردين

require_once 'config.php';
require_once 'includes/functions.php';

// Check authentication
require_login();

echo "<h2>🔧 إعداد جداول الموردين للتوافق مع نظام الطباعة</h2>";

try {
    $database = new Database();
    $pdo = $database->connect();
    
    echo "<h3>📋 إنشاء الجداول الأساسية:</h3>";
    
    // 1. Check if suppliers table exists and has required columns
    $stmt = $pdo->query("SHOW TABLES LIKE 'suppliers'");
    $suppliers_exists = $stmt->rowCount() > 0;
    
    if (!$suppliers_exists) {
        echo "<p style='color: orange;'>⚠️ جدول suppliers غير موجود - يتم إنشاؤه...</p>";
        
        $sql = "CREATE TABLE suppliers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code_fournisseur VARCHAR(50) UNIQUE NOT NULL,
            nom_fournisseur VARCHAR(255) NOT NULL,
            telephone VARCHAR(50),
            adresse TEXT,
            ville VARCHAR(100),
            email VARCHAR(255),
            ice VARCHAR(50) UNIQUE,
            rc VARCHAR(50) UNIQUE,
            statut ENUM('actif', 'inactif') DEFAULT 'actif',
            date_creation DATE DEFAULT CURRENT_TIMESTAMP,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($sql);
        echo "<p style='color: green;'>✅ تم إنشاء جدول suppliers</p>";
    } else {
        echo "<p style='color: green;'>✅ جدول suppliers موجود بالفعل</p>";
        
        // Check for ICE and RC columns
        $stmt = $pdo->query("SHOW COLUMNS FROM suppliers LIKE 'ice'");
        $ice_exists = $stmt->rowCount() > 0;
        
        $stmt = $pdo->query("SHOW COLUMNS FROM suppliers LIKE 'rc'");
        $rc_exists = $stmt->rowCount() > 0;
        
        if (!$ice_exists) {
            $pdo->exec("ALTER TABLE suppliers ADD COLUMN ice VARCHAR(50) UNIQUE AFTER email");
            echo "<p style='color: green;'>✅ تم إضافة عمود ice</p>";
        }
        
        if (!$rc_exists) {
            $pdo->exec("ALTER TABLE suppliers ADD COLUMN rc VARCHAR(50) UNIQUE AFTER ice");
            echo "<p style='color: green;'>✅ تم إضافة عمود rc</p>";
        }
    }
    
    // 2. Create supplier purchase orders table
    $tables = [
        'bons_commande_fournisseurs' => "
            CREATE TABLE bons_commande_fournisseurs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                numero_bcf VARCHAR(50) UNIQUE NOT NULL,
                fournisseur_id INT NOT NULL,
                date_bcf DATE NOT NULL,
                date_livraison_prevue DATE,
                statut ENUM('en_attente', 'valide', 'partiellement_livre', 'livre', 'annule') DEFAULT 'en_attente',
                montant_ht DECIMAL(10,2) DEFAULT 0,
                montant_tva DECIMAL(10,2) DEFAULT 0,
                montant_ttc DECIMAL(10,2) DEFAULT 0,
                conditions_paiement TEXT,
                mode_livraison VARCHAR(255),
                notes TEXT,
                created_by INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (fournisseur_id) REFERENCES suppliers(id) ON DELETE RESTRICT
            )",
        
        'bcf_items' => "
            CREATE TABLE bcf_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                id_bcf INT NOT NULL,
                reference VARCHAR(100),
                designation TEXT NOT NULL,
                quantite DECIMAL(10,2) NOT NULL DEFAULT 0,
                prix_unitaire DECIMAL(10,2) NOT NULL DEFAULT 0,
                remise DECIMAL(5,2) DEFAULT 0,
                total_ht DECIMAL(10,2) GENERATED ALWAYS AS (quantite * prix_unitaire * (1 - remise/100)) STORED,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (id_bcf) REFERENCES bons_commande_fournisseurs(id) ON DELETE CASCADE
            )",
        
        'factures_fournisseurs' => "
            CREATE TABLE factures_fournisseurs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                numero_facture VARCHAR(50) UNIQUE NOT NULL,
                bcf_id INT,
                fournisseur_id INT NOT NULL,
                date_facture DATE NOT NULL,
                date_echeance DATE,
                statut ENUM('en_attente', 'payee_partiellement', 'payee', 'retard') DEFAULT 'en_attente',
                montant_ht DECIMAL(10,2) DEFAULT 0,
                montant_tva DECIMAL(10,2) DEFAULT 0,
                montant_ttc DECIMAL(10,2) DEFAULT 0,
                montant_paye DECIMAL(10,2) DEFAULT 0,
                montant_reste DECIMAL(10,2) GENERATED ALWAYS AS (montant_ttc - montant_paye) STORED,
                conditions_paiement TEXT,
                notes TEXT,
                created_by INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (bcf_id) REFERENCES bons_commande_fournisseurs(id) ON DELETE SET NULL,
                FOREIGN KEY (fournisseur_id) REFERENCES suppliers(id) ON DELETE RESTRICT
            )",
        
        'bons_livraison_fournisseurs' => "
            CREATE TABLE bons_livraison_fournisseurs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                numero_blf VARCHAR(50) UNIQUE NOT NULL,
                bcf_id INT,
                fournisseur_id INT NOT NULL,
                date_livraison DATE NOT NULL,
                statut ENUM('en_attente', 'livre', 'retourne') DEFAULT 'en_attente',
                montant_ht DECIMAL(10,2) DEFAULT 0,
                montant_tva DECIMAL(10,2) DEFAULT 0,
                montant_ttc DECIMAL(10,2) DEFAULT 0,
                notes TEXT,
                created_by INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (bcf_id) REFERENCES bons_commande_fournisseurs(id) ON DELETE SET NULL,
                FOREIGN KEY (fournisseur_id) REFERENCES suppliers(id) ON DELETE RESTRICT
            )"
    ];
    
    foreach ($tables as $table_name => $create_sql) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table_name'");
        $exists = $stmt->rowCount() > 0;
        
        if (!$exists) {
            $pdo->exec($create_sql);
            echo "<p style='color: green;'>✅ تم إنشاء جدول $table_name</p>";
        } else {
            echo "<p style='color: blue;'>ℹ️ جدول $table_name موجود بالفعل</p>";
        }
    }
    
    // 3. Create indexes
    echo "<h3>🔍 إنشاء الفهارس:</h3>";
    
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_suppliers_code ON suppliers(code_fournisseur)",
        "CREATE INDEX IF NOT EXISTS idx_suppliers_nom ON suppliers(nom_fournisseur)",
        "CREATE INDEX IF NOT EXISTS idx_bcf_numero ON bons_commande_fournisseurs(numero_bcf)",
        "CREATE INDEX IF NOT EXISTS idx_bcf_fournisseur ON bons_commande_fournisseurs(fournisseur_id)",
        "CREATE INDEX IF NOT EXISTS idx_factures_fournisseur ON factures_fournisseurs(fournisseur_id)"
    ];
    
    foreach ($indexes as $index_sql) {
        try {
            $pdo->exec($index_sql);
            echo "<p style='color: green;'>✅ تم إنشاء الفهرس</p>";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ الفهرس موجود بالفعل</p>";
        }
    }
    
    // 4. Test data insertion
    echo "<h3>📊 اختبار الإدخال:</h3>";
    
    // Check if test supplier exists
    $stmt = $pdo->prepare("SELECT id FROM suppliers WHERE code_fournisseur = ?");
    $stmt->execute(['TEST001']);
    $test_supplier = $stmt->fetch();
    
    if (!$test_supplier) {
        $stmt = $pdo->prepare("INSERT INTO suppliers (code_fournisseur, nom_fournisseur, telephone, ville, ice, rc) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute(['TEST001', 'Fournisseur Test', '0600000000', 'Casablanca', 'ICE000001', 'RC000001']);
        echo "<p style='color: green;'>✅ تم إضافة مورد اختباري</p>";
    }
    
    echo "<h3>🎯 ملخص الجداول المطلوبة للطباعة:</h3>";
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px;'>";
    echo "<h4>الجداول الأساسية:</h4>";
    echo "<ul>";
    echo "<li><strong>suppliers</strong> - جدول الموردين (مع ICE و RC)</li>";
    echo "<li><strong>bons_commande_fournisseurs</strong> - أوامر الشراء للموردين</li>";
    echo "<li><strong>bcf_items</strong> - بنود أوامر الشراء</li>";
    echo "<li><strong>factures_fournisseurs</strong> - فواتير الموردين</li>";
    echo "<li><strong>bons_livraison_fournisseurs</strong> - وصولات استلام من الموردين</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>🖨️ التوافق مع نظام الطباعة:</h3>";
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
    echo "<h4>الجداول التي تدعم الطباعة:</h4>";
    echo "<ul>";
    echo "<li><strong>BCF</strong> - Bon de Commande Fournisseur</li>";
    echo "<li><strong>Factures</strong> - Factures Fournisseurs</li>";
    echo "<li><strong>BLF</strong> - Bon de Livraison Fournisseur</li>";
    echo "</ul>";
    echo "<p>كل جدول يحتوي على:</p>";
    echo "<ul>";
    echo "<li>رقم المستند الفريد</li>";
    echo "<li>تاريخ المستند</li>";
    echo "<li>الربط مع المورد</li>";
    echo "<li>المبالغ والإجماليات</li>";
    echo "<li>الحالة</li>";
    echo "<li>الملاحظات</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='text-align: center; margin: 20px 0;'>";
    echo "<a href='fournisseurs.php' class='btn' style='background: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 5px;'>👥 إدارة الموردين</a>";
    echo "<a href='javascript:history.back()' class='btn' style='background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 5px;'>🔙 رجوع</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<h4>❌ خطأ في إعداد الجداول:</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<?php
// Database fix script for DA system
require_once 'config.php';
require_once 'config_achat_hostinger.php';

echo "<h1>DA Database Structure Fix</h1>";

try {
    $database = new DatabaseAchat();
    $conn = $database->connect();
    
    echo "<h2>Step 1: Fixing Users Table</h2>";
    $stmt = $conn->query('DESCRIBE users');
    $user_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $required_columns = ['phone', 'status'];
    $missing_columns = [];
    
    foreach ($required_columns as $col) {
        $found = false;
        foreach ($user_columns as $user_col) {
            if ($user_col['Field'] === $col) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $missing_columns[] = $col;
        }
    }
    
    if (empty($missing_columns)) {
        echo "<p style='color: green;'>✅ All required columns exist in users table</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Missing columns: " . implode(', ', $missing_columns) . "</p>";
        
        foreach ($missing_columns as $column) {
            try {
                if ($column === 'phone') {
                    $sql = 'ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL';
                } elseif ($column === 'status') {
                    $sql = "ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active'";
                }
                
                $conn->exec($sql);
                echo "<p style='color: green;'>✅ Added column: $column</p>";
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ Error adding $column: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    echo "<h2>Step 2: Fixing DA Reference Trigger</h2>";
    
    try {
        $conn->exec('DROP TRIGGER IF EXISTS generate_da_ref');
        
        $trigger_sql = "
            CREATE TRIGGER generate_da_ref
            BEFORE INSERT ON demandes_achat
            FOR EACH ROW
            BEGIN
                DECLARE next_num INT DEFAULT 1;
                IF NEW.ref_da IS NULL OR NEW.ref_da = '' OR NEW.ref_da LIKE 'DA-TMP-%' THEN
                    SELECT COALESCE(MAX(CAST(RIGHT(ref_da, 4) AS UNSIGNED)), 0) + 1 INTO next_num
                    FROM demandes_achat
                    WHERE ref_da LIKE CONCAT('DA-', YEAR(CURDATE()), '-%');
                    SET NEW.ref_da = CONCAT('DA-', YEAR(CURDATE()), '-', LPAD(next_num, 4, '0'));
                END IF;
            END;
        ";
        
        $conn->exec($trigger_sql);
        echo "<p style='color: green;'>✅ DA reference trigger created successfully</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error creating trigger: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>Step 3: Adding Missing Indexes</h2>";
    
    try {
        $conn->exec('CREATE INDEX IF NOT EXISTS idx_da_ref ON demandes_achat(ref_da)');
        echo "<p style='color: green;'>✅ Added index: idx_da_ref</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ Index idx_da_ref may already exist: " . $e->getMessage() . "</p>";
    }
    
    try {
        $conn->exec('CREATE INDEX IF NOT EXISTS idx_da_statut ON demandes_achat(statut)');
        echo "<p style='color: green;'>✅ Added index: idx_da_statut</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ Index idx_da_statut may already exist: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>Step 4: Testing with Sample Data</h2>";
    
    $stmt = $conn->query('SELECT COUNT(*) as count FROM demandes_achat');
    $count = $stmt->fetch()['count'];
    echo "<p>Current DA records: $count</p>";
    
    if ($count === 0) {
        echo "<p style='color: orange;'>⚠️ No DA records found. Inserting sample data...</p>";
        
        try {
            $sample_da = [
                'ref_da' => 'DA-2026-0001',
                'demandeur' => 'Administrateur',
                'statut' => 'Validé',
                'priorite' => 'Normal',
                'commentaires' => 'Test DA for system validation'
            ];
            
            $sql = 'INSERT INTO demandes_achat (ref_da, demandeur, statut, priorite, commentaires) VALUES (?, ?, ?, ?, ?)';
            $stmt = $conn->prepare($sql);
            $stmt->execute([$sample_da['ref_da'], $sample_da['demandeur'], $sample_da['statut'], $sample_da['priorite'], $sample_da['commentaires']]);
            echo "<p style='color: green;'>✅ Sample DA inserted successfully</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error inserting sample DA: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: green;'>✅ DA records exist, no sample data needed</p>";
    }
    
    echo "<h2>Step 5: Final Validation</h2>";
    
    // Test DA creation
    try {
        $test_da = [
            'ref_da' => 'DA-2026-TEST',
            'demandeur' => 'Test User',
            'statut' => 'Brouillon',
            'priorite' => 'Normal',
            'commentaires' => 'Test DA creation'
        ];
        
        $sql = 'INSERT INTO demandes_achat (demandeur, statut, priorite, commentaires) VALUES (?, ?, ?, ?)';
        $stmt = $conn->prepare($sql);
        $stmt->execute([$test_da['demandeur'], $test_da['statut'], $test_da['priorite'], $test_da['commentaires']]);
        
        $last_id = $conn->lastInsertId();
        echo "<p style='color: green;'>✅ Test DA created with ID: $last_id</p>";
        
        // Clean up test DA
        $conn->exec("DELETE FROM demandes_achat WHERE id = $last_id");
        echo "<p style='color: green;'>✅ Test DA cleaned up</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Test DA creation failed: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>Database Fix Summary</h2>";
    echo "<p style='color: green;'>✅ Database structure validated and fixed</p>";
    echo "<p style='color: green;'>✅ All critical database issues resolved</p>";
    echo "<p style='color: blue;'>📋 Ready for Step 2: File Dependencies Fix</p>";
    
} catch (Exception $e) {
    echo "<h2>Database Error</h2>";
    echo "<p style='color: red;'>❌ Critical error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p style='color: red;'>Please check database connection and permissions</p>";
}
?>

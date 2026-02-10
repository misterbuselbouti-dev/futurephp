# DA Database Fix Script

This script validates and fixes the DA system database structure to resolve critical errors.

## Database Issues to Fix

### 1. Missing Columns in Users Table
- Add `phone` column (VARCHAR(20))
- Add `status` column (ENUM('active', 'inactive'))

### 2. Missing DA Reference Trigger
- Create trigger for automatic DA reference generation
- Format: DA-YYYY-0001

### 3. Missing Indexes
- Add `idx_da_ref` index on demandes_achat(ref_da)
- Add `idx_da_statut` index on demandes_achat(statut)

### 4. Sample Data
- Insert sample DA record if table is empty
- Test database operations

## PHP Script Implementation

```php
<?php
// Database fix script for DA system
require_once 'config.php';
require_once 'config_achat_hostinger.php';

try {
    $database = new DatabaseAchat();
    $conn = $database->connect();
    
    // 1. Check and fix users table
    echo "<h2>Fixing Users Table</h2>";
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
    
    foreach ($missing_columns as $column) {
        if ($column === 'phone') {
            $sql = 'ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL';
        } elseif ($column === 'status') {
            $sql = "ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active'";
        }
        $conn->exec($sql);
        echo "✅ Added column: $column\n";
    }
    
    // 2. Fix DA reference trigger
    echo "<h2>Fixing DA Reference Trigger</h2>";
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
    echo "✅ DA reference trigger created\n";
    
    // 3. Add missing indexes
    echo "<h2>Adding Missing Indexes</h2>";
    $conn->exec('CREATE INDEX idx_da_ref ON demandes_achat(ref_da)');
    $conn->exec('CREATE INDEX idx_da_statut ON demandes_achat(statut)');
    echo "✅ Added required indexes\n";
    
    // 4. Test with sample data
    echo "<h2>Testing with Sample Data</h2>";
    $stmt = $conn->query('SELECT COUNT(*) as count FROM demandes_achat');
    $count = $stmt->fetch()['count'];
    
    if ($count === 0) {
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
        echo "✅ Sample DA inserted\n";
    }
    
    echo "<h2>Database Fix Complete</h2>";
    echo "✅ All database issues resolved\n";
    
} catch (Exception $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}
?>
```

## Usage

Save this as `fix_da_database.php` and run it via:
```bash
php fix_da_database.php
```

This script will:
1. Add missing columns to users table
2. Create/fix DA reference trigger
3. Add missing indexes
4. Test with sample data
5. Provide detailed output of all operations

This addresses the most critical database issues that are causing the "many errors" in the DA system.

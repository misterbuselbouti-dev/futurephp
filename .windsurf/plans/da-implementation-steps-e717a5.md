# DA System Implementation Steps

This document provides step-by-step instructions to fix the DA system errors and achieve a stable, error-free system.

## Step 1: Database Structure Fix (Priority 1)

### Create fix_da_database.php
```php
<?php
// Database fix script for DA system
require_once 'config.php';
require_once 'config_achat_hostinger.php';

try {
    $database = new DatabaseAchat();
    $conn = $database->connect();
    
    // 1. Fix users table
    $stmt = $conn->query('DESCRIBE users');
    $user_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $required_columns = ['phone', 'status'];
    foreach ($required_columns as $col) {
        $found = false;
        foreach ($user_columns as $user_col) {
            if ($user_col['Field'] === $col) $found = true;
        }
        if (!$found) {
            if ($col === 'phone') {
                $conn->exec('ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL');
            } elseif ($col === 'status') {
                $conn->exec("ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active'");
            }
            echo "✅ Added column: $col\n";
        }
    }
    
    // 2. Fix DA reference trigger
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
    
    // 3. Add indexes
    $conn->exec('CREATE INDEX idx_da_ref ON demandes_achat(ref_da)');
    $conn->exec('CREATE INDEX idx_da_statut ON demandes_achat(statut)');
    echo "✅ Added required indexes\n";
    
    // 4. Test sample data
    $stmt = $conn->query('SELECT COUNT(*) as count FROM demandes_achat');
    $count = $stmt->fetch()['count'];
    if ($count === 0) {
        $sql = 'INSERT INTO demandes_achat (ref_da, demandeur, statut, priorite, commentaires) VALUES (?, ?, ?, ?, ?)';
        $stmt = $conn->prepare($sql);
        $stmt->execute(['DA-2026-0001', 'Administrateur', 'Validé', 'Normal', 'Test DA']);
        echo "✅ Sample DA inserted\n";
    }
    
    echo "✅ Database fix complete\n";
    
} catch (Exception $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}
?>
```

## Step 2: File Dependencies Fix (Priority 2)

### Fix Missing Files
1. Remove references to `achat_da_pdf.php` from:
   - `achat_da_view.php` (line 221)
   - `achat_da.php` (already removed)

2. Fix broken links in DA files:
   - Ensure all redirects point to existing files
   - Add proper error handling

## Step 3: SQL Query Optimization (Priority 3)

### Fix Aliases in achat_da.php
Replace complex queries with simple ones:
```php
// Before (problematic)
SELECT da.*, COUNT(pi.id) as nombre_articles
FROM demandes_achat da
LEFT JOIN purchase_items pi ON da.id = pi.parent_id
GROUP BY da.id

// After (simplified)
SELECT da.*, 
       (SELECT COUNT(*) FROM purchase_items WHERE parent_type = 'DA' AND parent_id = da.id) as nombre_articles
FROM demandes_achat da
```

### Fix achat_dp.php queries
Remove aliases and use full table names:
```php
// Replace "da.id" with "demandes_achat.id"
// Replace "dp.da_id" with "demandes_prix.da_id"
```

## Step 4: API Endpoint Fix (Priority 4)

### Fix achat_dp_get_da_items.php
Ensure it returns all required DA fields:
```php
$stmt = $conn->prepare("
    SELECT da.*, 
           (SELECT COUNT(*) FROM purchase_items WHERE parent_type = 'DA' AND parent_id = da.id) as nombre_articles
    FROM demandes_achat da 
    WHERE da.id = ?
");
```

## Step 5: UI/UX Improvements (Priority 5)

### Standardize Error Messages
Add consistent error handling across all DA pages:
```php
if (!$da) {
    $_SESSION['error_message'] = "Demande d'achat non trouvée";
    header('Location: achat_da.php');
    exit();
}
```

### Add Success Notifications
```php
$_SESSION['success_message'] = "Opération réussie";
```

## Execution Order

1. **Run Step 1**: Execute `fix_da_database.php` first
2. **Run Step 2**: Fix file dependencies
3. **Run Step 3**: Optimize SQL queries
4. **Run Step 4**: Fix API endpoints
5. **Run Step 5**: Improve UI/UX

## Testing

After each step, test:
1. DA creation workflow
2. DA → DP workflow
3. Error handling
4. Database operations

This systematic approach ensures all critical issues are resolved in the correct order.

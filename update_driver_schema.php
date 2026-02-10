<?php
require_once 'config.php';

$database = new Database();
$pdo = $database->connect();

echo "<h2>Updating Driver Schema...</h2>";

try {
    // 1. Add columns to drivers table
    echo "<p>Adding pin_code column to drivers table...</p>";
    $pdo->exec("ALTER TABLE drivers ADD COLUMN IF NOT EXISTS pin_code VARCHAR(8) DEFAULT '0000'");
    
    echo "<p>Adding is_active column to drivers table...</p>";
    $pdo->exec("ALTER TABLE drivers ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1");
    
    // 2. Update existing drivers
    echo "<p>Updating existing drivers with default values...</p>";
    $pdo->exec("UPDATE drivers SET pin_code = '0000', is_active = 1 WHERE pin_code IS NULL OR is_active IS NULL");
    
    // 3. Delete driver accounts from users table
    echo "<p>Deleting driver accounts from users table...</p>";
    $stmt = $pdo->prepare("DELETE FROM users WHERE role = 'driver'");
    $stmt->execute();
    echo "<p>Deleted " . $stmt->rowCount() . " driver accounts from users table</p>";
    
    // 4. Remove driver_id column from users table
    echo "<p>Removing driver_id column from users table...</p>";
    $pdo->exec("ALTER TABLE users DROP COLUMN IF EXISTS driver_id");
    
    // 5. Create indexes
    echo "<p>Creating indexes...</p>";
    $pdo->exec("ALTER TABLE drivers ADD INDEX IF NOT EXISTS idx_pin_code (pin_code)");
    $pdo->exec("ALTER TABLE drivers ADD INDEX IF NOT EXISTS idx_is_active (is_active)");
    
    echo "<h3 style='color: green;'>✅ Driver schema updated successfully!</h3>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error: " . $e->getMessage() . "</h3>";
}
?>

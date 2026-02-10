<?php
require_once 'config.php';

$database = new Database();
$pdo = $database->connect();

echo "<h2>Fixing breakdown_reports table...</h2>";

try {
    // Make created_by_user_id nullable
    echo "<p>Making created_by_user_id nullable...</p>";
    $pdo->exec("ALTER TABLE breakdown_reports MODIFY COLUMN created_by_user_id INT NULL");
    
    echo "<h3 style='color: green;'>✅ Fixed successfully!</h3>";
    echo "<p>You can now submit breakdown reports from driver portal.</p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error: " . $e->getMessage() . "</h3>";
}
?>

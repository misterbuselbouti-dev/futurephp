<?php
// Debug script for articles
require_once 'config.php';

echo "<h1>Debug Articles System</h1>";

// Check table exists
echo "<h2>1. Check Table Structure</h2>";
try {
    $db = (new Database())->connect();
    $stmt = $db->query("DESCRIBE articles_catalogue");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td></tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// Check data exists
echo "<h2>2. Check Data in Table</h2>";
try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM articles_catalogue");
    $count = $stmt->fetch();
    echo "<p>Total articles: " . $count['total'] . "</p>";
    
    if ($count['total'] > 0) {
        $stmt = $db->query("SELECT id, code_article, designation, stock_ksar, stock_tetouan, stock_actuel FROM articles_catalogue LIMIT 5");
        $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Code</th><th>Designation</th><th>Stock Ksar</th><th>Stock Tetouan</th><th>Stock Total</th></tr>";
        foreach ($articles as $article) {
            echo "<tr><td>{$article['id']}</td><td>{$article['code_article']}</td><td>{$article['designation']}</td><td>{$article['stock_ksar']}</td><td>{$article['stock_tetouan']}</td><td>{$article['stock_actuel']}</td></tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// Check API
echo "<h2>3. Test API Response</h2>";
echo "<iframe src='api/articles_stockables/list.php' width='100%' height='300'></iframe>";

// Check session
echo "<h2>4. Check Session</h2>";
// session_start() already called in config.php
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "</p>";
echo "<p>User logged in: " . (isset($_SESSION['user_id']) ? 'Yes' : 'No') . "</p>";
?>

<?php
// Fix report_id column references - search and fix
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Fix Report ID References</h1>";

// Database connection
try {
    require_once 'config.php';
    $database = new Database();
    $pdo = $database->connect();
    echo "<p style='color:green'>✅ Database connection: SUCCESS</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Database connection: FAILED - " . $e->getMessage() . "</p>";
    exit;
}

// Check current breakdown_reports structure
echo "<h2>1. Current breakdown_reports Structure</h2>";
try {
    $stmt = $pdo->query("DESCRIBE breakdown_reports");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Key</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Key']}</td></tr>";
    }
    echo "</table>";
    
    // Check if report_id column exists
    $has_report_id = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'report_id') {
            $has_report_id = true;
            break;
        }
    }
    
    if (!$has_report_id) {
        echo "<p style='color:orange'>⚠️ report_id column does not exist</p>";
        
        // Option 1: Add report_id as generated column (alias to id)
        echo "<h2>2. Adding report_id as generated column</h2>";
        try {
            $pdo->exec("ALTER TABLE breakdown_reports ADD COLUMN report_id INT GENERATED ALWAYS AS (id) STORED");
            echo "<p style='color:green'>✅ Added report_id as generated column (alias to id)</p>";
        } catch (Exception $e) {
            echo "<p style='color:red'>❌ Error adding report_id column: " . $e->getMessage() . "</p>";
            
            // Option 2: If generated column not supported, add regular column
            echo "<h2>3. Adding report_id as regular column</h2>";
            try {
                $pdo->exec("ALTER TABLE breakdown_reports ADD COLUMN report_id INT AFTER id");
                
                // Update all existing records
                $pdo->exec("UPDATE breakdown_reports SET report_id = id");
                echo "<p style='color:green'>✅ Added report_id column and populated with id values</p>";
            } catch (Exception $e2) {
                echo "<p style='color:red'>❌ Error adding regular report_id column: " . $e2->getMessage() . "</p>";
            }
        }
    } else {
        echo "<p style='color:green'>✅ report_id column already exists</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error getting table structure: " . $e->getMessage() . "</p>";
}

// Test queries that might use report_id
echo "<h2>4. Testing report_id queries</h2>";

// Test 1: Simple query with report_id
try {
    $stmt = $pdo->query("SELECT * FROM breakdown_reports WHERE report_id = 1 LIMIT 1");
    $result = $stmt->fetch();
    if ($result) {
        echo "<p style='color:green'>✅ Simple query with report_id: SUCCESS</p>";
        echo "<p>Found record: ID={$result['id']}, report_id={$result['report_id']}, report_ref={$result['report_ref']}</p>";
    } else {
        echo "<p style='color:orange'>⚠️ No records found with report_id = 1</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Simple query error: " . $e->getMessage() . "</p>";
}

// Test 2: Join with breakdown_assignments
try {
    $stmt = $pdo->query("
        SELECT ba.*, br.report_ref 
        FROM breakdown_assignments ba
        LEFT JOIN breakdown_reports br ON ba.breakdown_id = br.report_id
        LIMIT 3
    ");
    $results = $stmt->fetchAll();
    echo "<p style='color:green'>✅ Join query with report_id: SUCCESS (" . count($results) . " records)</p>";
    
    if (count($results) > 0) {
        echo "<table border='1'><tr><th>Assignment ID</th><th>Breakdown ID</th><th>Report Ref</th></tr>";
        foreach ($results as $r) {
            echo "<tr><td>{$r['id']}</td><td>{$r['breakdown_id']}</td><td>" . ($r['report_ref'] ?? '-') . "</td></tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Join query error: " . $e->getMessage() . "</p>";
    
    // Try with id instead of report_id
    echo "<h2>5. Testing with id instead of report_id</h2>";
    try {
        $stmt = $pdo->query("
            SELECT ba.*, br.report_ref 
            FROM breakdown_assignments ba
            LEFT JOIN breakdown_reports br ON ba.breakdown_id = br.id
            LIMIT 3
        ");
        $results = $stmt->fetchAll();
        echo "<p style='color:green'>✅ Join query with id: SUCCESS (" . count($results) . " records)</p>";
        
        if (count($results) > 0) {
            echo "<table border='1'><tr><th>Assignment ID</th><th>Breakdown ID</th><th>Report Ref</th></tr>";
            foreach ($results as $r) {
                echo "<tr><td>{$r['id']}</td><td>{$r['breakdown_id']}</td><td>" . ($r['report_ref'] ?? '-') . "</td></tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e2) {
        echo "<p style='color:red'>❌ Join query with id also failed: " . $e2->getMessage() . "</p>";
    }
}

// Search for files that might contain report_id references
echo "<h2>6. Searching for report_id references in PHP files</h2>";

$directory = __DIR__;
$files_with_report_id = [];

// Search in PHP files
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        if (strpos($content, 'report_id') !== false) {
            $files_with_report_id[] = $file->getPathname();
        }
    }
}

if (!empty($files_with_report_id)) {
    echo "<p style='color:orange'>⚠️ Found " . count($files_with_report_id) . " PHP files with report_id references:</p>";
    echo "<ul>";
    foreach ($files_with_report_id as $file) {
        $relative_path = str_replace($directory . '/', '', $file);
        echo "<li>$relative_path</li>";
    }
    echo "</ul>";
    
    // Show some content from the first few files
    foreach (array_slice($files_with_report_id, 0, 3) as $file) {
        echo "<h3>Content preview: " . basename($file) . "</h3>";
        $content = file_get_contents($file);
        $lines = explode("\n", $content);
        
        for ($i = 0; $i < count($lines); $i++) {
            if (strpos($lines[$i], 'report_id') !== false) {
                $line_num = $i + 1;
                echo "<p><strong>Line $line_num:</strong> " . htmlspecialchars($lines[$i]) . "</p>";
            }
        }
    }
} else {
    echo "<p style='color:green'>✅ No PHP files found with report_id references</p>";
}

echo "<hr>";
echo "<h2>7. Recommendations</h2>";
echo "<ul>";
echo "<li><strong>Option 1:</strong> Use the generated column approach (report_id = id)</li>";
echo "<li><strong>Option 2:</strong> Update all PHP code to use 'id' instead of 'report_id'</li>";
echo "<li><strong>Option 3:</strong> Keep both columns synchronized</li>";
echo "</ul>";

echo "<hr>";
echo "<p><a href='admin_breakdowns.php'>Test admin_breakdowns.php</a></p>";
echo "<p><a href='driver_breakdown_new.php'>Test driver_breakdown_new.php</a></p>";
?>

<?php
// Debug script for drivers table
require_once 'config.php';

echo "<h1>Debug Drivers System</h1>";

// Check database connection
echo "<h2>1. Database Connection</h2>";
try {
    $database = new Database();
    $pdo = $database->connect();
    echo "<p style='color:green'>✅ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Check if drivers table exists
echo "<h2>2. Check Drivers Table</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'drivers'");
    $table_exists = $stmt->rowCount() > 0;
    
    if ($table_exists) {
        echo "<p style='color:green'>✅ Drivers table exists</p>";
        
        // Show table structure
        $stmt = $pdo->query("DESCRIBE drivers");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Table Structure:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check data count
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM drivers");
        $count = $stmt->fetch()['total'];
        echo "<p><strong>Total drivers:</strong> " . $count . "</p>";
        
        if ($count > 0) {
            // Show sample data
            $stmt = $pdo->query("SELECT * FROM drivers LIMIT 5");
            $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Sample Data:</h3>";
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>Nom</th><th>Prénom</th><th>Name</th><th>Numero</th><th>Phone</th><th>Email</th><th>CIN</th><th>Active</th></tr>";
            foreach ($drivers as $driver) {
                echo "<tr>";
                echo "<td>{$driver['id']}</td>";
                echo "<td>" . ($driver['nom'] ?? '-') . "</td>";
                echo "<td>" . ($driver['prenom'] ?? '-') . "</td>";
                echo "<td>" . ($driver['name'] ?? '-') . "</td>";
                echo "<td>" . ($driver['numero_conducteur'] ?? '-') . "</td>";
                echo "<td>" . ($driver['phone'] ?? '-') . "</td>";
                echo "<td>" . ($driver['email'] ?? '-') . "</td>";
                echo "<td>" . ($driver['cin'] ?? '-') . "</td>";
                echo "<td>" . ($driver['is_active'] ?? '0') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color:orange'>⚠️ No drivers found in table. Adding sample data...</p>";
            
            // Add sample drivers
            $sample_drivers = [
                ['ALAMI', 'Mohammed', 'DR-001', '0661234567', 'm.alami@future.ma', 'AB123456'],
                ['BENANI', 'Ahmed', 'DR-002', '0662345678', 'a.benani@future.ma', 'CD234567'],
                ['CHAKIR', 'Youssef', 'DR-003', '0663456789', 'y.chakir@future.ma', 'EF345678'],
                ['DAHMANI', 'Omar', 'DR-004', '0664567890', 'o.dahmani@future.ma', 'GH456789'],
                ['EL IDRISSI', 'Karim', 'DR-005', '0665678901', 'k.elidrissi@future.ma', 'IJ567890']
            ];
            
            foreach ($sample_drivers as $driver) {
                // Check if nom/prenom columns exist
                $stmt = $pdo->query("SHOW COLUMNS FROM drivers");
                $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $has_nom_prenom = in_array('nom', $cols) && in_array('prenom', $cols);
                
                if ($has_nom_prenom) {
                    $stmt = $pdo->prepare("INSERT INTO drivers (nom, prenom, numero_conducteur, phone, email, cin, is_active, pin_code) VALUES (?, ?, ?, ?, ?, ?, 1, '1234')");
                    $stmt->execute($driver);
                } else {
                    $name = $driver[1] . ' ' . $driver[0];
                    $stmt = $pdo->prepare("INSERT INTO drivers (name, numero_conducteur, phone, email, cin, is_active, pin_code) VALUES (?, ?, ?, ?, ?, 1, '1234')");
                    $stmt->execute([$name, $driver[2], $driver[3], $driver[4], $driver[5]]);
                }
            }
            
            echo "<p style='color:green'>✅ Added 5 sample drivers</p>";
        }
        
    } else {
        echo "<p style='color:red'>❌ Drivers table does not exist</p>";
        
        // Create drivers table
        echo "<h3>Creating drivers table...</h3>";
        $sql = "CREATE TABLE drivers (
            id INT PRIMARY KEY AUTO_INCREMENT,
            nom VARCHAR(100),
            prenom VARCHAR(100),
            name VARCHAR(200),
            numero_conducteur VARCHAR(50) UNIQUE,
            phone VARCHAR(20),
            email VARCHAR(100),
            cin VARCHAR(20) UNIQUE,
            is_active TINYINT(1) DEFAULT 1,
            pin_code VARCHAR(8) DEFAULT '0000',
            bus_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "<p style='color:green'>✅ Drivers table created</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}

// Check session
echo "<h2>3. Check Session</h2>";
session_start();
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>User logged in: " . (isset($_SESSION['user_id']) ? 'Yes' : 'No') . "</p>";
echo "<p>User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "</p>";

// Test the actual query used in drivers.php
echo "<h2>4. Test Query from drivers.php</h2>";
try {
    // Detect structure: nom/prenom ou name
    $cols = $pdo->query("SHOW COLUMNS FROM drivers")->fetchAll(PDO::FETCH_COLUMN);
    $has_nom = in_array('nom', $cols) && in_array('prenom', $cols);
    echo "<p>Has nom/prenom columns: " . ($has_nom ? 'Yes' : 'No') . "</p>";
    
    $stmt = $pdo->query("
        SELECT d.*, b.bus_number, b.make, b.model
        FROM drivers d 
        LEFT JOIN buses b ON d.bus_id = b.id 
        ORDER BY " . ($has_nom ? "d.numero_conducteur, d.nom" : "d.name")
    );
    $drivers = $stmt->fetchAll();
    
    echo "<p>Query executed successfully. Found " . count($drivers) . " drivers</p>";
    
    if (count($drivers) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Name</th><th>Bus</th></tr>";
        foreach ($drivers as $d) {
            echo "<tr>";
            echo "<td>{$d['id']}</td>";
            echo "<td>" . ($d['nom'] && $d['prenom'] ? $d['nom'] . ' ' . $d['prenom'] : ($d['name'] ?? 'N/A')) . "</td>";
            echo "<td>" . ($d['bus_number'] ?? 'No bus') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Query error: " . $e->getMessage() . "</p>";
}
?>

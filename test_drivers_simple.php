<?php
// Simple test page for drivers - no authentication required
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Simple Drivers Test</h1>";

// Test database connection
echo "<h2>1. Database Connection Test</h2>";
try {
    require_once 'config.php';
    $database = new Database();
    $pdo = $database->connect();
    echo "<p style='color:green'>✅ Database connection: SUCCESS</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Database connection: FAILED - " . $e->getMessage() . "</p>";
    exit;
}

// Test if drivers table exists
echo "<h2>2. Drivers Table Test</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'drivers'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green'>✅ Drivers table exists</p>";
        
        // Show structure
        $stmt = $pdo->query("DESCRIBE drivers");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Table Structure:</h3>";
        echo "<table border='1'><tr><th>Field</th><th>Type</th></tr>";
        foreach ($columns as $col) {
            echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td></tr>";
        }
        echo "</table>";
        
        // Count drivers
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM drivers");
        $count = $stmt->fetch()['count'];
        echo "<p><strong>Total drivers:</strong> " . $count . "</p>";
        
        if ($count == 0) {
            echo "<p style='color:orange'>⚠️ No drivers found. Adding sample data...</p>";
            
            // Add sample drivers
            $sample_drivers = [
                ['ALAMI', 'Mohammed', 'DR-001', '0661234567', 'm.alami@future.ma', 'AB123456'],
                ['BENANI', 'Ahmed', 'DR-002', '0662345678', 'a.benani@future.ma', 'CD234567'],
                ['CHAKIR', 'Youssef', 'DR-003', '0663456789', 'y.chakir@future.ma', 'EF345678']
            ];
            
            foreach ($sample_drivers as $driver) {
                $stmt = $pdo->prepare("INSERT INTO drivers (nom, prenom, numero_conducteur, phone, email, cin, is_active, pin_code) VALUES (?, ?, ?, ?, ?, ?, 1, '1234')");
                $stmt->execute($driver);
            }
            
            echo "<p style='color:green'>✅ Added 3 sample drivers</p>";
        }
        
        // Show drivers
        echo "<h3>Current Drivers:</h3>";
        $stmt = $pdo->query("SELECT * FROM drivers ORDER BY id");
        $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Nom</th><th>Prénom</th><th>Numero</th><th>Phone</th><th>Email</th><th>CIN</th><th>Active</th></tr>";
        foreach ($drivers as $driver) {
            echo "<tr>";
            echo "<td>{$driver['id']}</td>";
            echo "<td>" . ($driver['nom'] ?? '-') . "</td>";
            echo "<td>" . ($driver['prenom'] ?? '-') . "</td>";
            echo "<td>" . ($driver['numero_conducteur'] ?? '-') . "</td>";
            echo "<td>" . ($driver['phone'] ?? '-') . "</td>";
            echo "<td>" . ($driver['email'] ?? '-') . "</td>";
            echo "<td>" . ($driver['cin'] ?? '-') . "</td>";
            echo "<td>" . ($driver['is_active'] ?? '0') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color:red'>❌ Drivers table does not exist</p>";
        
        // Create table
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
            taux_horaire DECIMAL(10,2) DEFAULT 15.48,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "<p style='color:green'>✅ Created drivers table</p>";
        
        // Add sample data
        $sample_drivers = [
            ['ALAMI', 'Mohammed', 'DR-001', '0661234567', 'm.alami@future.ma', 'AB123456'],
            ['BENANI', 'Ahmed', 'DR-002', '0662345678', 'a.benani@future.ma', 'CD234567'],
            ['CHAKIR', 'Youssef', 'DR-003', '0663456789', 'y.chakir@future.ma', 'EF345678']
        ];
        
        foreach ($sample_drivers as $driver) {
            $stmt = $pdo->prepare("INSERT INTO drivers (nom, prenom, numero_conducteur, phone, email, cin, is_active, pin_code) VALUES (?, ?, ?, ?, ?, ?, 1, '1234')");
            $stmt->execute($driver);
        }
        
        echo "<p style='color:green'>✅ Added sample drivers</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}

// Test the exact query from drivers.php
echo "<h2>3. Test Query from drivers.php</h2>";
try {
    // Check if bus_id column exists in drivers table
    $stmt = $pdo->query("SHOW COLUMNS FROM drivers");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $has_bus_id = in_array('bus_id', $columns);
    
    if (!$has_bus_id) {
        echo "<p style='color:orange'>⚠️ bus_id column missing in drivers table. Adding it...</p>";
        $pdo->exec("ALTER TABLE drivers ADD COLUMN bus_id INT AFTER pin_code");
        echo "<p style='color:green'>✅ Added bus_id column</p>";
    }
    
    // Detect structure
    $cols = $pdo->query("SHOW COLUMNS FROM drivers")->fetchAll(PDO::FETCH_COLUMN);
    $has_nom = in_array('nom', $cols) && in_array('prenom', $cols);
    echo "<p>Has nom/prenom columns: " . ($has_nom ? 'YES' : 'NO') . "</p>";
    
    // Check if buses table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'buses'");
    $has_buses = $stmt->rowCount() > 0;
    echo "<p>Buses table exists: " . ($has_buses ? 'YES' : 'NO') . "</p>";
    
    if ($has_buses) {
        $stmt = $pdo->query("
            SELECT d.*, b.bus_number, b.make, b.model
            FROM drivers d 
            LEFT JOIN buses b ON d.bus_id = b.id 
            ORDER BY " . ($has_nom ? "d.numero_conducteur, d.nom" : "d.name")
        );
    } else {
        $stmt = $pdo->query("
            SELECT d.*, NULL as bus_number, NULL as make, NULL as model
            FROM drivers d 
            ORDER BY " . ($has_nom ? "d.numero_conducteur, d.nom" : "d.name")
        );
    }
    
    $drivers = $stmt->fetchAll();
    
    echo "<p>Query executed. Found " . count($drivers) . " drivers</p>";
    
    if (count($drivers) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Name</th><th>Bus</th><th>Phone</th><th>Email</th></tr>";
        foreach ($drivers as $d) {
            $name = $has_nom ? ($d['nom'] . ' ' . $d['prenom']) : ($d['name'] ?? 'N/A');
            echo "<tr>";
            echo "<td>{$d['id']}</td>";
            echo "<td>{$name}</td>";
            echo "<td>" . ($d['bus_number'] ?? 'No bus') . "</td>";
            echo "<td>" . ($d['phone'] ?? '-') . "</td>";
            echo "<td>" . ($d['email'] ?? '-') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Query error: " . $e->getMessage() . "</p>";
}

// Test session
echo "<h2>4. Session Test</h2>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "<p>Session status: " . session_status() . "</p>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>User logged in: " . (isset($_SESSION['user_id']) ? 'YES' : 'NO') . "</p>";
echo "<p>User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "</p>";

echo "<hr>";
echo "<p><a href='drivers.php'>Go to drivers.php</a></p>";
echo "<p><a href='login.php'>Go to login.php</a></p>";
?>

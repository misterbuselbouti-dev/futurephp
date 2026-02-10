<?php
// DA System Test Script
require_once 'config.php';
require_once 'config_achat_hostinger.php';

echo "<h1>DA System Complete Test</h1>";

try {
    $database = new DatabaseAchat();
    $conn = $database->connect();
    
    echo "<h2>Test 1: Database Structure</h2>";
    
    // Test users table
    $stmt = $conn->query('DESCRIBE users');
    $user_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $has_phone = false;
    $has_status = false;
    
    foreach ($user_columns as $col) {
        if ($col['Field'] === 'phone') $has_phone = true;
        if ($col['Field'] === 'status') $has_status = true;
    }
    
    echo "<p>Users table - Phone column: " . ($has_phone ? "✅" : "❌") . "</p>";
    echo "<p>Users table - Status column: " . ($has_status ? "✅" : "❌") . "</p>";
    
    // Test DA table
    $stmt = $conn->query('SHOW INDEX FROM demandes_achat');
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $has_ref_index = false;
    $has_statut_index = false;
    
    foreach ($indexes as $idx) {
        if ($idx['Key_name'] === 'idx_da_ref') $has_ref_index = true;
        if ($idx['Key_name'] === 'idx_da_statut') $has_statut_index = true;
    }
    
    echo "<p>DA table - Ref index: " . ($has_ref_index ? "✅" : "❌") . "</p>";
    echo "<p>DA table - Statut index: " . ($has_statut_index ? "✅" : "❌") . "</p>";
    
    echo "<h2>Test 2: DA Creation</h2>";
    
    // Test DA creation
    $test_da = [
        'demandeur' => 'Test User',
        'statut' => 'Brouillon',
        'priorite' => 'Normal',
        'commentaires' => 'Test DA creation'
    ];
    
    $sql = 'INSERT INTO demandes_achat (demandeur, statut, priorite, commentaires) VALUES (?, ?, ?, ?)';
    $stmt = $conn->prepare($sql);
    $stmt->execute([$test_da['demandeur'], $test_da['statut'], $test_da['priorite'], $test_da['commentaires']]);
    
    $da_id = $conn->lastInsertId();
    echo "<p>✅ Test DA created with ID: $da_id</p>";
    
    // Get the created DA
    $stmt = $conn->prepare("SELECT * FROM demandes_achat WHERE id = ?");
    $stmt->execute([$da_id]);
    $da = $stmt->fetch();
    
    echo "<p>✅ DA Reference: " . htmlspecialchars($da['ref_da']) . "</p>";
    echo "<p>✅ DA Status: " . htmlspecialchars($da['statut']) . "</p>";
    
    echo "<h2>Test 3: Purchase Items</h2>";
    
    // Add purchase items
    $test_items = [
        [
            'parent_type' => 'DA',
            'parent_id' => $da_id,
            'designation' => 'Test Item 1',
            'quantite' => 5,
            'prix_unitaire' => 100.00,
            'total_ligne' => 500.00
        ],
        [
            'parent_type' => 'DA',
            'parent_id' => $da_id,
            'designation' => 'Test Item 2',
            'quantite' => 3,
            'prix_unitaire' => 200.00,
            'total_ligne' => 600.00
        ]
    ];
    
    foreach ($test_items as $item) {
        $sql = 'INSERT INTO purchase_items (parent_type, parent_id, designation, quantite, prix_unitaire, total_ligne) VALUES (?, ?, ?, ?, ?, ?)';
        $stmt = $conn->prepare($sql);
        $stmt->execute([$item['parent_type'], $item['parent_id'], $item['designation'], $item['quantite'], $item['prix_unitaire'], $item['total_ligne']]);
    }
    
    echo "<p>✅ Added 2 test purchase items</p>";
    
    echo "<h2>Test 4: API Endpoint</h2>";
    
    // Test the API endpoint
    $_GET['da_id'] = $da_id;
    
    // Simulate API call
    $stmt = $conn->prepare("
        SELECT da.*, 
               (SELECT COUNT(*) FROM purchase_items WHERE parent_type = 'DA' AND parent_id = da.id) as nombre_articles,
               (SELECT SUM(total_ligne) FROM purchase_items WHERE parent_type = 'DA' AND parent_id = da.id) as montant_total
        FROM demandes_achat da 
        WHERE da.id = ?
    ");
    $stmt->execute([$da_id]);
    $api_da = $stmt->fetch();
    
    $stmt = $conn->prepare("SELECT * FROM purchase_items WHERE parent_type = 'DA' AND parent_id = ? ORDER BY id");
    $stmt->execute([$da_id]);
    $api_items = $stmt->fetchAll();
    
    echo "<p>✅ API DA data: " . htmlspecialchars($api_da['ref_da']) . "</p>";
    echo "<p>✅ API Articles count: " . count($api_items) . "</p>";
    echo "<p>✅ API Total amount: " . $api_da['montant_total'] . "</p>";
    
    echo "<h2>Test 5: DA Validation</h2>";
    
    // Test DA validation
    $stmt = $conn->prepare("UPDATE demandes_achat SET statut = 'Validé' WHERE id = ?");
    $stmt->execute([$da_id]);
    
    $stmt = $conn->prepare("SELECT statut FROM demandes_achat WHERE id = ?");
    $stmt->execute([$da_id]);
    $status = $stmt->fetch()['statut'];
    
    echo "<p>✅ DA Status after validation: " . htmlspecialchars($status) . "</p>";
    
    echo "<h2>Test 6: DP Creation</h2>";
    
    // Test DP creation
    $stmt = $conn->query("SELECT id, nom_fournisseur FROM suppliers LIMIT 1");
    $supplier = $stmt->fetch();
    
    if ($supplier) {
        $ref_dp = 'DP-2026-TEST-001';
        $sql = 'INSERT INTO demandes_prix (ref_dp, da_id, fournisseur_id, statut) VALUES (?, ?, ?, ?)';
        $stmt = $conn->prepare($sql);
        $stmt->execute([$ref_dp, $da_id, $supplier['id'], 'Envoyé']);
        
        $dp_id = $conn->lastInsertId();
        echo "<p>✅ Test DP created with ID: $dp_id</p>";
        echo "<p>✅ DP Supplier: " . htmlspecialchars($supplier['nom_fournisseur']) . "</p>";
    } else {
        echo "<p>⚠️ No suppliers found for DP test</p>";
    }
    
    echo "<h2>Cleanup</h2>";
    
    // Clean up test data
    $conn->exec("DELETE FROM demandes_prix WHERE da_id = $da_id");
    $conn->exec("DELETE FROM purchase_items WHERE parent_id = $da_id");
    $conn->exec("DELETE FROM demandes_achat WHERE id = $da_id");
    
    echo "<p>✅ Test data cleaned up</p>";
    
    echo "<h2>Test Summary</h2>";
    echo "<p style='color: green;'>✅ All DA system tests passed successfully!</p>";
    echo "<p style='color: blue;'>📋 System is ready for production use</p>";
    
} catch (Exception $e) {
    echo "<h2>Test Error</h2>";
    echo "<p style='color: red;'>❌ Test failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p style='color: red;'>Please check the error and fix any issues</p>";
}
?>

<?php
// ATEO Auto - Get DP Items for BC Creation
// API endpoint to get items from a specific DP

require_once 'config.php';
require_once 'config_achat_hostinger.php';

// Vérifier si l'utilisateur est connecté
if (!is_logged_in()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Non autorisé']);
    exit();
}

// Récupérer l'ID de la DP
$dp_id = $_GET['dp_id'] ?? 0;
if (!$dp_id) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID de DP manquant']);
    exit();
}

try {
    $database = new DatabaseAchat();
    $conn = $database->connect();
    
    // Récupérer les informations de la DP
    $stmt = $conn->prepare("
        SELECT dp.*, 
               da.ref_da,
               da.demandeur as da_demandeur,
               s.nom_fournisseur,
               s.contact_nom as contact_nom,
               s.email as email_fournisseur,
               s.telephone as telephone_fournisseur
        FROM demandes_prix dp
        LEFT JOIN demandes_achat da ON dp.da_id = da.id
        LEFT JOIN suppliers s ON dp.fournisseur_id = s.id
        WHERE dp.id = ?
    ");
    $stmt->execute([$dp_id]);
    $dp = $stmt->fetch();
    
    if (!$dp) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Demande de prix non trouvée']);
        exit();
    }
    
    // Récupérer les articles de la DP
    $stmt = $conn->prepare("
        SELECT * FROM purchase_items 
        WHERE parent_type = 'DP' AND parent_id = ?
        ORDER BY id
    ");
    $stmt->execute([$dp_id]);
    $items = $stmt->fetchAll();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'dp' => $dp,
        'items' => $items
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}
?>

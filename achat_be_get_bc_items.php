<?php
// ATEO Auto - Get BC Items for BE Creation
// API endpoint to get items from a specific BC

require_once 'config.php';
require_once 'config_achat_hostinger.php';

// Vérifier si l'utilisateur est connecté
if (!is_logged_in()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Non autorisé']);
    exit();
}

// Récupérer l'ID du BC
$bc_id = $_GET['bc_id'] ?? 0;
if (!$bc_id) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID de BC manquant']);
    exit();
}

try {
    $database = new DatabaseAchat();
    $conn = $database->connect();
    
    // Récupérer les informations du BC
    $stmt = $conn->prepare("
        SELECT bc.*, 
               dp.ref_dp,
               da.ref_da,
               dp.fournisseur_id,
               s.nom_fournisseur,
               s.contact_nom as contact_nom,
               s.email as email_fournisseur,
               s.telephone as telephone_fournisseur
        FROM bons_commande bc
        LEFT JOIN demandes_prix dp ON bc.dp_id = dp.id
        LEFT JOIN demandes_achat da ON dp.da_id = da.id
        LEFT JOIN suppliers s ON dp.fournisseur_id = s.id
        WHERE bc.id = ?
    ");
    $stmt->execute([$bc_id]);
    $bc = $stmt->fetch();
    
    if (!$bc) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Bon de commande non trouvé']);
        exit();
    }
    
    // Récupérer les articles du BC avec quantité déjà reçue
    $stmt = $conn->prepare("
        SELECT bci.*, 
               COALESCE(SUM(bei.quantite_recue), 0) as quantite_deja_recue
        FROM bc_items bci
        LEFT JOIN be_items bei ON bci.id = bei.bc_item_id
        WHERE bci.bc_id = ?
        GROUP BY bci.id
        ORDER BY bci.id
    ");
    $stmt->execute([$bc_id]);
    $items = $stmt->fetchAll();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'bc' => $bc,
        'items' => $items
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}
?>

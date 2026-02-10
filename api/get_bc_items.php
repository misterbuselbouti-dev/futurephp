<?php
// ATEO Auto - API pour récupérer les articles d'un bon de commande
// Endpoint pour charger les articles d'un BC dans le BE

require_once '../config_achat_hostinger.php';
require_once '../config.php';

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

// Traitement de la requête POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bc_id'])) {
    try {
        $database = new DatabaseAchat();
        $conn = $database->connect();
        
        $bc_id = intval($_POST['bc_id']);
        
        // Récupérer les articles du BC (stock depuis stock_management ou stock_by_region)
        $stmt = $conn->prepare("
            SELECT bci.*, ac.designation, 'pièce' as unite,
                   COALESCE(sm.quantite_actuelle, 0) as stock_actuel
            FROM bc_items bci
            LEFT JOIN articles_catalogue ac ON bci.item_code = ac.code_article
            LEFT JOIN stock_management sm ON (bci.item_code = sm.code_piece OR bci.item_code = sm.item_code)
            WHERE bci.bc_id = ?
            ORDER BY bci.id
        ");
        
        $stmt->execute([$bc_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'items' => $items
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Requête invalide']);
}
?>

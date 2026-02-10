<?php
// ATEO Auto - Get DA Items for DP Creation
// API endpoint to get items from a specific DA

require_once 'config.php';
require_once 'config_achat_hostinger.php';

// Vérifier si l'utilisateur est connecté
if (!is_logged_in()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Non autorisé']);
    exit();
}

// Récupérer l'ID de la DA
$da_id = $_GET['da_id'] ?? 0;
if (!$da_id) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID de DA manquant']);
    exit();
}

try {
    $database = new DatabaseAchat();
    $conn = $database->connect();
    
    // Récupérer les informations de la DA avec informations supplémentaires
    $stmt = $conn->prepare("
        SELECT da.*, 
               (SELECT COUNT(*) FROM purchase_items WHERE parent_type = 'DA' AND parent_id = da.id) as nombre_articles,
               (SELECT SUM(total_ligne) FROM purchase_items WHERE parent_type = 'DA' AND parent_id = da.id) as montant_total
        FROM demandes_achat da 
        WHERE da.id = ?
    ");
    $stmt->execute([$da_id]);
    $da = $stmt->fetch();
    
    if (!$da) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Demande d\'achat non trouvée']);
        exit();
    }
    
    // Récupérer les articles de la DA
    $stmt = $conn->prepare("
        SELECT * FROM purchase_items 
        WHERE parent_type = 'DA' AND parent_id = ?
        ORDER BY id
    ");
    $stmt->execute([$da_id]);
    $items = $stmt->fetchAll();

    // Ajouter le dernier prix d'achat par article (basé sur bc_items)
    // On utilise ref_article comme clé quand elle existe
    foreach ($items as &$item) {
        $ref = $item['ref_article'] ?? null;
        $item['last_purchase_price'] = null;

        if ($ref) {
            try {
                $stmt_last = $conn->prepare("
                    SELECT bci.unit_price
                    FROM bc_items bci
                    INNER JOIN bons_commande bc ON bc.id = bci.bc_id
                    WHERE bci.item_code = ?
                    ORDER BY bc.date_commande DESC, bci.id DESC
                    LIMIT 1
                ");
                $stmt_last->execute([$ref]);
                $row_last = $stmt_last->fetch();
                if ($row_last && isset($row_last['unit_price'])) {
                    $item['last_purchase_price'] = $row_last['unit_price'];
                }
            } catch (Exception $eLast) {
                $item['last_purchase_price'] = null;
            }
        }
    }
    unset($item);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'da' => $da,
        'items' => $items
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}
?>

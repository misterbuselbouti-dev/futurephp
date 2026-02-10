<?php
// Inventory Integration for Breakdown Management
require_once '../config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check authentication
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

$database = new Database();
$pdo = $database->connect();

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'search_articles':
            $searchTerm = trim($_POST['search_term'] ?? '');
            $breakdownId = (int)$_POST['breakdown_id'];
            
            if (strlen($searchTerm) < 2) {
                echo json_encode(['success' => false, 'message' => 'Terme de recherche trop court']);
                exit;
            }
            
            // Search articles with stock availability
            $stmt = $pdo->prepare("
                SELECT 
                    ac.id,
                    ac.reference,
                    ac.designation,
                    ac.unite,
                    ac.stock_actuel,
                    ac.stock_minimal,
                    ac.prix_achat,
                    ac.prix_vente,
                    (ac.stock_actuel - ac.stock_minimal) as stock_difference,
                    CASE 
                        WHEN ac.stock_actuel <= ac.stock_minimal THEN 'critical'
                        WHEN ac.stock_actuel <= (ac.stock_minimal * 1.5) THEN 'low'
                        ELSE 'available'
                    END as stock_status
                FROM articles_catalogue ac
                WHERE (ac.reference LIKE ? OR ac.designation LIKE ? OR ac.code_barre LIKE ?)
                AND ac.is_active = 1
                ORDER BY 
                    CASE 
                        WHEN ac.stock_actuel <= ac.stock_minimal THEN 1
                        WHEN ac.stock_actuel <= (ac.stock_minimal * 1.5) THEN 2
                        ELSE 3
                    END,
                    ac.designation
                LIMIT 20
            ");
            
            $searchParam = '%' . $searchTerm . '%';
            $stmt->execute([$searchParam, $searchParam, $searchParam]);
            $articles = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'articles' => $articles]);
            break;
            
        case 'check_stock_availability':
            $articleId = (int)$_POST['article_id'];
            $requestedQuantity = (float)$_POST['quantity'];
            
            $stmt = $pdo->prepare("
                SELECT 
                    stock_actuel,
                    stock_minimal,
                    designation,
                    reference,
                    CASE 
                        WHEN stock_actuel >= ? THEN 'available'
                        WHEN stock_actuel > 0 THEN 'insufficient'
                        ELSE 'unavailable'
                    END as availability_status
                FROM articles_catalogue 
                WHERE id = ?
            ");
            $stmt->execute([$requestedQuantity, $articleId]);
            $article = $stmt->fetch();
            
            if ($article) {
                echo json_encode([
                    'success' => true,
                    'available' => $article['stock_actuel'],
                    'required' => $requestedQuantity,
                    'status' => $article['availability_status'],
                    'article' => $article
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Article non trouvé']);
            }
            break;
            
        case 'add_work_item':
            $breakdownId = (int)$_POST['breakdown_id'];
            $assignmentId = (int)$_POST['assignment_id'];
            $articleId = (int)$_POST['article_id'];
            $quantity = (float)$_POST['quantity'];
            $unitCost = (float)$_POST['unit_cost'];
            $notes = trim($_POST['notes'] ?? '');
            
            $pdo->beginTransaction();
            
            // Check stock availability
            $stmt = $pdo->prepare("SELECT stock_actuel, designation FROM articles_catalogue WHERE id = ? FOR UPDATE");
            $stmt->execute([$articleId]);
            $article = $stmt->fetch();
            
            if (!$article) {
                throw new Exception('Article non trouvé');
            }
            
            if ($article['stock_actuel'] < $quantity) {
                throw new Exception('Stock insuffisant. Disponible: ' . $article['stock_actuel'] . ', Requis: ' . $quantity);
            }
            
            // Calculate total cost
            $totalCost = $quantity * $unitCost;
            
            // Add work item
            $stmt = $pdo->prepare("
                INSERT INTO breakdown_work_items 
                (breakdown_id, assignment_id, article_id, quantity_used, unit_cost, total_cost, notes, added_by_user_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$breakdownId, $assignmentId, $articleId, $quantity, $unitCost, $totalCost, $notes, $_SESSION['user_id']]);
            $workItemId = $pdo->lastInsertId();
            
            // Update stock
            $stmt = $pdo->prepare("UPDATE articles_catalogue SET stock_actuel = stock_actuel - ? WHERE id = ?");
            $stmt->execute([$quantity, $articleId]);
            
            // Update assignment total cost
            $stmt = $pdo->prepare("
                UPDATE breakdown_assignments 
                SET total_cost = (
                    SELECT COALESCE(SUM(total_cost), 0) 
                    FROM breakdown_work_items 
                    WHERE assignment_id = ?
                )
                WHERE id = ?
            ");
            $stmt->execute([$assignmentId, $assignmentId]);
            
            // Log the action
            $stmt = $pdo->prepare("
                INSERT INTO breakdown_audit_log 
                (breakdown_id, assignment_id, action_type, field_name, old_value, new_value, performed_by_user_id) 
                VALUES (?, ?, 'item_added', 'stock', ?, ?, ?)
            ");
            $stmt->execute([
                $breakdownId, 
                $assignmentId, 
                json_encode(['article_id' => $articleId, 'old_stock' => $article['stock_actuel']]),
                json_encode(['article_id' => $articleId, 'quantity_used' => $quantity, 'new_stock' => $article['stock_actuel'] - $quantity]),
                $_SESSION['user_id']
            ]);
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Pièce ajoutée avec succès',
                'work_item_id' => $workItemId,
                'remaining_stock' => $article['stock_actuel'] - $quantity
            ]);
            break;
            
        case 'remove_work_item':
            $workItemId = (int)$_POST['work_item_id'];
            
            $pdo->beginTransaction();
            
            // Get work item details
            $stmt = $pdo->prepare("
                SELECT bwi.*, ac.designation
                FROM breakdown_work_items bwi
                JOIN articles_catalogue ac ON bwi.article_id = ac.id
                WHERE bwi.id = ?
            ");
            $stmt->execute([$workItemId]);
            $workItem = $stmt->fetch();
            
            if (!$workItem) {
                throw new Exception('Article de travail non trouvé');
            }
            
            // Restore stock
            $stmt = $pdo->prepare("UPDATE articles_catalogue SET stock_actuel = stock_actuel + ? WHERE id = ?");
            $stmt->execute([$workItem['quantity_used'], $workItem['article_id']]);
            
            // Remove work item
            $stmt = $pdo->prepare("DELETE FROM breakdown_work_items WHERE id = ?");
            $stmt->execute([$workItemId]);
            
            // Update assignment total cost
            $stmt = $pdo->prepare("
                UPDATE breakdown_assignments 
                SET total_cost = (
                    SELECT COALESCE(SUM(total_cost), 0) 
                    FROM breakdown_work_items 
                    WHERE assignment_id = ?
                )
                WHERE id = ?
            ");
            $stmt->execute([$workItem['assignment_id'], $workItem['assignment_id']]);
            
            // Log the action
            $stmt = $pdo->prepare("
                INSERT INTO breakdown_audit_log 
                (breakdown_id, assignment_id, action_type, field_name, new_value, performed_by_user_id) 
                VALUES (?, ?, 'item_removed', 'stock_restored', ?, ?)
            ");
            $stmt->execute([
                $workItem['breakdown_id'], 
                $workItem['assignment_id'], 
                json_encode(['article_id' => $workItem['article_id'], 'quantity_restored' => $workItem['quantity_used']]),
                $_SESSION['user_id']
            ]);
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Pièce retirée avec succès',
                'restored_quantity' => $workItem['quantity_used']
            ]);
            break;
            
        case 'get_breakdown_items':
            $breakdownId = (int)$_POST['breakdown_id'];
            
            $stmt = $pdo->prepare("
                SELECT 
                    bwi.*,
                    ac.reference,
                    ac.designation,
                    ac.unite,
                    ac.prix_achat
                FROM breakdown_work_items bwi
                JOIN articles_catalogue ac ON bwi.article_id = ac.id
                WHERE bwi.breakdown_id = ?
                ORDER BY bwi.added_at
            ");
            $stmt->execute([$breakdownId]);
            $items = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'items' => $items]);
            break;
            
        case 'get_low_stock_alerts':
            $stmt = $pdo->prepare("
                SELECT 
                    ac.id,
                    ac.reference,
                    ac.designation,
                    ac.stock_actuel,
                    ac.stock_minimal,
                    (ac.stock_minimal - ac.stock_actuel) as shortage
                FROM articles_catalogue ac
                WHERE ac.stock_actuel <= ac.stock_minimal
                AND ac.is_active = 1
                ORDER BY (ac.stock_minimal - ac.stock_actuel) DESC
                LIMIT 10
            ");
            $stmt->execute();
            $lowStockItems = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'items' => $lowStockItems]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

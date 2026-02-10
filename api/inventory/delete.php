<?php
// FUTURE AUTOMOTIVE - Delete Inventory Item API
// حذف عنصر من المخزون

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

try {
    $item_id = $_GET['id'] ?? '';

    if (empty($item_id) || !is_numeric($item_id)) {
        throw new Exception('ID d\'article invalide');
    }

    $database = new Database();
    $pdo = $database->connect();

    // Check if item exists
    $stmt = $pdo->prepare("SELECT id FROM inventory WHERE id = ?");
    $stmt->execute([$item_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Article non trouvé');
    }

    // Delete item
    $stmt = $pdo->prepare("DELETE FROM inventory WHERE id = ?");
    $result = $stmt->execute([$item_id]);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Article supprimé avec succès'
        ]);
    } else {
        throw new Exception('Erreur lors de la suppression de l\'article');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

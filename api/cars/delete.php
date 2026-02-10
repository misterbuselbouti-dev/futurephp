<?php
// FUTURE AUTOMOTIVE - Delete Car API
// حذف سيارة من قاعدة البيانات

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
    $car_id = $_GET['id'] ?? '';

    if (empty($car_id) || !is_numeric($car_id)) {
        throw new Exception('ID de véhicule invalide');
    }

    $database = new Database();
    $pdo = $database->connect();

    // Check if car exists
    $stmt = $pdo->prepare("SELECT id FROM cars WHERE id = ?");
    $stmt->execute([$car_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Véhicule non trouvé');
    }

    // Check if car has related records
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM work_orders WHERE car_id = ?");
    $stmt->execute([$car_id]);
    $work_order_count = $stmt->fetch()['count'];

    if ($work_order_count > 0) {
        throw new Exception('Impossible de supprimer ce véhicule car il est associé à des ordres de travail');
    }

    // Delete car
    $stmt = $pdo->prepare("DELETE FROM cars WHERE id = ?");
    $result = $stmt->execute([$car_id]);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Véhicule supprimé avec succès'
        ]);
    } else {
        throw new Exception('Erreur lors de la suppression du véhicule');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

<?php
// FUTURE AUTOMOTIVE - Save Car API
// حفظ سيارة جديدة في قاعدة البيانات

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

try {
    $customer_id = $_POST['customer_id'] ?? '';
    $make = $_POST['make'] ?? '';
    $model = $_POST['model'] ?? '';
    $year = $_POST['year'] ?? null;
    $color = $_POST['color'] ?? '';
    $plate_number = $_POST['plate_number'] ?? '';
    $vin_number = $_POST['vin_number'] ?? '';

    if (empty($customer_id) || empty($make) || empty($model)) {
        throw new Exception('Tous les champs requis doivent être remplis');
    }

    $database = new Database();
    $pdo = $database->connect();

    // Check if plate number already exists
    if (!empty($plate_number)) {
        $stmt = $pdo->prepare("SELECT id FROM cars WHERE plate_number = ?");
        $stmt->execute([$plate_number]);
        if ($stmt->fetch()) {
            throw new Exception('Ce numéro d\'immatriculation existe déjà');
        }
    }

    // Insert car
    $sql = "INSERT INTO cars (customer_id, make, model, year, color, plate_number, vin_number) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$customer_id, $make, $model, $year, $color, $plate_number, $vin_number]);

    if ($result) {
        $car_id = $pdo->lastInsertId();
        echo json_encode([
            'success' => true,
            'message' => 'Véhicule ajouté avec succès',
            'car_id' => $car_id
        ]);
    } else {
        throw new Exception('Erreur lors de l\'ajout du véhicule');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

<?php
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

if (!isset($_GET['customer_id'])) {
    echo json_encode([]);
    exit;
}

$customer_id = $_GET['customer_id'];

try {
    // Connect to database
    $database = new Database();
    $db = $database->connect();
    
    $stmt = $db->prepare("SELECT id, plate_number, make, model FROM cars WHERE customer_id = ? ORDER BY plate_number");
    $stmt->execute([$customer_id]);
    $cars = $stmt->fetchAll();
    
    echo json_encode($cars);
} catch (Exception $e) {
    echo json_encode([]);
}
?>

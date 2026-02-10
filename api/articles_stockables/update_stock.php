<?php
// FUTURE AUTOMOTIVE - Update Stock by Region
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Debug: Log session status
error_log("Session status: " . session_status() . ", User ID: " . ($_SESSION['user_id'] ?? 'not set'));

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autorisé - Session requise']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée - POST requis']);
    exit;
}

try {
    $db = (new Database())->connect();
    
    $article_id = (int)($_POST['article_id'] ?? 0);
    $region_code = $_POST['region'] ?? ''; // tetouan | ksar
    $stock = (float)($_POST['stock'] ?? 0);
    
    // Debug: Log received data
    error_log("Received data - article_id: $article_id, region: $region_code, stock: $stock");
    
    if ($article_id <= 0) {
        throw new Exception('ID article invalide: ' . $article_id);
    }
    
    if (!in_array($region_code, ['tetouan', 'ksar'])) {
        throw new Exception('Région invalide: ' . $region_code . ' (valeurs acceptées: tetouan, ksar)');
    }
    
    if ($stock < 0) {
        throw new Exception('Stock ne peut pas être négatif: ' . $stock);
    }
    
    $stmt = $db->prepare("SELECT id FROM regions WHERE code = ? LIMIT 1");
    $stmt->execute([$region_code]);
    $region = $stmt->fetch();
    if (!$region) {
        throw new Exception('Région "' . $region_code . '" non trouvée dans la base de données');
    }
    
    error_log("Region found: " . $region['id']);
    
    $stmt = $db->prepare("
        INSERT INTO stock_by_region (article_id, region_id, stock, stock_minimal) 
        VALUES (?, ?, ?, 0)
        ON DUPLICATE KEY UPDATE stock = VALUES(stock)
    ");
    $result = $stmt->execute([$article_id, $region['id'], $stock]);
    
    if (!$result) {
        throw new Exception('Erreur lors de la mise à jour du stock en base de données');
    }
    
    error_log("Stock updated successfully for article $article_id, region $region_code, new stock: $stock");
    
    echo json_encode(['success' => true, 'message' => 'Stock mis à jour avec succès']);
} catch (Exception $e) {
    error_log("Error in update_stock.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

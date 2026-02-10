<?php
// FUTURE AUTOMOTIVE - Update Stock by Region
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

try {
    $db = (new Database())->connect();
    
    $article_id = (int)($_POST['article_id'] ?? 0);
    $region_code = $_POST['region'] ?? ''; // tetouan | ksar
    $stock = (float)($_POST['stock'] ?? 0);
    
    if ($article_id <= 0 || !in_array($region_code, ['tetouan', 'ksar'])) {
        throw new Exception('Données invalides');
    }
    
    $stmt = $db->prepare("SELECT id FROM regions WHERE code = ? LIMIT 1");
    $stmt->execute([$region_code]);
    $region = $stmt->fetch();
    if (!$region) {
        throw new Exception('Région non trouvée');
    }
    
    $stmt = $db->prepare("
        INSERT INTO stock_by_region (article_id, region_id, stock, stock_minimal) 
        VALUES (?, ?, ?, 0)
        ON DUPLICATE KEY UPDATE stock = VALUES(stock)
    ");
    $stmt->execute([$article_id, $region['id'], $stock]);
    
    echo json_encode(['success' => true, 'message' => 'Stock mis à jour']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

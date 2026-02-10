<?php
// FUTURE AUTOMOTIVE - Articles Stockables List API
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

try {
    $db = (new Database())->connect();
    
    $search = trim($_GET['search'] ?? '');
    $region = $_GET['region'] ?? ''; // tetouan | ksar - vide = كل المناطق
    
    $sql = "
        SELECT a.id, a.code_article as reference, a.designation, a.prix_unitaire,
               a.stock_ksar, a.stock_tetouan, a.stock_actuel, a.stock_minimal, a.categorie
        FROM articles_catalogue a
        WHERE 1=1
    ";
    $params = [];
    
    if ($search !== '') {
        $sql .= " AND (a.code_article LIKE ? OR a.designation LIKE ? OR a.categorie LIKE ?)";
        $p = "%{$search}%";
        $params = array_merge($params, [$p, $p, $p]);
    }
    
    $sql .= " ORDER BY a.code_article";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $rows]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

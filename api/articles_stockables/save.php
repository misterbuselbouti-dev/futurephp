<?php
// FUTURE AUTOMOTIVE - Save/Update Article Stockable
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
    
    $action = $_POST['action'] ?? 'add';
    $id = (int)($_POST['id'] ?? 0);
    $reference = trim($_POST['reference'] ?? '');
    $ref_piece = trim($_POST['ref_piece'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $prix_unitaire = (float)($_POST['prix_unitaire'] ?? 0);
    $unite = 'pièce'; // Standardized unit
    
    if ($reference === '' || $designation === '') {
        throw new Exception('Référence et Désignation obligatoires');
    }
    
    if ($action === 'edit' && $id > 0) {
        $stmt = $db->prepare("UPDATE articles_catalogue SET code_article=?, designation=?, prix_unitaire=? WHERE id=?");
        $stmt->execute([$reference, $designation, $prix_unitaire, $id]);
        echo json_encode(['success' => true, 'message' => 'Article mis à jour', 'id' => $id]);
    } else {
        $stmt = $db->prepare("SELECT id FROM articles_catalogue WHERE code_article = ?");
        $stmt->execute([$reference]);
        if ($stmt->fetch()) {
            throw new Exception('Cette référence existe déjà');
        }
        $stmt = $db->prepare("INSERT INTO articles_catalogue (code_article, designation, prix_unitaire) VALUES (?,?,?)");
        $stmt->execute([$reference, $designation, $prix_unitaire]);
        $aid = (int)$db->lastInsertId();
        $stmtR = $db->prepare("SELECT id FROM regions WHERE code = ? LIMIT 1");
        foreach (['tetouan','ksar'] as $code) {
            $stmtR->execute([$code]);
            $r = $stmtR->fetch();
            if ($r) {
                $db->prepare("INSERT IGNORE INTO stock_by_region (article_id, region_id, stock, stock_minimal) VALUES (?,?,0,0)")
                   ->execute([$aid, $r['id']]);
            }
        }
        echo json_encode(['success' => true, 'message' => 'Article ajouté', 'id' => $aid]);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

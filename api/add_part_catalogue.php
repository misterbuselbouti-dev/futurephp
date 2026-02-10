<?php
// FUTURE AUTOMOTIVE - API: Add Part to Catalogue
// إضافة قطعة إلى الكتالوج

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

try {
    require_once '../config.php';
    $database = new Database();
    $pdo = $database->connect();
    
    // Valider les données
    $code_article = trim($_POST['code_article'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $categorie = $_POST['categorie'] ?? 'Divers';
    $prix_unitaire = floatval($_POST['prix_unitaire'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    
    if (empty($code_article) || empty($designation)) {
        throw new Exception('Code article et designation sont obligatoires');
    }
    
    // Vérifier si le code existe déjà
    $stmt = $pdo->prepare("SELECT id FROM articles_catalogue WHERE code_article = ?");
    $stmt->execute([$code_article]);
    if ($stmt->fetch()) {
        throw new Exception('Ce code article existe déjà');
    }
    
    // Insérer la nouvelle pièce
    $stmt = $pdo->prepare("
        INSERT INTO articles_catalogue (code_article, designation, categorie, prix_unitaire, description, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    ");
    
    $stmt->execute([$code_article, $designation, $categorie, $prix_unitaire, $description]);
    
    // Récupérer la pièce insérée
    $stmt = $pdo->prepare("SELECT * FROM articles_catalogue WHERE code_article = ?");
    $stmt->execute([$code_article]);
    $part = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'message' => 'Pièce ajoutée avec succès',
        'part' => [
            'code_article' => $part['code_article'],
            'recherche_complet' => $part['code_article'] . ' - ' . $part['designation']
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

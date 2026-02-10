<?php
// ATEO Auto - API pour ajouter un article au catalogue
// Endpoint pour ajouter rapidement un nouvel article

require_once '../config_achat_hostinger.php';
require_once '../config.php';

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

// Traitement de la requête POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $database = new DatabaseAchat();
        $conn = $database->connect();
        
        // Valider les données
        $code_article = trim($_POST['code_article'] ?? '');
        $designation = trim($_POST['designation'] ?? '');
        $categorie = trim($_POST['categorie'] ?? 'Divers');
        $unite = 'pièce'; // Standardized unit
        $description = trim($_POST['description'] ?? '');
        $prix_unitaire = floatval($_POST['prix_unitaire'] ?? 0);
        
        if (empty($code_article) || empty($designation)) {
            echo json_encode(['success' => false, 'message' => 'Le code article et la désignation sont obligatoires']);
            exit();
        }
        
        // Vérifier si le code article existe déjà
        $stmt = $conn->prepare("SELECT id FROM articles_catalogue WHERE code_article = ?");
        $stmt->execute([$code_article]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Ce code article existe déjà']);
            exit();
        }
        
        // Insérer le nouvel article
        $stmt = $conn->prepare("
            INSERT INTO articles_catalogue (code_article, designation, categorie, description, prix_unitaire, created_by) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $created_by = $_SESSION['user_id'] ?? null;
        $stmt->execute([$code_article, $designation, $categorie, $description, $prix_unitaire, $created_by]);
        
        $article_id = $conn->lastInsertId();
        
        // Logger l'action
        logAchat("Ajout article catalogue", "Code: $code_article, Désignation: $designation");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Article ajouté avec succès',
            'article' => [
                'id' => $article_id,
                'code_article' => $code_article,
                'designation' => $designation,
                'categorie' => $categorie,
                'unite' => $unite,
                'recherche_complet' => $code_article . ' - ' . $designation
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?>

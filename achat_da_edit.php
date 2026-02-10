<?php
// ATEO Auto - DA Edit Interface
// Interface pour modifier les demandes d'achat (DA)

require_once 'config_achat_hostinger.php';
require_once 'config.php';

// Vérifier si l'utilisateur est connecté
if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = 'achat_da_edit.php?id=' . ($_GET['id'] ?? '');
    header('Location: login.php');
    exit();
}

// Obtenir les informations de l'utilisateur
$user = get_logged_in_user();
$full_name = $user['full_name'] ?? $_SESSION['full_name'] ?? 'Administrateur';
$role = $_SESSION['role'] ?? 'admin';

// Vérifier l'ID de la DA
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "ID de demande d'achat invalide";
    header('Location: achat_da.php');
    exit();
}

$da_id = intval($_GET['id']);

// Récupérer les données de la DA
$da = null;
$items = [];
try {
    $database = new DatabaseAchat();
    $conn = $database->connect();
    
    // Récupérer la DA principale
    $stmt = $conn->prepare("SELECT * FROM demandes_achat WHERE id = ?");
    $stmt->execute([$da_id]);
    $da = $stmt->fetch();
    
    if (!$da) {
        $_SESSION['error_message'] = "Demande d'achat non trouvée";
        header('Location: achat_da.php');
        exit();
    }
    
    // Récupérer les articles de la DA
    $stmt = $conn->prepare("SELECT * FROM purchase_items WHERE parent_type = 'DA' AND parent_id = ?");
    $stmt->execute([$da_id]);
    $items = $stmt->fetchAll();
    
    // Récupérer les articles du catalogue
    $stmt = $conn->query("
        SELECT id, code_article, designation, categorie, 'pièce' as unite, 
               CONCAT(code_article, ' - ', designation) as recherche_complet
        FROM articles_catalogue 
        ORDER BY code_article
    ");
    $articles_catalogue = $stmt->fetchAll();
    
    // Récupérer les bus disponibles
    $bus_list = [];
    try {
        $stmt = $conn->query("
            SELECT id, bus_number, license_plate, make, model 
            FROM buses 
            WHERE status = 'active' 
            ORDER BY bus_number
        ");
        $bus_list = $stmt->fetchAll();
        
    } catch (Exception $e) {
        $error_message = "Erreur lors du chargement des bus: " . $e->getMessage();
    }
    
} catch (Exception $e) {
    $error_message = "Erreur lors du chargement: " . $e->getMessage();
}

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $database = new DatabaseAchat();
        $conn = $database->connect();
        
        // Validation des champs obligatoires
        $validation_errors = [];
        
        if (!empty($_POST['items'])) {
            foreach ($_POST['items'] as $index => $item) {
                if (!empty($item['ref_article']) && !empty($item['quantite'])) {
                    // Vérifier si le prix unitaire est renseigné
                    if (empty($item['prix_unitaire']) || floatval($item['prix_unitaire']) <= 0) {
                        $validation_errors[] = "L'article " . ($index + 1) . " doit avoir un prix unitaire valide";
                    }
                    
                    // Vérifier si le bus ID est valide
                    if (empty($item['bus_id'])) {
                        $validation_errors[] = "L'article " . ($index + 1) . " doit avoir un numéro de bus";
                    } else {
                        // Vérifier si le bus existe dans la base de données
                        $stmt = $conn->prepare("SELECT id FROM buses WHERE id = ? AND status = 'active'");
                        $stmt->execute([$item['bus_id']]);
                        if (!$stmt->fetch()) {
                            $validation_errors[] = "Le bus ID " . $item['bus_id'] . " n'existe pas ou n'est pas actif";
                        }
                    }
                }
            }
        }
        
        if (!empty($validation_errors)) {
            throw new Exception(implode('<br>', $validation_errors));
        }
        // Mettre à jour la DA principale
        $stmt = $conn->prepare("
            UPDATE demandes_achat 
            SET demandeur = ?, priorite = ?, commentaires = ?, date_modification = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $_POST['agent'],
            $_POST['priorite'] ?? 'Normal',
            $_POST['commentaires'] ?? '',
            $da_id
        ]);

        // Workflow simplifié: la DA reste validée
        $stmt = $conn->prepare("UPDATE demandes_achat SET statut = 'Validé' WHERE id = ?");
        $stmt->execute([$da_id]);
        
        // Supprimer les anciens articles
        $stmt = $conn->prepare("DELETE FROM purchase_items WHERE parent_type = 'DA' AND parent_id = ?");
        $stmt->execute([$da_id]);
        
        // Insérer les nouveaux articles
        if (!empty($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                if (!empty($item['ref_article']) && !empty($item['quantite'])) {
                    // Validation finale des champs obligatoires
                    if (empty($item['prix_unitaire']) || floatval($item['prix_unitaire']) <= 0) {
                        throw new Exception("Prix unitaire invalide pour l'article: " . $item['ref_article']);
                    }
                    
                    if (empty($item['bus_id'])) {
                        throw new Exception("Numéro de bus obligatoire pour l'article: " . $item['ref_article']);
                    }
                    
                    // Vérifier si le bus existe et est actif
                    $stmt = $conn->prepare("SELECT id FROM buses WHERE id = ? AND status = 'active'");
                    $stmt->execute([$item['bus_id']]);
                    if (!$stmt->fetch()) {
                        throw new Exception("Le bus ID " . $item['bus_id'] . " n'existe pas ou n'est pas actif");
                    }
                    
                    // Récupérer les détails de l'article depuis le catalogue
                    $stmt = $conn->prepare("
                        SELECT designation, 'pièce' as unite FROM articles_catalogue 
                        WHERE code_article = ?
                    ");
                    $stmt->execute([$item['ref_article']]);
                    $article = $stmt->fetch();
                    
                    if ($article) {
                        $stmt = $conn->prepare("
                            INSERT INTO purchase_items (parent_type, parent_id, ref_article, designation, quantite, unite, prix_unitaire, total_ligne, bus_id, region) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        
                        $total_ligne = floatval($item['quantite']) * floatval($item['prix_unitaire']);
                        
                        $stmt->execute([
                            'DA',
                            $da_id,
                            $item['ref_article'],
                            $article['designation'],
                            $item['quantite'],
                            $article['unite'],
                            $item['prix_unitaire'],
                            $total_ligne,
                            $item['bus_id'],
                            $item['region'] ?? null
                        ]);
                    }
                }
            }
        }
        
        // (suppression) transition brouillon -> en attente: non utilisée dans le workflow simplifié
        
        $_SESSION['success_message'] = "Demande d'achat modifiée avec succès!";
        header("Location: achat_da.php");
        exit();
        
    } catch (Exception $e) {
        $error_message = "Erreur lors de la modification: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier DA - <?php echo htmlspecialchars($da['ref_da'] ?? ''); ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .da-header {
            background: linear-gradient(135deg, #6f42c1 0%, #8b5cf6 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .item-row {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #6f42c1;
        }
        
        .btn-remove {
            background: #dc3545;
            border: none;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 5px;
        }
        
        .btn-remove:hover {
            background: #c82333;
        }
        
        .form-floating label {
            color: #6c757d;
        }
        
        .form-floating input:focus ~ label,
        .form-floating select:focus ~ label,
        .form-floating textarea:focus ~ label {
            color: #6f42c1;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <!-- Header -->
            <div class="da-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h1 class="mb-0">
                            <i class="fas fa-edit me-3"></i>
                            Modifier Demande d'Achat
                        </h1>
                        <p class="mb-0 mt-2 opacity-75">
                            Référence: <?php echo htmlspecialchars($da['ref_da'] ?? ''); ?>
                        </p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <span class="badge bg-white text-dark">
                            <i class="fas fa-info-circle me-1"></i>
                            Statut: <?php echo htmlspecialchars($da['statut'] ?? ''); ?>
                        </span>
                    </div>
                </div>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-file-alt me-2"></i>
                        Informations de la Demande
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="daForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="ref_da" 
                                           value="<?php echo htmlspecialchars($da['ref_da'] ?? ''); ?>" readonly>
                                    <label for="ref_da">Référence DA</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="agent" 
                                           name="agent" value="<?php echo htmlspecialchars($da['demandeur'] ?? $full_name); ?>" required>
                                    <label for="agent">Demandeur *</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <select class="form-select" id="priorite" name="priorite">
                                        <option value="Normal" <?php echo ($da['priorite'] ?? '') === 'Normal' ? 'selected' : ''; ?>>Normal</option>
                                        <option value="Urgent" <?php echo ($da['priorite'] ?? '') === 'Urgent' ? 'selected' : ''; ?>>Urgent</option>
                                        <option value="Très Urgent" <?php echo ($da['priorite'] ?? '') === 'Très Urgent' ? 'selected' : ''; ?>>Très Urgent</option>
                                    </select>
                                    <label for="priorite">Priorité</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="date" class="form-control" id="date_creation" 
                                           value="<?php echo date('Y-m-d', strtotime($da['date_creation'] ?? 'now')); ?>" readonly>
                                    <label for="date_creation">Date de création</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-floating mb-3">
                            <textarea class="form-control" id="commentaires" name="commentaires" rows="3" 
                                      placeholder="Ajouter des commentaires..."><?php echo htmlspecialchars($da['commentaires'] ?? ''); ?></textarea>
                            <label for="commentaires">Commentaires</label>
                        </div>
                        
                        <!-- Articles Section -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-list me-2"></i>
                                    Articles de la Demande
                                </h6>
                            </div>
                            <div class="card-body">
                                <div id="itemsContainer">
                                    <?php foreach ($items as $index => $item): ?>
                                        <div class="item-row" data-index="<?php echo $index; ?>">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="form-floating">
                                                        <select class="form-select" name="items[<?php echo $index; ?>][ref_article]" required>
                                                            <option value="">Sélectionner un article</option>
                                                            <?php foreach ($articles_catalogue as $article): ?>
                                                                <option value="<?php echo $article['code_article']; ?>" 
                                                                        <?php echo $item['ref_article'] === $article['code_article'] ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($article['recherche_complet']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <label>Article *</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-floating">
                                                        <input type="number" class="form-control" name="items[<?php echo $index; ?>][quantite]" 
                                                               value="<?php echo $item['quantite']; ?>" required min="1" step="0.01">
                                                        <label>Quantité *</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-floating">
                                                        <input type="number" class="form-control" name="items[<?php echo $index; ?>][prix_unitaire]" 
                                                               value="<?php echo $item['prix_unitaire']; ?>" step="0.01" min="0.01" required>
                                                        <label>Prix unitaire *</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-floating">
                                                        <select name="items[<?php echo $index; ?>][bus_id]" class="form-control" required>
                                                            <option value="">Sélectionner un bus</option>
                                                            <?php foreach ($bus_list as $bus): ?>
                                                                <option value="<?php echo $bus['id']; ?>" 
                                                                        <?php echo ($item['bus_id'] == $bus['id']) ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($bus['bus_number'] . ' - ' . $bus['license_plate']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <label>Bus *</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-floating">
                                                        <input type="text" class="form-control" name="items[<?php echo $index; ?>][region]" 
                                                               value="<?php echo htmlspecialchars($item['region'] ?? ''); ?>" placeholder="Région">
                                                        <label>Région</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-1">
                                                    <button type="button" class="btn btn-remove" onclick="removeItem(this)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <button type="button" class="btn btn-outline-primary mt-3" onclick="addItem()">
                                    <i class="fas fa-plus me-2"></i>Ajouter un article
                                </button>
                            </div>
                        </div>
                        
                        <!-- Buttons -->
                        <div class="d-flex justify-content-between mt-4">
                            <div>
                                <a href="achat_da.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Retour
                                </a>
                            </div>
                            <div>
                                <button type="button" class="btn btn-outline-secondary me-2" onclick="resetForm()">
                                    <i class="fas fa-redo me-2"></i>Réinitialiser
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Enregistrer les modifications
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let itemIndex = <?php echo max(array_keys($items)) + 1; ?>;
        
        function addItem() {
            const container = document.getElementById('itemsContainer');
            const itemRow = document.createElement('div');
            itemRow.className = 'item-row';
            itemRow.dataset.index = itemIndex;
            
            itemRow.innerHTML = `
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-floating">
                            <select class="form-select" name="items[${itemIndex}][ref_article]" required>
                                <option value="">Sélectionner un article</option>
                                <?php foreach ($articles_catalogue as $article): ?>
                                    <option value="<?php echo $article['code_article']; ?>">
                                        <?php echo htmlspecialchars($article['recherche_complet']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label>Article *</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-floating">
                            <input type="number" class="form-control" name="items[${itemIndex}][quantite]" required min="1" step="0.01">
                            <label>Quantité *</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-floating">
                            <input type="number" class="form-control" name="items[${itemIndex}][prix_unitaire]" step="0.01" min="0.01" required>
                            <label>Prix unitaire *</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-floating">
                            <select name="items[${itemIndex}][bus_id]" class="form-control" required>
                                <option value="">Sélectionner un bus</option>
                                <?php foreach ($bus_list as $bus): ?>
                                    <option value="<?php echo $bus['id']; ?>">
                                        <?php echo htmlspecialchars($bus['bus_number'] . ' - ' . $bus['license_plate']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label>Bus *</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-floating">
                            <input type="text" class="form-control" name="items[${itemIndex}][region]" placeholder="Région">
                            <label>Région</label>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-remove" onclick="removeItem(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            
            container.appendChild(itemRow);
            itemIndex++;
        }
        
        function removeItem(button) {
            const itemRow = button.closest('.item-row');
            itemRow.remove();
        }
        
        function resetForm() {
            if (confirm('Réinitialiser le formulaire? Toutes les modifications seront perdues.')) {
                document.getElementById('daForm').reset();
            }
        }
        
        // Validation du formulaire
        document.getElementById('daForm').addEventListener('submit', function(e) {
            const items = document.querySelectorAll('.item-row');
            let hasValidItem = false;
            
            items.forEach(item => {
                const refArticle = item.querySelector('select[name*="ref_article"]').value;
                const quantite = item.querySelector('input[name*="quantite"]').value;
                
                if (refArticle && quantite) {
                    hasValidItem = true;
                }
            });
            
            if (!hasValidItem) {
                e.preventDefault();
                alert('Veuillez ajouter au moins un article valide.');
            }
        });
    </script>
</body>
</html>

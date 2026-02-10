<?php
// ATEO Auto - Demande d'Achat Interface (Clean Version)
// Interface pour créer et gérer les demandes d'achat (DA) avec catalogue d'articles

require_once 'config_achat_hostinger.php';
require_once 'config.php';

// Vérifier si l'utilisateur est connecté
if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = 'achat_da.php';
    header('Location: login.php');
    exit();
}

// Obtenir les informations de l'utilisateur
$user = get_logged_in_user();
$full_name = $user['full_name'] ?? $_SESSION['full_name'] ?? 'Administrateur';
$role = $_SESSION['role'] ?? 'admin';

// Récupérer les articles du catalogue
$articles_catalogue = [];
try {
    $database = new DatabaseAchat();
    $conn = $database->connect();
    
    $stmt = $conn->query("
        SELECT id, code_article, designation, categorie, 'pièce' as unite, 
               CONCAT(code_article, ' - ', designation) as recherche_complet
        FROM articles_catalogue 
        ORDER BY code_article
    ");
    $articles_catalogue = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error_message = "Erreur lors du chargement du catalogue: " . $e->getMessage();
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $database = new DatabaseAchat();
        $conn = $database->connect();
        
        // Générer la référence DA
        $ref_da = generateReference('DA', 'demandes_achat', 'ref_da');
        
        // Validation des champs obligatoires
        $validation_errors = [];
        
        if (!empty($_POST['items'])) {
            foreach ($_POST['items'] as $index => $item) {
                if (!empty($item['ref_article']) && !empty($item['quantite'])) {
                    // Vérifier si le prix unitaire est renseigné
                    if (empty($item['prix_unitaire']) || floatval($item['prix_unitaire']) <= 0) {
                        $validation_errors[] = "L'article " . ($index + 1) . " doit avoir un prix unitaire valide";
                    }
                    
                    // Vérifier si le bus ID est renseigné
                    if (empty($item['bus_id'])) {
                        $validation_errors[] = "L'article " . ($index + 1) . " doit avoir un numéro de bus";
                    }
                }
            }
        }
        
        if (!empty($validation_errors)) {
            throw new Exception(implode('<br>', $validation_errors));
        }
        // Insérer la demande d'achat principale
        $stmt = $conn->prepare("
            INSERT INTO demandes_achat (ref_da, demandeur, statut, priorite, commentaires) 
            VALUES (?, ?, ?, ?, ?)
        ");

        // Acceptation automatique
        $statut = 'Validé';
        $stmt->execute([
            $ref_da,
            $_POST['agent'],
            $statut,
            $_POST['priorite'] ?? 'Normal',
            $_POST['commentaires'] ?? ''
        ]);
        
        $da_id = $conn->lastInsertId();
        
        // Insérer les articles
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
        
        // Logger l'action
        logAchat("Création DA", "Référence: $ref_da, Articles: " . count($_POST['items']));
        
        $_SESSION['success_message'] = "Demande d'achat $ref_da créée avec succès!";
        header("Location: achat_da.php");
        exit();
        
    } catch (Exception $e) {
        $error_message = "Erreur lors de la création de la demande: " . $e->getMessage();
    }
}

// Récupérer les demandes existantes
$demandes = [];
try {
    $database = new DatabaseAchat();
    $conn = $database->connect();
    
    // First get all DA
    $stmt = $conn->query("
        SELECT demandes_achat.*, 
               COUNT(purchase_items.id) as nombre_articles,
               SUM(purchase_items.total_ligne) as montant_total
        FROM demandes_achat
        LEFT JOIN purchase_items ON demandes_achat.id = purchase_items.parent_id AND purchase_items.parent_type = 'DA'
        GROUP BY demandes_achat.id
        ORDER BY demandes_achat.date_creation DESC
    ");
    $all_da = $stmt->fetchAll();
    
    // Add DP count to each DA
    $demandes = [];
    foreach ($all_da as $da) {
        // Check DP count
        $stmt_dp = $conn->prepare("SELECT COUNT(*) as count FROM demandes_prix WHERE da_id = ?");
        $stmt_dp->execute([$da['id']]);
        $da['dp_count'] = $stmt_dp->fetch()['count'];
        
        $demandes[] = $da;
    }
    
} catch (Exception $e) {
    $error_message = "Erreur lors du chargement des demandes: " . $e->getMessage();
}

// Récupérer la liste des bus
$bus_list = [];
$hasBusRegionCode = false;
try {
    $database = new Database();
    $conn = $database->connect();

    try {
        $col = $conn->query("SHOW COLUMNS FROM buses LIKE 'region_code'")->fetch();
        $hasBusRegionCode = $col ? true : false;
    } catch (Exception $e) {
        $hasBusRegionCode = false;
    }

    if ($hasBusRegionCode) {
        $stmt = $conn->query("SELECT id, bus_number, make, model, region_code FROM buses WHERE status = 'active' ORDER BY bus_number");
    } else {
        $stmt = $conn->query("SELECT id, bus_number, make, model FROM buses WHERE status = 'active' ORDER BY bus_number");
    }
    $bus_list = $stmt->fetchAll();
    
} catch (Exception $e) {
    // Continuer sans la liste des bus
}

// Récupérer la liste des utilisateurs (agents)
$users_list = [];
try {
    $database = new Database();
    $conn = $database->connect();
    
    // Check if users table has is_active column
    $userCols = [];
    try {
        $userCols = $conn->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        $userCols = [];
    }
    $hasIsActive = in_array('is_active', $userCols, true);
    
    // Get active users
    if ($hasIsActive) {
        $stmt = $conn->query("SELECT id, username, full_name FROM users WHERE is_active = 1 ORDER BY full_name");
    } else {
        $stmt = $conn->query("SELECT id, username, full_name FROM users ORDER BY full_name");
    }
    $users_list = $stmt->fetchAll();
    
} catch (Exception $e) {
    // Continuer sans la liste des utilisateurs
}
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande d'Achat - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@x.x.x/dist/select2-bootstrap4.min.css" rel="stylesheet" />
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        .main-content {
            margin-left: 260px;
            padding: 2rem;
            transition: margin-left 0.3s ease;
        }
        
        .da-modal {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .da-modal .modal-title {
            color: #333;
            font-weight: 600;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #808000;
            box-shadow: 0 0 0 0.2rem rgba(128, 128, 0, 0.25);
        }
        
        .required-star {
            color: #dc3545;
            margin-left: 4px;
        }
        
        .btn-olive {
            background: linear-gradient(135deg, #808000, #6b6b00);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-olive:hover {
            background: linear-gradient(135deg, #6b6b00, #585800);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(128, 128, 0, 0.3);
            color: white;
        }
        
        .btn-add-article {
            background: #28a745;
            border: none;
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-left: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-add-article:hover {
            background: #218838;
            transform: scale(1.1);
        }
        
        .item-row {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid #e9ecef;
        }
        
        .btn-remove-item {
            background: #dc3545;
            border: none;
            color: white;
            border-radius: 5px;
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }
        
        .btn-remove-item:hover {
            background: #c82333;
        }
        
        .select2-container--bootstrap4 .select2-selection {
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            min-height: 45px;
        }
        
        .select2-container--bootstrap4 .select2-selection--single {
            padding: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Include header -->
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <!-- Include sidebar -->
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <nav aria-label="breadcrumb" class="mb-2">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="buses.php">Accueil</a></li>
                    <li class="breadcrumb-item active">Liste des demandes d'achats</li>
                </ol>
            </nav>
            <!-- Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="mb-2">
                        <i class="fas fa-file-alt me-3"></i>
                        Demande d'Achat
                    </h1>
                    <p class="text-muted">Créer et gérer les demandes d'achat</p>
                </div>
            </div>
            
            <?php include __DIR__ . '/includes/achat_tabs.php'; ?>
            
            <!-- Messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- DA Modal Form -->
            <div class="da-modal">
                <h2 class="modal-title">
                    <i class="fas fa-plus-circle me-2"></i>
                    L'ajout d'une nouvelle demande d'achat
                </h2>
                
                <form id="daForm" method="POST" action="">
                    
                    <!-- Row 1: Agent (infos générales) -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="agent" class="form-label">
                                Agent <span class="required-star">*</span>
                            </label>
                            <select class="form-select" id="agent" name="agent" required>
                                <option value="">Sélectionner un agent</option>
                                <?php foreach ($users_list as $user): ?>
                                    <option value="<?php echo htmlspecialchars($user['full_name']); ?>" 
                                            <?php echo $user['full_name'] === $full_name ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['full_name']); ?>
                                        <?php if (!empty($user['username'])): ?>
                                            (<?php echo htmlspecialchars($user['username']); ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Dynamic Items Section -->
                    <div class="mb-4">
                        <h5 class="mb-3 d-flex justify-content-between align-items-center">
                            <span>
                                <i class="fas fa-list me-2"></i>
                                Articles de la demande
                            </span>
                            <button type="button" class="btn btn-sm btn-success" onclick="showAddArticleModal()">
                                <i class="fas fa-plus me-1"></i>Nouvel article catalogue
                            </button>
                        </h5>
                        <div id="itemsContainer">
                            <!-- Items will be added here dynamically -->
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="addItemBtn">
                            <i class="fas fa-plus me-2"></i>Ajouter un article
                        </button>
                    </div>
                    
                    <!-- Additional Fields -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="priorite" class="form-label">Priorité</label>
                            <select class="form-select" id="priorite" name="priorite">
                                <option value="Normal">Normal</option>
                                <option value="Urgent">Urgent</option>
                                <option value="Très Urgent">Très Urgent</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="commentaires" class="form-label">Commentaires</label>
                            <textarea class="form-control" id="commentaires" name="commentaires" rows="2" 
                                      placeholder="Ajouter des commentaires..."></textarea>
                        </div>
                    </div>
                    
                    <!-- Buttons Simple -->
                    <div class="d-flex justify-content-between">
                        <div class="ms-auto">
                            <button type="button" class="btn btn-outline-secondary me-2" id="resetBtn">
                                <i class="fas fa-redo me-2"></i>Réinitialiser
                            </button>
                            <button type="submit" name="submit" value="1" class="btn btn-olive">
                                <i class="fas fa-paper-plane me-2"></i>Soumettre
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Existing DA List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        Demandes d'achat existantes
                    </h5>
                </div>
                <div class="card-body">
                    <?php
                        $demandes_actives = [];
                        $demandes_archive = [];
                        foreach (($demandes ?? []) as $da_item) {
                            if (in_array($da_item['statut'], ['Annulé'])) {
                                $demandes_archive[] = $da_item;
                            } else {
                                $demandes_actives[] = $da_item;
                            }
                        }
                    ?>
                    <?php if (empty($demandes)): ?>
                        <p class="text-muted">Aucune demande d'achat trouvée.</p>
                    <?php else: ?>
                        <h6 class="mb-3">
                            <i class="fas fa-bolt me-2"></i>
                            En cours (modifiable)
                        </h6>

                        <?php if (empty($demandes_actives)): ?>
                            <p class="text-muted">Aucune demande en cours.</p>
                        <?php else: ?>
                            <div class="table-responsive mb-4">
                                <table class="table table-striped align-middle">
                                    <thead>
                                        <tr>
                                            <th>Référence</th>
                                            <th>Demandeur</th>
                                            <th>Date</th>
                                            <th>Articles</th>
                                            <th>Montant</th>
                                            <th>DP</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($demandes_actives as $da): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($da['ref_da']); ?></td>
                                                <td><?php echo htmlspecialchars($da['demandeur']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($da['date_creation'])); ?></td>
                                                <td><?php echo intval($da['nombre_articles'] ?? 0); ?></td>
                                                <td><?php echo number_format(floatval($da['montant_total'] ?? 0), 2, ',', ' '); ?> DH</td>
                                                <td><?php echo intval($da['dp_count'] ?? 0); ?></td>
                                                <td class="d-flex gap-2 flex-wrap">
                                                    <a href="achat_da_view.php?id=<?php echo $da['id']; ?>" class="btn btn-sm btn-outline-primary" title="Voir">
                                                        <i class="fas fa-eye"></i>
                                                    </a>

                                                    <?php if (($da['dp_count'] ?? 0) == 0): ?>
                                                        <a href="achat_da_edit.php?id=<?php echo $da['id']; ?>" class="btn btn-sm btn-outline-warning" title="Modifier">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="achat_da_delete.php?id=<?php echo $da['id']; ?>" class="btn btn-sm btn-outline-danger" title="Supprimer" onclick="return confirm('Supprimer cette demande d\'achat?');">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                        <h6 class="mb-3">
                            <i class="fas fa-archive me-2"></i>
                            Archive (terminé)
                        </h6>

                        <?php if (empty($demandes_archive)): ?>
                            <p class="text-muted">Aucune demande archivée.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped align-middle">
                                    <thead>
                                        <tr>
                                            <th>Référence</th>
                                            <th>Demandeur</th>
                                            <th>Date</th>
                                            <th>Articles</th>
                                            <th>Montant</th>
                                            <th>DP</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($demandes_archive as $da): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($da['ref_da']); ?></td>
                                                <td><?php echo htmlspecialchars($da['demandeur']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($da['date_creation'])); ?></td>
                                                <td><?php echo intval($da['nombre_articles'] ?? 0); ?></td>
                                                <td><?php echo number_format(floatval($da['montant_total'] ?? 0), 2, ',', ' '); ?> DH</td>
                                                <td><?php echo intval($da['dp_count'] ?? 0); ?></td>
                                                <td>
                                                    <a href="achat_da_view.php?id=<?php echo $da['id']; ?>" class="btn btn-sm btn-outline-primary" title="Voir">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Article Modal -->
    <div class="modal fade" id="addArticleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>
                        Ajouter un nouvel article au catalogue
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addArticleForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="newCodeArticle" class="form-label">
                                Code Article <span class="required-star">*</span>
                            </label>
                            <input type="text" class="form-control" id="newCodeArticle" name="code_article" required>
                        </div>
                        <div class="mb-3">
                            <label for="newDesignation" class="form-label">
                                Désignation <span class="required-star">*</span>
                            </label>
                            <input type="text" class="form-control" id="newDesignation" name="designation" required>
                        </div>
                        <div class="mb-3">
                            <label for="newCategorie" class="form-label">Catégorie</label>
                            <select class="form-select" id="newCategorie" name="categorie">
                                <option value="Filtres">Filtres</option>
                                <option value="Freinage">Freinage</option>
                                <option value="Moteur">Moteur</option>
                                <option value="Refroidissement">Refroidissement</option>
                                <option value="Suspension">Suspension</option>
                                <option value="Électrique">Électrique</option>
                                <option value="Éclairage">Éclairage</option>
                                <option value="Liquides">Liquides</option>
                                <option value="Accessoires">Accessoires</option>
                                <option value="Divers">Divers</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="newUnite" class="form-label">Unité</label>
                            <select class="form-select" id="newUnite" name="unite">
                                <option value="pièce">pièce</option>
                                <option value="jeu">jeu</option>
                                <option value="paire">paire</option>
                                <option value="litre">litre</option>
                                <option value="kg">kg</option>
                                <option value="mètre">mètre</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="newPrixUnitaire" class="form-label">Prix Unitaire</label>
                            <input type="number" class="form-control" id="newPrixUnitaire" name="prix_unitaire" 
                                   min="0" step="0.01" placeholder="0.00">
                        </div>
                        <div class="mb-3">
                            <label for="newDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="newDescription" name="description" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-plus me-2"></i>Ajouter l'article
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Include footer -->
    <?php include __DIR__ . '/includes/footer.php'; ?>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.form-select').select2({
                theme: 'bootstrap4',
                width: '100%'
            });
            
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipTriggerList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            let itemCount = 0;

            const busRegionMap = <?php
                $tmp = [];
                if (!empty($bus_list)) {
                    foreach ($bus_list as $b) {
                        if (isset($b['id'])) {
                            $tmp[(string)$b['id']] = $hasBusRegionCode ? ($b['region_code'] ?? '') : '';
                        }
                    }
                }
                echo json_encode($tmp);
            ?>;
            
            // Add item function
            function addItem() {
                itemCount++;
                const itemHtml = `
                    <div class="item-row" data-item-id="${itemCount}">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <label class="form-label">Réf <span class="required-star">*</span></label>
                                <select class="form-select" name="items[${itemCount}][ref_article]" required>
                                    <option value="">Sélectionner un article</option>
                                    <?php foreach ($articles_catalogue as $article): ?>
                                        <option value="<?php echo htmlspecialchars($article['code_article']); ?>">
                                            <?php echo htmlspecialchars($article['recherche_complet']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Quantité <span class="required-star">*</span></label>
                                <input type="number" class="form-control" name="items[${itemCount}][quantite]" 
                                       min="1" step="0.01" required placeholder="Qté">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Prix unitaire <span class="required-star">*</span></label>
                                <input type="number" class="form-control" name="items[${itemCount}][prix_unitaire]" 
                                       min="0.01" step="0.01" placeholder="Prix" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">N° Bus <span class="required-star">*</span></label>
                                <select class="form-select bus-select" name="items[${itemCount}][bus_id]" required>
                                    <option value="">Sélectionner</option>
                                    <?php foreach ($bus_list as $bus): ?>
                                        <option value="<?php echo $bus['id']; ?>">
                                            <?php echo htmlspecialchars($bus['bus_number'] ?? $bus['immatriculation'] ?? ''); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Région</label>
                                <select class="form-select region-select" name="items[${itemCount}][region]">
                                    <option value="">Sélectionner</option>
                                    <option value="tetouan">Tétouan</option>
                                    <option value="ksar">Ksar-Larache</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">&nbsp;</label>
                                <button type="button" class="btn btn-remove-item w-100" onclick="removeItem(${itemCount})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                
                $('#itemsContainer').append(itemHtml);
                
                // Re-initialize Select2 for new elements
                $('#itemsContainer .form-select').select2({
                    theme: 'bootstrap4',
                    width: '100%'
                });
            }

            function autoSetRegionForRow($row) {
                const busId = ($row.find('select.bus-select').val() || '').toString();
                const region = (busRegionMap && busRegionMap[busId]) ? busRegionMap[busId] : '';
                if (!region) return;
                const $regionSelect = $row.find('select.region-select');
                if ($regionSelect.length) {
                    $regionSelect.val(region).trigger('change');
                }
            }
            
            // Remove item function
            function removeItem(itemId) {
                $(`.item-row[data-item-id="${itemId}"]`).remove();
            }
            
            // Event handlers
            $('#addItemBtn').click(addItem);

            // Auto-select region when bus changes
            $('#itemsContainer').on('change', 'select.bus-select', function() {
                autoSetRegionForRow($(this).closest('.item-row'));
            });
            
            // Add first item by default
            addItem();
            
            // Reset form
            $('#resetBtn').click(function() {
                if (confirm('Êtes-vous sûr de vouloir réinitialiser le formulaire?')) {
                    $('#daForm')[0].reset();
                    $('#itemsContainer').empty();
                    itemCount = 0;
                    addItem();
                }
            });
            
            // Show add article modal
            window.showAddArticleModal = function() {
                $('#addArticleModal').modal('show');
            };
            
            // Handle add article form submission
            $('#addArticleForm').submit(function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: 'api/add_article_catalogue.php',
                    method: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Add new article to all select dropdowns
                            const newOption = `<option value="${response.article.code_article}">${response.article.recherche_complet}</option>`;
                            $('select[name*="ref_article"]').append(newOption);
                            
                            // Close modal and reset form
                            $('#addArticleModal').modal('hide');
                            $('#addArticleForm')[0].reset();
                            
                            // Show success message
                            alert('Article ajouté avec succès!');
                        } else {
                            alert('Erreur: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Erreur lors de l\'ajout de l\'article');
                    }
                });
            });
        });
        
        // Helper function for status badge class
        function getStatutBadgeClass(statut) {
            const classes = {
                'Brouillon': 'secondary',
                'En attente': 'warning',
                'Validé': 'success',
                'Rejeté': 'danger',
                'Annulé': 'dark'
            };
            return classes[statut] || 'secondary';
        }
    </script>
</body>
</html>

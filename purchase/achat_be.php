<?php
// ATEO Auto - Bon d'Entrée Interface (New Version)
// Interface pour gérer les bons d'entrée (BE) avec intégration stock

require_once 'config_achat_hostinger.php';
require_once 'config.php';

// Vérifier si l'utilisateur est connecté
if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = 'achat_be.php';
    header('Location: login.php');
    exit();
}

// Obtenir les informations de l'utilisateur
$user = get_logged_in_user();
$full_name = $user['full_name'] ?? $_SESSION['full_name'] ?? 'Administrateur';
$role = $_SESSION['role'] ?? 'admin';

// BC pré-sélectionné depuis l'URL (ex: depuis achat_bc.php ou achat_bc_view.php)
$preselect_bc_id = isset($_GET['bc_id']) ? (int)$_GET['bc_id'] : 0;

// Récupérer les bons de commande disponibles
$bons_commande = [];
try {
    $database = new DatabaseAchat();
    $conn = $database->connect();
    
    $stmt = $conn->query("
        SELECT bc.id,
               bc.ref_bc,
               dp.fournisseur_id AS fournisseur_id,
               s.nom_fournisseur,
               COUNT(bci.id) AS nombre_articles,
               bc.statut
        FROM bons_commande bc
        LEFT JOIN demandes_prix dp ON bc.dp_id = dp.id
        LEFT JOIN suppliers s ON dp.fournisseur_id = s.id
        LEFT JOIN bc_items bci ON bc.id = bci.bc_id
        WHERE bc.statut IN ('Commandé', 'Confirmé', 'Livré partiellement', 'Livré totalement')
          AND bc.id NOT IN (SELECT bc_id FROM bons_entree WHERE bc_id IS NOT NULL)
        GROUP BY bc.id
        ORDER BY bc.date_commande DESC
    ");
    $bons_commande = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error_message = "Erreur lors du chargement des bons de commande: " . $e->getMessage();
}

// Récupérer les articles du catalogue
$articles_catalogue = [];
try {
    $db_achat = new DatabaseAchat();
    $conn_art = $db_achat->connect();
    $stmt = $conn_art->query("
        SELECT id, code_article, designation, categorie, 'pièce' as unite, 
               CONCAT(code_article, ' - ', designation) as recherche_complet
        FROM articles_catalogue 
        ORDER BY code_article
    ");
    $articles_catalogue = $stmt->fetchAll();
} catch (Exception $e) {
    $error_message = ($error_message ?? '') . " Erreur catalogue: " . $e->getMessage();
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $database = new DatabaseAchat();
        $conn = $database->connect();

        $bc_id = isset($_POST['bc_id']) ? (int)$_POST['bc_id'] : 0;
        if ($bc_id <= 0) {
            throw new Exception("Bon de commande invalide.");
        }

        // Empêcher l'ajout du même BC plusieurs fois (sécurité côté serveur)
        $stmt = $conn->prepare("SELECT 1 FROM bons_entree WHERE bc_id = ? LIMIT 1");
        $stmt->execute([$bc_id]);
        if ($stmt->fetchColumn()) {
            throw new Exception("Ce bon de commande a déjà un bon d'entrée. Impossible de l'ajouter مرة أخرى.");
        }
        
        // Générer la référence BE
        $ref_be = generateReference('BE', 'bons_entree', 'ref_be');

        // N° bon de livraison obligatoire
        $bl_reference = trim((string)($_POST['bl_reference'] ?? ''));
        if ($bl_reference === '') {
            throw new Exception("N° bon de livraison obligatoire.");
        }
        
        $region_code = $_POST['region_code'] ?? 'tetouan';
        
        // Insérer le bon d'entrée principal (region_code si colonne existe)
        $cols = $conn->query("SHOW COLUMNS FROM bons_entree")->fetchAll(PDO::FETCH_COLUMN);
        $has_region = in_array('region_code', $cols);

        $has_bl_reference = in_array('bl_reference', $cols);
        if ($has_region && $has_bl_reference) {
            $stmt = $conn->prepare("INSERT INTO bons_entree (ref_be, bc_id, reception_date, receptionnaire, statut, notes, region_code, bl_reference, created_by) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$ref_be, $bc_id, $_POST['reception_date'], $_POST['receptionnaire'], 'Reçu', $_POST['notes'] ?? '', $region_code, $bl_reference, $_SESSION['user_id'] ?? null]);
        } elseif ($has_region && !$has_bl_reference) {
            $stmt = $conn->prepare("INSERT INTO bons_entree (ref_be, bc_id, reception_date, receptionnaire, statut, notes, region_code, created_by) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->execute([$ref_be, $bc_id, $_POST['reception_date'], $_POST['receptionnaire'], 'Reçu', $_POST['notes'] ?? '', $region_code, $_SESSION['user_id'] ?? null]);
        } elseif (!$has_region && $has_bl_reference) {
            $stmt = $conn->prepare("INSERT INTO bons_entree (ref_be, bc_id, reception_date, receptionnaire, statut, notes, bl_reference, created_by) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->execute([$ref_be, $bc_id, $_POST['reception_date'], $_POST['receptionnaire'], 'Reçu', $_POST['notes'] ?? '', $bl_reference, $_SESSION['user_id'] ?? null]);
        } else {
            $stmt = $conn->prepare("INSERT INTO bons_entree (ref_be, bc_id, reception_date, receptionnaire, statut, notes, created_by) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([$ref_be, $bc_id, $_POST['reception_date'], $_POST['receptionnaire'], 'Reçu', $_POST['notes'] ?? '', $_SESSION['user_id'] ?? null]);
        }
        
        $be_id = $conn->lastInsertId();
        
        // Récupérer les détails du BC (fournisseur lié via la DP)
        $stmt = $conn->prepare("
            SELECT dp.fournisseur_id, s.nom_fournisseur
            FROM bons_commande bc
            LEFT JOIN demandes_prix dp ON bc.dp_id = dp.id
            LEFT JOIN suppliers s ON dp.fournisseur_id = s.id
            WHERE bc.id = ?
        ");
        $stmt->execute([$bc_id]);
        $bc_info = $stmt->fetch();
        
        // Insérer les articles du BE
        if (!empty($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                if (!empty($item['ref_article']) && !empty($item['quantite_recue'])) {
                    // Récupérer les détails de l'article depuis le catalogue
                    $stmt = $conn->prepare("
                        SELECT designation, 'pièce' as unite FROM articles_catalogue 
                        WHERE code_article = ?
                    ");
                    $stmt->execute([$item['ref_article']]);
                    $article = $stmt->fetch();
                    
                    if ($article) {
                        // Insérer dans be_items
                        $stmt = $conn->prepare("
                            INSERT INTO be_items (be_id, bc_item_id, item_code, item_description, quantite_commandee, quantite_recue, emplacement, condition_status, notes) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        
                        $stmt->execute([
                            $be_id,
                            $item['bc_item_id'] ?? null,
                            $item['ref_article'],
                            $article['designation'],
                            $item['quantite_commandee'] ?? 0,
                            $item['quantite_recue'],
                            $item['emplacement'] ?? 'Principal',
                            $item['condition_status'] ?? 'bon',
                            $item['notes'] ?? ''
                        ]);
                        
                        // Mettre à jour le stock global (table stock_management du système d'achat d'origine)
                        // On utilise code_piece comme clé unique et on incrémente quantite_actuelle
                        $stmt = $conn->prepare("
                            INSERT INTO stock_management (code_piece, designation, quantite_actuelle, quantite_minimale, unite, emplacement)
                            VALUES (?, ?, ?, 0, ?, ?)
                            ON DUPLICATE KEY UPDATE 
                                quantite_actuelle = quantite_actuelle + VALUES(quantite_actuelle)
                        ");
                        $stmt->execute([
                            $item['ref_article'],                // code_piece
                            $article['designation'],             // designation
                            $item['quantite_recue'],             // quantite_actuelle à ajouter
                            'pièce',                            // unite (standardisée)
                            $item['emplacement'] ?? 'Principal', // emplacement
                        ]);

                        // Récupérer l'ID de la pièce pour historiser le mouvement
                        $piece_id = null;
                        try {
                            $sm = $conn->prepare("SELECT id FROM stock_management WHERE code_piece = ? LIMIT 1");
                            $sm->execute([$item['ref_article']]);
                            $rowSm = $sm->fetch();
                            if ($rowSm) {
                                $piece_id = (int)$rowSm['id'];
                            }
                        } catch (Exception $eSm) {
                            $piece_id = null;
                        }

                        // Logger le mouvement de stock dans stock_movements (structure d'origine)
                        if ($piece_id) {
                            $stmt = $conn->prepare("
                                INSERT INTO stock_movements (piece_id, type_mouvement, quantite, reference_document, date_mouvement, utilisateur, commentaires)
                                VALUES (?, 'Entrée', ?, ?, CURRENT_TIMESTAMP, ?, ?)
                            ");
                            $stmt->execute([
                                $piece_id,
                                $item['quantite_recue'],
                                $ref_be,
                                $_POST['receptionnaire'],
                                'Entrée de stock - BE: ' . $ref_be . ' / ' . $item['ref_article'],
                            ]);
                        }
                        
                        // Mise à jour stock_by_region (Ksar / Tétouan)
                        try {
                            $r = $conn->query("SELECT id FROM regions WHERE code = " . $conn->quote($region_code) . " LIMIT 1")->fetch();
                            $a = $conn->prepare("SELECT id FROM articles_stockables WHERE reference = ? LIMIT 1");
                            $a->execute([$item['ref_article']]);
                            $art = $a->fetch();
                            if ($r && $art) {
                                $conn->prepare("
                                    INSERT INTO stock_by_region (article_id, region_id, stock, stock_minimal)
                                    VALUES (?, ?, ?, 0)
                                    ON DUPLICATE KEY UPDATE stock = stock + ?
                                ")->execute([$art['id'], $r['id'], $item['quantite_recue'], $item['quantite_recue']]);
                            }
                        } catch (Exception $ex) {}
                    }
                }
            }
        }
        
        // Mettre à jour le statut du BC
        $stmt = $conn->prepare("
            UPDATE bons_commande SET statut = ? WHERE id = ?
        ");
        
        $new_statut = STATUT_BC_LIVRE_TOTAL; // 'Livré totalement' (t minuscule, conforme ENUM bons_commande)
        $stmt->execute([$new_statut, $bc_id]);
        
        // Logger l'action
        $loggedItems = $_POST['items'] ?? [];
        if (!is_array($loggedItems)) {
            $loggedItems = [];
        }
        logAchat(
            "Création BE",
            "Référence: $ref_be, BC: {$bc_id}, Articles: " . count($loggedItems)
        );
        
        $_SESSION['success_message'] = "Bon d'entrée $ref_be créé avec succès! Stock mis à jour.";
        header("Location: achat_be.php");
        exit();
        
    } catch (Exception $e) {
        $error_message = "Erreur lors de la création du bon d'entrée: " . $e->getMessage();
    }
}

// Récupérer les bons d'entrée existants
$bons_entree = [];
try {
    $database = new DatabaseAchat();
    $conn = $database->connect();
    
    $stmt = $conn->query("
        SELECT be.*, 
               COUNT(bei.id) as nombre_articles,
               SUM(bei.quantite_commandee) as total_commande,
               SUM(bei.quantite_recue) as total_recu,
               bc.ref_bc,
               s.nom_fournisseur
        FROM bons_entree be
        LEFT JOIN be_items bei ON be.id = bei.be_id
        LEFT JOIN bons_commande bc ON be.bc_id = bc.id
        LEFT JOIN demandes_prix dp ON bc.dp_id = dp.id
        LEFT JOIN suppliers s ON dp.fournisseur_id = s.id
        GROUP BY be.id
        ORDER BY be.reception_date DESC
    ");
    $bons_entree = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error_message = "Erreur lors du chargement des bons d'entrée: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bon d'Entrée - <?php echo APP_NAME; ?></title>
    
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
        
        .be-modal {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .be-modal .modal-title {
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
        
        .form-control:disabled, .form-select:disabled {
            background-color: #f8f9fa;
            cursor: not-allowed;
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
        
        .stock-info {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1rem;
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
                    <li class="breadcrumb-item"><a href="achat_bc.php">Achat</a></li>
                    <li class="breadcrumb-item active">Bon d'Entrée</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h1 class="h4 mb-0">
                    <i class="fas fa-truck-loading me-2"></i>Bon d'Entrée
                </h1>
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
            
            <!-- BE Modal Form -->
            <div class="be-modal">
                <h2 class="modal-title">
                    <i class="fas fa-plus-circle me-2"></i>
                    L'ajout d'un nouveau stock
                </h2>
                
                <form id="beForm" method="POST" action="">
                    <!-- Bon de commande + Fournisseur -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="bc_id" class="form-label">
                                Bon de commande <span class="required-star">*</span>
                            </label>
                            <select class="form-select" id="bc_id" name="bc_id" required>
                                <option value="">Sélectionner un bon de commande</option>
                                <?php foreach ($bons_commande as $bc): ?>
                                    <option value="<?php echo $bc['id']; ?>" data-fournisseur="<?php echo htmlspecialchars($bc['nom_fournisseur'] ?? ''); ?>" <?php echo ($preselect_bc_id && $bc['id'] == $preselect_bc_id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($bc['ref_bc'] . ' - ' . ($bc['nom_fournisseur'] ?? '')); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="fournisseur" class="form-label">Fournisseur</label>
                            <input type="text" class="form-control" id="fournisseur" name="fournisseur" readonly placeholder="Sélectionnez un BC">
                        </div>
                    </div>
                    <!-- Date + N° bon de livraison -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="reception_date" class="form-label">Date <span class="required-star">*</span></label>
                            <input type="date" class="form-control" id="reception_date" name="reception_date" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="bl_reference" class="form-label">N° bon de livraison <span class="required-star">*</span></label>
                            <input type="text" class="form-control" id="bl_reference" name="bl_reference" placeholder="Référence fournisseur" required>
                        </div>
                    </div>
                    <!-- Articles du BC uniquement (chargés après sélection du BC) -->
                    <div class="mb-4">
                        <h5 class="mb-3">
                            <i class="fas fa-list me-2"></i>
                            Articles du BC à réceptionner
                        </h5>
                        <p class="text-muted small mb-2">Sélectionnez un bon de commande ci-dessus : seuls les articles de ce BC seront listés.</p>
                        <div id="itemsContainer">
                            <!-- Rempli automatiquement avec les articles du BC sélectionné -->
                        </div>
                    </div>
                    
                    <!-- Région pour stock (Ksar / Tétouan) -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="region_code" class="form-label">Région stock</label>
                            <select class="form-select" id="region_code" name="region_code">
                                <option value="tetouan">Tétouan</option>
                                <option value="ksar">Ksar-Larache</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Additional Fields -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="receptionnaire" class="form-label">
                                Réceptionnaire <span class="required-star">*</span>
                            </label>
                            <input type="text" class="form-control" id="receptionnaire" name="receptionnaire" 
                                   required value="<?php echo htmlspecialchars($full_name); ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2" 
                                      placeholder="Ajouter des notes..."></textarea>
                        </div>
                    </div>
                    
                    <!-- Buttons -->
                    <div class="d-flex justify-content-between">
                        <div>
                            <button type="button" class="btn btn-outline-secondary" id="resetBtn">
                                <i class="fas fa-redo me-2"></i>Réinitialiser
                            </button>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-olive">
                                <i class="fas fa-plus me-2"></i>Ajouter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Existing BE List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        Bons d'entrée existants
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($bons_entree)): ?>
                        <p class="text-muted">Aucun bon d'entrée trouvé.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Référence</th>
                                        <th>Date</th>
                                        <th>Réceptionnaire</th>
                                        <th>BC Référence</th>
                                        <th>Fournisseur</th>
                                        <th>Qté commandée</th>
                                        <th>Total Reçu</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bons_entree as $be): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($be['ref_be']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($be['reception_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($be['receptionnaire']); ?></td>
                                            <td><?php echo htmlspecialchars($be['ref_bc']); ?></td>
                                            <td><?php echo htmlspecialchars($be['nom_fournisseur']); ?></td>
                                            <td><?php echo number_format((float)($be['total_commande'] ?? 0), 2, ',', ' '); ?></td>
                                            <td class="<?php echo ((float)($be['total_recu'] ?? 0) < (float)($be['total_commande'] ?? 0)) ? 'text-danger fw-semibold' : ''; ?>">
                                                <?php echo number_format((float)($be['total_recu'] ?? 0), 2, ',', ' '); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo getStatutBadgeClass($be['statut']); ?>">
                                                    <?php echo htmlspecialchars($be['statut']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="achat_be_view.php?id=<?php echo $be['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="achat_be_pdf.php?id=<?php echo $be['id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger" target="_blank">
                                                    <i class="fas fa-file-pdf"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
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
            
            let itemCount = 0;
            
            // Handle BC selection : charger uniquement les articles de ce BC
            $('#bc_id').change(function() {
                const bcId = $(this).val();
                const fournisseur = $(this).find(':selected').data('fournisseur');
                $('#fournisseur').val(fournisseur || '');
                $('#itemsContainer').empty();
                itemCount = 0;
                if (!bcId) return;
                $.ajax({
                    url: 'api/get_bc_items.php',
                    method: 'POST',
                    data: { bc_id: bcId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.items && response.items.length) {
                            response.items.forEach(function(item) {
                                addItemFromBC(item);
                            });
                        } else if (response.success && (!response.items || response.items.length === 0)) {
                            $('#itemsContainer').html('<p class="text-muted small">Aucun article dans ce bon de commande.</p>');
                        }
                    },
                    error: function() {
                        $('#itemsContainer').html('<p class="text-danger small">Erreur lors du chargement des articles du BC.</p>');
                    }
                });
            });
            
            // Add item from BC (uniquement les articles du BC) (API retourne item_code, item_description, quantity)
            function addItemFromBC(item) {
                itemCount++;
                const ref = item.item_code || item.ref_article || '';
                const designation = item.item_description || item.designation || '';
                const qty = item.quantity != null ? item.quantity : (item.quantite_commandee != null ? item.quantite_commandee : 0);
                const stockActuel = item.stock_actuel != null ? item.stock_actuel : 0;
                const bcItemId = item.id || '';
                const refLabel = ref ? (ref + (designation ? ' - ' + designation : '')) : '';
                const itemHtml = `
                    <div class="item-row" data-item-id="${itemCount}">
                        <input type="hidden" name="items[${itemCount}][bc_item_id]" value="${bcItemId}">
                        <input type="hidden" name="items[${itemCount}][quantite_commandee]" value="${qty}">
                        <input type="hidden" name="items[${itemCount}][ref_article]" value="${ref}">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <label class="form-label">Réf article</label>
                                <input type="text" class="form-control" value="${refLabel}" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Stock actuel</label>
                                <input type="number" class="form-control" name="items[${itemCount}][stock_actuel]" 
                                       value="${stockActuel}" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Stock demandé</label>
                                <input type="number" class="form-control" name="items[${itemCount}][stock_demande]" 
                                       value="${qty}" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Stock reçu <span class="required-star">*</span></label>
                                <input type="number" class="form-control" name="items[${itemCount}][quantite_recue]" 
                                       min="0" step="0.01" value="${qty}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Emplacement</label>
                                <select class="form-select" name="items[${itemCount}][emplacement]">
                                    <option value="Principal" selected>Principal</option>
                                    <option value="Réserve">Réserve</option>
                                    <option value="Extérieur">Extérieur</option>
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
                
                // Select2 pour les emplacements uniquement
                $('#itemsContainer .form-select').select2({ theme: 'bootstrap4', width: '100%' });
            }
            
            function removeItem(itemId) {
                $(`.item-row[data-item-id="${itemId}"]`).remove();
            }
            
            // Pré-sélection BC depuis l'URL (ex: achat_be.php?bc_id=5)
            var preselectBcId = <?php echo $preselect_bc_id ? (int)$preselect_bc_id : 0; ?>;
            if (preselectBcId && $('#bc_id option[value="' + preselectBcId + '"]').length) {
                $('#bc_id').val(preselectBcId).trigger('change');
                $('.be-modal').get(0) && $('.be-modal')[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            
            // Reset form
            $('#resetBtn').click(function() {
                if (confirm('Êtes-vous sûr de vouloir réinitialiser le formulaire?')) {
                    $('#beForm')[0].reset();
                    $('#itemsContainer').empty();
                    itemCount = 0;
                }
            });
        });
        
        // Helper function for status badge class
        function getStatutBadgeClass(statut) {
            const classes = {
                'Reçu': 'success',
                'Validé': 'primary',
                'Rejeté': 'danger',
                'Annulé': 'dark'
            };
            return classes[statut] || 'secondary';
        }
    </script>
</body>
</html>

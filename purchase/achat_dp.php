<?php
// FUTURE AUTOMOTIVE - Demande de Prix Interface
// Interface pour créer et gérer les demandes de prix (DP)

require_once 'config_achat_hostinger.php';
require_once 'config.php';

// Vérifier si l'utilisateur est connecté
if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = 'achat_dp.php';
    header('Location: login.php');
    exit();
}

// Obtenir les informations de l'utilisateur
$user = get_logged_in_user();
$full_name = $user['full_name'] ?? $_SESSION['full_name'] ?? 'Administrateur';
$role = $_SESSION['role'] ?? 'admin';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $database = new DatabaseAchat();
        $conn = $database->connect();

        // Empêcher la création de plusieurs DP pour la même DA (sécurité côté serveur)
        $da_id = isset($_POST['da_id']) ? (int)$_POST['da_id'] : 0;
        if ($da_id <= 0) {
            throw new Exception("Demande d'achat invalide.");
        }
        $stmt = $conn->prepare("SELECT 1 FROM demandes_prix WHERE da_id = ? LIMIT 1");
        $stmt->execute([$da_id]);
        if ($stmt->fetchColumn()) {
            throw new Exception("Cette demande d'achat a déjà une demande de prix.");
        }
        
        // Générer la référence DP
        $ref_dp = generateReference('DP', 'demandes_prix', 'ref_dp');
        
        // Validation des champs obligatoires
        $validation_errors = [];
        
        // Vérifier si le fournisseur existe
        $stmt = $conn->prepare("SELECT id FROM suppliers WHERE id = ?");
        $stmt->execute([$_POST['fournisseur_id']]);
        if (!$stmt->fetch()) {
            $validation_errors[] = "Le fournisseur sélectionné n'existe pas";
        }
        
        // Vérifier les articles
        if (!empty($_POST['items'])) {
            foreach ($_POST['items'] as $index => $item) {
                if (!empty($item['designation']) && !empty($item['quantite'])) {
                    // Vérifier si le prix unitaire est renseigné
                    if (empty($item['prix_unitaire']) || floatval($item['prix_unitaire']) <= 0) {
                        $validation_errors[] = "L'article " . ($index + 1) . " doit avoir un prix unitaire valide";
                    }
                    
                    // Vérifier si le bus ID est valide
                    if (empty($item['bus_id'])) {
                        $validation_errors[] = "Numéro de bus obligatoire pour l'article: " . $item['designation'];
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
        
        // Insérer la demande de prix principale
        $stmt = $conn->prepare("
            INSERT INTO demandes_prix (ref_dp, da_id, fournisseur_id, statut) 
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $ref_dp,
            $da_id,
            $_POST['fournisseur_id'],
            'Accepté'
        ]);
        
        $dp_id = $conn->lastInsertId();
        
        // Copier les articles depuis DA vers DP
        if (!empty($_POST['items'])) {
            $purchase_item_cols = $conn->query("SHOW COLUMNS FROM purchase_items")->fetchAll(PDO::FETCH_COLUMN);
            $has_ref = in_array('ref_article', $purchase_item_cols);
            $has_region = in_array('region', $purchase_item_cols);
            foreach ($_POST['items'] as $item) {
                if (!empty($item['designation']) && !empty($item['quantite'])) {
                    // Validation finale des champs obligatoires
                    if (empty($item['prix_unitaire']) || floatval($item['prix_unitaire']) <= 0) {
                        throw new Exception("Prix unitaire invalide pour l'article: " . $item['designation']);
                    }
                    
                    if (empty($item['bus_id'])) {
                        throw new Exception("Numéro de bus obligatoire pour l'article: " . $item['designation']);
                    }
                    
                    // Vérifier si le bus existe et est actif
                    $stmt = $conn->prepare("SELECT id FROM buses WHERE id = ? AND status = 'active'");
                    $stmt->execute([$item['bus_id']]);
                    if (!$stmt->fetch()) {
                        throw new Exception("Le bus ID " . $item['bus_id'] . " n'existe pas ou n'est pas actif");
                    }
                    
                    $total_ligne = floatval($item['quantite']) * floatval($item['prix_unitaire']);
                    $ref = $item['ref_article'] ?? $item['item_code'] ?? '';
                    $region = $item['region'] ?? null;
                    if ($has_ref && $ref) {
                        if ($has_region) {
                            $stmt = $conn->prepare("INSERT INTO purchase_items (parent_type, parent_id, ref_article, designation, quantite, prix_unitaire, total_ligne, bus_id, region) VALUES (?,?,?,?,?,?,?,?,?)");
                            $stmt->execute(['DP', $dp_id, $ref, $item['designation'], $item['quantite'], $item['prix_unitaire'], $total_ligne, $item['bus_id'], $region]);
                        } else {
                            $stmt = $conn->prepare("INSERT INTO purchase_items (parent_type, parent_id, ref_article, designation, quantite, prix_unitaire, total_ligne, bus_id) VALUES (?,?,?,?,?,?,?,?)");
                            $stmt->execute(['DP', $dp_id, $ref, $item['designation'], $item['quantite'], $item['prix_unitaire'], $total_ligne, $item['bus_id']]);
                        }
                    } else {
                        if ($has_region) {
                            $stmt = $conn->prepare("INSERT INTO purchase_items (parent_type, parent_id, designation, quantite, prix_unitaire, total_ligne, bus_id, region) VALUES (?,?,?,?,?,?,?,?)");
                            $stmt->execute(['DP', $dp_id, $item['designation'], $item['quantite'], $item['prix_unitaire'], $total_ligne, $item['bus_id'], $region]);
                        } else {
                            $stmt = $conn->prepare("INSERT INTO purchase_items (parent_type, parent_id, designation, quantite, prix_unitaire, total_ligne, bus_id) VALUES (?,?,?,?,?,?,?)");
                            $stmt->execute(['DP', $dp_id, $item['designation'], $item['quantite'], $item['prix_unitaire'], $total_ligne, $item['bus_id']]);
                        }
                    }
                }
            }
        }
        
        // Workflow simplifié: DP acceptée automatiquement à la création
        
        // Logger l'action
        logAchat("Création DP", "Référence: $ref_dp, DA ID: " . $da_id . ", Fournisseur: " . $_POST['fournisseur_id']);
        
        $_SESSION['success_message'] = "Demande de prix $ref_dp créée avec succès!";
        header("Location: achat_dp.php");
        exit();
        
    } catch (Exception $e) {
        $error_message = "Erreur lors de la création de la demande de prix: " . $e->getMessage();
    }
}

// Récupérer les demandes de prix existantes
$demandes_dp = [];
try {
    $database = new DatabaseAchat();
    $conn = $database->connect();
    
    $stmt = $conn->query("
        SELECT dp.*, 
               demandes_achat.ref_da,
               demandes_achat.demandeur as da_demandeur,
               s.nom_fournisseur,
               COUNT(pi.id) as nombre_articles,
               SUM(pi.total_ligne) as montant_total
        FROM demandes_prix dp
        LEFT JOIN demandes_achat ON dp.da_id = demandes_achat.id
        LEFT JOIN suppliers s ON dp.fournisseur_id = s.id
        LEFT JOIN purchase_items pi ON dp.id = pi.parent_id AND pi.parent_type = 'DP'
        GROUP BY dp.id
        ORDER BY dp.date_envoi DESC
    ");
    $demandes_dp = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error_message = "Erreur lors du chargement des demandes de prix: " . $e->getMessage();
}

// Récupérer les demandes d'achat disponibles pour créer des DP
$demandes_da = [];
try {
    $database = new DatabaseAchat();
    $conn = $database->connect();
    
    // First get all DA with their status
    $stmt = $conn->query("
        SELECT 
            demandes_achat.id,
            demandes_achat.ref_da,
            demandes_achat.demandeur AS da_demandeur,
            demandes_achat.date_creation,
            demandes_achat.statut,
            demandes_achat.priorite
        FROM demandes_achat
        WHERE demandes_achat.statut IN ('Validé')
        ORDER BY demandes_achat.date_creation DESC
    ");
    $all_da = $stmt->fetchAll();
    
    $demandes_da = [];
    foreach ($all_da as $da) {
        // Exclure les DA qui ont déjà une DP (1 DA = 1 DP)
        $stmt_dp = $conn->prepare("SELECT COUNT(*) as count FROM demandes_prix WHERE da_id = ?");
        $stmt_dp->execute([$da['id']]);
        $dp_count = (int)($stmt_dp->fetch()['count'] ?? 0);

        if ($dp_count === 0) {
            // Get article count
            $stmt_items = $conn->prepare("SELECT COUNT(*) as count FROM purchase_items WHERE parent_type = 'DA' AND parent_id = ?");
            $stmt_items->execute([$da['id']]);
            $da['nombre_articles'] = $stmt_items->fetch()['count'];

            $demandes_da[] = $da;
        }
    }
    
} catch (Exception $e) {
    $error_message = "Erreur lors du chargement des demandes d'achat: " . $e->getMessage();
}

// Get suppliers and buses for forms
try {
    $database = new DatabaseAchat();
    $conn = $database->connect();
    
    $stmt = $conn->query("SELECT id, nom_fournisseur FROM suppliers ORDER BY nom_fournisseur");
    $fournisseurs = $stmt->fetchAll();

    $hasBusRegionCode = false;
    try {
        $col = $conn->query("SHOW COLUMNS FROM buses LIKE 'region_code'")->fetch();
        $hasBusRegionCode = $col ? true : false;
    } catch (Exception $e) {
        $hasBusRegionCode = false;
    }

    if ($hasBusRegionCode) {
        $stmt = $conn->query("SELECT id, bus_number, license_plate, region_code FROM buses WHERE status = 'active' ORDER BY bus_number");
    } else {
        $stmt = $conn->query("SELECT id, bus_number, license_plate FROM buses WHERE status = 'active' ORDER BY bus_number");
    }
    $bus_list = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error_message = "Erreur lors du chargement des données: " . $e->getMessage();
}

$page_title = 'Demandes de Prix';
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - FUTURE AUTOMOTIVE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1e3a8a;
            --secondary-color: #f59e0b;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
            --light-bg: #f8f9fa;
            --dark-color: #1f2937;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--light-bg);
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
        }
        
        .page-header {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .dp-form {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .dp-list {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .da-selection {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .da-card {
            border-left: 4px solid var(--info-color);
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .da-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-left-color: var(--primary-color);
        }
        
        .da-card.selected {
            background: #f0f9ff;
            border-left-color: var(--primary-color);
            box-shadow: 0 4px 12px rgba(30,58,138,0.2);
        }
        
        .dp-card {
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .dp-card.envoye {
            border-color: var(--info-color);
        }
        
        .dp-card.recu {
            border-color: var(--success-color);
        }
        
        .dp-card.accepte {
            border-color: var(--warning-color);
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-envoye {
            background: #dbeafe;
            color: #2563eb;
        }
        
        .status-recu {
            background: #d1fae5;
            color: #059669;
        }
        
        .status-accepte {
            background: #fed7aa;
            color: #ea580c;
        }
        
        .btn-add-item {
            background: var(--success-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-add-item:hover {
            background: #059669;
            transform: translateY(-1px);
        }
        
        .btn-remove-item {
            background: var(--danger-color);
            color: white;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-remove-item:hover {
            background: #dc2626;
            transform: scale(1.1);
        }
        
        .item-row {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid #e5e7eb;
        }
        
        .required-star {
            color: var(--danger-color);
        }
        
        @media (max-width: 992px) { 
            .main-content { 
                margin-left: 0; 
            } 
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-header">
                <h1 class="mb-2">
                    <i class="fas fa-file-invoice me-3"></i>
                    Demandes de Prix
                </h1>
                <p class="text-muted">Créer et gérer les demandes de prix pour les fournisseurs</p>
            </div>

            <?php include __DIR__ . '/includes/achat_tabs.php'; ?>
            
            <!-- Alert Messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- DA Selection -->
            <div class="da-selection">
                <h3 class="mb-4">
                    <i class="fas fa-shopping-cart me-2"></i>
                    Sélectionner la Demande d'Achat
                </h3>
                
                <?php if (empty($demandes_da)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Aucune demande d'achat disponible</h5>
                        <p class="text-muted">Aucune demande d'achat valide n'est disponible pour créer une demande de prix.</p>
                        <p class="text-muted">Seules les demandes d'achat validées ou en attente sans DP existant peuvent être sélectionnées.</p>
                        <a href="achat_da.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Créer une DA
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($demandes_da as $da): ?>
                            <div class="col-lg-4 mb-3">
                                <div class="da-card" onclick="selectDA(<?php echo $da['id']; ?>)">
                                    <div class="p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h5 class="mb-1"><?php echo htmlspecialchars($da['ref_da']); ?></h5>
                                                <small class="text-muted">
                                                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($da['da_demandeur']); ?>
                                                    <i class="fas fa-calendar ms-2 me-1"></i><?php echo date('d/m/Y', strtotime($da['date_creation'])); ?>
                                                </small>
                                            </div>
                                            <div>
                                                <span class="badge bg-info">
                                                    <?php echo $da['nombre_articles']; ?> article(s)
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge bg-secondary">
                                                <?php echo htmlspecialchars($da['priorite']); ?>
                                            </span>
                                            <span class="badge bg-success">
                                                <?php echo htmlspecialchars($da['statut']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- DP Form -->
            <div class="dp-form" id="dpFormContainer" style="display: none;">
                <h3 class="mb-4">
                    <i class="fas fa-plus-circle me-2"></i>
                    Créer une Demande de Prix
                </h3>
                
                <form id="dpForm" method="POST">
                    <input type="hidden" id="selectedDAId" name="da_id" required>
                    <input type="hidden" id="daReferenceHidden" name="da_reference" readonly>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label">Référence DA</label>
                            <input type="text" class="form-control" id="daReference" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date d'envoi</label>
                            <input type="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Demandeur</label>
                            <input type="text" class="form-control" id="daDemandeur" readonly>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Statut DA</label>
                            <input type="text" class="form-control" id="daStatut" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Priorité</label>
                            <input type="text" class="form-control" id="daPriorite" readonly>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Fournisseur <span class="required-star">*</span></label>
                        <div class="row">
                            <div class="col-md-8">
                                <select name="fournisseur_id" class="form-select" required>
                                    <option value="">Sélectionner un fournisseur</option>
                                    <?php foreach ($fournisseurs as $fournisseur): ?>
                                        <option value="<?php echo $fournisseur['id']; ?>">
                                            <?php echo htmlspecialchars($fournisseur['nom_fournisseur']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-outline-secondary" onclick="showSupplierInfo()">
                                    <i class="fas fa-info-circle me-2"></i>Infos
                                </button>
                            </div>
                        </div>
                        <div id="supplierInfo" class="mt-2" style="display: none;">
                            <div class="alert alert-info">
                                <small id="supplierDetails"></small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">
                                <i class="fas fa-list me-2"></i>
                                Articles à Demander
                            </h5>
                            <button type="button" class="btn-add-item" onclick="addItem()">
                                <i class="fas fa-plus me-2"></i>Ajouter un Article
                            </button>
                        </div>
                        
                        <div id="itemsContainer">
                            <!-- Les articles seront copiés depuis la DA sélectionnée -->
                        </div>
                    </div>
                    
                    <input type="hidden" name="update_da_status" value="no">
                    
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-paper-plane me-2"></i>Générer la Demande de Prix
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- DP List -->
            <div class="dp-list">
                <h3 class="mb-4">
                    <i class="fas fa-list me-2"></i>
                    Demandes de Prix Existantes
                </h3>
                <?php
                    $dps_actives = [];
                    $dps_archive = [];
                    foreach (($demandes_dp ?? []) as $dp_item) {
                        if (in_array($dp_item['statut'], ['Accepté'])) {
                            $dps_actives[] = $dp_item;
                        } else {
                            $dps_archive[] = $dp_item;
                        }
                    }
                ?>
                
                <?php if (empty($demandes_dp)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-file-invoice fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Aucune demande de prix</h5>
                        <p class="text-muted">Commencez par créer votre première demande de prix.</p>
                    </div>
                <?php else: ?>
                    <h6 class="mb-3">
                        <i class="fas fa-bolt me-2"></i>
                        En cours (modifiable)
                    </h6>

                    <?php if (empty($dps_actives)): ?>
                        <p class="text-muted">Aucune demande de prix en cours.</p>
                    <?php else: ?>
                        <div class="table-responsive mb-4">
                            <table class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>Référence</th>
                                        <th>DA</th>
                                        <th>Fournisseur</th>
                                        <th>Date</th>
                                        <th>Statut</th>
                                        <th>Articles</th>
                                        <th>Montant</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dps_actives as $dp): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($dp['ref_dp']); ?></td>
                                            <td><?php echo htmlspecialchars($dp['ref_da']); ?></td>
                                            <td><?php echo htmlspecialchars($dp['nom_fournisseur']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($dp['date_envoi'])); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($dp['statut']); ?>">
                                                    <?php echo htmlspecialchars($dp['statut']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo intval($dp['nombre_articles'] ?? 0); ?></td>
                                            <td><?php echo number_format(floatval($dp['montant_total'] ?? 0), 2, ',', ' '); ?> MAD</td>
                                            <td class="d-flex gap-2 flex-wrap">
                                                <a href="achat_dp_view.php?id=<?php echo $dp['id']; ?>" class="btn btn-sm btn-outline-primary" title="Voir">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="achat_dp_edit.php?id=<?php echo $dp['id']; ?>" class="btn btn-sm btn-outline-warning" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="achat_dp_pdf.php?id=<?php echo $dp['id']; ?>" class="btn btn-sm btn-outline-secondary" title="PDF">
                                                    <i class="fas fa-file-pdf"></i>
                                                </a>
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

                    <?php if (empty($dps_archive)): ?>
                        <p class="text-muted">Aucune demande de prix archivée.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>Référence</th>
                                        <th>DA</th>
                                        <th>Fournisseur</th>
                                        <th>Date</th>
                                        <th>Statut</th>
                                        <th>Articles</th>
                                        <th>Montant</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dps_archive as $dp): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($dp['ref_dp']); ?></td>
                                            <td><?php echo htmlspecialchars($dp['ref_da']); ?></td>
                                            <td><?php echo htmlspecialchars($dp['nom_fournisseur']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($dp['date_envoi'])); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($dp['statut']); ?>">
                                                    <?php echo htmlspecialchars($dp['statut']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo intval($dp['nombre_articles'] ?? 0); ?></td>
                                            <td><?php echo number_format(floatval($dp['montant_total'] ?? 0), 2, ',', ' '); ?> MAD</td>
                                            <td class="d-flex gap-2 flex-wrap">
                                                <a href="achat_dp_view.php?id=<?php echo $dp['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="achat_dp_pdf.php?id=<?php echo $dp['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-file-pdf"></i>
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

    <!-- Include footer -->
    <?php include __DIR__ . '/includes/footer.php'; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let selectedDA = null;
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

        function autoSetRegionForItemRow(rowEl) {
            if (!rowEl) return;
            const busSelect = rowEl.querySelector('select[name*="[bus_id]"]');
            if (!busSelect) return;
            const busId = (busSelect.value || '').toString();
            const region = (busRegionMap && busRegionMap[busId]) ? busRegionMap[busId] : '';
            if (!region) return;
            const regionInput = rowEl.querySelector('input[name*="[region]"]');
            if (regionInput) {
                regionInput.value = region;
            }
        }
        
        function selectDA(daId) {
            // Désélectionner la carte précédente
            document.querySelectorAll('.da-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Sélectionner la nouvelle carte
            const card = event.currentTarget.closest('.da-card');
            card.classList.add('selected');
            
            // Récupérer les informations de la DA
            fetch(`achat_dp_get_da_items.php?da_id=${daId}`)
                .then(response => response.json())
                .then(data => {
                    selectedDA = data.da;
                    document.getElementById('selectedDAId').value = daId;
                    document.getElementById('daReference').value = data.da.ref_da;
                    const daReferenceHiddenEl = document.getElementById('daReferenceHidden');
                    if (daReferenceHiddenEl) {
                        daReferenceHiddenEl.value = data.da.ref_da;
                    }
                    document.getElementById('daDemandeur').value = data.da.demandeur;
                    document.getElementById('daStatut').value = data.da.statut;
                    document.getElementById('daPriorite').value = data.da.priorite;
                    
                    // Afficher le formulaire
                    document.getElementById('dpFormContainer').style.display = 'block';
                    
                    // Copier les articles
                    const container = document.getElementById('itemsContainer');
                    container.innerHTML = '';
                    
                    if (data.items && data.items.length > 0) {
                        data.items.forEach((item, index) => {
                            const itemRow = document.createElement('div');
                            itemRow.className = 'item-row';
                            itemRow.innerHTML = `
                                <div class="row g-3">
                                    <input type="hidden" name="items[${index}][ref_article]" value="${item.ref_article || ''}">
                                    <div class="col-md-5">
                                        <input type="text" name="items[${index}][designation]" class="form-control" value="${(item.designation || '').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;')}" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" name="items[${index}][quantite]" class="form-control" value="${item.quantite}" readonly>
                                    </div>
                                    <input type="hidden" name="items[${index}][region]" value="${item.region || ''}">
                                    <div class="col-md-2">
                                        <input type="number" name="items[${index}][prix_unitaire]" class="form-control" placeholder="Prix du fournisseur *" step="0.01" min="0.01" required onchange="calculateTotal(this)">
                                        <div class="form-text">
                                            DA: ${(parseFloat(item.prix_unitaire || 0)).toFixed(2)} | Dernier: ${(parseFloat(item.last_purchase_price || 0)).toFixed(2)}
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <select name="items[${index}][bus_id]" class="form-control bus-select" required>
                                            <option value="">Sélectionner un bus</option>
                                            <?php foreach ($bus_list as $bus): ?>
                                                <option value="<?php echo $bus['id']; ?>" ${parseInt(item.bus_id || 0) === <?php echo (int)$bus['id']; ?> ? 'selected' : ''}>
                                                    <?php echo htmlspecialchars($bus['bus_number'] . ' - ' . $bus['license_plate']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-1">
                                        <input type="text" name="items[${index}][total_ligne]" class="form-control" placeholder="Total" readonly>
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn-remove-item" onclick="removeItem(this)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            `;
                            container.appendChild(itemRow);
                            
                            // Calculer le total
                            calculateTotal(itemRow.querySelector('input[name*="[prix_unitaire]"]'));

                            // Auto-fill region from selected bus
                            autoSetRegionForItemRow(itemRow);
                        });
                    }
                    
                    // Faire défiler vers le formulaire
                    document.getElementById('dpForm').scrollIntoView({ behavior: 'smooth' });
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors du chargement des articles de la demande d\'achat');
                });
        }
        
        function addItem() {
            const container = document.getElementById('itemsContainer');
            const itemRow = document.createElement('div');
            itemRow.className = 'item-row';
            itemRow.innerHTML = `
                <div class="row g-3">
                    <div class="col-md-5">
                        <input type="text" name="items[${itemCount}][designation]" class="form-control" placeholder="Désignation *" required>
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="items[${itemCount}][quantite]" class="form-control" placeholder="Quantité *" min="1" required>
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="items[${itemCount}][prix_unitaire]" class="form-control" placeholder="Prix du fournisseur *" step="0.01" min="0.01" required onchange="calculateTotal(this)">
                    </div>
                    <div class="col-md-2">
                        <select name="items[${itemCount}][bus_id]" class="form-control bus-select" required>
                            <option value="">Sélectionner un bus</option>
                            <?php foreach ($bus_list as $bus): ?>
                                <option value="<?php echo $bus['id']; ?>">
                                    <?php echo htmlspecialchars($bus['bus_number'] . ' - ' . $bus['license_plate']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="hidden" name="items[${itemCount}][region]" value="">
                    <div class="col-md-1">
                        <input type="text" name="items[${itemCount}][total_ligne]" class="form-control" placeholder="Total" readonly>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn-remove-item" onclick="removeItem(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(itemRow);
            itemCount++;
        }
        
        function removeItem(button) {
            const itemRow = button.closest('.item-row');
            itemRow.remove();
        }
        
        function calculateTotal(input) {
            const row = input.closest('.item-row');
            const quantity = parseFloat(row.querySelector('input[name*="[quantite]"]').value) || 0;
            const price = parseFloat(row.querySelector('input[name*="[prix_unitaire]"]').value) || 0;
            const total = quantity * price;
            
            // Check if total input exists, if not create it
            let totalInput = row.querySelector('input[name*="[total_ligne]"]');
            if (!totalInput) {
                // Create total input if it doesn't exist
                const totalCol = document.createElement('div');
                totalCol.className = 'col-md-1';
                totalCol.innerHTML = `<input type="text" name="items[${row.querySelector('input[name*="[quantite]"]').name.match(/\[(\d+)\]/)[1]}][total_ligne]" class="form-control" placeholder="Total" readonly>`;
                row.querySelector('.col-md-1').parentNode.insertBefore(totalCol, row.querySelector('.col-md-1'));
                totalInput = totalCol.querySelector('input');
            }
            
            totalInput.value = total.toFixed(2);
        }
        
        function showSupplierInfo() {
            const supplierSelect = document.querySelector('select[name="fournisseur_id"]');
            const selectedOption = supplierSelect.options[supplierSelect.selectedIndex];
            const supplierId = supplierSelect.value;
            
            if (!supplierId) {
                document.getElementById('supplierInfo').style.display = 'none';
                return;
            }
            
            // Get supplier details from PHP data
            const suppliers = <?php echo json_encode($fournisseurs); ?>;
            const supplier = suppliers.find(s => s.id == supplierId);
            
            if (supplier) {
                const details = `
                    <strong>${supplier.nom_fournisseur}</strong><br>
                    ${supplier.contact_nom ? 'Contact: ' + supplier.contact_nom + '<br>' : ''}
                    ${supplier.email ? 'Email: ' + supplier.email + '<br>' : ''}
                    ${supplier.telephone ? 'Téléphone: ' + supplier.telephone + '<br>' : ''}
                    ${supplier.adresse ? 'Adresse: ' + supplier.adresse : ''}
                `;
                document.getElementById('supplierDetails').innerHTML = details;
                document.getElementById('supplierInfo').style.display = 'block';
            }
        }
        
        function exportDP() {
            // Implémenter l'exportation Excel/CSV
            alert('Fonction d\'exportation à implémenter');
        }
        
        // Calcul automatique des totaux
        document.addEventListener('input', function(e) {
            if (e.target.name.includes('[quantite]') || e.target.name.includes('[prix_unitaire]')) {
                calculateTotal(e.target);
            }
        });
        
        // Show supplier info when supplier is selected
        document.addEventListener('change', function(e) {
            if (e.target.name === 'fournisseur_id') {
                showSupplierInfo();
            }

            if (e.target && e.target.matches('select.bus-select')) {
                autoSetRegionForItemRow(e.target.closest('.item-row'));
            }
        });
    </script>
</body>
</html>

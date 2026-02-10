<?php
// ATEO Auto - Bon de Commande Interface
// Interface pour créer et gérer les bons de commande (BC)

require_once 'config_achat_hostinger.php';
require_once 'config.php';

// Vérifier si l'utilisateur est connecté
if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = 'achat_bc.php';
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
        
        // Générer la référence BC
        $ref_bc = generateReference('BC', 'bons_commande', 'ref_bc');
        
        // Calculer les montants
        $total_ht = 0;
        if (!empty($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                $total_ht += floatval($item['quantite']) * floatval($item['prix_unitaire']);
            }
        }
        
        $tva_rate = floatval($_POST['tva_rate'] ?? 20);
        $tva_amount = $total_ht * ($tva_rate / 100);
        $total_ttc = $total_ht + $tva_amount;
        
        // Insérer le bon de commande principal
        // La table bons_commande (achat_tables_hostinger.sql) ne contient que ces colonnes:
        // ref_bc, dp_id, date_commande, total_ht, tva, total_ttc, statut
        $stmt = $conn->prepare("
            INSERT INTO bons_commande (ref_bc, dp_id, date_commande, total_ht, tva, total_ttc, statut) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $ref_bc,
            $_POST['dp_id'],
            $_POST['date_commande'] ?? date('Y-m-d'),
            $total_ht,
            $tva_amount,
            $total_ttc,
            'Commandé'
        ]);
        
        $bc_id = $conn->lastInsertId();
        
        // Copier les articles depuis DP vers BC
        if (!empty($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                if (!empty($item['designation']) && !empty($item['quantite'])) {
                    $item_total = floatval($item['quantite']) * floatval($item['prix_unitaire']);
                    $item_tva = $item_total * ($tva_rate / 100);
                    
                    $stmt = $conn->prepare("
                        INSERT INTO bc_items (bc_id, item_code, item_description, quantity, unit_price, total_price, tax_rate, tax_amount, total_with_tax) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $bc_id,
                        $item['item_code'] ?? '',
                        $item['designation'],
                        $item['quantite'],
                        $item['prix_unitaire'],
                        $item_total,
                        $tva_rate,
                        $item_tva,
                        $item_total + $item_tva
                    ]);
                }
            }
        }
        
        // Mettre à jour le statut du DP
        $stmt = $conn->prepare("
            UPDATE demandes_prix 
            SET statut = 'Accepté' 
            WHERE id = ?
        ");
        $stmt->execute([$_POST['dp_id']]);
        
        // Logger l'action
        logAchat("Création BC", "Référence: $ref_bc, DP ID: " . $_POST['dp_id'] . ", Total TTC: $total_ttc");
        
        $_SESSION['success_message'] = "Bon de commande $ref_bc créé avec succès!";
        header("Location: achat_bc.php");
        exit();
        
    } catch (Exception $e) {
        $error_message = "Erreur lors de la création du bon de commande: " . $e->getMessage();
    }
}

// Récupérer les bons de commande existants
$bons_commande = [];
try {
    $database = new DatabaseAchat();
    $conn = $database->connect();
    
    $stmt = $conn->query("
        SELECT bc.*, 
               dp.ref_dp,
               da.ref_da,
               s.nom_fournisseur,
               COUNT(bci.id) as nombre_articles,
               SUM(bci.total_with_tax) as montant_total
        FROM bons_commande bc
        LEFT JOIN demandes_prix dp ON bc.dp_id = dp.id
        LEFT JOIN demandes_achat da ON dp.da_id = da.id
        LEFT JOIN suppliers s ON dp.fournisseur_id = s.id
        LEFT JOIN bc_items bci ON bc.id = bci.bc_id
        GROUP BY bc.id
        ORDER BY bc.date_commande DESC
    ");
    $bons_commande = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error_message = "Erreur lors du chargement des bons de commande: " . $e->getMessage();
}

// Récupérer les DP acceptées pour créer des BC
$dps_acceptees = [];
try {
    $database = new DatabaseAchat();
    $conn = $database->connect();
    
    $stmt = $conn->query("
        SELECT dp.*, 
               da.ref_da,
               da.demandeur as da_demandeur,
               s.nom_fournisseur,
               s.contact_nom as contact_nom,
               s.email as email_fournisseur,
               s.telephone as telephone_fournisseur,
               COUNT(pi.id) as nombre_articles,
               SUM(pi.total_ligne) as montant_total
        FROM demandes_prix dp
        LEFT JOIN demandes_achat da ON dp.da_id = da.id
        LEFT JOIN suppliers s ON dp.fournisseur_id = s.id
        LEFT JOIN purchase_items pi ON dp.id = pi.parent_id AND pi.parent_type = 'DP'
        WHERE dp.statut = 'Accepté'
        AND dp.id NOT IN (
            SELECT dp_id FROM bons_commande WHERE dp_id IS NOT NULL
        )
        GROUP BY dp.id
        ORDER BY dp.date_envoi DESC
    ");
    $dps_acceptees = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error_message = "Erreur lors du chargement des DP acceptées: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bon de Commande - ATEO Auto</title>
    <meta name="description" content="Interface de gestion des bons de commande pour l'atelier ATEO Auto">
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <!-- ISO 9001 Professional Theme -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/iso-theme.css">
    <link rel="stylesheet" href="assets/css/iso-components.css">
    <link rel="stylesheet" href="assets/css/iso-bootstrap.css">
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
            margin-left: 260px;
            padding: var(--space-8);
            min-height: 100vh;
        }
        
        .iso-card {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-primary);
            border-radius: var(--radius-lg);
            padding: var(--space-6);
            margin-bottom: var(--space-6);
            transition: transform 0.2s;
        }
        
        .iso-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .dp-selection {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .bc-form {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .bc-list {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .dp-card {
            border-left: 4px solid var(--success-color);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .dp-card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .dp-card.selected {
            background: #f0fdf4;
            border-left-color: var(--primary-color);
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-commande {
            background: #dbeafe;
            color: #2563eb;
        }
        .status-confirmé {
            background: #d1fae5;
            color: #059669;
        }
        
        .status-livre_partiellement {
            background: #fef3c7;
            color: #d97706;
        }
        
        .status-livre_totalement {
            background: #d1fae5;
            color: #059669;
        }
        
        .status-annule {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .bc-card {
            border-left: 4px solid;
            transition: all 0.3s ease;
        }
        
        .bc-card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .bc-card.commande {
            border-left-color: #3b82f6;
        }
        .bc-card.confirmé {
            border-left-color: #059669;
        }
        
        .bc-card.livre_partiellement {
            border-left-color: #f59e0b;
        }
        
        .bc-card.livre_totalement {
            border-left-color: #10b981;
        }
        
        .bc-card.annule {
            border-left-color: #ef4444;
        }
        
        .financial-summary {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .financial-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .financial-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 1.2rem;
            color: var(--primary-color);
        }
        
        .item-row {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid #e5e7eb;
        }
        
        .btn-add-item {
            background: var(--info-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-add-item:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }
        
        .btn-remove-item {
            background: var(--danger-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-remove-item:hover {
            background: #dc2626;
            transform: scale(1.1);
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
        }
        
        /* Smart Search Styles */
        .advanced-search-options {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            border: 1px solid #e5e7eb;
        }
        
        .search-summary {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 0.75rem;
            margin-top: 1rem;
            color: #1976d2;
        }
        
        .highlight {
            background-color: #fff3cd;
            padding: 2px 4px;
            border-radius: 3px;
            font-weight: bold;
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
                    <li class="breadcrumb-item active">Liste des bons de commande</li>
                </ol>
            </nav>
            <!-- Header -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h1 class="mb-2">
                        <i class="fas fa-file-invoice-dollar me-3"></i>
                        Bon de Commande
                    </h1>
                    <p class="text-muted mb-0">Créer et gérer les bons de commande officiels</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="d-flex gap-2 justify-content-md-end">
                        <button class="btn btn-outline-secondary" onclick="exportBC()">
                            <i class="fas fa-download me-2"></i>Exporter
                        </button>
                    </div>
                </div>
            </div>

            <?php include __DIR__ . '/includes/achat_tabs.php'; ?>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; unset($_SESSION['success_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- DP Selection -->
            <div class="dp-selection">
                <h3 class="mb-4">
                    <i class="fas fa-check-circle me-2"></i>
                    Sélectionner la Demande de Prix Acceptée
                </h3>
                
                <?php if (empty($dps_acceptees)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-file-invoice fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Aucune demande de prix acceptée</h5>
                        <p class="text-muted">Aucune demande de prix acceptée n'est disponible pour créer un bon de commande.</p>
                        <a href="achat_dp.php" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Gérer les DP
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($dps_acceptees as $dp): ?>
                            <div class="col-lg-6 mb-3">
                                <div class="dp-card" onclick="selectDP(<?php echo $dp['id']; ?>)">
                                    <div class="p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h5 class="mb-1"><?php echo htmlspecialchars($dp['ref_dp']); ?></h5>
                                                <small class="text-muted">
                                                    <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($dp['ref_da']); ?>
                                                    <i class="fas fa-store ms-2 me-1"></i><?php echo htmlspecialchars($dp['nom_fournisseur']); ?>
                                                    <i class="fas fa-calendar ms-2 me-1"></i><?php echo date('d/m/Y', strtotime($dp['date_envoi'])); ?>
                                                </small>
                                            </div>
                                            <div>
                                                <span class="badge bg-success">Accepté</span>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="badge bg-info">
                                                <?php echo $dp['nombre_articles']; ?> article(s)
                                            </span>
                                            <?php if ($dp['montant_total'] > 0): ?>
                                                <span class="badge bg-warning">
                                                    <?php echo number_format(floatval($dp['montant_total'] ?? 0), 2, ',', ' '); ?> MAD
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="small text-muted">
                                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($dp['contact_nom'] ?? 'Non spécifié'); ?>
                                            <i class="fas fa-phone ms-2 me-1"></i><?php echo htmlspecialchars($dp['telephone_fournisseur'] ?? 'Non spécifié'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- BC Form -->
            <div class="bc-form" id="bcFormContainer" style="display: none;">
                <h3 class="mb-4">
                    <i class="fas fa-file-invoice-dollar me-2"></i>
                    Nouveau Bon de Commande
                </h3>
                
                <form method="POST" id="bcForm">
                    <input type="hidden" name="dp_id" id="selectedDPId">
                    
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <label class="form-label">Demande de Prix</label>
                            <input type="text" class="form-control" id="dpReference" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fournisseur</label>
                            <input type="text" class="form-control" id="fournisseurNom" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date de commande</label>
                            <input type="date" name="date_commande" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Taux TVA</label>
                            <select name="tva_rate" class="form-select" onchange="calculateFinancials()">
                                <option value="20">20% - Taux normal</option>
                                <option value="15">15% - Taux réduit</option>
                                <option value="10">10% - Taux super-réduit</option>
                                <option value="0">0% - Exonéré de TVA</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Conditions de paiement</label>
                            <input type="text" name="payment_terms" class="form-control" value="30 jours" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Adresse de livraison</label>
                            <input type="text" name="delivery_address" class="form-control" value="ATEO Auto - Tanger, Maroc" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">
                                <i class="fas fa-list me-2"></i>
                                Articles Commandés
                            </h5>
                            <button type="button" class="btn-add-item" onclick="addItem()">
                                <i class="fas fa-plus me-2"></i>Ajouter un Article
                            </button>
                        </div>
                        
                        <div id="itemsContainer">
                            <!-- Les articles seront copiés depuis la DP sélectionnée -->
                        </div>
                    </div>
                    
                    <!-- Financial Summary -->
                    <div class="financial-summary">
                        <h5 class="mb-3">
                            <i class="fas fa-calculator me-2"></i>
                            Récapitulatif Financier
                        </h5>
                        <div class="financial-row">
                            <span>Total HT:</span>
                            <span id="totalHT">0.00 MAD</span>
                        </div>
                        <div class="financial-row">
                            <span>TVA (20%):</span>
                            <span id="tvaAmount">0.00 MAD</span>
                        </div>
                        <div class="financial-row">
                            <span>Total TTC:</span>
                            <span id="totalTTC">0.00 MAD</span>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-file-invoice-dollar me-2"></i>Générer le Bon de Commande
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- BC List -->
            <div class="bc-list">
                <h3 class="mb-4">
                    <i class="fas fa-list me-2"></i>
                    Bons de Commande Existant
                </h3>

                <?php
                    $bcs_actifs = [];
                    $bcs_archive = [];
                    foreach (($bons_commande ?? []) as $bc_item) {
                        if (($bc_item['statut'] ?? '') === 'Commandé') {
                            $bcs_actifs[] = $bc_item;
                        } else {
                            $bcs_archive[] = $bc_item;
                        }
                    }
                ?>
                
                <?php if (empty($bons_commande)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-file-invoice-dollar fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Aucun bon de commande</h5>
                        <p class="text-muted">Commencez par créer votre premier bon de commande.</p>
                    </div>
                <?php else: ?>

                    <h6 class="mb-3">
                        <i class="fas fa-bolt me-2"></i>
                        En cours (modifiable)
                    </h6>

                    <?php if (empty($bcs_actifs)): ?>
                        <p class="text-muted">Aucun bon de commande en cours.</p>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($bcs_actifs as $bc): ?>
                                <div class="col-lg-6 mb-4">
                                    <div class="bc-card <?php echo strtolower(str_replace(' ', '_', $bc['statut'])); ?>">
                                        <div class="p-3">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <h5 class="mb-1"><?php echo htmlspecialchars($bc['ref_bc']); ?></h5>
                                                    <small class="text-muted">
                                                        <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($bc['ref_dp']); ?>
                                                        <i class="fas fa-store ms-2 me-1"></i><?php echo htmlspecialchars($bc['nom_fournisseur']); ?>
                                                        <i class="fas fa-calendar ms-2 me-1"></i><?php echo date('d/m/Y', strtotime($bc['date_commande'])); ?>
                                                    </small>
                                                </div>
                                                <div>
                                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '_', $bc['statut'])); ?>">
                                                        <?php echo htmlspecialchars($bc['statut']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="badge bg-info">
                                                    <?php echo $bc['nombre_articles']; ?> article(s)
                                                </span>
                                                <span class="badge bg-success">
                                                    <?php echo number_format(floatval($bc['montant_total'] ?? 0), 2, ',', ' '); ?> MAD
                                                </span>
                                            </div>
                                            
                                            <div class="d-flex gap-2 flex-wrap">
                                                <a href="achat_bc_view.php?id=<?php echo $bc['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye me-1"></i>Voir
                                                </a>
                                                <a href="achat_be.php?bc_id=<?php echo $bc['id']; ?>" class="btn btn-sm btn-success">
                                                    <i class="fas fa-truck-loading me-1"></i>Bon d'Entrée
                                                </a>
                                                <a href="achat_bc_edit.php?id=<?php echo $bc['id']; ?>" class="btn btn-sm btn-outline-warning">
                                                    <i class="fas fa-edit me-1"></i>Modifier
                                                </a>
                                                <button class="btn btn-sm btn-outline-info" disabled title="Création de BL non disponible">
                                                    <i class="fas fa-truck me-1"></i>Créer BL
                                                </button>
                                                <a href="achat_bc_pdf.php?id=<?php echo $bc['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-file-pdf me-1"></i>PDF
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Smart Search Bar for Archive -->
                    <div class="iso-card mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">
                                <i class="fas fa-search me-2"></i>
                                Recherche dans l'Archive
                            </h5>
                            <button class="btn btn-outline-secondary btn-sm" onclick="toggleAdvancedSearch()">
                                <i class="fas fa-cog me-1"></i>Options
                            </button>
                        </div>
                        
                        <!-- Main Search -->
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control" 
                                           id="smartSearchInput" 
                                           placeholder="Rechercher par référence, fournisseur, montant, statut..."
                                           onkeyup="performSmartSearch(this.value)">
                                    <button class="btn btn-primary" onclick="performSmartSearch(document.getElementById('smartSearchInput').value)">
                                        <i class="fas fa-search me-1"></i>Rechercher
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex gap-2">
                                    <button class="btn btn-outline-warning btn-sm flex-fill" onclick="clearSearch()">
                                        <i class="fas fa-times me-1"></i>Effacer
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Advanced Search Options -->
                        <div id="advancedSearchOptions" class="advanced-search-options" style="display: none;">
                            <div class="row">
                                <div class="col-md-3">
                                    <label class="form-label">Fournisseur:</label>
                                    <select class="form-select" id="filterFournisseur" onchange="performSmartSearch(document.getElementById('smartSearchInput').value)">
                                        <option value="">Tous</option>
                                        <?php
                                        // Get unique suppliers from database
                                        try {
                                            $stmt = $conn->query("SELECT DISTINCT nom_fournisseur FROM bons_commande WHERE nom_fournisseur IS NOT NULL ORDER BY nom_fournisseur");
                                            while ($row = $stmt->fetch()) {
                                                echo '<option value="' . htmlspecialchars($row['nom_fournisseur']) . '">' . htmlspecialchars($row['nom_fournisseur']) . '</option>';
                                            }
                                        } catch (Exception $e) {
                                            echo '<option value="">Erreur chargement</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Statut:</label>
                                    <select class="form-select" id="filterStatut" onchange="performSmartSearch(document.getElementById('smartSearchInput').value)">
                                        <option value="">Tous</option>
                                        <option value="Commandé">Commandé</option>
                                        <option value="Reçu">Reçu</option>
                                        <option value="Validé">Validé</option>
                                        <option value="Annulé">Annulé</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Montant min:</label>
                                    <input type="number" class="form-control" id="filterMontantMin" placeholder="0" onchange="performSmartSearch(document.getElementById('smartSearchInput').value)">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Montant max:</label>
                                    <input type="number" class="form-control" id="filterMontantMax" placeholder="99999" onchange="performSmartSearch(document.getElementById('smartSearchInput').value)">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Search Summary -->
                        <div id="searchSummary" class="search-summary" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center">
                                <span id="searchSummaryText"></span>
                                <button class="btn btn-outline-secondary btn-sm" onclick="clearSearch()">
                                    <i class="fas fa-times me-1"></i>Afficher tout
                                </button>
                            </div>
                        </div>
                    </div>

                    <h6 class="mb-3">
                        <i class="fas fa-archive me-2"></i>
                        Archive (terminé)
                    </h6>

                    <?php if (empty($bcs_archive)): ?>
                        <p class="text-muted">Aucun bon de commande archivé.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped align-middle bc-table">
                                <thead>
                                    <tr>
                                        <th>Référence</th>
                                        <th>DP</th>
                                        <th>Fournisseur</th>
                                        <th>Date</th>
                                        <th>Statut</th>
                                        <th>Articles</th>
                                        <th>Montant</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bcs_archive as $bc): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($bc['ref_bc']); ?></td>
                                            <td><?php echo htmlspecialchars($bc['ref_dp']); ?></td>
                                            <td><?php echo htmlspecialchars($bc['nom_fournisseur']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($bc['date_commande'])); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower(str_replace(' ', '_', $bc['statut'])); ?>">
                                                    <?php echo htmlspecialchars($bc['statut']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo intval($bc['nombre_articles'] ?? 0); ?></td>
                                            <td><?php echo number_format(floatval($bc['montant_total'] ?? 0), 2, ',', ' '); ?> MAD</td>
                                            <td class="d-flex gap-2 flex-wrap">
                                                <a href="achat_bc_view.php?id=<?php echo $bc['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="achat_bc_pdf.php?id=<?php echo $bc['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-file-pdf"></i>
                                                </a>
                                                <a href="achat_be.php?bc_id=<?php echo $bc['id']; ?>" class="btn btn-sm btn-success">
                                                    <i class="fas fa-truck-loading"></i>
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
        let selectedDP = null;
        let itemCount = 0;
        let allBCData = [];
        
        // Store all Archive BC data for search
        document.addEventListener('DOMContentLoaded', function() {
            // Collect only Archive BC data from the table
            const bcTableRows = document.querySelectorAll('.bc-table tbody tr');
            
            console.log('Found Archive BC table rows:', bcTableRows.length);
            
            bcTableRows.forEach((row, index) => {
                const bcData = extractBCDataFromTableRow(row);
                if (bcData) {
                    console.log('Archive BC Table Data', index, bcData);
                    allBCData.push(bcData);
                }
            });
            
            console.log('Total Archive BC Data collected:', allBCData.length);
        });
        
        function extractBCDataFromCard(card) {
            try {
                const refElement = card.querySelector('h5');
                const ref = refElement ? refElement.textContent.trim() : '';
                
                const detailsElement = card.querySelector('.text-muted');
                const details = detailsElement ? detailsElement.textContent.trim() : '';
                
                const statutElement = card.querySelector('.status-badge');
                const statut = statutElement ? statutElement.textContent.trim() : '';
                
                const montantElement = card.querySelector('.bg-success');
                const montant = montantElement ? montantElement.textContent.trim() : '';
                
                const articlesElement = card.querySelector('.bg-info');
                const articles = articlesElement ? articlesElement.textContent.trim() : '';
                
                return {
                    ref: ref,
                    details: details,
                    statut: statut,
                    montant: montant,
                    articles: articles,
                    element: card,
                    type: 'card'
                };
            } catch (e) {
                console.error('Error extracting BC data from card:', e);
                return null;
            }
        }
        
        function extractBCDataFromTableRow(row) {
            try {
                const cells = row.querySelectorAll('td');
                if (cells.length < 5) return null;
                
                return {
                    ref: cells[0] ? cells[0].textContent.trim() : '',
                    details: cells[1] ? cells[1].textContent.trim() : '',
                    statut: cells[2] ? cells[2].textContent.trim() : '',
                    articles: cells[3] ? cells[3].textContent.trim() : '',
                    montant: cells[4] ? cells[4].textContent.trim() : '',
                    element: row,
                    type: 'table'
                };
            } catch (e) {
                console.error('Error extracting BC data from table row:', e);
                return null;
            }
        }
        
        // Smart Search Functions
        function performSmartSearch(searchTerm) {
            console.log('Performing search for:', searchTerm);
            console.log('Total Archive BC Data available:', allBCData.length);
            
            if (!searchTerm || searchTerm.length < 2) {
                console.log('Search term too short, showing all Archive BC');
                showAllArchiveBC();
                hideSearchSummary();
                return;
            }
            
            const results = [];
            
            allBCData.forEach((bc, index) => {
                let matches = false;
                
                console.log(`Checking Archive BC ${index}:`, bc);
                
                // Search in all fields
                matches = searchInAllFields(bc, searchTerm);
                
                console.log(`Archive BC ${index} matches:`, matches);
                
                if (matches) {
                    results.push(bc);
                }
            });
            
            console.log('Search results:', results.length);
            displaySearchResults(results, searchTerm);
        }
        
        function searchInAllFields(bc, searchTerm) {
            const term = searchTerm.toLowerCase();
            
            // Clean amount text for comparison (remove spaces, currency symbols, etc.)
            const cleanMontant = bc.montant.replace(/[^\d.,]/g, '').replace(',', '.');
            
            // Check if search term is a number and compare with amount
            const isNumericSearch = /^\d+(\.\d+)?$/.test(term);
            const amountMatch = isNumericSearch && cleanMontant.includes(term);
            
            return (
                bc.ref.toLowerCase().includes(term) ||
                bc.details.toLowerCase().includes(term) ||
                bc.statut.toLowerCase().includes(term) ||
                amountMatch ||
                bc.articles.toLowerCase().includes(term)
            );
        }
        
        function displaySearchResults(results, searchTerm) {
            console.log('Displaying search results:', results.length);
            
            // Hide all BC elements
            allBCData.forEach(bc => {
                bc.element.style.display = 'none';
            });
            
            // Show matching results
            results.forEach(bc => {
                bc.element.style.display = '';
                // Highlight matching text
                highlightSearchTerm(bc.element, searchTerm);
            });
            
            // Show search summary
            showSearchSummary(results.length, searchTerm);
        }
        
        function highlightSearchTerm(element, searchTerm) {
            if (!searchTerm || searchTerm.length < 2) return;
            
            const textNodes = getTextNodes(element);
            const regex = new RegExp(`(${escapeRegExp(searchTerm)})`, 'gi');
            
            textNodes.forEach(node => {
                const text = node.textContent;
                const matches = text.match(regex);
                if (matches) {
                    const span = document.createElement('span');
                    span.innerHTML = text.replace(regex, '<mark>$1</mark>');
                    node.parentNode.replaceChild(span, node);
                }
            });
        }
        
        function getTextNodes(element) {
            const textNodes = [];
            const walker = document.createTreeWalker(
                element,
                NodeFilter.SHOW_TEXT,
                null,
                false
            );
            
            let node;
            while (node = walker.nextNode()) {
                if (node.textContent.trim()) {
                    textNodes.push(node);
                }
            }
            
            return textNodes;
        }
        
        function escapeRegExp(string) {
            return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }
        
        function showAllArchiveBC() {
            console.log('Showing all Archive BC');
            allBCData.forEach(bc => {
                bc.element.style.display = '';
                // Remove highlights
                removeHighlights(bc.element);
            });
        }
        
        function hideSearchSummary() {
            const summary = document.getElementById('searchSummary');
            if (summary) {
                summary.style.display = 'none';
            }
        }
        
        function showSearchSummary(count, searchTerm) {
            const summary = document.getElementById('searchSummary');
            const summaryText = document.getElementById('searchSummaryText');
            
            if (summary && summaryText) {
                summaryText.textContent = `Trouvé ${count} résultat(s) pour "${searchTerm}"`;
                summary.style.display = 'block';
            }
        }
        
        function clearSearch() {
            document.getElementById('smartSearchInput').value = '';
            showAllArchiveBC();
            hideSearchSummary();
        }
        
        function toggleAdvancedSearch() {
            const options = document.getElementById('advancedSearchOptions');
            if (options) {
                options.style.display = options.style.display === 'none' ? 'block' : 'none';
            }
        }
        
        function removeHighlights(element) {
            const marks = element.querySelectorAll('mark');
            marks.forEach(mark => {
                const parent = mark.parentNode;
                parent.replaceChild(document.createTextNode(mark.textContent), mark);
                parent.normalize();
            });
        }
        
        // Manual search function for testing
        function manualSearch() {
            const searchTerm = prompt('Enter search term:');
            if (searchTerm) {
                console.log('Manual search for:', searchTerm);
                performSmartSearch(searchTerm);
            }
        }
        
        // Test function to verify BC data
        function testBCData() {
            console.log('=== BC Data Test ===');
            console.log('Total BC Data:', allBCData.length);
            
            allBCData.forEach((bc, index) => {
                console.log(`BC ${index}:`, {
                    ref: bc.ref,
                    details: bc.details,
                    statut: bc.statut,
                    montant: bc.montant,
                    articles: bc.articles,
                    type: bc.type
                });
            });
            
            // Test search for common terms
            const testTerms = ['ALLIANCE', '60', 'Brouillon', 'Validé'];
            testTerms.forEach(term => {
                const results = allBCData.filter(bc => 
                    searchInAllFields(bc, term)
                );
                console.log(`Test search for "${term}": ${results.length} results`);
                results.forEach((bc, index) => {
                    console.log(`  - Result ${index}: ${bc.ref}`);
                });
            });
        }
        
        // Add test button to the page
        document.addEventListener('DOMContentLoaded', function() {
            // Add test button after search bar
            const searchCard = document.querySelector('.iso-card');
            if (searchCard) {
                const testButton = document.createElement('button');
                testButton.className = 'btn btn-outline-info btn-sm mt-2';
                testButton.innerHTML = '<i class="fas fa-bug me-1"></i>Test Search';
                testButton.onclick = testBCData;
                searchCard.appendChild(testButton);
            }
        });
        
        function updateSearchPlaceholder() {
            const searchType = document.getElementById('searchType').value;
            const input = document.getElementById('smartSearchInput');
            
            const placeholders = {
                'all': 'Rechercher par référence, fournisseur, montant, statut...',
                'ref_bc': 'Rechercher par référence BC...',
                'ref_dp': 'Rechercher par référence DP...',
                'fournisseur': 'Rechercher par fournisseur...',
                'montant': 'Rechercher par montant...',
                'statut': 'Rechercher par statut...',
                'date': 'Rechercher par date...'
            };
            
            input.placeholder = placeholders[searchType] || placeholders['all'];
        }
        
        // Advanced Search Functions
        function toggleAdvancedSearch() {
            const options = document.getElementById('advancedSearchOptions');
            options.style.display = options.style.display === 'none' ? 'block' : 'none';
        }
        
        function performAdvancedSearch() {
            const filters = {
                dateFrom: document.getElementById('searchDateFrom').value,
                dateTo: document.getElementById('searchDateTo').value,
                amountMin: document.getElementById('searchAmountMin').value,
                amountMax: document.getElementById('searchAmountMax').value,
                statut: document.getElementById('searchStatut').value,
                fournisseur: document.getElementById('searchFournisseur').value,
                articles: document.getElementById('searchArticles').value
            };
            
            const results = [];
            
            allBCData.forEach(bc => {
                if (matchesAdvancedFilters(bc, filters)) {
                    results.push(bc);
                }
            });
            
            displaySearchResults(results, 'recherche avancée');
        }
        
        function matchesAdvancedFilters(bc, filters) {
            // Check date filter
            if (filters.dateFrom || filters.dateTo) {
                // Extract date from details (assuming format: dd/mm/yyyy)
                const dateMatch = bc.details.match(/(\d{2}\/\d{2}\/\d{4})/);
                if (dateMatch) {
                    const bcDate = new Date(dateMatch[1].split('/').reverse().join('-'));
                    const fromDate = filters.dateFrom ? new Date(filters.dateFrom) : null;
                    const toDate = filters.dateTo ? new Date(filters.dateTo) : null;
                    
                    if (fromDate && bcDate < fromDate) return false;
                    if (toDate && bcDate > toDate) return false;
                }
            }
            
            // Check amount filter
            if (filters.amountMin || filters.amountMax) {
                const amount = parseFloat(bc.montant.replace(/[^\d.-]/g, ''));
                if (!isNaN(amount)) {
                    if (filters.amountMin && amount < parseFloat(filters.amountMin)) return false;
                    if (filters.amountMax && amount > parseFloat(filters.amountMax)) return false;
                }
            }
            
            // Check statut filter
            if (filters.statut && !bc.statut.toLowerCase().includes(filters.statut.toLowerCase())) {
                return false;
            }
            
            // Check fournisseur filter
            if (filters.fournisseur && !bc.details.toLowerCase().includes(filters.fournisseur.toLowerCase())) {
                return false;
            }
            
            // Check articles filter
            if (filters.articles) {
                const articles = parseInt(bc.articles.replace(/[^\d]/g, ''));
                if (!isNaN(articles) && articles !== parseInt(filters.articles)) {
                    return false;
                }
            }
            
            return true;
        }
        
        function resetAdvancedSearch() {
            document.getElementById('searchDateFrom').value = '';
            document.getElementById('searchDateTo').value = '';
            document.getElementById('searchAmountMin').value = '';
            document.getElementById('searchAmountMax').value = '';
            document.getElementById('searchStatut').value = '';
            document.getElementById('searchFournisseur').value = '';
            document.getElementById('searchArticles').value = '';
            
            clearSearch();
        }
        
        function selectDP(dpId) {
            // Désélectionner la carte précédente
            document.querySelectorAll('.dp-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Sélectionner la nouvelle carte
            const card = event.currentTarget.closest('.dp-card');
            card.classList.add('selected');
            
            // Récupérer les informations de la DP
            fetch(`achat_bc_get_dp_items.php?dp_id=${dpId}`)
                .then(response => response.json())
                .then(data => {
                    selectedDP = data.dp;
                    document.getElementById('selectedDPId').value = dpId;
                    document.getElementById('dpReference').value = data.dp.ref_dp;
                    document.getElementById('fournisseurNom').value = data.dp.nom_fournisseur;
                    
                    // Afficher le formulaire
                    document.getElementById('bcFormContainer').style.display = 'block';
                    
                    // Copier les articles
                    const container = document.getElementById('itemsContainer');
                    container.innerHTML = '';
                    
                    if (data.items && data.items.length > 0) {
                        data.items.forEach((item, index) => {
                            const itemRow = document.createElement('div');
                            itemRow.className = 'item-row';
                            itemRow.innerHTML = `
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <input type="text" name="items[${index}][designation]" class="form-control" value="${item.designation}" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" name="items[${index}][quantite]" class="form-control" value="${item.quantite}" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" name="items[${index}][prix_unitaire]" class="form-control" value="${item.prix_unitaire}" step="0.01" min="0" onchange="calculateFinancials()">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" class="form-control" value="${(parseFloat(item.quantite) * parseFloat(item.prix_unitaire)).toFixed(2)}" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" name="items[${index}][item_code]" class="form-control" placeholder="Code article" value="${item.item_code || item.ref_article || ''}">
                                    </div>
                                </div>
                            `;
                            container.appendChild(itemRow);
                        });
                    }
                    
                    // Calculer les totaux
                    calculateFinancials();
                    
                    // Faire défiler vers le formulaire
                    document.getElementById('bcForm').scrollIntoView({ behavior: 'smooth' });
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors du chargement des articles de la demande de prix');
                });
        }
        
        function addItem() {
            const container = document.getElementById('itemsContainer');
            const itemRow = document.createElement('div');
            itemRow.className = 'item-row';
            itemRow.innerHTML = `
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" name="items[${itemCount}][designation]" class="form-control" placeholder="Désignation *" required>
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="items[${itemCount}][quantite]" class="form-control" placeholder="Quantité *" min="1" required onchange="calculateFinancials()">
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="items[${itemCount}][prix_unitaire]" class="form-control" placeholder="Prix unitaire" step="0.01" min="0" onchange="calculateFinancials()">
                    </div>
                    <div class="col-md-2">
                        <input type="text" class="form-control" placeholder="Total" readonly>
                    </div>
                    <div class="col-md-2">
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
            calculateFinancials();
        }
        
        function calculateFinancials() {
            let totalHT = 0;
            const items = document.querySelectorAll('#itemsContainer .item-row');
            
            items.forEach(item => {
                const quantity = parseFloat(item.querySelector('input[name*="[quantite]"]').value) || 0;
                const price = parseFloat(item.querySelector('input[name*="[prix_unitaire]"]').value) || 0;
                const itemTotal = quantity * price;
                
                // Mettre à jour le total de l'article
                const totalInput = item.querySelector('input[placeholder="Total"]');
                if (totalInput) {
                    totalInput.value = itemTotal.toFixed(2);
                }
                
                totalHT += itemTotal;
            });
            
            const tvaRate = parseFloat(document.querySelector('select[name="tva_rate"]').value) || 20;
            const tvaAmount = totalHT * (tvaRate / 100);
            const totalTTC = totalHT + tvaAmount;
            
            document.getElementById('totalHT').textContent = totalHT.toFixed(2) + ' MAD';
            document.getElementById('tvaAmount').textContent = tvaAmount.toFixed(2) + ' MAD';
            document.getElementById('totalTTC').textContent = totalTTC.toFixed(2) + ' MAD';
            
            // Ajouter une note pour TVA 0%
            const tvaNote = document.getElementById('tvaNote');
            if (tvaRate === 0) {
                if (!tvaNote) {
                    const noteDiv = document.createElement('div');
                    noteDiv.id = 'tvaNote';
                    noteDiv.className = 'alert alert-info mt-2';
                    noteDiv.innerHTML = '<i class="fas fa-info-circle me-2"></i>Régime de l\'Auto-entrepreneur - Exonéré de TVA';
                    document.querySelector('.financial-summary').appendChild(noteDiv);
                }
            } else if (tvaNote) {
                tvaNote.remove();
            }
        }
        
        function exportBC() {
            // Implémenter l'exportation Excel/CSV
            alert('Fonction d\'exportation à implémenter');
        }
        
        // Calcul automatique des totaux
        document.addEventListener('input', function(e) {
            if (e.target.name.includes('[quantite]') || e.target.name.includes('[prix_unitaire]')) {
                calculateFinancials();
            }
        });
        
        document.addEventListener('change', function(e) {
            if (e.target.name === 'tva_rate') {
                calculateFinancials();
            }
        });
    </script>
</body>
</html>

<?php
// ATEO Auto - View Bon de Commande
// Interface pour afficher les détails d'un bon de commande

require_once 'config.php';
require_once 'config_achat_hostinger.php';

// Vérifier si l'utilisateur est connecté
if (!is_logged_in()) {
    header('Location: login.php');
    exit();
}

// Récupérer l'ID du BC
$bc_id = $_GET['id'] ?? 0;
if (!$bc_id) {
    header('Location: achat_bc.php');
    exit();
}

// Récupérer les détails du bon de commande
try {
    $database = new DatabaseAchat();
    $conn = $database->connect();
    
    // Récupérer le bon de commande principal
    $stmt = $conn->prepare("
        SELECT bc.*, 
               dp.ref_dp,
               da.ref_da,
               dp.fournisseur_id,
               s.nom_fournisseur,
               s.contact_nom as contact_nom,
               s.email as email_fournisseur,
               s.telephone as telephone_fournisseur,
               s.adresse as adresse_fournisseur
        FROM bons_commande bc
        LEFT JOIN demandes_prix dp ON bc.dp_id = dp.id
        LEFT JOIN demandes_achat da ON dp.da_id = da.id
        LEFT JOIN suppliers s ON dp.fournisseur_id = s.id
        WHERE bc.id = ?
    ");
    $stmt->execute([$bc_id]);
    $bc = $stmt->fetch();
    
    if (!$bc) {
        header('Location: achat_bc.php');
        exit();
    }
    
    // Récupérer les articles du BC
    $stmt = $conn->prepare("
        SELECT * FROM bc_items 
        WHERE bc_id = ?
        ORDER BY id
    ");
    $stmt->execute([$bc_id]);
    $articles = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error_message = "Erreur lors du chargement du bon de commande: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bon de Commande - <?php echo htmlspecialchars($bc['ref_bc']); ?></title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
            margin-left: 260px;
            padding: 20px;
            min-height: 100vh;
        }
        
        .bc-header {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .bc-details {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .supplier-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .status-commande {
            background: #dbeafe;
            color: #2563eb;
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
        
        .articles-table {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .table th {
            background: var(--primary-color);
            color: white;
            border: none;
        }
        
        .table th,
        .table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        
        .table tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .total-row {
            background: var(--primary-color) !important;
            color: white !important;
            font-weight: bold;
        }
        
        .financial-summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .financial-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        
        .financial-item:last-child {
            border-bottom: none;
            font-size: 1.3rem;
            font-weight: bold;
        }
        
        .action-buttons {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .btn-action {
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
            margin-bottom: 0.5rem;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
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
            <!-- Header -->
            <div class="bc-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h1 class="mb-2">
                            <i class="fas fa-file-invoice-dollar me-3"></i>
                            <?php echo htmlspecialchars($bc['ref_bc']); ?>
                        </h1>
                        <p class="text-muted mb-0">Détails du bon de commande</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <div class="d-flex gap-2 justify-content-md-end flex-wrap">
                            <a href="achat_be.php?bc_id=<?php echo $bc_id; ?>" class="btn btn-success">
                                <i class="fas fa-truck-loading me-2"></i>Créer Bon d'Entrée
                            </a>
                            <a href="achat_bc.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Retour
                            </a>
                            <a href="achat_bc_print.php?id=<?php echo $bc_id; ?>" target="_blank" rel="noopener" class="btn btn-outline-primary">
                                <i class="fas fa-print me-2"></i>Imprimer
                            </a>
                            <a href="achat_bc_pdf.php?id=<?php echo $bc_id; ?>" target="_blank" rel="noopener" class="btn btn-outline-danger">
                                <i class="fas fa-file-pdf me-2"></i>PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- BC Details -->
            <div class="bc-details">
                <h3 class="mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    Informations Générales
                </h3>
                
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Référence:</strong></td>
                                <td><?php echo htmlspecialchars($bc['ref_bc']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Demande de Prix:</strong></td>
                                <td><?php echo htmlspecialchars($bc['ref_dp']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Demande d'Achat:</strong></td>
                                <td><?php echo htmlspecialchars($bc['ref_da']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Date de commande:</strong></td>
                                <td><?php echo date('d/m/Y', strtotime($bc['date_commande'])); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Statut:</strong></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '_', $bc['statut'])); ?>">
                                        <?php echo htmlspecialchars($bc['statut']); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Taux TVA:</strong></td>
                                <td>
                                    <?php 
                                    $tva_rate = $bc['tva_rate'] ?? 20;
                                    echo number_format($tva_rate, 1, ',', ' ') . '%';
                                    if ($tva_rate == 0) {
                                        echo ' <span class="badge bg-info ms-2">Exonéré</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Conditions de paiement:</strong></td>
                                <td><?php echo htmlspecialchars($bc['payment_terms'] ?? 'Non spécifié'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Nombre d'articles:</strong></td>
                                <td><?php echo count($articles); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- Supplier Information -->
                <div class="supplier-info">
                    <h4><i class="fas fa-store me-2"></i>Informations Fournisseur</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Nom:</strong></td>
                                    <td><?php echo htmlspecialchars($bc['nom_fournisseur'] ?? 'Non spécifié'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Contact:</strong></td>
                                    <td><?php echo htmlspecialchars($bc['contact_nom'] ?? 'Non spécifié'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td><?php echo htmlspecialchars($bc['email_fournisseur'] ?? 'Non spécifié'); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Téléphone:</strong></td>
                                    <td><?php echo htmlspecialchars($bc['telephone_fournisseur'] ?? 'Non spécifié'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Adresse de livraison:</strong></td>
                                    <td><?php echo htmlspecialchars($bc['delivery_address'] ?? 'Non spécifiée'); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Financial Summary -->
            <div class="financial-summary">
                <h3 class="mb-4">
                    <i class="fas fa-calculator me-2"></i>
                    Récapitulatif Financier
                </h3>
                
                <div class="financial-item">
                    <span>Total HT:</span>
                    <span><?php echo number_format($bc['total_ht'], 2, ',', ' '); ?> MAD</span>
                </div>
                <div class="financial-item">
                    <span>TVA (<?php echo number_format($bc['tva_rate'] ?? 20, 1, ',', ' '); ?>%):</span>
                    <span><?php echo number_format($bc['tva'], 2, ',', ' '); ?> MAD</span>
                </div>
                <?php if (($bc['tva_rate'] ?? 20) == 0): ?>
                <div class="financial-item" style="background: rgba(255,255,255,0.2); padding: 8px; border-radius: 5px; margin: 5px 0;">
                    <span style="font-size: 14px; font-style: italic;">Exonéré de TVA - Régime de l'Auto-entrepreneur</span>
                </div>
                <?php endif; ?>
                <div class="financial-item">
                    <span>Total TTC:</span>
                    <span><?php echo number_format($bc['total_ttc'], 2, ',', ' '); ?> MAD</span>
                </div>
            </div>
            
            <!-- Articles Table -->
            <div class="articles-table">
                <h3 class="mb-4">
                    <i class="fas fa-list me-2"></i>
                    Articles Commandés
                </h3>
                
                <?php if (empty($articles)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Aucun article</h5>
                        <p class="text-muted">Ce bon de commande ne contient aucun article.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Code Article</th>
                                    <th>Désignation</th>
                                    <th>Quantité</th>
                                    <th>Prix Unitaire</th>
                                    <th>Total HT</th>
                                    <th>TVA</th>
                                    <th>Total TTC</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_ht_check = 0;
                                $total_tva_check = 0;
                                $total_ttc_check = 0;
                                foreach ($articles as $index => $article): 
                                    $total_ht_check += $article['total_price'];
                                    $total_tva_check += $article['tax_amount'];
                                    $total_ttc_check += $article['total_with_tax'];
                                ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($article['item_code'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($article['item_description']); ?></td>
                                        <td><?php echo number_format($article['quantity']); ?></td>
                                        <td><?php echo number_format($article['unit_price'], 2, ',', ' '); ?> MAD</td>
                                        <td><?php echo number_format($article['total_price'], 2, ',', ' '); ?> MAD</td>
                                        <td><?php echo number_format($article['tax_rate'], 1, ',', ' '); ?>%</td>
                                        <td><?php echo number_format($article['total_with_tax'], 2, ',', ' '); ?> MAD</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="total-row">
                                    <th colspan="5" style="text-align: right;">Total Général:</th>
                                    <th><?php echo number_format($total_ht_check, 2, ',', ' '); ?> MAD</th>
                                    <th><?php echo number_format($total_tva_check, 2, ',', ' '); ?> MAD</th>
                                    <th><?php echo number_format($total_ttc_check, 2, ',', ' '); ?> MAD</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <h3 class="mb-4">
                    <i class="fas fa-cogs me-2"></i>
                    Actions
                </h3>
                
                <div class="row">
                    <div class="col-md-3">
                        <?php if ($bc['statut'] === 'Commandé'): ?>
                            <a href="achat_bc_edit.php?id=<?php echo $bc_id; ?>" class="btn-action btn-warning w-100">
                                <i class="fas fa-edit me-2"></i>Modifier
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3">
                        <?php if ($bc['statut'] === 'Commandé' || $bc['statut'] === 'Livré partiellement'): ?>
                            <!-- BL creation not implemented; show disabled button to avoid confusion -->
                            <button type="button" class="btn-action btn-info w-100" disabled title="Création de BL non disponible">
                                <i class="fas fa-truck me-2"></i>Créer BL
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3">
                        <!-- Duplication not implemented for BC: show disabled button -->
                        <button class="btn-action btn-secondary w-100" disabled title="Duplication non implémentée">
                            <i class="fas fa-copy me-2"></i>Dupliquer
                        </button>
                    </div>
                    <div class="col-md-3">
                        <?php if ($bc['statut'] === 'Commandé'): ?>
                            <!-- Cancellation not implemented for BC: show disabled button -->
                            <button class="btn-action btn-danger w-100" disabled title="Annulation non implémentée">
                                <i class="fas fa-times me-2"></i>Annuler
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <a href="mailto:<?php echo htmlspecialchars($bc['email_fournisseur'] ?? ''); ?>" class="btn-action btn-primary w-100">
                            <i class="fas fa-envelope me-2"></i>Envoyer par Email
                        </a>
                    </div>
                    <!-- Phone contact removed to avoid accidental calls; WhatsApp kept if phone exists -->
                    <div class="col-md-6">
                        <button class="btn-action btn-outline-primary w-100" onclick="sendWhatsApp()" <?php echo empty($bc['telephone_fournisseur']) ? 'disabled title=\"Numéro non disponible\"' : ''; ?>>
                            <i class="fab fa-whatsapp me-2"></i>Envoyer WhatsApp
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include footer -->
    <?php include __DIR__ . '/includes/footer.php'; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function duplicateBC() {
            alert('La duplication de bon de commande sera bientôt disponible.');
        }
        
        function cancelBC() {
            alert('L’annulation de bon de commande sera bientôt disponible.');
        }
        
        function sendWhatsApp() {
            const phone = '<?php echo htmlspecialchars($bc['telephone_fournisseur'] ?? ''); ?>';
            const ref = '<?php echo addslashes($bc['ref_bc'] ?? ''); ?>';
            const message = 'Bonjour, je vous envoie notre bon de commande ' + ref + '. Vous pouvez consulter le PDF détaillé sur notre plateforme.';
            
            if (phone) {
                window.open('https://wa.me/' + phone.replace(/\\D/g, '') + '?text=' + encodeURIComponent(message), '_blank');
            } else {
                alert('Numéro de téléphone non disponible');
            }
        }
    </script>
</body>
</html>

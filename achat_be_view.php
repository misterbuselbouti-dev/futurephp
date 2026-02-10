<?php
// ATEO Auto - View Bon d'Entrée
// Interface pour afficher les détails d'un bon d'entrée

require_once 'config.php';
require_once 'config_achat_hostinger.php';

// Vérifier si l'utilisateur est connecté
if (!is_logged_in()) {
    header('Location: login.php');
    exit();
}

// Récupérer l'ID du BE
$be_id = $_GET['id'] ?? 0;
if (!$be_id) {
    header('Location: achat_be.php');
    exit();
}

// Récupérer les détails du bon d'entrée
try {
    $database = new DatabaseAchat();
    $conn = $database->connect();
    
    // Récupérer le bon d'entrée principal
    $stmt = $conn->prepare("
        SELECT be.*, 
               bc.ref_bc,
               bc.date_commande,
               bc.total_ttc as bc_total,
               dp.ref_dp,
               da.ref_da,
               dp.fournisseur_id,
               s.nom_fournisseur,
               s.contact_nom as contact_nom,
               s.email as email_fournisseur,
               s.telephone as telephone_fournisseur,
               u.full_name as created_by_name
        FROM bons_entree be
        LEFT JOIN bons_commande bc ON be.bc_id = bc.id
        LEFT JOIN demandes_prix dp ON bc.dp_id = dp.id
        LEFT JOIN demandes_achat da ON dp.da_id = da.id
        LEFT JOIN suppliers s ON dp.fournisseur_id = s.id
        LEFT JOIN users u ON be.created_by = u.id
        WHERE be.id = ?
    ");
    $stmt->execute([$be_id]);
    $be = $stmt->fetch();
    
    if (!$be) {
        header('Location: achat_be.php');
        exit();
    }
    
    // Récupérer les articles du BE
    $stmt = $conn->prepare("
        SELECT bei.*, 
               bci.item_code,
               bci.item_description,
               bci.quantity as quantite_commandee,
               bci.unit_price
        FROM be_items bei
        LEFT JOIN bc_items bci ON bei.bc_item_id = bci.id
        WHERE bei.be_id = ?
        ORDER BY bei.id
    ");
    $stmt->execute([$be_id]);
    $articles = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error_message = "Erreur lors du chargement du bon d'entrée: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bon d'Entrée - <?php echo htmlspecialchars($be['ref_be']); ?></title>
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
        
        .be-header {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .be-details {
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
        
        .status-recu {
            background: #d1fae5;
            color: #059669;
        }
        
        .status-valide {
            background: #dcfce7;
            color: #059669;
        }
        
        .status-rejete {
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
        
        .condition-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .condition-bon {
            background: #d1fae5;
            color: #059669;
        }
        
        .condition-endommage {
            background: #fef3c7;
            color: #d97706;
        }
        
        .condition-abime {
            background: #fee2e2;
            color: #dc2626;
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
            <div class="be-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h1 class="mb-2">
                            <i class="fas fa-truck-loading me-3"></i>
                            <?php echo htmlspecialchars($be['ref_be']); ?>
                        </h1>
                        <p class="text-muted mb-0">Détails du bon d'entrée</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <div class="d-flex gap-2 justify-content-md-end">
                            <a href="achat_be.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Retour
                            </a>
                            <button class="btn btn-outline-primary" onclick="window.print()">
                                <i class="fas fa-print me-2"></i>Imprimer
                            </button>
                            <a href="achat_be_pdf.php?id=<?php echo $be_id; ?>" class="btn btn-outline-danger">
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
            
            <!-- BE Details -->
            <div class="be-details">
                <h3 class="mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    Informations Générales
                </h3>
                
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Référence:</strong></td>
                                <td><?php echo htmlspecialchars($be['ref_be']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Bon de Commande:</strong></td>
                                <td><?php echo htmlspecialchars($be['ref_bc']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Date de réception:</strong></td>
                                <td><?php echo date('d/m/Y', strtotime($be['reception_date'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Réceptionnaire:</strong></td>
                                <td><?php echo htmlspecialchars($be['receptionnaire']); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Statut:</strong></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($be['statut']); ?>">
                                        <?php echo htmlspecialchars($be['statut']); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Date commande BC:</strong></td>
                                <td><?php echo date('d/m/Y', strtotime($be['date_commande'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Total TTC BC:</strong></td>
                                <td><?php echo number_format($be['bc_total'], 2, ',', ' '); ?> MAD</td>
                            </tr>
                            <tr>
                                <td><strong>Créé par:</strong></td>
                                <td><?php echo htmlspecialchars($be['created_by_name'] ?? 'Inconnu'); ?></td>
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
                                    <td><?php echo htmlspecialchars($be['nom_fournisseur']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Contact:</strong></td>
                                    <td><?php echo htmlspecialchars($be['contact_nom'] ?? 'Non spécifié'); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td><?php echo htmlspecialchars($be['email_fournisseur'] ?? 'Non spécifié'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Téléphone:</strong></td>
                                    <td><?php echo htmlspecialchars($be['telephone_fournisseur'] ?? 'Non spécifié'); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($be['notes'])): ?>
                    <div class="mt-4">
                        <h5><i class="fas fa-comment me-2"></i>Notes</h5>
                        <div class="alert alert-info">
                            <?php echo nl2br(htmlspecialchars($be['notes'])); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Articles Table -->
            <div class="articles-table">
                <h3 class="mb-4">
                    <i class="fas fa-list me-2"></i>
                    Articles Reçus
                </h3>
                
                <?php if (empty($articles)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Aucun article</h5>
                        <p class="text-muted">Ce bon d'entrée ne contient aucun article.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Code Article</th>
                                    <th>Désignation</th>
                                    <th>Quantité Commandée</th>
                                    <th>Quantité Reçue</th>
                                    <th>Prix Unitaire</th>
                                    <th>Total</th>
                                    <th>État</th>
                                    <th>Emplacement</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_recu = 0;
                                $total_value = 0;
                                foreach ($articles as $index => $article): 
                                    $item_total = $article['quantite_recue'] * $article['unit_price'];
                                    $total_recu += $article['quantite_recue'];
                                    $total_value += $item_total;
                                ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($article['item_code'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($article['item_description']); ?></td>
                                        <td><?php echo number_format($article['quantite_commandee']); ?></td>
                                        <td><strong><?php echo number_format($article['quantite_recue']); ?></strong></td>
                                        <td><?php echo number_format($article['unit_price'], 2, ',', ' '); ?> MAD</td>
                                        <td><?php echo number_format($item_total, 2, ',', ' '); ?> MAD</td>
                                        <td>
                                            <span class="condition-badge condition-<?php echo strtolower($article['condition_status']); ?>">
                                                <?php echo htmlspecialchars($article['condition_status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($article['emplacement']); ?></td>
                                    </tr>
                                    <?php if (!empty($article['batch_number'])): ?>
                                    <tr>
                                        <td colspan="9" style="background: #f8f9fa; font-size: 0.9rem;">
                                            <strong>Numéro de lot:</strong> <?php echo htmlspecialchars($article['batch_number']); ?>
                                            <?php if (!empty($article['expiry_date'])): ?>
                                                | <strong>Date d'expiration:</strong> <?php echo date('d/m/Y', strtotime($article['expiry_date'])); ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if (!empty($article['notes'])): ?>
                                    <tr>
                                        <td colspan="9" style="background: #f8f9fa; font-size: 0.9rem;">
                                            <strong>Notes:</strong> <?php echo htmlspecialchars($article['notes']); ?>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-primary">
                                    <th colspan="5" style="text-align: right;">Total Général:</th>
                                    <th><?php echo number_format($total_recu); ?></th>
                                    <th><?php echo number_format($total_value, 2, ',', ' '); ?> MAD</th>
                                    <th colspan="2"></th>
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
                        <?php if ($be['statut'] === 'Reçu'): ?>
                            <a href="achat_be_edit.php?id=<?php echo $be_id; ?>" class="btn-action btn-warning w-100">
                                <i class="fas fa-edit me-2"></i>Modifier
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3">
                        <button class="btn-action btn-secondary w-100" onclick="duplicateBE()">
                            <i class="fas fa-copy me-2"></i>Dupliquer
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn-action btn-info w-100" onclick="validateBE()">
                            <i class="fas fa-check me-2"></i>Valider
                        </button>
                    </div>
                    <div class="col-md-3">
                        <?php if ($be['statut'] === 'Reçu'): ?>
                            <button class="btn-action btn-danger w-100" onclick="rejectBE()">
                                <i class="fas fa-times me-2"></i>Rejeter
                            </button>
                        <?php endif; ?>
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
        function duplicateBE() {
            if (confirm('Voulez-vous dupliquer ce bon d\'entrée?')) {
                // Implémenter la duplication
                window.location.href = 'achat_be_duplicate.php?id=<?php echo $be_id; ?>';
            }
        }
        
        function validateBE() {
            if (confirm('Êtes-vous sûr de voulovalider ce bon d\'entrée?')) {
                // Implémenter la validation
                window.location.href = 'achat_be_validate.php?id=<?php echo $be_id; ?>';
            }
        }
        
        function rejectBE() {
            if (confirm('Êtes-vous sûr de vouloir rejeter ce bon d\'entrée?')) {
                // Implémenter le rejet
                window.location.href = 'achat_be_reject.php?id=<?php echo $be_id; ?>';
            }
        }
    </script>
</body>
</html>

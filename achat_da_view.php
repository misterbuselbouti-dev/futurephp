<?php
// ATEO Auto - View Demande d'Achat
// Interface pour afficher les détails d'une demande d'achat

require_once 'config.php';
require_once 'config_achat_hostinger.php';

// Vérifier si l'utilisateur est connecté
if (!is_logged_in()) {
    header('Location: login.php');
    exit();
}

// Récupérer l'ID de la demande
$da_id = $_GET['id'] ?? 0;
if (!$da_id) {
    header('Location: achat_da.php');
    exit();
}

// Récupérer les détails de la demande
try {
    $database = new DatabaseAchat();
    $conn = $database->connect();
    
    // Récupérer la demande principale
    $stmt = $conn->prepare("
        SELECT * FROM demandes_achat WHERE id = ?
    ");
    $stmt->execute([$da_id]);
    $demande = $stmt->fetch();
    
    if (!$demande) {
        header('Location: achat_da.php');
        exit();
    }
    
    // Récupérer les articles
    $stmt = $conn->prepare("
        SELECT * FROM purchase_items 
        WHERE parent_type = 'DA' AND parent_id = ?
        ORDER BY id
    ");
    $stmt->execute([$da_id]);
    $articles = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error_message = "Erreur lors du chargement de la demande: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande d'Achat - <?php echo htmlspecialchars($demande['ref_da']); ?></title>
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
            margin-right: 280px;
            padding: 20px;
            min-height: 100vh;
        }
        
        .da-header {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .da-details {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .priority-badge {
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .priority-normal {
            background: #dbeafe;
            color: #2563eb;
        }
        
        .priority-urgent {
            background: #fef3c7;
            color: #d97706;
        }
        
        .priority-critique {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .status-brouillon {
            background: #f3f4f6;
            color: #6b7280;
        }
        
        .status-en_attente {
            background: #dbeafe;
            color: #2563eb;
        }
        
        .status-valide {
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
                margin-right: 0;
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
            <div class="da-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h1 class="mb-2">
                            <i class="fas fa-shopping-cart me-3"></i>
                            <?php echo htmlspecialchars($demande['ref_da']); ?>
                        </h1>
                        <p class="text-muted mb-0">Détails de la demande d'achat</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <div class="d-flex gap-2 justify-content-md-end">
                            <a href="achat_da.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Retour
                            </a>
                            <button class="btn btn-outline-primary" onclick="window.print()">
                                <i class="fas fa-print me-2"></i>Imprimer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <!-- DA Details -->
            <div class="da-details">
                <h3 class="mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    Informations Générales
                </h3>
                
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Référence:</strong></td>
                                <td><?php echo htmlspecialchars($demande['ref_da']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Demandeur:</strong></td>
                                <td><?php echo htmlspecialchars($demande['demandeur']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Date de création:</strong></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($demande['date_creation'])); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Priorité:</strong></td>
                                <td>
                                    <span class="priority-badge priority-<?php echo strtolower($demande['priorite']); ?>">
                                        <?php echo htmlspecialchars($demande['priorite']); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Statut:</strong></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower(str_replace('é', 'e', $demande['statut'])); ?>">
                                        <?php echo htmlspecialchars($demande['statut']); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Nombre d'articles:</strong></td>
                                <td><?php echo count($articles); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <?php if (!empty($demande['commentaires'])): ?>
                    <div class="mt-4">
                        <h5><i class="fas fa-comment me-2"></i>Commentaires</h5>
                        <div class="alert alert-info">
                            <?php echo nl2br(htmlspecialchars($demande['commentaires'])); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Articles Table -->
            <div class="articles-table">
                <h3 class="mb-4">
                    <i class="fas fa-list me-2"></i>
                    Articles Demandés
                </h3>
                
                <?php if (empty($articles)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Aucun article</h5>
                        <p class="text-muted">Cette demande ne contient aucun article.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Désignation</th>
                                    <th>Quantité</th>
                                    <th>Prix Unitaire</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_general = 0;
                                foreach ($articles as $index => $article): 
                                    $total_general += $article['total_ligne'];
                                ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($article['designation']); ?></td>
                                        <td><?php echo number_format($article['quantite']); ?></td>
                                        <td><?php echo number_format($article['prix_unitaire'], 2, ',', ' '); ?> MAD</td>
                                        <td><?php echo number_format($article['total_ligne'], 2, ',', ' '); ?> MAD</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-primary">
                                    <th colspan="4" class="text-end">Total Général:</th>
                                    <th><?php echo number_format($total_general, 2, ',', ' '); ?> MAD</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <div class="row g-2">
                    <?php if ($demande['statut'] === 'En attente'): ?>
                    <div class="col-md-3">
                        <a href="achat_da_validate.php?id=<?php echo $da_id; ?>" class="btn-action btn-success w-100" onclick="return confirm('Valider cette demande d\'achat?');">
                            <i class="fas fa-check me-2"></i>Valider
                        </a>
                    </div>
                    <?php endif; ?>
                    <?php if ($demande['statut'] === 'Brouillon'): ?>
                    <div class="col-md-3">
                        <button class="btn-action btn-danger w-100" onclick="cancelDA()">
                            <i class="fas fa-times me-2"></i>Annuler
                        </button>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-3">
                        <?php if ($demande['statut'] === 'Validé' || $demande['statut'] === 'En attente'): ?>
                            <a href="achat_dp.php?da_id=<?php echo $da_id; ?>" class="btn-action btn-info w-100">
                                <i class="fas fa-paper-plane me-2"></i>Générer DP
                            </a>
                        <?php elseif ($demande['statut'] === 'Brouillon'): ?>
                            <a href="achat_dp_create.php?da_id=<?php echo $da_id; ?>" class="btn-action btn-info w-100">
                                <i class="fas fa-paper-plane me-2"></i>Générer DP
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3">
                        <button class="btn-action btn-secondary w-100" onclick="duplicateDA()">
                            <i class="fas fa-copy me-2"></i>Dupliquer
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
        function duplicateDA() {
            if (confirm('Voulez-vous dupliquer cette demande d\'achat?')) {
                // Implémenter la duplication
                window.location.href = 'achat_da_duplicate.php?id=<?php echo $da_id; ?>';
            }
        }
        
        function cancelDA() {
            if (confirm('Êtes-vous sûr de vouloir annuler cette demande d\'achat?')) {
                // Implémenter l'annulation
                window.location.href = 'achat_da_cancel.php?id=<?php echo $da_id; ?>';
            }
        }
    </script>
</body>
</html>

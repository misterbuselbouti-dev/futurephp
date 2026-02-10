<?php
// ATEO Auto - View Demande de Prix
// Interface pour afficher les détails d'une demande de prix

require_once 'config.php';
require_once 'config_achat_hostinger.php';

// Vérifier si l'utilisateur est connecté
if (!is_logged_in()) {
    header('Location: login.php');
    exit();
}

// Récupérer l'ID de la DP
$dp_id = $_GET['id'] ?? 0;
if (!$dp_id) {
    header('Location: achat_dp.php');
    exit();
}

// Récupérer les détails de la demande de prix
try {
    $database = new DatabaseAchat();
    $conn = $database->connect();
    
    // Récupérer la demande principale
    $stmt = $conn->prepare("
        SELECT dp.*, 
               da.ref_da,
               da.demandeur as da_demandeur,
               s.nom_fournisseur,
               s.contact_nom as contact_nom,
               s.email as email_fournisseur,
               s.telephone as telephone_fournisseur
        FROM demandes_prix dp
        LEFT JOIN demandes_achat da ON dp.da_id = da.id
        LEFT JOIN suppliers s ON dp.fournisseur_id = s.id
        WHERE dp.id = ?
    ");
    $stmt->execute([$dp_id]);
    $dp = $stmt->fetch();
    
    if (!$dp) {
        header('Location: achat_dp.php');
        exit();
    }
    
    // Récupérer les articles
    $stmt = $conn->prepare("
        SELECT * FROM purchase_items 
        WHERE parent_type = 'DP' AND parent_id = ?
        ORDER BY id
    ");
    $stmt->execute([$dp_id]);
    $articles = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error_message = "Erreur lors du chargement de la demande de prix: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande de Prix - <?php echo htmlspecialchars($dp['ref_dp']); ?></title>
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
        
        .dp-header {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .dp-details {
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
        
        .status-envoye {
            background: #dbeafe;
            color: #2563eb;
        }
        
        .status-recu {
            background: #d1fae5;
            color: #059669;
        }
        
        .status-accepte {
            background: #dcfce7;
            color: #059669;
        }
        
        .status-refuse {
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
        
        .response-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .response-card {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .response-card:hover {
            border-color: var(--primary-color);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .response-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .price-input {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 0.5rem;
            width: 120px;
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
            <div class="dp-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h1 class="mb-2">
                            <i class="fas fa-file-invoice me-3"></i>
                            <?php echo htmlspecialchars($dp['ref_dp']); ?>
                        </h1>
                        <p class="text-muted mb-0">Détails de la demande de prix</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <div class="d-flex gap-2 justify-content-md-end">
                            <a href="achat_dp.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Retour
                            </a>
                            <button class="btn btn-outline-primary" onclick="window.print()">
                                <i class="fas fa-print me-2"></i>Imprimer
                            </button>
                            <a href="achat_dp_pdf.php?id=<?php echo $dp_id; ?>" class="btn btn-outline-danger">
                                <i class="fas fa-file-pdf me-2"></i>PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <?php include __DIR__ . '/includes/achat_tabs.php'; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- DP Details -->
            <div class="dp-details">
                <h3 class="mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    Informations Générales
                </h3>
                
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Référence:</strong></td>
                                <td><?php echo htmlspecialchars($dp['ref_dp']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Demande d'Achat:</strong></td>
                                <td><?php echo htmlspecialchars($dp['ref_da']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Date d'envoi:</strong></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($dp['date_envoi'])); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Statut:</strong></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($dp['statut']); ?>">
                                        <?php echo htmlspecialchars($dp['statut']); ?>
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
                
                <!-- Supplier Information -->
                <div class="supplier-info">
                    <h4><i class="fas fa-store me-2"></i>Informations Fournisseur</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Nom:</strong></td>
                                    <td><?php echo htmlspecialchars($dp['nom_fournisseur']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Contact:</strong></td>
                                    <td><?php echo htmlspecialchars($dp['contact_nom'] ?? 'Non spécifié'); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td><?php echo htmlspecialchars($dp['email_fournisseur'] ?? 'Non spécifié'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Téléphone:</strong></td>
                                    <td><?php echo htmlspecialchars($dp['telephone_fournisseur'] ?? 'Non spécifié'); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($dp['commentaires'])): ?>
                    <div class="mt-4">
                        <h5><i class="fas fa-comment me-2"></i>Commentaires</h5>
                        <div class="alert alert-info">
                            <?php echo nl2br(htmlspecialchars($dp['commentaires'])); ?>
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
                        <p class="text-muted">Cette demande de prix ne contient aucun article.</p>
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
                                <tr class="total-row">
                                    <th colspan="4" style="text-align: right;">Total Général:</th>
                                    <th><?php echo number_format($total_general, 2, ',', ' '); ?> MAD</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Response Section -->
            <div class="response-section">
                <h3 class="mb-4">
                    <i class="fas fa-reply me-2"></i>
                    Réponses des Fournisseurs
                </h3>
                
                <div class="response-card">
                    <div class="response-header">
                        <h5>Ajouter une réponse</h5>
                        <button class="btn btn-sm btn-primary" onclick="addResponse()">
                            <i class="fas fa-plus me-2"></i>Ajouter
                        </button>
                    </div>
                    <div id="responsesContainer">
                        <!-- Les réponses seront ajoutées ici -->
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <div class="row">
                    <div class="col-md-3">
                        <?php if ($dp['statut'] === 'Envoyé'): ?>
                            <a href="achat_dp_edit.php?id=<?php echo $dp_id; ?>" class="btn-action btn-warning w-100">
                                <i class="fas fa-edit me-2"></i>Modifier
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3">
                        <a href="achat_dp_response.php?id=<?php echo $dp_id; ?>" class="btn-action btn-info w-100">
                            <i class="fas fa-reply me-2"></i>Ajouter Réponse
                        </a>
                    </div>
                    <div class="col-md-3">
                        <button class="btn-action btn-secondary w-100" onclick="duplicateDP()">
                            <i class="fas fa-copy me-2"></i>Dupliquer
                        </button>
                    </div>
                    <div class="col-md-3">
                        <?php if ($dp['statut'] === 'Envoyé'): ?>
                            <button class="btn-action btn-danger w-100" onclick="cancelDP()">
                                <i class="fas fa-times me-2"></i>Annuler
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
    <script>
        function addResponse() {
            const container = document.getElementById('responsesContainer');
            const responseCount = container.children.length + 1;
            
            const responseDiv = document.createElement('div');
            responseDiv.className = 'response-card';
            responseDiv.innerHTML = `
                <div class="response-header">
                    <h5>Réponse ${responseCount}</h5>
                    <button class="btn btn-sm btn-danger btn-sm" onclick="removeResponse(this)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <input type="text" class="form-control" placeholder="Prix unitaire (MAD)" name="price_${responseCount}" step="0.01" min="0">
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control" placeholder="Délai de livraison (jours)" name="delivery_time_${responseCount}">
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <textarea class="form-control" placeholder="Commentaires sur la proposition" name="comments_${responseCount}" rows="3"></textarea>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <button class="btn btn-success btn-sm" onclick="saveResponse(${responseCount})">
                            <i class="fas fa-save me-2"></i>Enregistrer
                        </button>
                    </div>
                    <div class="col-md-6">
                        <button class="btn btn-secondary btn-sm" onclick="removeResponse(this)">
                            <i class="fas fa-times me-2"></i>Supprimer
                        </button>
                    </div>
                </div>
            `;
            
            container.appendChild(responseDiv);
        }
        
        function removeResponse(button) {
            button.closest('.response-card').remove();
        }
        
        function saveResponse(responseIndex) {
            // Implémenter la sauvegarde de la réponse
            alert('Fonction de sauvegarde à implémenter');
        }
        
        function duplicateDP() {
            if (confirm('Voulez-vous dupliquer cette demande de prix?')) {
                // Implémenter la duplication
                window.location.href = 'achat_dp_duplicate.php?id=<?php echo $dp_id; ?>';
            }
        }
        
        function cancelDP() {
            if (confirm('Êtes-vous sûr de vouloir annuler cette demande de prix?')) {
                // Implémenter l'annulation
                window.location.href = 'achat_dp_cancel.php?id=<?php echo $dp_id; ?>';
            }
        }
    </script>
</body>
</html>

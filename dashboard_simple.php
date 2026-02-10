<?php
// FUTURE AUTOMOTIVE - Simple Theme Example
// Clean and simple dashboard example

require_once 'config.php';
require_once 'includes/functions.php';

// Authentication check
if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = basename($_SERVER['PHP_SELF']);
    header('Location: login.php');
    exit();
}

$user = get_logged_in_user();
$page_title = "Tableau de Bord Simple";
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo APP_NAME; ?></title>
    
    <!-- Simple Clean Theme -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/simple-theme.css">
    
    <style>
        /* Simple Dashboard Styles */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--space-4);
            margin-bottom: var(--space-6);
        }
        
        .stat-card {
            background-color: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: var(--space-6);
            text-align: center;
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .stat-value {
            font-size: var(--font-size-2xl);
            font-weight: 700;
            margin-bottom: var(--space-2);
        }
        
        .stat-label {
            font-size: var(--font-size-sm);
            color: var(--text-light);
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: var(--space-4);
        }
        
        .quick-action {
            background-color: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: var(--space-4);
            text-align: center;
            text-decoration: none;
            color: var(--text-dark);
            transition: all 0.2s;
        }
        
        .quick-action:hover {
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
        }
        
        .quick-action i {
            display: block;
            font-size: 1.5rem;
            margin-bottom: var(--space-2);
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            padding: var(--space-4);
            border-bottom: 1px solid var(--border);
            transition: background-color 0.2s;
        }
        
        .activity-item:hover {
            background-color: #f8fafc;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: var(--space-4);
            font-size: 1.2rem;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-title {
            font-weight: 500;
            color: var(--text-dark);
            margin-bottom: var(--space-1);
        }
        
        .activity-time {
            font-size: var(--font-size-sm);
            color: var(--text-muted);
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: var(--space-3);
            }
            
            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <!-- Simple Header -->
    <?php include 'includes/header_simple.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-6">
            <div>
                <h1 class="mb-2">Tableau de Bord</h1>
                <p class="text-muted mb-0">Bienvenue, <?php echo htmlspecialchars($user['full_name']); ?></p>
            </div>
            <div class="d-flex gap-3">
                <button class="btn btn-outline-primary" onclick="window.location.href='site_audit.php'">
                    <i class="fas fa-clipboard-check me-2"></i>Audit
                </button>
                <button class="btn btn-outline-success" onclick="window.location.href='remove_unnecessary_files.php'">
                    <i class="fas fa-trash-alt me-2"></i>Nettoyer
                </button>
                <button class="btn btn-primary" onclick="window.location.href='admin_breakdowns_workshop.php'">
                    <i class="fas fa-plus me-2"></i>Nouveau
                </button>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value text-primary">24</div>
                <div class="stat-label">Bus Actifs</div>
            </div>
            <div class="stat-card">
                <div class="stat-value text-success">18</div>
                <div class="stat-label">Chauffeurs</div>
            </div>
            <div class="stat-card">
                <div class="stat-value text-warning">3</div>
                <div class="stat-label">En Maintenance</div>
            </div>
            <div class="stat-card">
                <div class="stat-value text-info">156</div>
                <div class="stat-label">Articles</div>
            </div>
            <div class="stat-card">
                <div class="stat-value text-danger">85%</div>
                <div class="stat-label">Performance Achats</div>
            </div>
        </div>

        <!-- Dashboard Grid -->
        <div class="row">
            <!-- Recent Activity -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">Activité Récente</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="activity-item">
                            <div class="activity-icon bg-success text-white">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">Maintenance terminée - Bus #12</div>
                                <div class="activity-time">Il y a 2 heures</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon bg-warning text-white">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">Stock faible - Filtres à huile</div>
                                <div class="activity-time">Il y a 4 heures</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon bg-info text-white">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">Nouvelle demande d'achat créée</div>
                                <div class="activity-time">Il y a 6 heures</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon bg-danger text-white">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">Rapport performance achats mis à jour</div>
                                <div class="activity-time">Il y a 8 heures</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon bg-primary text-white">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">Nouveau chauffeur ajouté</div>
                                <div class="activity-time">Hier</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">Actions Rapides</h3>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <a href="achat_da.php" class="quick-action">
                                <i class="fas fa-plus-circle"></i>
                                <span>Nouvelle DA</span>
                            </a>
                            <a href="purchase_performance.php" class="quick-action">
                                <i class="fas fa-chart-line"></i>
                                <span>Performance Achats</span>
                            </a>
                            <a href="admin_breakdowns_workshop.php" class="quick-action">
                                <i class="fas fa-tools"></i>
                                <span>Ordre Travail</span>
                            </a>
                            <a href="articles_stockables.php" class="quick-action">
                                <i class="fas fa-box"></i>
                                <span>Inventaire</span>
                            </a>
                            <a href="buses_complete.php" class="quick-action">
                                <i class="fas fa-bus"></i>
                                <span>Ajouter Bus</span>
                            </a>
                            <a href="fournisseurs.php" class="quick-action">
                                <i class="fas fa-users"></i>
                                <span>Fournisseurs</span>
                            </a>
                            <a href="archive_dashboard.php" class="quick-action">
                                <i class="fas fa-archive"></i>
                                <span>Archives</span>
                            </a>
                            <a href="site_cleanup.php" class="quick-action">
                                <i class="fas fa-broom"></i>
                                <span>Nettoyage</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Status -->
        <div class="card mt-4">
            <div class="card-header">
                <h3 class="mb-0">État du Système</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Base de données</span>
                            <span class="badge bg-success">Connectée</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Dernière sauvegarde</span>
                            <span class="text-sm">Il y a 2 heures</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Stock critique</span>
                            <span class="badge bg-warning">3 articles</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Performance Achats</span>
                            <span class="badge bg-success">85%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Simple dashboard interactions
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Simple theme loaded successfully');
        });
    </script>
</body>
</html>

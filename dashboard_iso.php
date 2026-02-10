<?php
// FUTURE AUTOMOTIVE - ISO 9001 Professional Page Template
// Corporate Design System Template

require_once 'config.php';
require_once 'includes/functions.php';

// Authentication check
if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = basename($_SERVER['PHP_SELF']);
    header('Location: login.php');
    exit();
}

$user = get_logged_in_user();
$page_title = "Tableau de Bord ISO 9001";
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo APP_NAME; ?></title>
    
    <!-- Professional ISO 9001 Theme -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- ISO 9001 Professional Design System -->
    <link rel="stylesheet" href="assets/css/iso-theme.css">
    <link rel="stylesheet" href="assets/css/iso-components.css">
    <link rel="stylesheet" href="assets/css/iso-bootstrap.css">
    
    <style>
        /* Page-specific styles */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: var(--space-6);
            margin-bottom: var(--space-8);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--space-4);
            margin-bottom: var(--space-6);
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: var(--space-6);
            border-radius: var(--radius-lg);
            text-align: center;
            transition: transform var(--transition);
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
        }
        
        .stat-value {
            font-size: var(--text-4xl);
            font-weight: var(--font-bold);
            margin-bottom: var(--space-2);
        }
        
        .stat-label {
            font-size: var(--text-sm);
            opacity: 0.9;
        }
        
        .recent-activity {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            gap: var(--space-4);
            padding: var(--space-4);
            border-bottom: 1px solid var(--border-primary);
            transition: background-color var(--transition-fast);
        }
        
        .activity-item:hover {
            background-color: var(--bg-secondary);
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
            font-size: var(--text-lg);
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-title {
            font-weight: var(--font-medium);
            color: var(--text-primary);
            margin-bottom: var(--space-1);
        }
        
        .activity-time {
            font-size: var(--text-xs);
            color: var(--text-muted);
        }
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: var(--space-4);
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: var(--space-3);
            }
            
            .stat-value {
                font-size: var(--text-3xl);
            }
        }
    </style>
</head>
<body>
    <!-- Professional ISO 9001 Header -->
    <?php include 'includes/header_iso.php'; ?>

    <!-- Professional Main Content -->
    <main class="main-content">
        <!-- Breadcrumb -->
        <nav class="breadcrumb">
            <a href="dashboard.php" class="breadcrumb-item">Accueil</a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-item active">Tableau de Bord</span>
        </nav>

        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-6">
            <div>
                <h1 class="mb-2">Tableau de Bord</h1>
                <p class="text-muted mb-0">Vue d'ensemble du système Future Automotive</p>
            </div>
            <div class="d-flex gap-3">
                <button class="btn btn-outline-primary">
                    <i class="fas fa-download me-2"></i>
                    Exporter
                </button>
                <button class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>
                    Nouveau
                </button>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value">24</div>
                <div class="stat-label">Bus Actifs</div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, var(--success) 0%, #1a4d2e 100%);">
                <div class="stat-value">18</div>
                <div class="stat-label">Chauffeurs</div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, var(--warning) 0%, #8b5a00 100%);">
                <div class="stat-value">3</div>
                <div class="stat-label">En Maintenance</div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, var(--info) 0%, #1e3a5f 100%);">
                <div class="stat-value">156</div>
                <div class="stat-label">Articles</div>
            </div>
        </div>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Activité Récente</h3>
                    <button class="btn btn-sm btn-outline-primary">Voir tout</button>
                </div>
                <div class="card-body p-0">
                    <div class="recent-activity">
                        <div class="activity-item">
                            <div class="activity-icon bg-success">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">Maintenance terminée - Bus #12</div>
                                <div class="activity-time">Il y a 2 heures</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon bg-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">Stock faible - Filtres à huile</div>
                                <div class="activity-time">Il y a 4 heures</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon bg-info">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">Nouvelle demande d'achat créée</div>
                                <div class="activity-time">Il y a 6 heures</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon bg-primary">
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
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Actions Rapides</h3>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-3">
                        <a href="achat_da.php" class="btn btn-outline-primary d-flex align-items-center justify-content-center">
                            <i class="fas fa-plus-circle me-2"></i>
                            Nouvelle Demande d'Achat
                        </a>
                        <a href="work_orders.php" class="btn btn-outline-success d-flex align-items-center justify-content-center">
                            <i class="fas fa-tools me-2"></i>
                            Créer Ordre de Travail
                        </a>
                        <a href="articles_stockables.php" class="btn btn-outline-info d-flex align-items-center justify-content-center">
                            <i class="fas fa-box me-2"></i>
                            Gérer Inventaire
                        </a>
                        <a href="buses_complete.php" class="btn btn-outline-warning d-flex align-items-center justify-content-center">
                            <i class="fas fa-bus me-2"></i>
                            Ajouter Bus
                        </a>
                    </div>
                </div>
            </div>

            <!-- System Status -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">État du Système</h3>
                </div>
                <div class="card-body">
                    <div class="space-y-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-secondary">Base de données</span>
                            <span class="status-indicator status-success">Connectée</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-secondary">Dernière sauvegarde</span>
                            <span class="text-sm">Il y a 2 heures</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-secondary">Stock critique</span>
                            <span class="status-indicator status-warning">3 articles</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-secondary">Maintenance en cours</span>
                            <span class="status-indicator status-info">3 bus</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-secondary">Notifications</span>
                            <span class="status-indicator status-danger">5 non lues</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Overview -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Aperçu Performance</h3>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-primary">Semaine</button>
                    <button class="btn btn-sm btn-primary">Mois</button>
                    <button class="btn btn-sm btn-outline-primary">Année</button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-primary">92%</div>
                            <div class="text-sm text-muted">Taux de service</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-success">4.8</div>
                            <div class="text-sm text-muted">Satisfaction client</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-warning">24h</div>
                            <div class="text-sm text-muted">Temps moyen réparation</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-info">98%</div>
                            <div class="text-sm text-muted">Disponibilité flotte</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Professional Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Professional dashboard interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Animate statistics on load
            const statValues = document.querySelectorAll('.stat-value');
            statValues.forEach(stat => {
                const finalValue = parseInt(stat.textContent);
                let currentValue = 0;
                const increment = finalValue / 20;
                
                const timer = setInterval(() => {
                    currentValue += increment;
                    if (currentValue >= finalValue) {
                        currentValue = finalValue;
                        clearInterval(timer);
                    }
                    stat.textContent = Math.floor(currentValue);
                }, 100);
            });
            
            // Auto-refresh recent activity
            setInterval(() => {
                // Simulate activity refresh
                console.log('Refreshing activity...');
            }, 30000);
        });
    </script>
</body>
</html>

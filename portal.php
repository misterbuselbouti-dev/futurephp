<?php
// FUTURE AUTOMOTIVE - Enhanced System Portal
// Main portal for accessing all enhanced features
require_once 'config.php';
require_once 'includes/functions.php';

if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = 'portal.php';
    header('Location: login.php');
    exit();
}

$user = get_logged_in_user();
$full_name = $user['full_name'] ?? $_SESSION['full_name'] ?? 'Administrateur';
$role = $_SESSION['role'] ?? 'admin';
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #ffffff;
            padding-top: 70px;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s;
        }
        .portal-header {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            border: 1px solid #e2e8f0;
        }
        .portal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .portal-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            text-align: center;
            transition: all 0.2s ease;
            text-decoration: none;
            color: inherit;
        }
        .portal-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-color: #1e3a8a;
        }
        .portal-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #1e3a8a;
        }
        .portal-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #1e293b;
        }
        .portal-description {
            color: #64748b;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }
        .portal-badge {
            background: #f1f5f9;
            color: #64748b;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #1e293b;
        }
        .section-description {
            color: #64748b;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <!-- Header -->
            <div class="portal-header">
                <h1 class="mb-2">🚀 Portal Système</h1>
                <p class="text-muted mb-0">Bienvenue, <?php echo htmlspecialchars($full_name); ?> - Accès rapide à toutes les fonctionnalités</p>
            </div>

            <!-- Enhanced Breakdown Management -->
            <div class="mb-4">
                <h2 class="section-title">
                    <i class="fas fa-screwdriver-wrench me-2"></i>
                    Gestion des Pannes (Enhanced)
                </h2>
                <p class="section-description">Système complet de gestion des pannes avec suivi du temps, inventaire et audit</p>
                <div class="portal-grid">
                    <a href="admin/admin_breakdowns.php" class="portal-card">
                        <div class="portal-icon">
                            <i class="fas fa-list"></i>
                        </div>
                        <div class="portal-title">Liste des Pannes</div>
                        <div class="portal-description">Vue d'ensemble avec filtres avancés et recherche</div>
                        <span class="portal-badge">Enhanced</span>
                    </a>
                    <a href="admin/admin_breakdown_view.php" class="portal-card">
                        <div class="portal-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="portal-title">Détails Panne</div>
                        <div class="portal-description">Gestion complète avec temps, pièces et audit</div>
                        <span class="portal-badge">Enhanced</span>
                    </a>
                </div>
            </div>

            <!-- Core Management -->
            <div class="mb-4">
                <h2 class="section-title">
                    <i class="fas fa-cogs me-2"></i>
                    Gestion Principale
                </h2>
                <p class="section-description">Fonctionnalités de base du système</p>
                <div class="portal-grid">
                    <a href="management/buses.php" class="portal-card">
                        <div class="portal-icon">
                            <i class="fas fa-bus"></i>
                        </div>
                        <div class="portal-title">Bus</div>
                        <div class="portal-description">Gestion de la flotte de bus</div>
                    </a>
                    <a href="management/drivers.php" class="portal-card">
                        <div class="portal-icon">
                            <i class="fas fa-id-card"></i>
                        </div>
                        <div class="portal-title">Chauffeurs</div>
                        <div class="portal-description">Gestion des chauffeurs</div>
                    </a>
                    <a href="management/inventory.php" class="portal-card">
                        <div class="portal-icon">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <div class="portal-title">Inventaire</div>
                        <div class="portal-description">Gestion des stocks et pièces</div>
                    </a>
                </div>
            </div>

            <!-- Purchase Management -->
            <div class="mb-4">
                <h2 class="section-title">
                    <i class="fas fa-shopping-cart me-2"></i>
                    Gestion des Achats
                </h2>
                <p class="section-description">Système complet de gestion des achats</p>
                <div class="portal-grid">
                    <a href="purchase/achat_da.php" class="portal-card">
                        <div class="portal-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="portal-title">Demandes d'Achat</div>
                        <div class="portal-description">Créer et gérer les demandes</div>
                    </a>
                    <a href="purchase/achat_dp.php" class="portal-card">
                        <div class="portal-icon">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                        <div class="portal-title">Demandes de Prix</div>
                        <div class="portal-description">Demander des prix aux fournisseurs</div>
                    </a>
                    <a href="purchase/achat_bc.php" class="portal-card">
                        <div class="portal-icon">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <div class="portal-title">Bons de Commande</div>
                        <div class="portal-description">Gérer les commandes</div>
                    </a>
                    <a href="purchase/achat_be.php" class="portal-card">
                        <div class="portal-icon">
                            <i class="fas fa-truck-loading"></i>
                        </div>
                        <div class="portal-title">Bons d'Entrée</div>
                        <div class="portal-description">Réception des marchandises</div>
                    </a>
                </div>
            </div>

            <!-- Reports & Analytics -->
            <div class="mb-4">
                <h2 class="section-title">
                    <i class="fas fa-chart-bar me-2"></i>
                    Rapports & Analyse
                </h2>
                <p class="section-description">Rapports et analyses du système</p>
                <div class="portal-grid">
                    <a href="reports/reports.php" class="portal-card">
                        <div class="portal-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="portal-title">Rapports</div>
                        <div class="portal-description">Rapports détaillés du système</div>
                    </a>
                    <a href="admin/admin_breakdowns.php?export=true" class="portal-card">
                        <div class="portal-icon">
                            <i class="fas fa-download"></i>
                        </div>
                        <div class="portal-title">Export</div>
                        <div class="portal-description">Exporter les données</div>
                    </a>
                </div>
            </div>

            <!-- User Portals -->
            <div class="mb-4">
                <h2 class="section-title">
                    <i class="fas fa-users me-2"></i>
                    Portails Utilisateurs
                </h2>
                <p class="section-description">Accès pour les différents types d'utilisateurs</p>
                <div class="portal-grid">
                    <a href="technician/technician_breakdowns.php" class="portal-card">
                        <div class="portal-icon">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="portal-title">Technicien</div>
                        <div class="portal-description">Portail des techniciens</div>
                    </a>
                    <a href="driver/driver_portal.php" class="portal-card">
                        <div class="portal-icon">
                            <i class="fas fa-id-card"></i>
                        </div>
                        <div class="portal-title">Chauffeur</div>
                        <div class="portal-description">Portail des chauffeurs</div>
                    </a>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="mb-4">
                <h2 class="section-title">
                    <i class="fas fa-bolt me-2"></i>
                    Actions Rapides
                </h2>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="admin/admin_breakdowns.php?action=new" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Nouvelle Panne
                    </a>
                    <a href="management/buses.php?action=new" class="btn btn-success">
                        <i class="fas fa-plus me-2"></i>Nouveau Bus
                    </a>
                    <a href="management/drivers.php?action=new" class="btn btn-info">
                        <i class="fas fa-user-plus me-2"></i>Nouveau Chauffeur
                    </a>
                    <a href="purchase/achat_da.php?action=new" class="btn btn-warning">
                        <i class="fas fa-shopping-cart me-2"></i>Nouvelle Demande
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// FUTURE AUTOMOTIVE - Admin Dashboard
// Page d'administration propre et épurée
require_once 'config.php';

require_login();

$user = get_logged_in_user();
$full_name = $user['full_name'] ?? 'Administrateur';
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="mb-2">
                        <i class="fas fa-user-shield me-2"></i>
                        Administration
                    </h1>
                    <p class="text-muted mb-0">Bienvenue, <?php echo htmlspecialchars($full_name); ?></p>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 col-lg-4 mb-3">
                    <a href="dashboard.php" class="card text-decoration-none text-dark h-100">
                        <div class="card-body">
                            <i class="fas fa-tachometer-alt fa-2x text-primary mb-2"></i>
                            <h5 class="card-title">Tableau de bord</h5>
                            <p class="card-text text-muted small">Vue d'ensemble du système</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4 mb-3">
                    <a href="drivers.php" class="card text-decoration-none text-dark h-100">
                        <div class="card-body">
                            <i class="fas fa-id-card fa-2x text-primary mb-2"></i>
                            <h5 class="card-title">Chauffeurs</h5>
                            <p class="card-text text-muted small">Gestion des conducteurs</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4 mb-3">
                    <a href="work_orders.php" class="card text-decoration-none text-dark h-100">
                        <div class="card-body">
                            <i class="fas fa-wrench fa-2x text-primary mb-2"></i>
                            <h5 class="card-title">Ordres de travail</h5>
                            <p class="card-text text-muted small">Gestion des interventions</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4 mb-3">
                    <a href="inventory.php" class="card text-decoration-none text-dark h-100">
                        <div class="card-body">
                            <i class="fas fa-boxes-stacked fa-2x text-primary mb-2"></i>
                            <h5 class="card-title">Stock</h5>
                            <p class="card-text text-muted small">Gestion du stock</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4 mb-3">
                    <a href="invoices.php" class="card text-decoration-none text-dark h-100">
                        <div class="card-body">
                            <i class="fas fa-file-invoice-dollar fa-2x text-primary mb-2"></i>
                            <h5 class="card-title">Factures</h5>
                            <p class="card-text text-muted small">Gestion des factures</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4 mb-3">
                    <a href="settings.php" class="card text-decoration-none text-dark h-100">
                        <div class="card-body">
                            <i class="fas fa-cog fa-2x text-primary mb-2"></i>
                            <h5 class="card-title">Paramètres</h5>
                            <p class="card-text text-muted small">Configuration du système</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>

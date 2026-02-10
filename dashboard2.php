<?php
// FUTURE AUTOMOTIVE - Dashboard
// Tableau de bord du système ERP - Bus Management System
require_once 'config.php';
require_once 'includes/functions.php';

if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = 'dashboard.php';
    header('Location: login.php');
    exit();
}

$user = get_logged_in_user();
$full_name = $user['full_name'] ?? $_SESSION['full_name'] ?? 'Administrateur';
$role = $_SESSION['role'] ?? 'admin';

// Statistiques for Bus Management System
$db_connected = false;
$stats = [
    'buses' => 0,
    'drivers' => 0,
    'active_buses' => 0,
    'maintenance_buses' => 0,
    'articles' => 0,
    'low_stock' => 0,
    'breakdowns' => 0,
    'work_orders' => 0,
    'notifications' => 0,
    'users' => 0,
    'total_da' => 0,
    'total_bc' => 0,
    'total_be' => 0
];

$recent_activities = [];
$currency = getCurrencySymbol();

try {
    $database = new Database();
    $conn = $database->connect();
    $db_connected = true;

    // Bus statistics
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM buses");
    $stmt->execute();
    $stats['buses'] = (int) $stmt->fetch()['count'];

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM buses WHERE status = 'active'");
    $stmt->execute();
    $stats['active_buses'] = (int) $stmt->fetch()['count'];

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM buses WHERE status = 'maintenance'");
    $stmt->execute();
    $stats['maintenance_buses'] = (int) $stmt->fetch()['count'];

    // Drivers statistics
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM drivers WHERE is_active = 1");
    $stmt->execute();
    $stats['drivers'] = (int) $stmt->fetch()['count'];

    // Articles statistics
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM articles_catalogue");
    $stmt->execute();
    $stats['articles'] = (int) $stmt->fetch()['count'];

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM articles_catalogue WHERE stock_actuel <= stock_minimal");
    $stmt->execute();
    $stats['low_stock'] = (int) $stmt->fetch()['count'];

    // Breakdowns and work orders
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM breakdown_reports");
    $stmt->execute();
    $stats['breakdowns'] = (int) $stmt->fetch()['count'];

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM work_orders");
    $stmt->execute();
    $stats['work_orders'] = (int) $stmt->fetch()['count'];

    // Notifications
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE is_read = 0");
    $stmt->execute();
    $stats['notifications'] = (int) $stmt->fetch()['count'];

    // Users
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE is_active = 1");
    $stmt->execute();
    $stats['users'] = (int) $stmt->fetch()['count'];
    // Recent activities for Bus Management
    try {
        $stmt = $conn->prepare("
            SELECT br.id, br.breakdown_date, br.description, br.status, 
                   d.nom, d.prenom, b.bus_number
            FROM breakdown_reports br
            LEFT JOIN drivers d ON br.driver_id = d.id
            LEFT JOIN buses b ON br.bus_id = b.id
            ORDER BY br.breakdown_date DESC LIMIT 5
        ");
        $stmt->execute();
        $recent_activities = $stmt->fetchAll();
    } catch (Exception $e) {
        $recent_activities = [];
    }

    // Achat module statistics (if available)
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM demandes_achat");
        $stmt->execute();
        $stats['total_da'] = (int) $stmt->fetch()['count'];

        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM bons_commande");
        $stmt->execute();
        $stats['total_bc'] = (int) $stmt->fetch()['count'];

        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM bons_entree");
        $stmt->execute();
        $stats['total_be'] = (int) $stmt->fetch()['count'];
    } catch (Exception $e) {
        // Achat module not available
    }
    } catch (Exception $e) {}

    try {
        $database_achat = new DatabaseAchat();
        $conn_achat = $database_achat->connect();
        $stmt = $conn_achat->prepare("SELECT COUNT(*) as count FROM demandes_achat");
        $stmt->execute();
        $stats['total_da'] = (int) $stmt->fetch()['count'];
        $stmt = $conn_achat->prepare("SELECT COUNT(*) as count FROM bons_commande");
        $stmt->execute();
        $stats['total_bc'] = (int) $stmt->fetch()['count'];
        $stmt = $conn_achat->prepare("SELECT COUNT(*) as count FROM bons_entree");
        $stmt->execute();
        $stats['total_be'] = (int) $stmt->fetch()['count'];
    } catch (Exception $e) {}
} catch (Exception $e) {}

function breakdownStatusBadge($status) {
    $map = [
        'reported' => ['Signalé', 'warning'], 
        'assigned' => ['Assigné', 'info'], 
        'in_progress' => ['En cours', 'primary'], 
        'resolved' => ['Résolu', 'success'], 
        'closed' => ['Fermé', 'secondary']
    ];
    $t = $map[$status] ?? [$status, 'secondary'];
    return '<span class="badge bg-' . $t[1] . '">' . $t[0] . '</span>';
}
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="dashboard-main">
        <div class="dashboard-container">

            <!-- En-tête principal -->
            <header class="dashboard-header">
                <div class="dashboard-header-left">
                    <h1>Tableau de bord</h1>
                    <p class="dashboard-welcome">Bienvenue, <?php echo htmlspecialchars($full_name); ?></p>
                </div>
                <div class="dashboard-header-right">
                    <div class="dashboard-status <?php echo $db_connected ? 'status-ok' : 'status-error'; ?>">
                        <i class="fas fa-<?php echo $db_connected ? 'circle-check' : 'circle-exclamation'; ?>"></i>
                        <span><?php echo $db_connected ? 'Système opérationnel' : 'Base de données déconnectée'; ?></span>
                    </div>
                    <div class="dashboard-date">
                        <i class="fas fa-calendar-day"></i>
                        <span><?php echo date('l d F Y'); ?></span>
                    </div>
                </div>
            </header>

            <!-- Section: Aujourd'hui -->
            <section class="dashboard-section">
                <h2 class="section-title"><i class="fas fa-sun"></i> Aujourd'hui</h2>
                <div class="today-cards">
                    <div class="today-card">
                        <div class="today-card-icon bg-warning">
                            <i class="fas fa-bus"></i>
                        </div>
                        <div class="today-card-content">
                            <span class="today-card-value"><?php echo $stats['maintenance_buses']; ?></span>
                            <span class="today-card-label">Bus en maintenance</span>
                        </div>
                        <a href="buses_complete.php?status=maintenance" class="today-card-link">Voir <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="today-card">
                        <div class="today-card-icon bg-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="today-card-content">
                            <span class="today-card-value"><?php echo $stats['low_stock']; ?></span>
                            <span class="today-card-label">Articles en rupture</span>
                        </div>
                        <a href="articles_stockables.php?low_stock=1" class="today-card-link">Voir <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="today-card">
                        <div class="today-card-icon bg-info">
                            <i class="fas fa-bell"></i>
                        </div>
                        <div class="today-card-content">
                            <span class="today-card-value"><?php echo $stats['notifications']; ?></span>
                            <span class="today-card-label">Notifications</span>
                        </div>
                        <a href="notifications.php" class="today-card-link">Voir <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </section>

            <!-- Statistiques principales -->
            <section class="dashboard-section">
                <h2 class="section-title"><i class="fas fa-chart-pie"></i> Statistiques</h2>
                <div class="stats-row">
                    <div class="stat-box stat-buses">
                        <div class="stat-box-icon"><i class="fas fa-bus"></i></div>
                        <div class="stat-box-body">
                            <div class="stat-box-value"><?php echo $stats['buses']; ?></div>
                            <div class="stat-box-label">Total Bus</div>
                        </div>
                        <a href="buses_complete.php" class="stat-box-link">Voir tout</a>
                    </div>
                    <div class="stat-box stat-drivers">
                        <div class="stat-box-icon"><i class="fas fa-id-card"></i></div>
                        <div class="stat-box-body">
                            <div class="stat-box-value"><?php echo $stats['drivers']; ?></div>
                            <div class="stat-box-label">Chauffeurs</div>
                        </div>
                        <a href="drivers.php" class="stat-box-link">Voir tout</a>
                    </div>
                    <div class="stat-box stat-articles">
                        <div class="stat-box-icon"><i class="fas fa-boxes"></i></div>
                        <div class="stat-box-body">
                            <div class="stat-box-value"><?php echo $stats['articles']; ?></div>
                            <div class="stat-box-label">Articles</div>
                        </div>
                        <a href="articles_stockables.php" class="stat-box-link">Voir tout</a>
                    </div>
                    <div class="stat-box stat-breakdowns">
                        <div class="stat-box-icon"><i class="fas fa-wrench"></i></div>
                        <div class="stat-box-body">
                            <div class="stat-box-value"><?php echo $stats['breakdowns']; ?></div>
                            <div class="stat-box-label">Pannes</div>
                        </div>
                        <a href="admin_breakdowns.php" class="stat-box-link">Voir tout</a>
                    </div>
                    <div class="stat-box stat-work-orders">
                        <div class="stat-box-icon"><i class="fas fa-clipboard-list"></i></div>
                        <div class="stat-box-body">
                            <div class="stat-box-value"><?php echo $stats['work_orders']; ?></div>
                            <div class="stat-box-label">Ordres de travail</div>
                        </div>
                        <a href="work_orders.php" class="stat-box-link">Voir tout</a>
                    </div>
                    <div class="stat-box stat-users">
                        <div class="stat-box-icon"><i class="fas fa-users-cog"></i></div>
                        <div class="stat-box-body">
                            <div class="stat-box-value"><?php echo $stats['users']; ?></div>
                            <div class="stat-box-label">Utilisateurs</div>
                        </div>
                        <a href="users_management.php" class="stat-box-link">Voir tout</a>
                    </div>
                </div>
            </section>
                            <!-- Actions rapides + Activités récentes -->
            <div class="dashboard-grid-2">
                <section class="dashboard-section">
                    <h2 class="section-title"><i class="fas fa-bolt"></i> Actions rapides</h2>
                    <div class="quick-actions">
                        <a href="buses_complete.php" class="quick-action">
                            <i class="fas fa-plus-circle"></i>
                            <span>Ajouter un bus</span>
                        </a>
                        <a href="drivers.php" class="quick-action">
                            <i class="fas fa-user-plus"></i>
                            <span>Ajouter un chauffeur</span>
                        </a>
                        <a href="admin_breakdowns.php" class="quick-action">
                            <i class="fas fa-wrench"></i>
                            <span>Nouvelle panne</span>
                        </a>
                        <a href="articles_stockables.php" class="quick-action">
                            <i class="fas fa-box"></i>
                            <span>Gérer le stock</span>
                        </a>
                        <a href="notifications.php" class="quick-action">
                            <i class="fas fa-bell"></i>
                            <span>Notifications</span>
                        </a>
                        <a href="export_data.php" class="quick-action">
                            <i class="fas fa-download"></i>
                            <span>Exporter</span>
                        </a>
                    </div>
                </section>

                <section class="dashboard-section">
                    <h2 class="section-title"><i class="fas fa-clock"></i> Activités récentes</h2>
                    <div class="recent-activities">
                        <?php if (empty($recent_activities)): ?>
                            <div class="no-activities">
                                <i class="fas fa-info-circle"></i>
                                <span>Aucune activité récente</span>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_activities as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title">
                                            Panne - <?php echo htmlspecialchars($activity['bus_number'] ?? 'N/A'); ?>
                                        </div>
                                        <div class="activity-description">
                                            <?php echo htmlspecialchars(substr($activity['description'], 0, 50)) . '...'; ?>
                                        </div>
                                        <div class="activity-meta">
                                            <span class="activity-driver">
                                                <?php echo htmlspecialchars($activity['nom'] ?? '') . ' ' . htmlspecialchars($activity['prenom'] ?? ''); ?>
                                            </span>
                                            <span class="activity-date">
                                                <?php echo date('d/m H:i', strtotime($activity['breakdown_date'])); ?>
                                            </span>
                                            <?php echo breakdownStatusBadge($activity['status']); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
                    <div class="quick-actions">
                        <a href="work_orders.php?action=add" class="quick-action">
                            <i class="fas fa-plus-circle"></i>
                            <span>Ordre de travail</span>
                        </a>
                        <a href="inventory.php" class="quick-action">
                            <i class="fas fa-boxes-stacked"></i>
                            <span>Gérer le stock</span>
                        </a>
                        <a href="invoices.php" class="quick-action">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <span>Factures</span>
                        </a>
                        <a href="reports.php" class="quick-action">
                            <i class="fas fa-chart-line"></i>
                            <span>Rapports</span>
                        </a>
                    </div>
                </section>

                <section class="dashboard-section">
                    <h2 class="section-title">
                        <i class="fas fa-clock-rotate-left"></i> Dernières interventions
                        <a href="work_orders.php" class="section-link">Tout voir</a>
                    </h2>
                    <div class="recent-list">
                        <?php if (empty($recent_work_orders)): ?>
                            <p class="recent-empty">Aucun ordre de travail récent</p>
                        <?php else: ?>
                            <?php foreach ($recent_work_orders as $wo): ?>
                                <a href="work_orders.php?id=<?php echo $wo['id']; ?>" class="recent-item">
                                    <div class="recent-item-info">
                                        <span class="recent-item-id">OT #<?php echo $wo['id']; ?></span>
                                        <span class="recent-item-detail"><?php echo htmlspecialchars($wo['customer_name'] ?? '-'); ?> • <?php echo htmlspecialchars($wo['plate_number'] ?? '-'); ?></span>
                                    </div>
                                    <div class="recent-item-meta">
                                        <?php echo workOrderStatusBadge($wo['status']); ?>
                                        <span class="recent-item-date"><?php echo date('d/m', strtotime($wo['created_at'])); ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <!-- Module Achat -->
            <?php if ($stats['total_da'] > 0 || $stats['total_bc'] > 0 || $stats['total_be'] > 0): ?>
            <section class="dashboard-section">
                <h2 class="section-title"><i class="fas fa-shopping-cart"></i> Module Achat</h2>
                <div class="stats-row">
                    <div class="stat-box stat-da">
                        <div class="stat-box-icon"><i class="fas fa-file-alt"></i></div>
                        <div class="stat-box-body">
                            <div class="stat-box-value"><?php echo $stats['total_da']; ?></div>
                            <div class="stat-box-label">Demandes d'Achat</div>
                        </div>
                        <a href="achat_da.php" class="stat-box-link">Voir tout</a>
                    </div>
                    <div class="stat-box stat-bc">
                        <div class="stat-box-icon"><i class="fas fa-file-invoice"></i></div>
                        <div class="stat-box-body">
                            <div class="stat-box-value"><?php echo $stats['total_bc']; ?></div>
                            <div class="stat-box-label">Bons de Commande</div>
                        </div>
                        <a href="achat_bc.php" class="stat-box-link">Voir tout</a>
                    <div class="achat-stat">
                        <span class="achat-stat-value"><?php echo number_format($stats['total_bc']); ?></span>
                        <span class="achat-stat-label">Bons de commande</span>
                        <a href="achat_bc.php" class="achat-stat-link">Voir BC</a>
                    </div>
                    <div class="achat-stat">
                        <span class="achat-stat-value"><?php echo number_format($stats['total_be']); ?></span>
                        <span class="achat-stat-label">Bons d'entrée</span>
                        <a href="achat_be.php" class="achat-stat-link">Voir BE</a>
                    </div>
                    <div class="achat-action">
                        <a href="achat_da.php" class="btn-achat">
                            <i class="fas fa-file-circle-plus"></i>
                            Nouvelle demande d'achat
                        </a>
                        <a href="achat_dp.php" class="btn-achat btn-achat-secondary">
                            <i class="fas fa-file-invoice"></i>
                            Demandes de prix
                        </a>
                    </div>
                </div>
            </section>

        </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggle = document.querySelector('#sidebarToggle');
            if (toggle) toggle.addEventListener('click', () => document.body.classList.toggle('sidebar-collapsed'));
        });
    </script>
</body>
</html>

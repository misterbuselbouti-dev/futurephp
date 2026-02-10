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
    <title>Tableau de Bord - <?php echo APP_NAME; ?> - Simple Clean Theme</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/simple-theme.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #ffffff;
            padding-top: 70px;
            line-height: 1.5;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s;
        }
        .sidebar-collapsed .main-content {
            margin-left: 70px;
        }
        .dashboard-header {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            border: 1px solid #e2e8f0;
        }
        .dashboard-section {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            border: 1px solid #e2e8f0;
        }
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #1e293b;
        }
        .today-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        .today-card {
            background: transparent;
            color: #1e293b;
            padding: 1.5rem;
            border-radius: 8px;
            position: relative;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .today-card-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        .today-card-value {
            font-size: 2rem;
            font-weight: 700;
            display: block;
        }
        .today-card-label {
            font-size: 0.875rem;
            opacity: 0.9;
        }
        .today-card-link {
            color: white;
            text-decoration: none;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            margin-top: 1rem;
        }
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        .stat-box {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .stat-box-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #4299e1;
        }
        .stat-box-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d3748;
        }
        .stat-box-label {
            font-size: 0.875rem;
            color: #718096;
        }
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }
        .quick-action {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
            text-decoration: none;
            color: #4a5568;
            text-align: center;
            transition: all 0.2s;
        }
        .quick-action:hover {
            background: #4299e1;
            color: white;
            transform: translateY(-2px);
        }
        .quick-action i {
            display: block;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        .activity-item {
            display: flex;
            align-items: start;
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .activity-icon {
            width: 40px;
            height: 40px;
            background: #fed7d7;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: #c53030;
        }
        .activity-content {
            flex: 1;
        }
        .activity-title {
            font-weight: 600;
            color: #2d3748;
        }
        .activity-description {
            font-size: 0.875rem;
            color: #718096;
            margin: 0.25rem 0;
        }
        .activity-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.75rem;
            color: #a0aec0;
        }
        .no-activities {
            text-align: center;
            padding: 2rem;
            color: #a0aec0;
        }
        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header_simple.php'; ?>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">

            <!-- En-tête principal -->
            <header class="dashboard-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h1>Tableau de bord</h1>
                        <p class="text-muted mb-0">Bienvenue, <?php echo htmlspecialchars($full_name); ?></p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <div class="d-inline-flex align-items-center gap-3">
                            <div class="badge bg-<?php echo $db_connected ? 'success' : 'danger'; ?>">
                                <i class="fas fa-<?php echo $db_connected ? 'circle-check' : 'circle-exclamation'; ?>"></i>
                                <?php echo $db_connected ? 'Système opérationnel' : 'Base de données déconnectée'; ?>
                            </div>
                            <div class="text-muted">
                                <i class="fas fa-calendar-day"></i>
                                <?php echo date('d/m/Y'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Section: Aujourd'hui -->
            <section class="dashboard-section">
                <h2 class="section-title"><i class="fas fa-sun"></i> Aujourd'hui</h2>
                <div class="today-cards">
                    <div class="today-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <div class="today-card-icon">
                            <i class="fas fa-bus"></i>
                        </div>
                        <div class="today-card-content">
                            <span class="today-card-value"><?php echo $stats['maintenance_buses']; ?></span>
                            <span class="today-card-label">Bus en maintenance</span>
                        </div>
                        <a href="management/buses.php?status=maintenance" class="today-card-link">Voir <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="today-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <div class="today-card-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="today-card-content">
                            <span class="today-card-value"><?php echo $stats['low_stock']; ?></span>
                            <span class="today-card-label">Articles en rupture</span>
                        </div>
                        <a href="management/inventory.php?low_stock=1" class="today-card-link">Voir <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="today-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <div class="today-card-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <div class="today-card-content">
                            <span class="today-card-value"><?php echo $stats['notifications']; ?></span>
                            <span class="today-card-label">Notifications</span>
                        </div>
                        <a href="admin/admin_breakdowns.php?filter_status=nouveau" class="today-card-link">Voir <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </section>

            <!-- Statistiques principales -->
            <section class="dashboard-section">
                <h2 class="section-title"><i class="fas fa-chart-pie"></i> Statistiques</h2>
                <div class="stats-row">
                    <div class="stat-box">
                        <div class="stat-box-icon" style="color: #e53e3e;">
                            <i class="fas fa-bus"></i>
                        </div>
                        <div class="stat-box-value"><?php echo $stats['buses']; ?></div>
                        <div class="stat-box-label">Total Bus</div>
                        <a href="management/buses.php" class="btn btn-sm btn-outline-primary mt-2">Voir tout</a>
                    </div>
                    <div class="stat-box">
                        <div class="stat-box-icon" style="color: #38a169;">
                            <i class="fas fa-id-card"></i>
                        </div>
                        <div class="stat-box-value"><?php echo $stats['drivers']; ?></div>
                        <div class="stat-box-label">Chauffeurs</div>
                        <a href="management/drivers.php" class="btn btn-sm btn-outline-primary mt-2">Voir tout</a>
                    </div>
                    <div class="stat-box">
                        <div class="stat-box-icon" style="color: #3182ce;">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <div class="stat-box-value"><?php echo $stats['articles']; ?></div>
                        <div class="stat-box-label">Articles</div>
                        <a href="management/inventory.php" class="btn btn-sm btn-outline-primary mt-2">Voir tout</a>
                    </div>
                    <div class="stat-box">
                        <div class="stat-box-icon" style="color: #d69e2e;">
                            <i class="fas fa-wrench"></i>
                        </div>
                        <div class="stat-box-value"><?php echo $stats['breakdowns']; ?></div>
                        <div class="stat-box-label">Pannes</div>
                        <a href="admin/admin_breakdowns.php" class="btn btn-sm btn-outline-primary mt-2">Voir tout</a>
                    </div>
                    <div class="stat-box">
                        <div class="stat-box-icon" style="color: #805ad5;">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="stat-box-value"><?php echo $stats['work_orders']; ?></div>
                        <div class="stat-box-label">Ordres de travail</div>
                        <a href="work_orders.php" class="btn btn-sm btn-outline-primary mt-2">Voir tout</a>
                    </div>
                    <div class="stat-box">
                        <div class="stat-box-icon" style="color: #dd6b20;">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        <div class="stat-box-value"><?php echo $stats['users']; ?></div>
                        <div class="stat-box-label">Utilisateurs</div>
                        <a href="users_management.php" class="btn btn-sm btn-outline-primary mt-2">Voir tout</a>
                    </div>
                </div>
            </section>

            <!-- Actions rapides + Activités récentes -->
            <div class="row">
                <div class="col-lg-6">
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
                            <a href="admin/admin_breakdowns.php" class="quick-action">
                            <i class="fas fa-wrench"></i>
                            <span>Nouvelle panne</span>
                        </a>
                        <a href="management/inventory.php" class="quick-action">
                            <i class="fas fa-box"></i>
                            <span>Gérer le stock</span>
                        </a>
                        <a href="admin/admin_breakdowns.php" class="quick-action">
                            <i class="fas fa-screwdriver-wrench"></i>
                            <span>Gestion pannes</span>
                        </a>
                        <a href="notifications.php" class="quick-action">
                            <i class="fas fa-bell"></i>
                            <span>Notifications</span>
                        </a>
                        <a href="reports/reports.php" class="quick-action">
                            <i class="fas fa-download"></i>
                            <span>Rapports</span>
                        </a>
                        <a href="purchase/achat_da.php" class="quick-action">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Achats</span>
                        </a>
                        </div>
                    </section>
                </div>
                <div class="col-lg-6">
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
            </div>

            <!-- Module Achat -->
            <?php if ($stats['total_da'] > 0 || $stats['total_bc'] > 0 || $stats['total_be'] > 0): ?>
            <section class="dashboard-section">
                <h2 class="section-title"><i class="fas fa-shopping-cart"></i> Module Achat</h2>
                <div class="stats-row">
                    <div class="stat-box">
                        <div class="stat-box-icon" style="color: #9f7aea;">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="stat-box-value"><?php echo $stats['total_da']; ?></div>
                        <div class="stat-box-label">Demandes d'Achat</div>
                        <a href="achat_da.php" class="btn btn-sm btn-outline-primary mt-2">Voir tout</a>
                    </div>
                    <div class="stat-box">
                        <div class="stat-box-icon" style="color: #4299e1;">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                        <div class="stat-box-value"><?php echo $stats['total_bc']; ?></div>
                        <div class="stat-box-label">Bons de Commande</div>
                        <a href="achat_bc.php" class="btn btn-sm btn-outline-primary mt-2">Voir tout</a>
                    </div>
                    <div class="stat-box">
                        <div class="stat-box-icon" style="color: #48bb78;">
                            <i class="fas fa-truck-loading"></i>
                        </div>
                        <div class="stat-box-value"><?php echo $stats['total_be']; ?></div>
                        <div class="stat-box-label">Bons d'Entrée</div>
                        <a href="achat_be.php" class="btn btn-sm btn-outline-primary mt-2">Voir tout</a>
                    </div>
                </div>
            </section>
            <?php endif; ?>

        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>

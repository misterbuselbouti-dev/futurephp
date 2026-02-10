<?php
// FUTURE AUTOMOTIVE - Professional Dashboard
// Clean, intelligent design with serious colors
require_once 'config.php';
require_once 'includes/functions.php';

if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = 'dashboard_professional.php';
    header('Location: login.php');
    exit();
}

$user = get_logged_in_user();
$full_name = $user['full_name'] ?? $_SESSION['full_name'] ?? 'Administrateur';
$role = $_SESSION['role'] ?? 'admin';

// Professional statistics
$stats = [
    'buses' => 0,
    'drivers' => 0,
    'active_buses' => 0,
    'maintenance_buses' => 0,
    'breakdowns' => 0,
    'work_orders' => 0,
    'notifications' => 0,
    'users' => 0
];

$db_connected = false;
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

    // Driver statistics
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM drivers WHERE is_active = 1");
    $stmt->execute();
    $stats['drivers'] = (int) $stmt->fetch()['count'];

    // Breakdown statistics
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM breakdown_reports WHERE status != 'resolved'");
    $stmt->execute();
    $stats['breakdowns'] = (int) $stmt->fetch()['count'];

    // Work orders
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM work_orders WHERE status != 'completed'");
    $stmt->execute();
    $stats['work_orders'] = (int) $stmt->fetch()['count'];

    // Users
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE is_active = 1");
    $stmt->execute();
    $stats['users'] = (int) $stmt->fetch()['count'];

} catch (Exception $e) {
    $db_connected = false;
}

// Recent activities
$recent_activities = [];
if ($db_connected) {
    try {
        $stmt = $conn->prepare("
            SELECT 'breakdown' as type, created_at, report_ref as reference, description 
            FROM breakdown_reports 
            ORDER BY created_at DESC LIMIT 3
        ");
        $stmt->execute();
        $recent_activities = $stmt->fetchAll();
    } catch (Exception $e) {
        $recent_activities = [];
    }
}
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/professional.css">
    <link rel="icon" href="favicon.ico">
</head>
<body>
    <!-- Professional Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="dashboard_professional.php" class="logo">
                    🚗 <?php echo APP_NAME; ?>
                </a>
                
                <nav class="nav">
                    <a href="dashboard_professional.php" class="nav-link active">Tableau de Bord</a>
                    <a href="management/" class="nav-link">Gestion</a>
                    <a href="purchase/" class="nav-link">Achats</a>
                    <a href="reports/" class="nav-link">Rapports</a>
                    <a href="profile.php" class="nav-link">Profil</a>
                    <a href="logout.php" class="nav-link">Déconnexion</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Welcome Section -->
            <div style="margin-bottom: 2rem;">
                <h1 style="font-size: 2rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.5rem;">
                    Bienvenue, <?php echo htmlspecialchars($full_name); ?>
                </h1>
                <p style="color: var(--text-muted); margin: 0;">
                    Tableau de bord du système de gestion automobile
                </p>
            </div>

            <!-- Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['buses']); ?></div>
                    <div class="stat-label">Total des Bus</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['active_buses']); ?></div>
                    <div class="stat-label">Bus Actifs</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['drivers']); ?></div>
                    <div class="stat-label">Chauffeurs</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['breakdowns']); ?></div>
                    <div class="stat-label">Pannes en Cours</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['work_orders']); ?></div>
                    <div class="stat-label">Ordres de Travail</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['users']); ?></div>
                    <div class="stat-label">Utilisateurs</div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-header">
                    <h2 class="card-title">Actions Rapides</h2>
                </div>
                <div class="card-body">
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <a href="management/buses.php" class="btn btn-primary">
                            🚌 Gérer les Bus
                        </a>
                        <a href="management/drivers.php" class="btn btn-secondary">
                            👤 Gérer les Chauffeurs
                        </a>
                        <a href="admin_breakdowns.php" class="btn btn-warning">
                            🔧 Voir les Pannes
                        </a>
                        <a href="purchase/" class="btn btn-success">
                            📋 Achats
                        </a>
                        <a href="reports/" class="btn btn-info">
                            📊 Rapports
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Activités Récentes</h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($recent_activities)): ?>
                        <div class="table" style="margin: 0;">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Référence</th>
                                        <th>Description</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_activities as $activity): ?>
                                        <tr>
                                            <td>
                                                <span class="badge badge-info">
                                                    <?php echo ucfirst($activity['type']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($activity['reference'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars(substr($activity['description'] ?? '-', 0, 50)); ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($activity['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            Aucune activité récente à afficher.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- System Status -->
            <div class="card" style="margin-top: 2rem;">
                <div class="card-header">
                    <h2 class="card-title">État du Système</h2>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <div style="text-align: center;">
                            <div style="font-size: 2rem; margin-bottom: 0.5rem;">
                                <?php echo $db_connected ? '🟢' : '🔴'; ?>
                            </div>
                            <div style="font-weight: 600;">Base de Données</div>
                            <div style="color: var(--text-muted); font-size: 0.875rem;">
                                <?php echo $db_connected ? 'Connectée' : 'Déconnectée'; ?>
                            </div>
                        </div>
                        
                        <div style="text-align: center;">
                            <div style="font-size: 2rem; margin-bottom: 0.5rem;">
                                🟢
                            </div>
                            <div style="font-weight: 600;">Système</div>
                            <div style="color: var(--text-muted); font-size: 0.875rem;">
                                Opérationnel
                            </div>
                        </div>
                        
                        <div style="text-align: center;">
                            <div style="font-size: 2rem; margin-bottom: 0.5rem;">
                                🟢
                            </div>
                            <div style="font-weight: 600;">Serveur</div>
                            <div style="color: var(--text-muted); font-size: 0.875rem;">
                                Actif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Professional Footer -->
    <footer style="background: var(--bg-primary); border-top: 1px solid var(--border); padding: 2rem 0; margin-top: 4rem;">
        <div class="container">
            <div style="text-align: center; color: var(--text-muted);">
                <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Tous droits réservés.</p>
                <p style="font-size: 0.875rem; margin-top: 0.5rem;">
                    Version <?php echo APP_VERSION ?? '1.0.0'; ?> | Système de Gestion Automobile Professionnel
                </p>
            </div>
        </div>
    </footer>
</body>
</html>

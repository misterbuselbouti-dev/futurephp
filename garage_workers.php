<?php
// FUTURE AUTOMOTIVE - Garage Workers Management Page
// صفحة متخصصة لإدارة العاملين في الكاراج

require_once 'config.php';
require_once 'includes/functions.php';

// Check authentication
require_login();

// Page title
$page_title = 'Travailleurs du Garage';

// Get data from database
try {
    $database = new Database();
    $pdo = $database->connect();
    
    // Get garage specialties
    $stmt = $pdo->query("SELECT * FROM garage_specialties ORDER BY name");
    $specialties = $stmt->fetchAll();
    
    // Get workers by specialty
    $workers_by_specialty = [];
    $total_workers = 0;
    
    foreach ($specialties as $specialty) {
        $stmt = $pdo->prepare("
            SELECT id, username, full_name, phone, is_active, created_at 
            FROM users 
            WHERE role = ? 
            ORDER BY full_name
        ");
        $stmt->execute([$specialty['name']]);
        $workers = $stmt->fetchAll();
        
        $workers_by_specialty[$specialty['name']] = [
            'specialty_info' => $specialty,
            'workers' => $workers,
            'active_count' => count(array_filter($workers, fn($w) => $w['is_active'] ?? 1)),
            'total_count' => count($workers)
        ];
        
        $total_workers += count($workers);
    }
    
    // Get statistics
    $stats = [
        'total_workers' => $total_workers,
        'active_workers' => 0,
        'specialties_count' => count($specialties)
    ];
    
    foreach ($workers_by_specialty as $specialty_data) {
        $stats['active_workers'] += $specialty_data['active_count'];
    }
    
} catch (Exception $e) {
    $workers_by_specialty = [];
    $specialties = [];
    $stats = ['total_workers' => 0, 'active_workers' => 0, 'specialties_count' => 0];
    $error_message = "Error loading data: " . $e->getMessage();
}

function getRoleDisplay($role) {
    $roles = [
        'mecanicien' => ['color' => 'success', 'specialty' => 'Mécanique', 'icon' => 'fa-wrench'],
        'electricien' => ['color' => 'warning', 'specialty' => 'Électricité', 'icon' => 'fa-bolt'],
        'tolier' => ['color' => 'info', 'specialty' => 'Tôlerie', 'icon' => 'fa-hammer'],
        'peintre' => ['color' => 'danger', 'specialty' => 'Peinture', 'icon' => 'fa-paint-brush'],
        'chef_atelier' => ['color' => 'dark', 'specialty' => 'Supervision', 'icon' => 'fa-user-tie'],
        'receptionniste' => ['color' => 'secondary', 'specialty' => 'Accueil', 'icon' => 'fa-user'],
        'technician' => ['color' => 'primary', 'specialty' => 'Technique', 'icon' => 'fa-tools'],
        'agent' => ['color' => 'light', 'specialty' => 'Service', 'icon' => 'fa-user'],
        'maintenance_manager' => ['color' => 'warning', 'specialty' => 'Maintenance', 'icon' => 'fa-cogs'],
        'admin' => ['color' => 'danger', 'specialty' => 'Administration', 'icon' => 'fa-shield-alt']
    ];
    return $roles[$role] ?? ['color' => 'secondary', 'specialty' => 'Autre', 'icon' => 'fa-user'];
}
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .specialty-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: transform 0.2s, box-shadow 0.2s;
            overflow: hidden;
        }
        .specialty-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        .specialty-header {
            padding: 1.5rem;
            color: white;
            position: relative;
        }
        .specialty-icon {
            font-size: 2rem;
            opacity: 0.8;
        }
        .worker-item {
            padding: 0.75rem;
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.2s;
        }
        .worker-item:hover {
            background-color: #f8f9fa;
        }
        .worker-item:last-child {
            border-bottom: none;
        }
        .status-badge {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }
        .status-active { background-color: #28a745; }
        .status-inactive { background-color: #6c757d; }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
        }
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="mb-2">
                        <i class="fas fa-wrench me-3"></i>
                        Travailleurs du Garage
                    </h1>
                    <p class="text-muted">Gestion des travailleurs spécialisés du garage</p>
                </div>
            </div>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $stats['total_workers']; ?></div>
                        <div>Total Travailleurs</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $stats['active_workers']; ?></div>
                        <div>Travailleurs Actifs</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $stats['specialties_count']; ?></div>
                        <div>Spécialités</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $stats['total_workers'] > 0 ? round(($stats['active_workers'] / $stats['total_workers']) * 100, 1) : 0; ?>%</div>
                        <div>Taux d'Activité</div>
                    </div>
                </div>
            </div>
            
            <!-- Specialties Grid -->
            <div class="row">
                <?php foreach ($workers_by_specialty as $specialty_name => $specialty_data): ?>
                    <?php 
                    $specialty = $specialty_data['specialty_info'];
                    $workers = $specialty_data['workers'];
                    $roleDisplay = getRoleDisplay($specialty_name);
                    ?>
                    <div class="col-lg-6 col-xl-4 mb-4">
                        <div class="card specialty-card">
                            <div class="specialty-header" style="background-color: <?php echo $specialty['color']; ?>;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-1">
                                            <i class="fas <?php echo $specialty['icon']; ?> specialty-icon me-2"></i>
                                            <?php echo htmlspecialchars($specialty['name_fr']); ?>
                                        </h5>
                                        <small><?php echo htmlspecialchars($specialty['description']); ?></small>
                                    </div>
                                    <div class="text-end">
                                        <div class="badge bg-white text-dark">
                                            <?php echo $specialty_data['active_count']; ?>/<?php echo $specialty_data['total_count']; ?>
                                        </div>
                                        <div class="small mt-1">Actifs</div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($workers)): ?>
                                    <div class="text-center py-4 text-muted">
                                        <i class="fas fa-user-slash fa-2x mb-2"></i>
                                        <p>Aucun travailleur dans cette spécialité</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($workers as $worker): ?>
                                        <div class="worker-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="d-flex align-items-center">
                                                    <span class="status-badge <?php echo ($worker['is_active'] ?? 1) ? 'status-active' : 'status-inactive'; ?> me-2"></span>
                                                    <div>
                                                        <div class="fw-semibold"><?php echo htmlspecialchars($worker['full_name']); ?></div>
                                                        <small class="text-muted"><?php echo htmlspecialchars($worker['phone'] ?? 'N/A'); ?></small>
                                                    </div>
                                                </div>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary btn-sm" onclick="viewWorker(<?php echo $worker['id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-outline-warning btn-sm" onclick="toggleWorkerStatus(<?php echo $worker['id']; ?>)">
                                                        <i class="fas fa-<?php echo ($worker['is_active'] ?? 1) ? 'ban' : 'check'; ?>"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer bg-light">
                                <button class="btn btn-sm btn-outline-primary w-100" onclick="addWorker('<?php echo $specialty_name; ?>')">
                                    <i class="fas fa-plus me-1"></i>
                                    Ajouter un <?php echo htmlspecialchars($specialty['name_fr']); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Quick Actions -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-bolt me-2"></i>
                                Actions Rapides
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-2">
                                    <button class="btn btn-success w-100" onclick="showAllWorkers()">
                                        <i class="fas fa-users me-2"></i>
                                        Voir Tous les Travailleurs
                                    </button>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <button class="btn btn-warning w-100" onclick="showInactiveWorkers()">
                                        <i class="fas fa-user-clock me-2"></i>
                                        Travailleurs Inactifs
                                    </button>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <button class="btn btn-info w-100" onclick="exportWorkers()">
                                        <i class="fas fa-download me-2"></i>
                                        Exporter la Liste
                                    </button>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <button class="btn btn-primary w-100" onclick="manageSpecialties()">
                                        <i class="fas fa-cog me-2"></i>
                                        Gérer les Spécialités
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewWorker(workerId) {
            // View worker details
            window.location.href = 'employees.php?view=' + workerId;
        }
        
        function toggleWorkerStatus(workerId) {
            if (confirm('Changer le statut de ce travailleur?')) {
                // Toggle worker status
                fetch('api/employees/toggle_status.php', {
                    method: 'POST',
                    body: JSON.stringify({id: workerId})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erreur: ' + data.error);
                    }
                });
            }
        }
        
        function addWorker(specialty) {
            // Redirect to employees page with pre-selected specialty
            window.location.href = 'employees.php?role=' + specialty;
        }
        
        function showAllWorkers() {
            window.location.href = 'employees.php';
        }
        
        function showInactiveWorkers() {
            window.location.href = 'employees.php?filter=inactive';
        }
        
        function exportWorkers() {
            // Export workers list
            window.open('api/employees/export.php?format=csv');
        }
        
        function manageSpecialties() {
            alert('Fonctionnalité de gestion des spécialités bientôt disponible!');
        }
    </script>
</body>
</html>

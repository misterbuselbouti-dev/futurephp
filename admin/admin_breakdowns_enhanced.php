<?php
require_once 'config.php';
require_once 'includes/functions.php';

require_login();
$user = get_logged_in_user();
$role = $user['role'] ?? '';
if (!in_array($role, ['admin', 'maintenance_manager'], true)) {
    http_response_code(403);
    echo 'Accès refusé.';
    exit;
}

$page_title = 'Incidents (Maintenance) - Enhanced';

$database = new Database();
$pdo = $database->connect();

$reports = [];
$error_message = '';
$success_message = '';

// Filter parameters
$filter_status = $_GET['filter_status'] ?? '';
$filter_technician = $_GET['filter_technician'] ?? '';
$filter_bus = $_GET['filter_bus'] ?? '';
filter_date_from = $_GET['filter_date_from'] ?? '';
$filter_date_to = $_GET['filter_date_to'] ?? '';
$filter_urgency = $_GET['filter_urgency'] ?? '';
$search_term = $_GET['search'] ?? '';

try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'pan_issues'");
    $hasPan = (bool)$stmt->fetch();

    // Base SQL with joins
    $sql = "
        SELECT 
            br.*, 
            b.bus_number, b.license_plate,
            d.nom as driver_nom, d.prenom as driver_prenom, d.phone as driver_phone,
            pi.pan_code, pi.label_fr,
            ba.assigned_to_user_id,
            CONCAT(u.full_name, ' (', u.role, ')') as assigned_technician,
            ba.started_at,
            ba.ended_at,
            ba.actual_hours,
            ba.total_cost,
            COUNT(bwi.id) as items_used_count
        FROM breakdown_reports br
        LEFT JOIN buses b ON br.bus_id = b.id
        LEFT JOIN drivers d ON br.driver_id = d.id
        LEFT JOIN pan_issues pi ON br.pan_issue_id = pi.id
        LEFT JOIN breakdown_assignments ba ON br.id = ba.report_id
        LEFT JOIN users u ON ba.assigned_to_user_id = u.id
        LEFT JOIN breakdown_work_items bwi ON ba.id = bwi.assignment_id
    ";

    if ($hasPan) {
        $sql .= " WHERE 1=1 ";
    } else {
        $sql .= " WHERE 1=1 ";
    }

    // Apply filters
    $params = [];
    
    if (!empty($filter_status)) {
        $sql .= " AND br.status = ? ";
        $params[] = $filter_status;
    }
    
    if (!empty($filter_technician)) {
        $sql .= " AND ba.assigned_to_user_id = ? ";
        $params[] = $filter_technician;
    }
    
    if (!empty($filter_bus)) {
        $sql .= " AND br.bus_id = ? ";
        $params[] = $filter_bus;
    }
    
    if (!empty($filter_urgency)) {
        $sql .= " AND br.urgency = ? ";
        $params[] = $filter_urgency;
    }
    
    if (!empty($filter_date_from)) {
        $sql .= " AND DATE(br.created_at) >= ? ";
        $params[] = $filter_date_from;
    }
    
    if (!empty($filter_date_to)) {
        $sql .= " AND DATE(br.created_at) <= ? ";
        $params[] = $filter_date_to;
    }
    
    if (!empty($search_term)) {
        $sql .= " AND (br.report_ref LIKE ? OR br.description LIKE ? OR b.bus_number LIKE ? OR d.nom LIKE ? OR d.prenom LIKE ?) ";
        $searchParam = '%' . $search_term . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    $sql .= " GROUP BY br.id ORDER BY br.created_at DESC ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reports = $stmt->fetchAll();

} catch (Exception $e) {
    $error_message = 'Erreur lors du chargement: ' . $e->getMessage();
}

// Load filter options
$buses = [];
$technicians = [];
$statuses = [];
$urgencies = [];

try {
    // Load buses
    $stmt = $pdo->query("SELECT id, bus_number FROM buses ORDER BY bus_number");
    $buses = $stmt->fetchAll();
    
    // Load technicians/agents
    $stmt = $pdo->query("SELECT id, full_name FROM users WHERE role IN ('technician', 'agent') AND is_active = 1 ORDER BY full_name");
    $technicians = $stmt->fetchAll();
    
    // Load statuses
    $stmt = $pdo->query("SELECT DISTINCT status FROM breakdown_reports ORDER BY status");
    $statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Load urgencies
    $stmt = $pdo->query("SELECT DISTINCT urgency FROM breakdown_reports WHERE urgency IS NOT NULL ORDER BY urgency");
    $urgencies = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (Exception $e) {
    // Continue without filter options
}

function status_badge(string $status): string {
    $map = [
        'nouveau' => 'warning',
        'assigne' => 'info',
        'en_cours' => 'primary',
        'termine' => 'success',
        'annule' => 'secondary'
    ];
    return $map[$status] ?? 'secondary';
}

function urgency_badge(string $urgency): string {
    return $urgency === 'urgent' ? 'danger' : 'secondary';
}

function format_duration($hours) {
    if (!$hours) return '-';
    $h = floor($hours);
    $m = round(($hours - $h) * 60);
    return sprintf('%dh %dm', $h, $m);
}
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .filter-section {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            text-align: center;
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e3a8a;
        }
        .stat-label {
            color: #64748b;
            font-size: 0.875rem;
        }
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .btn-filter-clear {
            background: transparent;
            border: 1px solid #e2e8f0;
            color: #64748b;
        }
        .btn-filter-clear:hover {
            background: #f8fafc;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="mb-2"><i class="fas fa-screwdriver-wrench me-3"></i><?php echo htmlspecialchars($page_title); ?></h1>
                    <p class="text-muted">Gestion avancée des incidents avec filtres et recherche</p>
                </div>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($reports); ?></div>
                    <div class="stat-label">Total Incidents</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo count(array_filter($reports, fn($r) => $r['status'] === 'nouveau')); ?></div>
                    <div class="stat-label">Nouveaux</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo count(array_filter($reports, fn($r) => $r['status'] === 'en_cours')); ?></div>
                    <div class="stat-label">En Cours</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo count(array_filter($reports, fn($r) => $r['status'] === 'termine')); ?></div>
                    <div class="stat-label">Terminés</div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <h5 class="mb-3"><i class="fas fa-filter me-2"></i>Filtres et Recherche</h5>
                <form method="GET" class="filter-form">
                    <div class="filter-row">
                        <div>
                            <label class="form-label">Recherche</label>
                            <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Référence, description, bus...">
                        </div>
                        <div>
                            <label class="form-label">Statut</label>
                            <select class="form-select" name="filter_status">
                                <option value="">Tous les statuts</option>
                                <?php foreach ($statuses as $status): ?>
                                    <option value="<?php echo $status; ?>" <?php echo $filter_status === $status ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($status); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Technicien</label>
                            <select class="form-select" name="filter_technician">
                                <option value="">Tous les techniciens</option>
                                <?php foreach ($technicians as $tech): ?>
                                    <option value="<?php echo $tech['id']; ?>" <?php echo $filter_technician == $tech['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tech['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Bus</label>
                            <select class="form-select" name="filter_bus">
                                <option value="">Tous les bus</option>
                                <?php foreach ($buses as $bus): ?>
                                    <option value="<?php echo $bus['id']; ?>" <?php echo $filter_bus == $bus['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($bus['bus_number']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="filter-row">
                        <div>
                            <label class="form-label">Urgence</label>
                            <select class="form-select" name="filter_urgency">
                                <option value="">Toutes les urgences</option>
                                <?php foreach ($urgencies as $urgency): ?>
                                    <option value="<?php echo $urgency; ?>" <?php echo $filter_urgency === $urgency ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($urgency); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Date début</label>
                            <input type="date" class="form-control" name="filter_date_from" value="<?php echo htmlspecialchars($filter_date_from); ?>">
                        </div>
                        <div>
                            <label class="form-label">Date fin</label>
                            <input type="date" class="form-control" name="filter_date_to" value="<?php echo htmlspecialchars($filter_date_to); ?>">
                        </div>
                        <div class="d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Appliquer
                            </button>
                            <a href="admin_breakdowns.php" class="btn btn-filter-clear">
                                <i class="fas fa-times me-2"></i>Effacer
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Results Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Résultats (<?php echo count($reports); ?> incidents)</h5>
                    <div>
                        <a class="btn btn-outline-primary btn-sm" href="../notifications.php">
                            <i class="fas fa-bell"></i> Notifications
                        </a>
                        <button class="btn btn-outline-success btn-sm" onclick="exportResults()">
                            <i class="fas fa-download"></i> Exporter
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($reports)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-search fa-3x mb-3"></i>
                            <div>Aucun incident trouvé avec les filtres actuels</div>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Réf</th>
                                        <th>Bus</th>
                                        <th>Type</th>
                                        <th>PAN</th>
                                        <th>Urgence</th>
                                        <th>Technicien</th>
                                        <th>Temps</th>
                                        <th>Coût</th>
                                        <th>Pièces</th>
                                        <th>Statut</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reports as $r): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($r['report_ref']); ?></strong></td>
                                            <td><?php echo htmlspecialchars(($r['bus_number'] ?? '-') . ((isset($r['license_plate']) && $r['license_plate']) ? ' - ' . $r['license_plate'] : '')); ?></td>
                                            <td><?php echo htmlspecialchars($r['category']); ?></td>
                                            <td><?php echo htmlspecialchars(($r['pan_code'] ?? '-') . (($r['label_fr'] ?? '') ? ' - ' . $r['label_fr'] : '')); ?></td>
                                            <td><span class="badge bg-<?php echo urgency_badge($r['urgency']); ?>"><?php echo htmlspecialchars($r['urgency']); ?></span></td>
                                            <td><?php echo htmlspecialchars($r['assigned_technician'] ?? '-'); ?></td>
                                            <td><?php echo format_duration($r['actual_hours']); ?></td>
                                            <td><?php echo $r['total_cost'] ? number_format($r['total_cost'], 2) . ' MAD' : '-'; ?></td>
                                            <td>
                                                <?php if ($r['items_used_count'] > 0): ?>
                                                    <span class="badge bg-info"><?php echo $r['items_used_count']; ?> pièces</span>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="badge bg-<?php echo status_badge($r['status']); ?>"><?php echo htmlspecialchars($r['status']); ?></span></td>
                                            <td><?php echo $r['created_at'] ? date('d/m/Y H:i', strtotime($r['created_at'])) : '-'; ?></td>
                                            <td>
                                                <a class="btn btn-sm btn-outline-primary" href="admin_breakdown_view.php?id=<?php echo (int)$r['id']; ?>">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button class="btn btn-sm btn-outline-success" onclick="quickAssign(<?php echo (int)$r['id']; ?>)">
                                                    <i class="fas fa-user-plus"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function exportResults() {
            // Simple CSV export
            let csv = 'Référence,Bus,Type,PAN,Urgence,Technicien,Temps,Coût,Pièces,Statut,Date\n';
            document.querySelectorAll('tbody tr').forEach(row => {
                let cells = row.querySelectorAll('td');
                let rowData = [
                    cells[0].textContent.trim(),
                    cells[1].textContent.trim(),
                    cells[2].textContent.trim(),
                    cells[3].textContent.trim(),
                    cells[4].textContent.trim(),
                    cells[5].textContent.trim(),
                    cells[6].textContent.trim(),
                    cells[7].textContent.trim(),
                    cells[8].textContent.trim(),
                    cells[9].textContent.trim(),
                    cells[10].textContent.trim()
                ];
                csv += rowData.map(cell => `"${cell}"`).join(',') + '\n';
            });
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'breakdowns_export.csv';
            a.click();
        }

        function quickAssign(breakdownId) {
            // Quick assign modal
            const modal = new bootstrap.Modal(document.createElement('div'));
            // Implementation would go here
            console.log('Quick assign for breakdown:', breakdownId);
        }
    </script>
</body>
</html>

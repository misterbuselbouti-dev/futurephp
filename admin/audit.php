<?php
// FUTURE AUTOMOTIVE - Comprehensive Audit System
// Complete audit management for all system operations

require_once '../config.php';
require_once '../includes/functions.php';

// Check authentication
if (!is_logged_in()) {
    header('Location: ../login.php');
    exit();
}

$user = get_logged_in_user();
$role = $user['role'] ?? '';

// Only admin can access audit
if ($role !== 'admin') {
    http_response_code(403);
    echo 'Accès refusé.';
    exit();
}

$page_title = 'Système d\'Audit Complet';
$database = new Database();
$pdo = $database->connect();

// Get filter parameters
$filter_date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$filter_date_to = $_GET['date_to'] ?? date('Y-m-d');
$filter_user = $_GET['filter_user'] ?? '';
$filter_action = $_GET['filter_action'] ?? '';
$filter_entity = $_GET['filter_entity'] ?? '';
$limit = (int)($_GET['limit'] ?? 50);
$page = (int)($_GET['page'] ?? 1);
$offset = ($page - 1) * $limit;

// Get audit logs
$audit_logs = [];
$total_logs = 0;

try {
    $sql = "
        SELECT 
            bal.*,
            u.full_name as performed_by_name,
            u.role as performed_by_role,
            DATE_FORMAT(bal.performed_at, '%d/%m/%Y %H:%i:%s') as formatted_time,
            CASE 
                WHEN bal.action_type = 'assignment' THEN 'Assignation'
                WHEN bal.action_type = 'work_started' THEN 'Début travail'
                WHEN bal.action_type = 'work_ended' THEN 'Fin travail'
                WHEN bal.action_type = 'session_started' THEN 'Session démarrée'
                WHEN bal.action_type = 'session_paused' THEN 'Session mise en pause'
                WHEN bal.action_type = 'session_resumed' THEN 'Session reprise'
                WHEN bal.action_type = 'session_ended' THEN 'Session terminée'
                WHEN bal.action_type = 'item_added' THEN 'Pièce ajoutée'
                WHEN bal.action_type = 'item_removed' THEN 'Pièce retirée'
                WHEN bal.action_type = 'status_changed' THEN 'Statut modifié'
                WHEN bal.action_type = 'user_login' THEN 'Connexion utilisateur'
                WHEN bal.action_type = 'user_logout' THEN 'Déconnexion utilisateur'
                WHEN bal.action_type = 'file_access' THEN 'Accès fichier'
                WHEN bal.action_type = 'data_export' THEN 'Export données'
                WHEN bal.action_type = 'system_backup' THEN 'Sauvegarde système'
                ELSE bal.action_type
            END as action_display
        FROM breakdown_audit_log bal
        JOIN users u ON bal.performed_by_user_id = u.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if (!empty($filter_date_from)) {
        $sql .= " AND DATE(bal.performed_at) >= ?";
        $params[] = $filter_date_from;
    }
    
    if (!empty($filter_date_to)) {
        $sql .= " AND DATE(bal.performed_at) <= ?";
        $params[] = $filter_date_to;
    }
    
    if (!empty($filter_user)) {
        $sql .= " AND bal.performed_by_user_id = ?";
        $params[] = $filter_user;
    }
    
    if (!empty($filter_action)) {
        $sql .= " AND bal.action_type = ?";
        $params[] = $filter_action;
    }
    
    if (!empty($filter_entity)) {
        $sql .= " AND bal.entity_type = ?";
        $params[] = $filter_entity;
    }
    
    // Get total count
    $count_sql = str_replace("ORDER BY bal.performed_at DESC LIMIT ? OFFSET ?", "", $sql);
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_logs = $stmt->fetch()['total'];
    
    // Get paginated results
    $sql .= " ORDER BY bal.performed_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $audit_logs = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error_message = 'Erreur lors du chargement: ' . $e->getMessage();
}

// Get statistics
$stats = [];
try {
    // Total actions
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM breakdown_audit_log");
    $stmt->execute();
    $stats['total_actions'] = $stmt->fetch()['total'];
    
    // Actions by type
    $stmt = $pdo->prepare("
        SELECT action_type, COUNT(*) as count 
        FROM breakdown_audit_log 
        GROUP BY action_type 
        ORDER BY count DESC
    ");
    $stmt->execute();
    $stats['by_action'] = $stmt->fetchAll();
    
    // Actions by user
    $stmt = $pdo->prepare("
        SELECT u.full_name, COUNT(*) as count 
        FROM breakdown_audit_log bal
        JOIN users u ON bal.performed_by_user_id = u.id
        GROUP BY u.id, u.full_name
        ORDER BY count DESC
        LIMIT 10
    ");
    $stmt->execute();
    $stats['by_user'] = $stmt->fetchAll();
    
    // Actions by date
    $stmt = $pdo->prepare("
        SELECT DATE(performed_at) as date, COUNT(*) as count
        FROM breakdown_audit_log
        WHERE performed_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
        GROUP BY DATE(performed_at)
        ORDER BY date DESC
    ");
    $stmt->execute();
    $stats['by_date'] = $stmt->fetchAll();
    
    // Recent critical actions
    $stmt = $pdo->prepare("
        SELECT bal.*, u.full_name as performed_by_name
        FROM breakdown_audit_log bal
        JOIN users u ON bal.performed_by_user_id = u.id
        WHERE bal.action_type IN ('item_removed', 'work_ended', 'status_changed', 'user_logout')
        ORDER BY bal.performed_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $stats['recent_critical'] = $stmt->fetchAll();
    
} catch (Exception $e) {
    // Continue without stats
}

// Get users for filter dropdown
$users = [];
try {
    $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE is_active = 1 ORDER BY full_name");
    $stmt->execute();
    $users = $stmt->fetchAll();
} catch (Exception $e) {
    $users = [];
}

// Get action types for filter dropdown
$action_types = [];
try {
    $stmt = $pdo->prepare("SELECT DISTINCT action_type FROM breakdown_audit_log ORDER BY action_type");
    $stmt->execute();
    $action_types = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $action_types = [];
}

// Get entity types for filter dropdown
$entity_types = [];
try {
    $stmt = $pdo->prepare("SELECT DISTINCT entity_type FROM breakdown_audit_log WHERE entity_type IS NOT NULL ORDER BY entity_type");
    $stmt->execute();
    $entity_types = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $entity_types = [];
}

function formatValue($value) {
    if (!$value) return '-';
    try {
        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        return $value;
    } catch (Exception $e) {
        return $value;
    }
}

function getActionIcon($action) {
    $icons = [
        'assignment' => 'fas fa-user-plus',
        'work_started' => 'fas fa-play',
        'work_ended' => 'fas fa-stop',
        'session_started' => 'fas fa-play-circle',
        'session_paused' => 'fas fa-pause-circle',
        'session_resumed' => 'fas fa-play-circle',
        'session_ended' => 'fas fa-stop-circle',
        'item_added' => 'fas fa-plus',
        'item_removed' => 'fas fa-minus',
        'status_changed' => 'fas fa-exchange-alt',
        'user_login' => 'fas fa-sign-in-alt',
        'user_logout' => 'fas fa-sign-out-alt',
        'file_access' => 'fas fa-file-alt',
        'data_export' => 'fas fa-download',
        'system_backup' => 'fas fa-save'
    ];
    return $icons[$action] ?? 'fas fa-info-circle';
}

function getActionColor($action) {
    $colors = [
        'assignment' => 'primary',
        'work_started' => 'success',
        'work_ended' => 'warning',
        'session_started' => 'info',
        'session_paused' => 'warning',
        'session_resumed' => 'info',
        'session_ended' => 'danger',
        'item_added' => 'success',
        'item_removed' => 'danger',
        'status_changed' => 'warning',
        'user_login' => 'success',
        'user_logout' => 'secondary',
        'file_access' => 'info',
        'data_export' => 'primary',
        'system_backup' => 'info'
    ];
    return $colors[$action] ?? 'secondary';
}
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo APP_NAME; ?></title>
    
    <!-- ISO 9001 Professional Theme -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/iso-theme.css">
    <link rel="stylesheet" href="../assets/css/iso-components.css">
    <link rel="stylesheet" href="../assets/css/iso-bootstrap.css">
    
    <style>
        .audit-header {
            background-color: var(--primary);
            color: white;
            padding: var(--space-6);
            border-radius: var(--radius-lg);
            margin-bottom: var(--space-6);
        }
        
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
            color: var(--primary);
            margin-bottom: var(--space-2);
        }
        
        .stat-label {
            color: var(--text-light);
            font-size: var(--font-size-sm);
        }
        
        .iso-card {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-primary);
            border-radius: var(--radius-lg);
            padding: var(--space-6);
            margin-bottom: var(--space-6);
            transition: transform 0.2s;
        }
        
        .iso-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .form-control, .form-select {
            border-radius: var(--radius);
            border: 1px solid var(--border);
            transition: all 0.2s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(var(--primary-rgb), 0.25);
        }
        
        .table-responsive {
            border-radius: var(--radius);
            overflow: hidden;
        }
        
        .action-badge {
            display: inline-flex;
            align-items: center;
            gap: var(--space-1);
            padding: var(--space-1) var(--space-2);
            border-radius: var(--radius);
            font-size: var(--font-size-xs);
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 260px;
                padding: var(--space-8);
                min-height: 100vh;
            }
        }
        
        .filter-section {
            background-color: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: var(--space-6);
            margin-bottom: var(--space-6);
        }
        
        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--space-4);
            margin-bottom: var(--space-4);
        }
        
        .audit-table {
            background-color: var(--bg-white);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }
        
        .timeline-item {
            position: relative;
            padding-left: 2rem;
            margin-bottom: var(--space-4);
            border-left: 2px solid var(--border);
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -8px;
            top: 0.5rem;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--text-muted);
        }
        
        .timeline-item.critical::before {
            background: var(--danger);
        }
        
        .value-display {
            background-color: var(--bg-light);
            padding: var(--space-2);
            border-radius: var(--radius);
            font-family: monospace;
            font-size: var(--font-size-sm);
            max-width: 200px;
            overflow: auto;
        }
        
        .pagination {
            justify-content: center;
        }
    </style>
</head>
<body>
    <!-- Include header -->
    <?php include '../includes/header_simple.php'; ?>
    
    <!-- Include sidebar -->
    <?php include '../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-6">
                <div>
                    <h1 class="mb-2">
                        <i class="fas fa-shield-alt me-3"></i>
                        <?php echo htmlspecialchars($page_title); ?>
                    </h1>
                    <p class="text-muted mb-0">Bienvenue, <?php echo htmlspecialchars($user['full_name']); ?></p>
                </div>
                <div class="d-flex gap-3">
                    <button class="btn btn-primary" onclick="window.location.href='../dashboard.php'">
                        <i class="fas fa-home me-2"></i>Dashboard
                    </button>
                    <button class="btn btn-success" onclick="window.location.href='#'">
                        <i class="fas fa-save me-2"></i>Enregistrer
                    </button>
                    <button class="btn btn-warning" onclick="window.location.href='../quick_audit.php'">
                        <i class="fas fa-clipboard-check me-2"></i>Audit Rapide
                    </button>
                    <button class="btn btn-outline-secondary" onclick="window.location.href='../remove_unnecessary_files.php'">
                        <i class="fas fa-trash-alt me-2"></i>Nettoyer
                    </button>
                </div>
            </div>

            <!-- Audit Header -->
            <div class="audit-header">
                <h2 class="mb-2">
                    <i class="fas fa-history me-3"></i>
                    Surveillance Complète
                </h2>
                <p class="mb-0">Suivi de toutes les opérations du système</p>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['total_actions'] ?? 0); ?></div>
                    <div class="stat-label">Total Actions</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($stats['by_action'] ?? []); ?></div>
                    <div class="stat-label">Types d'Actions</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($stats['by_user'] ?? []); ?></div>
                    <div class="stat-label">Utilisateurs Actifs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($stats['by_date'] ?? []); ?></div>
                    <div class="stat-label">Jours d'Activité</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filter-section">
                <h5 class="mb-3">Filtres</h5>
                <form method="GET" class="filter-row">
                    <div>
                        <label class="form-label">Date début</label>
                        <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($filter_date_from); ?>">
                    </div>
                    <div>
                        <label class="form-label">Date fin</label>
                        <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($filter_date_to); ?>">
                    </div>
                    <div>
                        <label class="form-label">Utilisateur</label>
                        <select class="form-select" name="filter_user">
                            <option value="">Tous les utilisateurs</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>" <?php echo $filter_user == $user['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Action</label>
                        <select class="form-select" name="filter_action">
                            <option value="">Toutes les actions</option>
                            <?php foreach ($action_types as $action): ?>
                                <option value="<?php echo $action; ?>" <?php echo $filter_action === $action ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($action); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Entité</label>
                        <select class="form-select" name="filter_entity">
                            <option value="">Toutes les entités</option>
                            <?php foreach ($entity_types as $entity): ?>
                                <option value="<?php echo $entity; ?>" <?php echo $filter_entity === $entity ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($entity); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Appliquer
                        </button>
                        <a href="audit.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Réinitialiser
                        </a>
                        <button type="button" class="btn btn-outline-success" onclick="exportAudit()">
                            <i class="fas fa-download me-2"></i>Exporter
                        </button>
                    </div>
                </form>
            </div>

            <!-- Recent Critical Actions -->
            <?php if (!empty($stats['recent_critical'])): ?>
                <div class="iso-card mb-4">
                    <h5 class="mb-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Actions Critiques Récentes
                    </h5>
                    <div class="timeline">
                        <?php foreach ($stats['recent_critical'] as $action): ?>
                            <div class="timeline-item critical">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="<?php echo getActionIcon($action['action_type']); ?> me-2"></i>
                                            <strong><?php echo $action['action_display']; ?></strong>
                                            <span class="badge bg-<?php echo getActionColor($action['action_type']); ?> ms-2">
                                                <?php echo $action['action_type']; ?>
                                            </span>
                                        </div>
                                        <div class="text-muted small">
                                            <i class="fas fa-user me-1"></i>
                                            <?php echo htmlspecialchars($action['performed_by_name']); ?>
                                            <span class="ms-2">•</span>
                                            <?php echo $action['formatted_time']; ?>
                                        </div>
                                        <?php if ($action['field_name'] || $action['old_value'] || $action['new_value']): ?>
                                            <div class="mb-2">
                                                <?php if ($action['field_name']): ?>
                                                    <strong><?php echo htmlspecialchars($action['field_name']); ?>:</strong>
                                                <?php endif; ?>
                                                <?php if ($action['old_value']): ?>
                                                    <div class="value-display">
                                                        <small>Ancien:</small><br>
                                                        <?php echo formatValue($action['old_value']); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($action['new_value']): ?>
                                                    <div class="value-display">
                                                        <small>Nouveau:</small><br>
                                                        <?php echo formatValue($action['new_value']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Audit Table -->
            <div class="audit-table">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        Journal d'Audit
                        <span class="badge bg-secondary ms-2"><?php echo number_format($total_logs); ?> enregistrements</span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($audit_logs)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-search fa-3x mb-3"></i>
                            <div>Aucune action trouvée avec les filtres actuels</div>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Action</th>
                                        <th>Entité</th>
                                        <th>Champ</th>
                                        <th>Ancienne valeur</th>
                                        <th>Nouvelle valeur</th>
                                        <th>Utilisateur</th>
                                        <th>IP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($audit_logs as $log): ?>
                                        <tr>
                                            <td><?php echo $log['formatted_time']; ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo getActionColor($log['action_type']); ?> action-badge">
                                                    <i class="<?php echo getActionIcon($log['action_type']); ?> me-1"></i>
                                                    <?php echo $log['action_display']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($log['entity_type'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($log['field_name'] ?? '-'); ?></td>
                                            <td>
                                                <?php if ($log['old_value']): ?>
                                                    <div class="value-display">
                                                        <?php echo formatValue($log['old_value']); ?>
                                                    </div>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($log['new_value']): ?>
                                                    <div class="value-display">
                                                        <?php echo formatValue($log['new_value']); ?>
                                                    </div>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-user me-2"></i>
                                                    <div>
                                                        <div><?php echo htmlspecialchars($log['performed_by_name']); ?></div>
                                                        <small class="text-muted"><?php echo $log['performed_by_role']; ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><small><?php echo $log['ip_address'] ?? '-'; ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_logs > $limit): ?>
                            <nav>
                                <ul class="pagination">
                                    <?php
                                    $total_pages = ceil($total_logs / $limit);
                                    $current_page = $page;
                                    
                                    // Previous button
                                    $prev_disabled = $current_page <= 1 ? 'disabled' : '';
                                    echo "<li class='page-item {$prev_disabled}'>";
                                    echo "<a class='page-link' href='?page=" . ($current_page - 1) . "' "'>Précédent</a>";
                                    echo "</li>";
                                    
                                    // Page numbers
                                    $start_page = max(1, $current_page - 2);
                                    $end_page = min($total_pages, $current_page + 2);
                                    
                                    for ($i = $start_page; $i <= $end_page; $i++) {
                                        $active = $i === $current_page ? 'active' : '';
                                        echo "<li class='page-item {$active}'>";
                                        echo "<a class='page-link' href='?page={$i}'>{$i}</a>";
                                        echo "</li>";
                                    }
                                    
                                    // Next button
                                    $next_disabled = $current_page >= $total_pages ? 'disabled' : '';
                                    echo "<li class='page-item {$next_disabled}'>";
                                    echo "<a class='page-link' href='?page=" . ($current_page + 1) . "' "'>Suivant</a>";
                                    echo "</li>";
                                    ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function exportAudit() {
            const csv = 'Date,Action,Entité,Champ,Ancienne valeur,Nouvelle valeur,Utilisateur,Rôle,IP\n';
            
            document.querySelectorAll('tbody tr').forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length >= 8) {
                    const rowData = [
                        cells[0].textContent.trim(),
                        cells[1].textContent.trim(),
                        cells[2].textContent.trim(),
                        cells[3].textContent.trim(),
                        cells[4].textContent.trim(),
                        cells[5].textContent.trim(),
                        cells[6].textContent.trim(),
                        cells[7].textContent.trim()
                    ];
                    csv += rowData.map(cell => `"${cell}"`).join(',') + '\n';
                }
            });
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'audit_log_' + new Date().toISOString().split('T')[0] + '.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }
        
        // Auto-refresh every 30 seconds for real-time monitoring
        setInterval(() => {
            if (document.hidden) return; // Don't refresh if tab is not visible
            window.location.reload();
        }, 30000);
    </script>
</body>
</html>

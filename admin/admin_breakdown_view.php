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

$reportId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$reportId) {
    http_response_code(400);
    echo 'ID manquant.';
    exit;
}

$database = new Database();
$pdo = $database->connect();

$error_message = '';
$success_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'assign') {
            $assignedTo = isset($_POST['assigned_to_user_id']) && $_POST['assigned_to_user_id'] !== '' ? (int)$_POST['assigned_to_user_id'] : null;
            if (!$assignedTo) {
                throw new Exception('Veuillez sélectionner un agent/technicien.');
            }

            $pdo->beginTransaction();

            // Check if assignment already exists
            $stmt = $pdo->prepare("SELECT id FROM breakdown_assignments WHERE report_id = ? AND assigned_to_user_id = ?");
            $stmt->execute([$reportId, $assignedTo]);
            $existing = $stmt->fetch();

            if ($existing) {
                $stmt = $pdo->prepare("UPDATE breakdown_assignments SET assigned_to_user_id = ?, assigned_by_user_id = ?, assigned_at = NOW() WHERE report_id = ?");
                $stmt->execute([$assignedTo, $user['id'], $reportId]);
                $assignmentId = $existing['id'];
            } else {
                $stmt = $pdo->prepare("INSERT INTO breakdown_assignments (report_id, assigned_to_user_id, assigned_by_user_id, assigned_at) VALUES (?,?,?, NOW())");
                $stmt->execute([$reportId, $assignedTo, $user['id']]);
                $assignmentId = $pdo->lastInsertId();
            }

            $pdo->prepare("UPDATE breakdown_reports SET status = 'assigne' WHERE id = ?")->execute([$reportId]);

            // Create notification
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, entity_type, entity_id, is_read) VALUES (?, 'breakdown_assigned', 'breakdown_report', ?, 0)");
            $stmt->execute([(int)$assignedTo, (int)$reportId]);

            // Log action
            $stmt = $pdo->prepare("INSERT INTO breakdown_audit_log (breakdown_id, assignment_id, action_type, new_value, performed_by_user_id) VALUES (?, ?, 'assignment', ?, ?)");
            $stmt->execute([$reportId, $assignmentId, json_encode(['technician_id' => $assignedTo]), $user['id']]);

            $pdo->commit();
            $success_message = 'Incident assigné avec succès.';

        } elseif ($action === 'start_work') {
            $stmt = $pdo->prepare("SELECT id FROM breakdown_assignments WHERE report_id = ? AND assigned_to_user_id = ?");
            $stmt->execute([$reportId, (int)($_POST['assigned_to_user_id'] ?? 0)]);
            $assignment = $stmt->fetch();
            if (!$assignment) throw new Exception('Aucune assignation trouvée.');

            $stmt = $pdo->prepare("INSERT INTO breakdown_time_logs (breakdown_id, assignment_id, user_id, action_type, created_by_user_id) VALUES (?, ?, ?, 'start', ?)");
            $stmt->execute([$reportId, $assignment['id'], $user['id'], $user['id']]);

            $pdo->prepare("UPDATE breakdown_assignments SET started_at = NOW(), work_status = 'in_progress' WHERE id = ?")->execute([$assignment['id']]);
            $pdo->prepare("UPDATE breakdown_reports SET status = 'en_cours' WHERE id = ?")->execute([$reportId]);

            $success_message = 'Intervention démarrée.';

        } elseif ($action === 'end_work') {
            $notes = trim($_POST['notes'] ?? '');
            $stmt = $pdo->prepare("SELECT id, started_at FROM breakdown_assignments WHERE report_id = ?");
            $stmt->execute([$reportId]);
            $assignment = $stmt->fetch();
            if (!$assignment || empty($assignment['started_at'])) throw new Exception("L'intervention n'a pas encore démarré.");

            $stmt = $pdo->prepare("INSERT INTO breakdown_time_logs (breakdown_id, assignment_id, user_id, action_type, notes, created_by_user_id) VALUES (?, ?, ?, 'end', ?, ?)");
            $stmt->execute([$reportId, $assignment['id'], $user['id'], $notes, $user['id']]);

            $pdo->prepare("UPDATE breakdown_assignments SET ended_at = NOW(), work_status = 'completed' WHERE id = ?")->execute([$assignment['id']]);
            $pdo->prepare("UPDATE breakdown_reports SET status = 'termine' WHERE id = ?")->execute([$reportId]);

            // Calculate duration
            $stmt = $pdo->prepare("CALL CalculateWorkDuration(?, ?)");
            $stmt->execute([$reportId, $assignment['id']]);

            $success_message = 'Intervention terminée.';
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error_message = $e->getMessage();
    }
}

// Load comprehensive data
$report = null;
$assignment = null;
$workItems = [];
$timeLogs = [];
$auditLogs = [];

try {
    // Load report with all details
    $stmt = $pdo->prepare("
        SELECT 
            br.*, 
            b.bus_number, b.license_plate,
            CONCAT(d.prenom, ' ', d.nom) AS driver_name,
            d.phone AS driver_phone,
            pi.pan_code,
            pi.label_fr
        FROM breakdown_reports br
        LEFT JOIN buses b ON br.bus_id = b.id
        LEFT JOIN drivers d ON br.driver_id = d.id
        LEFT JOIN pan_issues pi ON br.pan_issue_id = pi.id
        WHERE br.id = ?
    ");
    $stmt->execute([$reportId]);
    $report = $stmt->fetch();

    if ($report) {
        // Load assignment with technician details
        $stmt = $pdo->prepare("
            SELECT 
                ba.*,
                u.full_name as technician_name,
                u.role as technician_role,
                u.phone as technician_phone,
                COUNT(bwi.id) as items_used_count,
                SUM(bwi.total_cost) as total_material_cost
            FROM breakdown_assignments ba
            LEFT JOIN users u ON ba.assigned_to_user_id = u.id
            LEFT JOIN breakdown_work_items bwi ON ba.id = bwi.assignment_id
            WHERE ba.report_id = ?
            GROUP BY ba.id
            ORDER BY ba.assigned_at DESC
        ");
        $stmt->execute([$reportId]);
        $assignment = $stmt->fetch();

        // Load work items
        $stmt = $pdo->prepare("
            SELECT 
                bwi.*,
                ac.reference,
                ac.designation,
                ac.unite,
                ac.prix_achat
            FROM breakdown_work_items bwi
            JOIN articles_catalogue ac ON bwi.article_id = ac.id
            WHERE bwi.breakdown_id = ?
            ORDER BY bwi.added_at
        ");
        $stmt->execute([$reportId]);
        $workItems = $stmt->fetchAll();

        // Load time logs
        $stmt = $pdo->prepare("
            SELECT 
                tl.*,
                u.full_name,
                DATE_FORMAT(tl.action_time, '%d/%m/%Y %H:%i:%s') as formatted_time
            FROM breakdown_time_logs tl
            JOIN users u ON tl.user_id = u.id
            WHERE tl.breakdown_id = ?
            ORDER BY tl.action_time
        ");
        $stmt->execute([$reportId]);
        $timeLogs = $stmt->fetchAll();

        // Load audit logs
        $stmt = $pdo->prepare("
            SELECT 
                bal.*,
                u.full_name as performed_by_name,
                DATE_FORMAT(bal.performed_at, '%d/%m/%Y %H:%i:%s') as formatted_time
            FROM breakdown_audit_log bal
            JOIN users u ON bal.performed_by_user_id = u.id
            WHERE bal.breakdown_id = ?
            ORDER BY bal.performed_at DESC
            LIMIT 50
        ");
        $stmt->execute([$reportId]);
        $auditLogs = $stmt->fetchAll();
    }
} catch (Exception $e) {
    $error_message = 'Erreur lors du chargement: ' . $e->getMessage();
}

if (!$report) {
    http_response_code(404);
    echo 'Incident introuvable.';
    exit;
}

// Mark notifications as read
try {
    $pdo->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND entity_type = 'breakdown_report' AND entity_id = ? AND is_read = 0")
        ->execute([$user['id'], $reportId]);
} catch (Exception $e) {
    // Continue
}

// Load technicians
$technicians = [];
try {
    $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE role IN ('technician', 'agent') AND is_active = 1 ORDER BY full_name");
    $stmt->execute();
    $technicians = $stmt->fetchAll();
} catch (Exception $e) {
    $technicians = [];
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
    <title>Détails Incident #<?php echo $reportId; ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .status-timeline {
            position: relative;
            padding-left: 30px;
        }
        .status-timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e2e8f0;
        }
        .status-item {
            position: relative;
            margin-bottom: 20px;
        }
        .status-item::before {
            content: '';
            position: absolute;
            left: -25px;
            top: 5px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #64748b;
        }
        .status-item.active::before {
            background: #10b981;
        }
        .status-item.warning::before {
            background: #f59e0b;
        }
        .status-item.danger::before {
            background: #ef4444;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .info-card {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .info-card h6 {
            color: #64748b;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        .info-card .value {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <!-- Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="admin_breakdowns_enhanced.php">Incidents</a></li>
                            <li class="breadcrumb-item active">Détails #<?php echo $reportId; ?></li>
                        </ol>
                    </nav>
                    <h1 class="mb-2">
                        <i class="fas fa-screwdriver-wrench me-3"></i>
                        Détails Incident #<?php echo $reportId; ?>
                    </h1>
                    <p class="text-muted">Gestion complète de l'incident</p>
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

            <!-- Quick Info Grid -->
            <div class="info-grid">
                <div class="info-card">
                    <h6>Référence</h6>
                    <div class="value"><?php echo htmlspecialchars($report['report_ref']); ?></div>
                </div>
                <div class="info-card">
                    <h6>Bus</h6>
                    <div class="value"><?php echo htmlspecialchars(($report['bus_number'] ?? '-') . ' - ' . ($report['license_plate'] ?? '-')); ?></div>
                </div>
                <div class="info-card">
                    <h6>Chauffeur</h6>
                    <div class="value"><?php echo htmlspecialchars($report['driver_name'] ?? '-'); ?></div>
                </div>
                <div class="info-card">
                    <h6>Téléphone</h6>
                    <div class="value"><?php echo htmlspecialchars($report['driver_phone'] ?? '-'); ?></div>
                </div>
                <div class="info-card">
                    <h6>Catégorie</h6>
                    <div class="value"><?php echo htmlspecialchars($report['category']); ?></div>
                </div>
                <div class="info-card">
                    <h6>Urgence</h6>
                    <div class="value">
                        <span class="badge bg-<?php echo urgency_badge($report['urgency']); ?>">
                            <?php echo htmlspecialchars($report['urgency']); ?>
                        </span>
                    </div>
                </div>
                <div class="info-card">
                    <h6>Kilométrage</h6>
                    <div class="value"><?php echo $report['kilometrage'] ? number_format($report['kilometrage']) : '-'; ?> km</div>
                </div>
                <div class="info-card">
                    <h6>Statut</h6>
                    <div class="value">
                        <span class="badge bg-<?php echo status_badge($report['status']); ?>">
                            <?php echo htmlspecialchars($report['status']); ?>
                        </span>
                    </div>
                </div>
                <div class="info-card">
                    <h6>Date</h6>
                    <div class="value"><?php echo date('d/m/Y H:i', strtotime($report['created_at'])); ?></div>
                </div>
            </div>

            <!-- Description -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Description</h6>
                </div>
                <div class="card-body">
                    <p><?php echo nl2br(htmlspecialchars($report['description'] ?? '-')); ?></p>
                </div>
            </div>

            <!-- Assignment Section -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Assignation</h6>
                    <?php if (!$assignment): ?>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#assignmentModal">
                            <i class="fas fa-user-plus me-2"></i>Assigner
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($assignment): ?>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Technicien:</strong> <?php echo htmlspecialchars($assignment['technician_name']); ?></p>
                                <p><strong>Rôle:</strong> <?php echo htmlspecialchars($assignment['technician_role']); ?></p>
                                <p><strong>Téléphone:</strong> <?php echo htmlspecialchars($assignment['technician_phone'] ?? '-'); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Assigné le:</strong> <?php echo date('d/m/Y H:i', strtotime($assignment['assigned_at'])); ?></p>
                                <p><strong>Début:</strong> <?php echo $assignment['started_at'] ? date('d/m/Y H:i', strtotime($assignment['started_at'])) : '-'; ?></p>
                                <p><strong>Fin:</strong> <?php echo $assignment['ended_at'] ? date('d/m/Y H:i', strtotime($assignment['ended_at'])) : '-'; ?></p>
                            </div>
                        </div>
                        
                        <?php if ($assignment['notes']): ?>
                            <div class="mt-3">
                                <p><strong>Notes:</strong></p>
                                <p><?php echo nl2br(htmlspecialchars($assignment['notes'])); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Work Actions -->
                        <div class="mt-3">
                            <?php if ($assignment['work_status'] === 'pending'): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="start_work">
                                    <input type="hidden" name="assigned_to_user_id" value="<?php echo $assignment['assigned_to_user_id']; ?>">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-play me-2"></i>Démarrer le travail
                                    </button>
                                </form>
                            <?php elseif ($assignment['work_status'] === 'in_progress'): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="end_work">
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-stop me-2"></i>Terminer le travail
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Aucun technicien assigné</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Time Tracking -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-clock me-2"></i>
                        Suivi du temps
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>Temps total:</strong> <?php echo format_duration($assignment['actual_hours'] ?? 0); ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Statut:</strong> 
                                <span class="badge bg-<?php echo $assignment['work_status'] === 'completed' ? 'success' : ($assignment['work_status'] === 'in_progress' ? 'primary' : 'secondary'); ?>">
                                    <?php echo $assignment['work_status'] ?? 'pending'; ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Coût matériel:</strong> <?php echo $assignment['total_material_cost'] ? number_format($assignment['total_material_cost'], 2) . ' MAD' : '-'; ?></p>
                        </div>
                    </div>
                    
                    <!-- Time Logs -->
                    <div class="mt-3">
                        <h6>Historique des sessions</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Action</th>
                                        <th>Utilisateur</th>
                                        <th>Heure</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($timeLogs as $log): ?>
                                        <tr>
                                            <td>
                                                <?php
                                                $actionLabels = [
                                                    'start' => 'Démarrage',
                                                    'pause' => 'Pause',
                                                    'resume' => 'Reprise',
                                                    'end' => 'Fin'
                                                ];
                                                $actionColor = [
                                                    'start' => 'success',
                                                    'pause' => 'warning',
                                                    'resume' => 'info',
                                                    'end' => 'danger'
                                                ];
                                                $action = $log['action_type'];
                                                ?>
                                                <span class="badge bg-<?php echo $actionColor[$action] ?? 'secondary'; ?>">
                                                    <?php echo $actionLabels[$action] ?? $action; ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($log['full_name']); ?></td>
                                            <td><?php echo $log['formatted_time']; ?></td>
                                            <td><?php echo htmlspecialchars($log['notes'] ?? '-'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Work Items -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-tools me-2"></i>
                        Pièces utilisées (<?php echo count($workItems); ?>)
                    </h6>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#inventoryModal">
                        <i class="fas fa-plus me-2"></i>Ajouter une pièce
                    </button>
                </div>
                <div class="card-body">
                    <?php if ($workItems): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Référence</th>
                                        <th>Désignation</th>
                                        <th>Quantité</th>
                                        <th>Prix unitaire</th>
                                        <th>Total</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $totalCost = 0;
                                    foreach ($workItems as $item): 
                                        $itemTotal = $item['quantity_used'] * $item['unit_cost'];
                                        $totalCost += $itemTotal;
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['reference']); ?></td>
                                            <td><?php echo htmlspecialchars($item['designation']); ?></td>
                                            <td><?php echo $item['quantity_used']; ?> <?php echo htmlspecialchars($item['unite']); ?></td>
                                            <td><?php echo number_format($item['unit_cost'], 2); ?> MAD</td>
                                            <td><?php echo number_format($itemTotal, 2); ?> MAD</td>
                                            <td><?php echo htmlspecialchars($item['notes'] ?? '-'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-primary">
                                        <th colspan="5">Total Coût:</th>
                                        <th><?php echo number_format($totalCost, 2); ?> MAD</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Aucune pièce utilisée</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Audit Log -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        Journal des modifications
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Action</th>
                                    <th>Utilisateur</th>
                                    <th>Détails</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($auditLogs as $log): ?>
                                    <tr>
                                        <td><?php echo $log['formatted_time']; ?></td>
                                        <td>
                                            <?php
                                            $actionLabels = [
                                                'assignment' => 'Assignation',
                                                'work_started' => 'Début travail',
                                                'work_ended' => 'Fin travail',
                                                'session_started' => 'Session démarrée',
                                                'session_paused' => 'Session mise en pause',
                                                'session_resumed' => 'Session reprise',
                                                'session_ended' => 'Session terminée',
                                                'item_added' => 'Pièce ajoutée',
                                                'item_removed' => 'Pièce retirée'
                                            ];
                                            ?>
                                            <span class="badge bg-info">
                                                <?php echo $actionLabels[$log['action_type']] ?? $log['action_type']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($log['performed_by_name']); ?></td>
                                        <td>
                                            <?php if ($log['field_name']): ?>
                                                <strong><?php echo htmlspecialchars($log['field_name']); ?>:</strong><br>
                                            <?php endif; ?>
                                            <?php 
                                            if ($log['old_value'] || $log['new_value']) {
                                                echo '<small class="text-muted">';
                                                if ($log['old_value']) {
                                                    echo 'Avant: ' . htmlspecialchars(substr($log['old_value'], 0, 100));
                                                }
                                                if ($log['new_value']) {
                                                    echo '<br>Après: ' . htmlspecialchars(substr($log['new_value'], 0, 100));
                                                }
                                                echo '</small>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card">
                <div class="card-body">
                    <a href="admin_breakdowns_enhanced.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Retour à la liste
                    </a>
                    <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                        <i class="fas fa-print me-2"></i>Imprimer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Include all modal components -->
    <?php include __DIR__ . '/worker_assignment_modal.php'; ?>
    <?php include __DIR__ . '/inventory_integration_modal.php'; ?>
    <?php include __DIR__ . '/time_tracking_interface.php'; ?>
    <?php include __DIR__ . '/audit_interface.php'; ?>

    <script>
        // Initialize all interfaces
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize time tracking if assignment exists
            <?php if ($assignment): ?>
                initTimeTracking(<?php echo $reportId; ?>, <?php echo $assignment['id']; ?>);
            <?php endif; ?>
            
            // Initialize audit interface
            initAuditInterface(<?php echo $reportId; ?>);
        });

        // Print function
        function printPage() {
            window.print();
        }
    </script>
</body>
</html>

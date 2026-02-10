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

$currentUserId = (int)($_SESSION['user_id'] ?? 0);

// Handle assignment and start/end work
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
                $stmt->execute([$assignedTo, $currentUserId, $reportId]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO breakdown_assignments (report_id, assigned_to_user_id, assigned_by_user_id, assigned_at) VALUES (?,?,?, NOW())");
                $stmt->execute([$reportId, $assignedTo, $currentUserId]);
            }

            $pdo->prepare("UPDATE breakdown_reports SET status = 'assigne' WHERE id = ?")->execute([$reportId]);

            // Create notification for assigned agent/technician
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, entity_type, entity_id, is_read) VALUES (?, ?, ?, ?, ?, 0)");
            $stmt->execute([
                (int)$assignedTo,
                'breakdown_assigned',
                'breakdown_report',
                (int)$reportId,
                0
            ]);

            $pdo->commit();
            $success_message = 'Incident assigné et notification envoyée.';
        } elseif ($action === 'start') {
            $stmt = $pdo->prepare("SELECT id FROM breakdown_assignments WHERE report_id = ? AND assigned_to_user_id = ?");
            $stmt->execute([$reportId, (int)($_POST['assigned_to_user_id'] ?? 0)]);
            $assignment = $stmt->fetch();
            if (!$assignment) throw new Exception('Aucune assignation trouvée.');

            $pdo->prepare("UPDATE breakdown_assignments SET started_at = NOW() WHERE id = ?")->execute([$assignment['id']]);
            $pdo->prepare("UPDATE breakdown_reports SET status = 'en_cours' WHERE id = ?")->execute([$reportId]);
            $success_message = 'Intervention démarrée.';
        } elseif ($action === 'end') {
            $notes = trim($_POST['notes'] ?? '');
            $stmt = $pdo->prepare("SELECT id, started_at FROM breakdown_assignments WHERE report_id = ?");
            $stmt->execute([$reportId]);
            $assignment = $stmt->fetch();
            if (!$assignment || empty($assignment['started_at'])) throw new Exception("L'intervention n'a pas encore démarré.");

            $pdo->prepare("UPDATE breakdown_assignments SET ended_at = NOW(), notes = ? WHERE id = ?")->execute([$notes, $assignment['id']]);
            $pdo->prepare("UPDATE breakdown_reports SET status = 'termine' WHERE id = ?")->execute([$reportId]);
            $success_message = 'Intervention terminée.';
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error_message = $e->getMessage();
    }
}

// Load report data
$report = null;
$assignment = null;

try {
    $sql = "
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
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$reportId]);
    $report = $stmt->fetch();

    if ($report) {
        // Load assignment
        $stmt = $pdo->prepare("SELECT * FROM breakdown_assignments WHERE report_id = ? ORDER BY assigned_at DESC LIMIT 1");
        $stmt->execute([$reportId]);
        $assignment = $stmt->fetch();
    }
} catch (Exception $e) {
    $error_message = 'Erreur lors du chargement: ' . $e->getMessage();
}

if (!$report) {
    http_response_code(404);
    echo 'Incident introuvable.';
    exit;
}

// Mark related notifications as read for this user
try {
    $pdo->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND entity_type = 'breakdown_report' AND entity_id = ? AND is_read = 0")
        ->execute([$currentUserId, $reportId]);
} catch (Exception $e) {
}

// Load technicians/agents
$technicians = [];
try {
    // First try technicians
    $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE role = 'technician' AND is_active = 1 ORDER BY full_name");
    $stmt->execute();
    $technicians = $stmt->fetchAll();
    
    // If no technicians, try agents
    if (empty($technicians)) {
        $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE role = 'agent' AND is_active = 1 ORDER BY full_name");
        $stmt->execute();
        $technicians = $stmt->fetchAll();
    }
    
    // If still no technicians/agents, try all active users except drivers
    if (empty($technicians)) {
        $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE role != 'driver' AND is_active = 1 ORDER BY full_name");
        $stmt->execute();
        $technicians = $stmt->fetchAll();
    }
} catch (Exception $e) {
    $technicians = [];
}

$driverName = trim(($report['driver_prenom'] ?? '') . ' ' . ($report['driver_nom'] ?? ''));
$driverName = $driverName !== '' ? $driverName : '-';

$page_title = 'Incident ' . ($report['report_ref'] ?? '');
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
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="mb-2">
                        <i class="fas fa-exclamation-triangle me-3"></i>
                        <?php echo htmlspecialchars($page_title); ?>
                    </h1>
                    <p class="text-muted">Détails et suivi de l'incident</p>
                </div>
            </div>
            
            <!-- Alert Messages -->
            <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <div class="row g-3">
                <div class="col-lg-7">
                    <div class="card mb-3">
                        <div class="card-header"><h5 class="mb-0">Détails</h5></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-control bg-light">Type: <strong><?php echo htmlspecialchars($report['category']); ?></strong></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-control bg-light">Urgence: <strong><?php echo htmlspecialchars($report['urgency']); ?></strong></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-control bg-light">Kilométrage: <strong><?php echo $report['kilometrage'] !== null ? (int)$report['kilometrage'] : '-'; ?></strong></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-control bg-light">Statut: <strong><?php echo htmlspecialchars($report['status']); ?></strong></div>
                                </div>
                                <div class="col-12">
                                    <div class="form-control bg-light">PAN: <strong><?php echo htmlspecialchars(($report['pan_code'] ?? '-') . (($report['label_fr'] ?? '') ? ' - ' . $report['label_fr'] : '')); ?></strong></div>
                                </div>
                                <div class="col-12">
                                    <div class="form-control bg-light">Chauffeur: <strong><?php echo htmlspecialchars($driverName); ?></strong> <?php echo ($report['driver_phone'] ?? '') ? ' - ' . htmlspecialchars($report['driver_phone']) : ''; ?></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Message</label>
                                    <div class="p-3 border rounded bg-light" style="white-space: pre-wrap;"><?php echo htmlspecialchars($report['message_text'] ?? '-'); ?></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Message vocal</label>
                                    <?php if (!empty($report['audio_path'])): ?>
                                        <audio controls style="width:100%">
                                            <source src="<?php echo htmlspecialchars($report['audio_path']); ?>">
                                        </audio>
                                    <?php else: ?>
                                        <div class="text-muted">—</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card mb-3">
                        <div class="card-header"><h5 class="mb-0">Affectation d'agent/technicien</h5></div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="assign">
                                <div class="mb-3">
                                    <label class="form-label">Agent/Technicien *</label>
                                    <select class="form-select" name="assigned_to_user_id" required>
                                        <option value="">Sélectionner</option>
                                        <?php foreach ($technicians as $t): ?>
                                            <option value="<?php echo (int)$t['id']; ?>" <?php echo ($assignment && (int)($assignment['assigned_to_user_id'] ?? 0) === (int)$t['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($t['full_name'] ?? ('User #' . $t['id'])); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-user-check"></i> Assigner
                                </button>
                            </form>

                            <?php if ($assignment && !empty($assignment['assigned_at'])): ?>
                                <hr>
                                <div class="text-muted small">
                                    Assigné le: <?php echo date('d/m/Y H:i', strtotime($assignment['assigned_at'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($assignment): ?>
                    <div class="card mb-3">
                        <div class="card-header"><h5 class="mb-0">Suivi de l'intervention</h5></div>
                        <div class="card-body">
                            <div class="mb-2">
                                <strong>Démarré:</strong> <?php echo $assignment['started_at'] ? date('d/m/Y H:i', strtotime($assignment['started_at'])) : '-'; ?>
                            </div>
                            <div class="mb-2">
                                <strong>Terminé:</strong> <?php echo $assignment['ended_at'] ? date('d/m/Y H:i', strtotime($assignment['ended_at'])) : '-'; ?>
                            </div>
                            <?php if ($assignment['started_at'] && $assignment['ended_at']): ?>
                                <?php
                                    $start = new DateTime($assignment['started_at']);
                                    $end = new DateTime($assignment['ended_at']);
                                    $duration = $end->diff($start);
                                    $hours = $duration->h + $duration->days * 24 + $duration->i / 60;
                                ?>
                                <div class="mb-2">
                                    <strong>Durée:</strong> <?php echo number_format($hours, 2); ?> heures
                                </div>
                            <?php endif; ?>
                            <?php if ($assignment['notes']): ?>
                                <div class="mb-2">
                                    <strong>Notes:</strong> <?php echo htmlspecialchars($assignment['notes']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!$assignment['started_at']): ?>
                            <form method="POST" class="mt-3">
                                <input type="hidden" name="action" value="start">
                                <input type="hidden" name="assigned_to_user_id" value="<?php echo $assignment['assigned_to_user_id']; ?>">
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-play"></i> Démarrer
                                </button>
                            </form>
                            <?php elseif ($assignment['started_at'] && !$assignment['ended_at']): ?>
                            <form method="POST" class="mt-3">
                                <input type="hidden" name="action" value="end">
                                <div class="mb-3">
                                    <label class="form-label">Notes de fin</label>
                                    <textarea class="form-control" name="notes" rows="3"></textarea>
                                </div>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-stop"></i> Terminer
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>

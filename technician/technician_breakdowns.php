<?php
require_once 'config.php';
require_once 'includes/functions.php';

require_login();
$user = get_logged_in_user();
$role = $user['role'] ?? '';
if ($role !== 'technician') {
    http_response_code(403);
    echo 'Accès refusé.';
    exit;
}

$page_title = 'Mes Interventions';

$database = new Database();
$pdo = $database->connect();

$reports = [];
$error_message = '';
$success_message = '';

// Detect users schema
$userCols = [];
try {
    $userCols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $userCols = [];
}
$hasIsActive = in_array('is_active', $userCols, true);

// Handle start/end work
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $reportId = (int)($_POST['report_id'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');

    try {
        $pdo->beginTransaction();

        if ($action === 'start') {
            // Ensure we have an assignment record
            $stmt = $pdo->prepare("SELECT id FROM breakdown_assignments WHERE report_id = ? AND assigned_to_user_id = ?");
            $stmt->execute([$reportId, (int)$user['id']]);
            $assignment = $stmt->fetch();

            if (!$assignment) {
                // Create assignment if missing
                $stmt = $pdo->prepare("INSERT INTO breakdown_assignments (report_id, assigned_to_user_id, assigned_by_user_id, assigned_at) VALUES (?,?,?,NOW())");
                $stmt->execute([$reportId, (int)$user['id'], (int)$user['id']]);
                $assignmentId = (int)$pdo->lastInsertId();
            } else {
                $assignmentId = (int)$assignment['id'];
            }

            $pdo->prepare("UPDATE breakdown_assignments SET started_at = NOW() WHERE id = ?")->execute([$assignmentId]);
            $pdo->prepare("UPDATE breakdown_reports SET status = 'en_cours' WHERE id = ?")->execute([$reportId]);
            $success_message = 'Intervention démarrée';
        } elseif ($action === 'end') {
            $stmt = $pdo->prepare("SELECT id, started_at FROM breakdown_assignments WHERE report_id = ? AND assigned_to_user_id = ?");
            $stmt->execute([$reportId, (int)$user['id']]);
            $assignment = $stmt->fetch();

            if (!$assignment || empty($assignment['started_at'])) {
                throw new Exception("L'intervention n'a pas encore démarré.");
            }

            $pdo->prepare("UPDATE breakdown_assignments SET ended_at = NOW(), notes = ? WHERE id = ?")->execute([$notes, $assignment['id']]);
            $pdo->prepare("UPDATE breakdown_reports SET status = 'termine' WHERE id = ?")->execute([$reportId]);
            $success_message = 'Intervention terminée';
        }
        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error_message = $e->getMessage();
    }
}

// Load assigned reports
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'pan_issues'");
    $hasPan = (bool)$stmt->fetch();

    $sql = "
        SELECT 
            br.*, 
            b.bus_number, b.license_plate,
            d.nom as driver_nom, d.prenom as driver_prenom, d.phone as driver_phone,
            pi.pan_code, pi.label_fr,
            ba.id as assignment_id,
            ba.started_at,
            ba.ended_at,
            ba.notes as assignment_notes
        FROM breakdown_reports br
        LEFT JOIN buses b ON br.bus_id = b.id
        LEFT JOIN drivers d ON br.driver_id = d.id
        LEFT JOIN pan_issues pi ON br.pan_issue_id = pi.id
        LEFT JOIN breakdown_assignments ba ON br.id = ba.report_id AND ba.assigned_to_user_id = ?
        WHERE br.id IN (
            SELECT report_id FROM breakdown_assignments WHERE assigned_to_user_id = ?
        )
        ORDER BY br.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([(int)$user['id'], (int)$user['id']]);
    $reports = $stmt->fetchAll();
} catch (Exception $e) {
    $error_message = 'Erreur lors du chargement: ' . $e->getMessage();
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
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="mb-2"><i class="fas fa-screwdriver-wrench me-3"></i><?php echo htmlspecialchars($page_title); ?></h1>
                    <p class="text-muted">Interventions qui me sont assignées</p>
                </div>
            </div>

            <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Liste des interventions</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($reports)): ?>
                        <div class="text-center py-4 text-muted">Aucune intervention assignée</div>
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
                                        <th>KM</th>
                                        <th>Statut</th>
                                        <th>Démarré</th>
                                        <th>Terminé</th>
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
                                            <td><?php echo $r['kilometrage'] !== null ? (int)$r['kilometrage'] : '-'; ?></td>
                                            <td><span class="badge bg-<?php echo status_badge($r['status']); ?>"><?php echo htmlspecialchars($r['status']); ?></span></td>
                                            <td><?php echo $r['started_at'] ? date('d/m H:i', strtotime($r['started_at'])) : '-'; ?></td>
                                            <td><?php echo $r['ended_at'] ? date('d/m H:i', strtotime($r['ended_at'])) : '-'; ?></td>
                                            <td>
                                                <div class="btn-group-vertical btn-group-sm">
                                                    <a class="btn btn-sm btn-outline-primary" href="admin_breakdown_view.php?id=<?php echo (int)$r['id']; ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if (empty($r['started_at'])): ?>
                                                        <form method="POST" style="margin:0;">
                                                            <input type="hidden" name="action" value="start">
                                                            <input type="hidden" name="report_id" value="<?php echo (int)$r['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Démarrer cette intervention?')">
                                                                <i class="fas fa-play"></i>
                                                            </button>
                                                        </form>
                                                    <?php elseif (empty($r['ended_at'])): ?>
                                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#endModal<?php echo (int)$r['id']; ?>">
                                                            <i class="fas fa-stop"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
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

    <!-- End Intervention Modals -->
    <?php foreach ($reports as $r): ?>
        <?php if (!empty($r['started_at']) && empty($r['ended_at'])): ?>
            <div class="modal fade" id="endModal<?php echo (int)$r['id']; ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST">
                            <div class="modal-header">
                                <h5 class="modal-title">Terminer l'intervention</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="action" value="end">
                                <input type="hidden" name="report_id" value="<?php echo (int)$r['id']; ?>">
                                <div class="mb-3">
                                    <label class="form-label">Notes (optionnel)</label>
                                    <textarea class="form-control" name="notes" rows="4" placeholder="Décrivez ce qui a été fait..."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-stop me-2"></i>Terminer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php include __DIR__ . '/includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

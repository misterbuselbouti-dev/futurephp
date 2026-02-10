<?php
require_once 'config.php';
require_once 'includes/functions.php';

require_login();
$user = get_logged_in_user();
$role = $user['role'] ?? '';
if (!in_array($role, ['admin', 'maintenance_manager', 'technician'], true)) {
    http_response_code(403);
    echo 'Accès refusé.';
    exit;
}

$busId = isset($_GET['bus']) ? (int)$_GET['bus'] : 0;
if (!$busId) {
    http_response_code(400);
    echo 'Paramètre bus manquant.';
    exit;
}

$page_title = 'Maintenance - Bus';

$database = new Database();
$pdo = $database->connect();

$bus = null;
$reports = [];
$error_message = '';

try {
    $stmt = $pdo->prepare("SELECT * FROM buses WHERE id = ?");
    $stmt->execute([$busId]);
    $bus = $stmt->fetch();

    $stmt = $pdo->query("SHOW TABLES LIKE 'breakdown_reports'");
    $hasBreakdowns = (bool)$stmt->fetch();

    if ($hasBreakdowns) {
        $stmt = $pdo->prepare("
            SELECT 
                br.*, 
                pi.pan_code, pi.label_fr
            FROM breakdown_reports br
            LEFT JOIN pan_issues pi ON br.pan_issue_id = pi.id
            WHERE br.bus_id = ?
            ORDER BY br.created_at DESC
        ");
        $stmt->execute([$busId]);
        $reports = $stmt->fetchAll();
    }
} catch (Exception $e) {
    $error_message = $e->getMessage();
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

?><!DOCTYPE html>
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
                <div class="col-12 d-flex justify-content-between align-items-start">
                    <div>
                        <h1 class="mb-1"><i class="fas fa-screwdriver-wrench me-3"></i>Maintenance</h1>
                        <div class="text-muted">
                            Bus: <?php echo htmlspecialchars($bus['bus_number'] ?? ('#' . $busId)); ?>
                            <?php if (!empty($bus['license_plate'])): ?>
                                - <?php echo htmlspecialchars($bus['license_plate']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <a href="buses.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
                    </div>
                </div>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Incidents (Breakdowns)</h5>
                    <a class="btn btn-outline-primary" href="admin_breakdowns.php">
                        <i class="fas fa-list"></i> Tous les incidents
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($reports)): ?>
                        <div class="text-muted">Aucun incident enregistré pour ce bus.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Réf</th>
                                        <th>Type</th>
                                        <th>PAN</th>
                                        <th>Urgence</th>
                                        <th>KM</th>
                                        <th>Statut</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reports as $r): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($r['report_ref']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($r['category']); ?></td>
                                            <td><?php echo htmlspecialchars(($r['pan_code'] ?? '-') . (($r['label_fr'] ?? '') ? ' - ' . $r['label_fr'] : '')); ?></td>
                                            <td><?php echo htmlspecialchars($r['urgency']); ?></td>
                                            <td><?php echo $r['kilometrage'] !== null ? (int)$r['kilometrage'] : '-'; ?></td>
                                            <td><span class="badge bg-<?php echo status_badge($r['status']); ?>"><?php echo htmlspecialchars($r['status']); ?></span></td>
                                            <td><?php echo $r['created_at'] ? date('d/m/Y H:i', strtotime($r['created_at'])) : '-'; ?></td>
                                            <td>
                                                <a class="btn btn-sm btn-outline-primary" href="admin_breakdown_view.php?id=<?php echo (int)$r['id']; ?>">
                                                    <i class="fas fa-eye"></i>
                                                </a>
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

    <?php include __DIR__ . '/includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

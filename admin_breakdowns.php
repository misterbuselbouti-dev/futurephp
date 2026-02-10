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

$page_title = 'Incidents (Maintenance)';

$database = new Database();
$pdo = $database->connect();

$reports = [];
$error_message = '';

try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'pan_issues'");
    $hasPan = (bool)$stmt->fetch();

    $sql = "
        SELECT 
            br.*, 
            b.bus_number, b.license_plate,
            d.nom as driver_nom, d.prenom as driver_prenom, d.phone as driver_phone,
            pi.pan_code, pi.label_fr
        FROM breakdown_reports br
        LEFT JOIN buses b ON br.bus_id = b.id
        LEFT JOIN drivers d ON br.driver_id = d.id
    ";

    if ($hasPan) {
        $sql .= " LEFT JOIN pan_issues pi ON br.pan_issue_id = pi.id ";
    } else {
        $sql .= " LEFT JOIN pan_issues pi ON 1=0 ";
    }

    $sql .= " ORDER BY br.created_at DESC ";

    $reports = $pdo->query($sql)->fetchAll();
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
                <div class="col-12">
                    <h1 class="mb-2"><i class="fas fa-screwdriver-wrench me-3"></i><?php echo htmlspecialchars($page_title); ?></h1>
                    <p class="text-muted">Réception des incidents envoyés par les chauffeurs</p>
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
                    <h5 class="mb-0">Liste des incidents</h5>
                    <a class="btn btn-outline-primary" href="notifications.php">
                        <i class="fas fa-bell"></i> Notifications
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($reports)): ?>
                        <div class="text-center py-4 text-muted">Aucun incident</div>
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
                                        <th>Kilométrage</th>
                                        <th>Statut</th>
                                        <th>Date</th>
                                        <th>Action</th>
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

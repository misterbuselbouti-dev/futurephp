<?php
// FUTURE AUTOMOTIVE - Purchase Performance Dashboard
// لوحة تحقق أداء المشتريات البسيطة والذكية

require_once 'config_achat_hostinger.php';
require_once 'config.php';
require_login();

$page_title = 'Rapport Performance Achats';
$pdo = null;
$performance_data = [];
$total_stats = [];

try {
    $db = new DatabaseAchat();
    $pdo = $db->connect();
    
    // Get supplier performance summary
    $stmt = $pdo->query("
        SELECT 
            s.id as supplier_id,
            s.nom_fournisseur,
            COUNT(bc.id) as total_bc,
            COALESCE(SUM(bc.total_ttc), 0) as total_bc_amount,
            COUNT(be.id) as total_be,
            COALESCE(
                (SELECT SUM(bei.quantite_recue * bci.unit_price)
                 FROM be_items bei
                 INNER JOIN bc_items bci ON bei.bc_item_id = bci.id
                 WHERE bei.be_id IN (
                     SELECT be2.id FROM bons_entree be2 
                     INNER JOIN bons_commande bc2 ON be2.bc_id = bc2.id
                     INNER JOIN demandes_prix dp2 ON bc2.dp_id = dp2.id
                     WHERE dp2.fournisseur_id = s.id
                 )
                ), 0
            ) as total_be_amount,
            CASE 
                WHEN COALESCE(SUM(bc.total_ttc), 0) = 0 THEN 0
                ELSE ROUND((
                    COALESCE(
                        (SELECT SUM(bei.quantite_recue * bci.unit_price)
                         FROM be_items bei
                         INNER JOIN bc_items bci ON bei.bc_item_id = bci.id
                         WHERE bei.be_id IN (
                             SELECT be2.id FROM bons_entree be2 
                             INNER JOIN bons_commande bc2 ON be2.bc_id = bc2.id
                             INNER JOIN demandes_prix dp2 ON bc2.dp_id = dp2.id
                             WHERE dp2.fournisseur_id = s.id
                         )
                        ), 0
                    ) / COALESCE(SUM(bc.total_ttc), 0)) * 100, 2)
            END as receipt_rate,
            CASE 
                WHEN COUNT(be.id) = 0 THEN 'Non reçu'
                WHEN COUNT(be.id) = COUNT(bc.id) THEN 'Complet'
                ELSE 'Partiel'
            END as delivery_status
        FROM suppliers s
        INNER JOIN demandes_prix dp ON s.id = dp.fournisseur_id
        INNER JOIN bons_commande bc ON dp.id = bc.dp_id
        LEFT JOIN bons_entree be ON bc.id = be.bc_id
        GROUP BY s.id, s.nom_fournisseur
        ORDER BY receipt_rate DESC, total_bc_amount DESC
    ");
    
    $performance_data = $stmt->fetchAll();
    
    // Calculate total statistics
    $total_bc_count = 0;
    $total_be_count = 0;
    $total_bc_sum = 0;
    $total_be_sum = 0;
    
    foreach ($performance_data as $supplier) {
        $total_bc_count += $supplier['total_bc'];
        $total_be_count += $supplier['total_be'];
        $total_bc_sum += $supplier['total_bc_amount'];
        $total_be_sum += $supplier['total_be_amount'];
    }
    
    $total_stats = [
        'total_suppliers' => count($performance_data),
        'total_bc_count' => $total_bc_count,
        'total_be_count' => $total_be_count,
        'total_bc_amount' => $total_bc_sum,
        'total_be_amount' => $total_be_sum,
        'overall_receipt_rate' => $total_bc_sum > 0 ? round(($total_be_sum / $total_bc_sum) * 100, 2) : 0
    ];
    
} catch (Exception $e) {
    $error_message = "Erreur lors du chargement des données: " . $e->getMessage();
}

// Helper function to get performance badge
function getPerformanceBadge($rate) {
    if ($rate >= 80) return ['class' => 'success', 'icon' => '🟢', 'text' => 'Excellent'];
    if ($rate >= 60) return ['class' => 'warning', 'icon' => '🟡', 'text' => 'Moyen'];
    return ['class' => 'danger', 'icon' => '🔴', 'text' => 'Faible'];
}
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .main-content { margin-left: 260px; padding: 2rem; }
        .performance-card { border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
        .performance-card .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .stat-card { border-radius: 10px; border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.06); transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-card .card-body { padding: 1.5rem; }
        .stat-number { font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem; }
        .performance-table th { background: #f8f9fa; font-weight: 600; border-bottom: 2px solid #dee2e6; }
        .performance-badge { padding: 0.4rem 0.8rem; border-radius: 20px; font-size: 0.85rem; font-weight: 500; }
        .rate-bar { height: 8px; border-radius: 4px; background: #e9ecef; overflow: hidden; }
        .rate-fill { height: 100%; transition: width 0.3s ease; }
        .rate-excellent { background: #28a745; }
        .rate-good { background: #ffc107; }
        .rate-poor { background: #dc3545; }
        @media (max-width: 992px) { .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="buses.php">Accueil</a></li>
                    <li class="breadcrumb-item active"><?php echo $page_title; ?></li>
                </ol>
            </nav>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="mb-0"><i class="fas fa-chart-line me-2"></i><?php echo $page_title; ?></h1>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" onclick="exportData()">
                        <i class="fas fa-download me-1"></i>Exporter Excel
                    </button>
                    <button class="btn btn-outline-secondary" onclick="refreshData()">
                        <i class="fas fa-sync-alt me-1"></i>Actualiser
                    </button>
                </div>
            </div>

            <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_message; ?>
            </div>
            <?php endif; ?>

            <!-- Summary Statistics -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <div class="stat-number text-primary"><?php echo $total_stats['total_suppliers'] ?? 0; ?></div>
                            <div class="text-muted">Total Fournisseurs</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <div class="stat-number text-info"><?php echo $total_stats['total_bc_count'] ?? 0; ?></div>
                            <div class="text-muted">Total BC</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <div class="stat-number text-success"><?php echo $total_stats['total_be_count'] ?? 0; ?></div>
                            <div class="text-muted">Total BE</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <div class="stat-number text-warning"><?php echo $total_stats['overall_receipt_rate'] ?? 0; ?>%</div>
                            <div class="text-muted">Taux de Réception</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Table -->
            <div class="card performance-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i>Performance Fournisseurs</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($performance_data)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Aucune donnée d'achat disponible</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover performance-table">
                            <thead>
                                <tr>
                                    <th>Fournisseur</th>
                                    <th>Nb BC</th>
                                    <th>Montant BC</th>
                                    <th>Nb BE</th>
                                    <th>Montant BE</th>
                                    <th>Taux Réception</th>
                                    <th>Statut Livraison</th>
                                    <th>Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($performance_data as $supplier): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($supplier['nom_fournisseur']); ?></strong>
                                    </td>
                                    <td><?php echo $supplier['total_bc']; ?></td>
                                    <td><?php echo number_format($supplier['total_bc_amount'], 2, ',', ' '); ?> DH</td>
                                    <td><?php echo $supplier['total_be']; ?></td>
                                    <td><?php echo number_format($supplier['total_be_amount'], 2, ',', ' '); ?> DH</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="rate-bar me-2" style="width: 60px;">
                                                <div class="rate-fill <?php 
                                                    echo $supplier['receipt_rate'] >= 80 ? 'rate-excellent' : 
                                                         ($supplier['receipt_rate'] >= 60 ? 'rate-good' : 'rate-poor'); 
                                                ?>" style="width: <?php echo $supplier['receipt_rate']; ?>%;"></div>
                                            </div>
                                            <span><?php echo $supplier['receipt_rate']; ?>%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $supplier['delivery_status'] === 'Complet' ? 'success' : 
                                                 ($supplier['delivery_status'] === 'Partiel' ? 'warning' : 'secondary'); 
                                        ?>">
                                            <?php echo $supplier['delivery_status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $badge = getPerformanceBadge($supplier['receipt_rate']);
                                        ?>
                                        <span class="performance-badge bg-<?php echo $badge['class']; ?> text-white">
                                            <?php echo $badge['icon'] . ' ' . $badge['text']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Performance Insights -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Insights Performance</h6>
                        </div>
                        <div class="card-body">
                            <?php
                            $excellent_count = 0;
                            $poor_count = 0;
                            foreach ($performance_data as $supplier) {
                                if ($supplier['receipt_rate'] >= 80) $excellent_count++;
                                if ($supplier['receipt_rate'] < 60) $poor_count++;
                            }
                            ?>
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <?php echo $excellent_count; ?> fournisseurs avec performance excellente (80%+)
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                    <?php echo $poor_count; ?> fournisseurs nécessitent un suivi (< 60%)
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-info-circle text-info me-2"></i>
                                    Taux de réception global: <?php echo $total_stats['overall_receipt_rate'] ?? 0; ?>%
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Répartition Performance</h6>
                        </div>
                        <div class="card-body">
                            <?php
                            $total = count($performance_data);
                            $excellent_percent = $total > 0 ? round(($excellent_count / $total) * 100, 1) : 0;
                            $poor_percent = $total > 0 ? round(($poor_count / $total) * 100, 1) : 0;
                            $medium_percent = 100 - $excellent_percent - $poor_percent;
                            ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Excellent</span>
                                    <span><?php echo $excellent_percent; ?>%</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success" style="width: <?php echo $excellent_percent; ?>%"></div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Moyen</span>
                                    <span><?php echo $medium_percent; ?>%</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-warning" style="width: <?php echo $medium_percent; ?>%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Faible</span>
                                    <span><?php echo $poor_percent; ?>%</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-danger" style="width: <?php echo $poor_percent; ?>%"></div>
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
        function exportData() {
            // Simple CSV export
            let csv = 'Fournisseur,Nb BC,Montant BC,Nb BE,Montant BE,Taux Réception,Statut Livraison,Performance\n';
            
            <?php foreach ($performance_data as $supplier): ?>
            csv += '<?php echo htmlspecialchars($supplier['nom_fournisseur']); ?>,' +
                  '<?php echo $supplier['total_bc']; ?>,' +
                  '<?php echo $supplier['total_bc_amount']; ?>,' +
                  '<?php echo $supplier['total_be']; ?>,' +
                  '<?php echo $supplier['total_be_amount']; ?>,' +
                  '<?php echo $supplier['receipt_rate']; ?>%,' +
                  '<?php echo $supplier['delivery_status']; ?>,' +
                  '<?php 
                    $badge = getPerformanceBadge($supplier['receipt_rate']);
                    echo $badge['text']; 
                    ?>' + '\n';
            <?php endforeach; ?>
            
            const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'purchase_performance_' + new Date().toISOString().split('T')[0] + '.csv';
            link.click();
        }

        function refreshData() {
            location.reload();
        }
    </script>
</body>
</html>

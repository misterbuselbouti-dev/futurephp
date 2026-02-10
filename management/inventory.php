<?php
// FUTURE AUTOMOTIVE - Vue synthèse du stock
// S'appuie sur articles_stockables + stock_by_region (Tétouan / Ksar)

require_once 'config.php';
require_once 'includes/functions.php';

require_login();

$page_title = 'Stock global';

// Récupération des statistiques et de la liste pour la vue
$total_articles = 0;
$total_stock_tetouan = 0;
$total_stock_ksar = 0;
$low_stock_rows = 0;
$rupture_rows = 0;
$rows = [];
$error_message = '';
$total_quantity = 0;
$total_value = 0.0;
$total_value_tetouan = 0.0;
$total_value_ksar = 0.0;

try {
    $db = (new Database())->connect();

    // Statistiques globales
    // 1) Nombre d'articles actifs
    $stmt = $db->query("SELECT COUNT(*) AS total FROM articles_catalogue");
    $total_articles = (int)($stmt->fetch()['total'] ?? 0);

    // 2) Stock total par région
    $stmt = $db->query("
        SELECT 
            SUM(stock_ksar) as total_ksar,
            SUM(stock_tetouan) as total_tetouan,
            SUM(stock_actuel) as total_global
        FROM articles_catalogue
    ");
    $stock_totals = $stmt->fetch();
    $total_stock_ksar = (float)$stock_totals['total_ksar'];
    $total_stock_tetouan = (float)$stock_totals['total_tetouan'];
    $total_quantity = (float)$stock_totals['total_global'];

    // 3) Détails par article
    $sql = "
        SELECT a.id, a.code_article as reference, a.designation, a.prix_unitaire, a.categorie,
               a.stock_ksar, a.stock_tetouan, a.stock_actuel, a.stock_minimal
        FROM articles_catalogue a
        ORDER BY a.code_article
    ";
    $stmt = $db->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $r) {
        $st = (float)$r['stock_tetouan'];
        $sk = (float)$r['stock_ksar'];
        $tot = (float)$r['stock_actuel'];
        $unit_price = (float)$r['prix_unitaire'];
        
        // Accumuler القيم المالية لكل منطقة
        $total_value += $tot * $unit_price;
        $total_value_tetouan += $st * $unit_price;
        $total_value_ksar += $sk * $unit_price;
        
        // Compter les stocks bas et rupture
        $minimal = (float)$r['stock_minimal'];
        if ($tot <= $minimal) {
            $rupture_rows++;
        } elseif ($tot <= ($minimal * 2)) {
            $low_stock_rows++;
        }
    }

} catch (Exception $e) {
    $error_message = $e->getMessage();
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
        .main-content { margin-left: 260px; padding: 2rem; }
        @media (max-width: 992px) { .main-content { margin-left: 0; } }
        .stock-card { border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
        .stock-card .card-body { display: flex; justify-content: space-between; align-items: center; }
        .stock-card h3 { margin: 0; font-size: 1.6rem; }
        .stock-card p { margin: 0; font-size: 0.9rem; }
        .badge-low { background: #fef3c7; color: #92400e; }
        .badge-rupture { background: #fee2e2; color: #991b1b; }
        .table-stock th { background: #f1f5f9; font-size: 0.75rem; text-transform: uppercase; }
        .badge-region-tet { background: #dbeafe; color: #1d4ed8; }
        .badge-region-ksar { background: #dcfce7; color: #166534; }
        .breadcrumb { background: transparent; padding: 0; }
    </style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="container-fluid">
        <nav aria-label="breadcrumb" class="mb-2">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="buses.php">Accueil</a></li>
                <li class="breadcrumb-item active">Stock</li>
            </ol>
        </nav>

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <i class="fas fa-boxes-stacked me-2"></i>
                Vue globale du stock
            </h1>
            <div class="d-flex flex-wrap gap-2">
                <a href="articles_stockables.php" class="btn btn-outline-primary">
                    <i class="fas fa-box-open me-1"></i>Articles stockables
                </a>
                <a href="stock_tetouan.php" class="btn btn-outline-info">
                    <i class="fas fa-warehouse me-1"></i>Stock Tétouan
                </a>
                <a href="stock_ksar.php" class="btn btn-outline-success">
                    <i class="fas fa-warehouse me-1"></i>Stock Ksar-Larache
                </a>
            </div>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistiques -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card stock-card border-0">
                    <div class="card-body">
                        <div>
                            <p class="text-muted mb-1">Articles actifs</p>
                            <h3><?php echo number_format($total_articles); ?></h3>
                        </div>
                        <i class="fas fa-box-open fa-2x text-primary opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stock-card border-0">
                    <div class="card-body">
                        <div>
                            <p class="text-muted mb-1">Stock Tétouan</p>
                            <h3><?php echo number_format($total_stock_tetouan, 2, ',', ' '); ?></h3>
                        </div>
                        <i class="fas fa-warehouse fa-2x text-info opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stock-card border-0">
                    <div class="card-body">
                        <div>
                            <p class="text-muted mb-1">Stock Ksar-Larache</p>
                            <h3><?php echo number_format($total_stock_ksar, 2, ',', ' '); ?></h3>
                        </div>
                        <i class="fas fa-warehouse fa-2x text-success opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stock-card border-0">
                    <div class="card-body">
                        <div>
                            <p class="text-muted mb-1">Alertes stock</p>
                            <h3>
                                <span class="badge badge-low"><?php echo $low_stock_rows; ?> faible</span>
                                <span class="badge badge-rupture ms-1"><?php echo $rupture_rows; ?> rupture</span>
                            </h3>
                        </div>
                        <i class="fas fa-triangle-exclamation fa-2x text-warning opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Totaux globaux (quantité + valeurs par région + total) -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card stock-card border-0">
                    <div class="card-body">
                        <div>
                            <p class="text-muted mb-1">Quantité totale en stock</p>
                            <h3><?php echo number_format($total_quantity, 0, ',', ' '); ?></h3>
                        </div>
                        <i class="fas fa-layer-group fa-2x text-secondary opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stock-card border-0">
                    <div class="card-body">
                        <div>
                            <p class="text-muted mb-1">Valeur - Stock Tétouan (MAD)</p>
                            <h3><?php echo number_format($total_value_tetouan, 2, ',', ' '); ?> MAD</h3>
                        </div>
                        <i class="fas fa-warehouse fa-2x text-info opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stock-card border-0">
                    <div class="card-body">
                        <div>
                            <p class="text-muted mb-1">Valeur - Stock Ksar (MAD)</p>
                            <h3><?php echo number_format($total_value_ksar, 2, ',', ' '); ?> MAD</h3>
                        </div>
                        <i class="fas fa-warehouse fa-2x text-success opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stock-card border-0">
                    <div class="card-body">
                        <div>
                            <p class="text-muted mb-1">Valeur financière totale (MAD)</p>
                            <h3><?php echo number_format($total_value, 2, ',', ' '); ?> MAD</h3>
                        </div>
                        <i class="fas fa-money-bill-wave fa-2x text-success opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tableau de synthèse -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span><i class="fas fa-list me-1"></i>Articles et stock par région</span>
                <div class="input-group input-group-sm" style="max-width: 260px;">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" id="searchStock" placeholder="Chercher...">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 table-stock" id="stockTable">
                        <thead>
                            <tr>
                                <th>Réf</th>
                                <th>Désignation</th>
                                <th>Catégorie</th>
                                <th>Prix (MAD)</th>
                                <th>Stock Tétouan</th>
                                <th>Stock Ksar</th>
                                <th>Total</th>
                                <th>Alerte</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="8" class="text-center py-3 text-muted">Aucun article trouvé.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rows as $r):
                                $st = (float)$r['stock_tetouan'];
                                $sk = (float)$r['stock_ksar'];
                                $minimal = (float)$r['stock_minimal'];
                                $tot = (float)$r['stock_actuel'];
                                $badge = '';
                                if ($tot <= $minimal) {
                                    $badge = '<span class="badge badge-rupture">Rupture</span>';
                                } elseif ($tot <= ($minimal * 2)) {
                                    $badge = '<span class="badge badge-low">Faible</span>';
                                }
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($r['reference']); ?></strong></td>
                                <td><?php echo htmlspecialchars($r['designation']); ?></td>
                                <td><?php echo htmlspecialchars($r['categorie'] ?? ''); ?></td>
                                <td><?php echo number_format((float)$r['prix_unitaire'], 2, ',', ' '); ?></td>
                                <td><span class="badge badge-region-tet"><?php echo number_format($st, 2, ',', ' '); ?></span></td>
                                <td><span class="badge badge-region-ksar"><?php echo number_format($sk, 2, ',', ' '); ?></span></td>
                                <td><?php echo number_format($tot, 2, ',', ' '); ?></td>
                                <td><?php echo $badge; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Filtre simple côté client sur le tableau
    document.getElementById('searchStock')?.addEventListener('input', function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#stockTable tbody tr').forEach(tr => {
            tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
</script>
</body>
</html>

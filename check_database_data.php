<?php
// FUTURE AUTOMOTIVE - Check Database Data
// فحص بيانات قاعدة البيانات

require_once 'config_achat_hostinger.php';
require_once 'config.php';

// Vérifier si l'utilisateur est connecté
if (!is_logged_in()) {
    header('Location: login.php');
    exit();
}

$user = get_logged_in_user();
$role = $_SESSION['role'] ?? '';

// Only admin can access this tool
if ($role !== 'admin') {
    http_response_code(403);
    echo 'Accès refusé.';
    exit();
}

$page_title = 'Database Data Check';
$database = new DatabaseAchat();
$conn = $database->connect();

$error_message = '';
$success_message = '';

// Check tables and data
$tables_to_check = [
    'bons_commande' => 'Bon de Commande',
    'demandes_prix' => 'Demande de Prix',
    'fournisseurs' => 'Fournisseurs',
    'articles_catalogue' => 'Articles Catalogue'
];

$table_info = [];
$total_records = 0;

foreach ($tables_to_check as $table_name => $table_description) {
    try {
        // Check if table exists
        $stmt = $conn->query("SHOW TABLES LIKE '$table_name'");
        $table_exists = $stmt->rowCount() > 0;
        
        if ($table_exists) {
            // Get table structure
            $stmt = $conn->query("DESCRIBE $table_name");
            $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get record count
            $stmt = $conn->query("SELECT COUNT(*) as count FROM $table_name");
            $count = $stmt->fetch()['count'];
            
            // Get sample data
            $sample_data = [];
            if ($count > 0) {
                $stmt = $conn->query("SELECT * FROM $table_name LIMIT 5");
                $sample_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            $table_info[$table_name] = [
                'exists' => true,
                'description' => $table_description,
                'structure' => $structure,
                'count' => $count,
                'sample_data' => $sample_data
            ];
            
            $total_records += $count;
        } else {
            $table_info[$table_name] = [
                'exists' => false,
                'description' => $table_description,
                'structure' => [],
                'count' => 0,
                'sample_data' => []
            ];
        }
    } catch (Exception $e) {
        $error_message = "Erreur lors de la vérification de la table $table_name: " . $e->getMessage();
    }
}

// Check for sample BC data
$bc_sample = null;
if ($table_info['bons_commande']['exists'] && $table_info['bons_commande']['count'] > 0) {
    try {
        $stmt = $conn->query("SELECT * FROM bons_commande ORDER BY id DESC LIMIT 10");
        $bc_sample = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_message = "Erreur lors de la récupération des données BC: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo APP_NAME; ?></title>
    
    <!-- Simple Clean Theme -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/simple-theme.css">
    
    <style>
        .main-content {
            margin-left: 260px;
            padding: 2rem;
        }
        
        .workshop-card {
            background-color: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: var(--space-6);
            margin-bottom: var(--space-6);
            transition: transform 0.2s;
        }
        
        .workshop-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .status-success {
            color: var(--success);
            font-weight: bold;
        }
        
        .status-error {
            color: var(--danger);
            font-weight: bold;
        }
        
        .status-warning {
            color: var(--warning);
        }
        
        .status-info {
            color: var(--info);
        }
        
        .table-responsive {
            border-radius: var(--radius);
            overflow: hidden;
        }
        
        .code-block {
            background-color: var(--bg-light);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: var(--space-4);
            font-family: monospace;
            font-size: var(--font-size-sm);
            overflow-x: auto;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: var(--space-4);
            }
        }
    </style>
</head>
<body>
    <!-- Include header -->
    <?php include 'includes/header.php'; ?>
    
    <!-- Include sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-6">
                <div>
                    <h1 class="mb-2">
                        <i class="fas fa-database me-3"></i>
                        Database Data Check
                    </h1>
                    <p class="text-muted mb-0">Bienvenue, <?php echo htmlspecialchars($user['full_name']); ?></p>
                </div>
                <div class="d-flex gap-3">
                    <button class="btn btn-outline-primary" onclick="window.location.href='quick_audit.php'">
                        <i class="fas fa-clipboard-check me-2"></i>Audit
                    </button>
                    <button class="btn btn-outline-success" onclick="window.location.href='remove_unnecessary_files.php'">
                        <i class="fas fa-trash-alt me-2"></i>Nettoyer
                    </button>
                    <button class="btn btn-primary" onclick="window.location.href='dashboard_simple.php'">
                        <i class="fas fa-home me-2"></i>Dashboard
                    </button>
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

            <!-- Summary Card -->
            <div class="workshop-card">
                <h2 class="mb-4">📊 ملخص قاعدة البيانات</h2>
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center">
                            <h3 class="status-success"><?php echo $total_records; ?></h3>
                            <p class="text-muted">إجمالي السجلات</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h3 class="status-info"><?php echo count(array_filter($table_info, fn($t) => $t['exists'])); ?></h3>
                            <p class="text-muted">جداول موجودة</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h3 class="status-warning"><?php echo count(array_filter($table_info, fn($t) => $t['count'] > 0)); ?></h3>
                    <p class="text-muted">جداول ببيانات</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h3 class="status-error"><?php echo count(array_filter($table_info, fn($t) => !$t['exists'])); ?></h3>
                            <p class="text-muted">جداول مفقودة</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Details -->
            <div class="workshop-card">
                <h2 class="mb-4">📋 تفاصيل الجداول</h2>
                
                <?php foreach ($table_info as $table_name => $info): ?>
                    <div class="mb-5">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4>
                                <i class="fas fa-table me-2"></i>
                                <?php echo htmlspecialchars($info['description']); ?>
                                <small class="text-muted">(<?php echo $table_name; ?>)</small>
                            </h4>
                            <div>
                                <?php if ($info['exists']): ?>
                                    <span class="badge bg-success">✅ موجود</span>
                                    <span class="badge bg-info ms-2"><?php echo $info['count']; ?> سجل</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">❌ غير موجود</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($info['exists']): ?>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h5>الهيكل:</h5>
                                    <div class="code-block">
                                        <?php foreach ($info['structure'] as $column): ?>
                                            <div>
                                                <strong><?php echo htmlspecialchars($column['Field']); ?></strong>
                                                <span class="text-muted">(<?php echo htmlspecialchars($column['Type']); ?>)</span>
                                                <?php if ($column['Null'] === 'NO'): ?>
                                                    <span class="badge bg-warning ms-2">NOT NULL</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h5>بيانات تجريبية:</h5>
                                    <?php if (!empty($info['sample_data'])): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <?php foreach ($info['sample_data'][0] as $key => $value): ?>
                                                            <th><?php echo htmlspecialchars($key); ?></th>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($info['sample_data'] as $row): ?>
                                                        <tr>
                                                            <?php foreach ($row as $key => $value): ?>
                                                                <td><?php echo htmlspecialchars($value); ?></td>
                                                            <?php endforeach; ?>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            لا توجد بيانات تجريبية
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                الجدول غير موجود في قاعدة البيانات
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
                <div class="mt-4">
                    <button class="btn btn-primary" onclick="window.location.reload()">
                        <i class="fas fa-redo me-2"></i>
                        تحديث الفحص
                    </button>
                </div>
            </div>

            <!-- BC Sample Data -->
            <?php if ($bc_sample): ?>
                <div class="workshop-card">
                    <h2 class="mb-4">📄 عينة من بيانات Bon de Commande</h2>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Ref BC</th>
                                    <th>ID DP</th>
                                    <th>Date</th>
                                    <th>Total HT</th>
                                    <th>TVA</th>
                                    <th>Total TTC</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bc_sample as $row): ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['ref_bc']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['dp_id']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($row['date_commande'])); ?></td>
                                        <td><?php echo number_format($row['total_ht'], 2, ',', ' '); ?> MAD</td>
                                        <td><?php echo number_format($row['tva'], 2, ',', ' '); ?> MAD</td>
                                        <td><?php echo number_format($row['total_ttc'], 2, ',', ' '); ?> MAD</td>
                                        <td>
                                            <span class="badge bg-<?php echo getStatutColor($row['statut']); ?>">
                                                <?php echo htmlspecialchars($row['statut']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
function getStatutColor($statut) {
    $colors = [
        'Brouillon' => 'secondary',
        'Validé' => 'success',
        'Envoyé' => 'primary',
        'Accepté' => 'info',
        'Rejeté' => 'danger'
    ];
    return $colors[$statut] ?? 'secondary';
}
?>

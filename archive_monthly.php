<?php
// FUTURE AUTOMOTIVE - Archive Monthly View
// عرض تفاصيل الأرشيف الشهري

require_once 'config.php';
require_once 'config_achat_hostinger.php';
require_login();

$page_title = 'الأرشيف الشهري';

// Get month parameter
$month = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    $month = date('Y-m');
}

try {
    $database_achat = new DatabaseAchat();
    $pdo = $database_achat->connect();
    
    // Get monthly summary
    $stmt = $pdo->prepare("
        SELECT year_month, 
               SUM(da_count) as total_da,
               SUM(dp_count) as total_dp,
               SUM(bc_count) as total_bc,
               SUM(be_count) as total_be,
               SUM(total_amount) as total_amount,
               COUNT(DISTINCT supplier_id) as suppliers_count
        FROM monthly_transactions_summary 
        WHERE year_month = ?
    ");
    $stmt->execute([$month]);
    $monthly_summary = $stmt->fetch();
    
    // Get supplier details for the month
    $stmt = $pdo->prepare("
        SELECT mts.*, s.nom_fournisseur
        FROM monthly_transactions_summary mts
        LEFT JOIN suppliers s ON mts.supplier_id = s.id
        WHERE mts.year_month = ?
        ORDER BY mts.total_amount DESC
    ");
    $stmt->execute([$month]);
    $supplier_data = $stmt->fetchAll();
    
    // Get archived transactions for the month
    $stmt = $pdo->prepare("
        SELECT transaction_type, reference, amount, supplier_id, status, archived_at
        FROM transaction_archive
        WHERE year_month = ?
        ORDER BY transaction_date DESC, transaction_type
    ");
    $stmt->execute([$month]);
    $archived_transactions = $stmt->fetchAll();
    
    // Get non-archived transactions for the month
    $non_archived = [];
    
    // DA transactions
    $stmt = $pdo->prepare("
        SELECT 'DA' as type, ref_da as reference, id, date_creation as date, demandeur, statut
        FROM demandes_achat 
        WHERE year_month = ? AND is_archived = FALSE
        ORDER BY date_creation DESC
    ");
    $stmt->execute([$month]);
    $non_archived = array_merge($non_archived, $stmt->fetchAll());
    
    // DP transactions
    $stmt = $pdo->prepare("
        SELECT 'DP' as type, ref_dp as reference, id, date_envoi as date, statut
        FROM demandes_prix 
        WHERE year_month = ? AND is_archived = FALSE
        ORDER BY date_envoi DESC
    ");
    $stmt->execute([$month]);
    $non_archived = array_merge($non_archived, $stmt->fetchAll());
    
    // BC transactions
    $stmt = $pdo->prepare("
        SELECT 'BC' as type, ref_bc as reference, id, date_commande as date, total_ttc as amount, statut
        FROM bons_commande 
        WHERE year_month = ? AND is_archived = FALSE
        ORDER BY date_commande DESC
    ");
    $stmt->execute([$month]);
    $non_archived = array_merge($non_archived, $stmt->fetchAll());
    
    // BE transactions
    $stmt = $pdo->prepare("
        SELECT 'BE' as type, ref_be as reference, id, reception_date as date, statut
        FROM bons_entree 
        WHERE year_month = ? AND is_archived = FALSE
        ORDER BY reception_date DESC
    ");
    $stmt->execute([$month]);
    $non_archived = array_merge($non_archived, $stmt->fetchAll());
    
} catch (Exception $e) {
    $error_message = "Erreur lors du chargement des données: " . $e->getMessage();
    $monthly_summary = [];
    $supplier_data = [];
    $archived_transactions = [];
    $non_archived = [];
}

function getTransactionTypeLabel($type) {
    $labels = [
        'DA' => 'طلب شراء',
        'DP' => 'طلب سعر',
        'BC' => 'أمر شراء',
        'BE' => 'إيصال استلام'
    ];
    return $labels[$type] ?? $type;
}

function getTransactionTypeColor($type) {
    $colors = [
        'DA' => 'primary',
        'DP' => 'warning',
        'BC' => 'success',
        'BE' => 'info'
    ];
    return $colors[$type] ?? 'secondary';
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
        .summary-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .supplier-card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.2s;
        }
        .supplier-card:hover {
            transform: translateY(-2px);
        }
        .transaction-item {
            border-left: 4px solid #007bff;
            padding: 1rem;
            margin-bottom: 0.5rem;
            background: #f8f9fa;
            border-radius: 0 8px 8px 0;
        }
        .month-selector {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="mb-2">
                                <i class="fas fa-calendar-alt me-3"></i>
                                الأرشيف الشهري
                            </h1>
                            <p class="text-muted">عرض تفاصيل الأرشيف للشهر: <?php echo htmlspecialchars($month); ?></p>
                        </div>
                        <div class="month-selector">
                            <div class="input-group">
                                <input type="month" class="form-control" id="monthSelector" value="<?php echo htmlspecialchars($month); ?>">
                                <button class="btn btn-primary" onclick="changeMonth()">
                                    <i class="fas fa-search"></i> عرض
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Monthly Summary -->
            <?php if ($monthly_summary): ?>
                <div class="summary-card">
                    <div class="row text-center">
                        <div class="col-md-2">
                            <h3><?php echo $monthly_summary['total_da']; ?></h3>
                            <small>طلبات الشراء</small>
                        </div>
                        <div class="col-md-2">
                            <h3><?php echo $monthly_summary['total_dp']; ?></h3>
                            <small>طلبات الأسعار</small>
                        </div>
                        <div class="col-md-2">
                            <h3><?php echo $monthly_summary['total_bc']; ?></h3>
                            <small>أوامر الشراء</small>
                        </div>
                        <div class="col-md-2">
                            <h3><?php echo $monthly_summary['total_be']; ?></h3>
                            <small>إيصالات الاستلام</small>
                        </div>
                        <div class="col-md-2">
                            <h3><?php echo $monthly_summary['suppliers_count']; ?></h3>
                            <small>الموردين</small>
                        </div>
                        <div class="col-md-2">
                            <h3><?php echo number_format($monthly_summary['total_amount'], 2, ',', ' '); ?></h3>
                            <small>درهم إجمالي</small>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Supplier Breakdown -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-building me-2"></i>
                                تفاصيل الموردين
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($supplier_data)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-building fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">لا توجد بيانات للموردين في هذا الشهر</p>
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($supplier_data as $supplier): ?>
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="card supplier-card">
                                                <div class="card-body">
                                                    <h6 class="card-title">
                                                        <?php echo htmlspecialchars($supplier['nom_fournisseur'] ?? 'غير محدد'); ?>
                                                    </h6>
                                                    <div class="row text-center">
                                                        <div class="col-3">
                                                            <small class="text-muted">DA</small>
                                                            <div class="badge bg-primary"><?php echo $supplier['da_count']; ?></div>
                                                        </div>
                                                        <div class="col-3">
                                                            <small class="text-muted">DP</small>
                                                            <div class="badge bg-warning"><?php echo $supplier['dp_count']; ?></div>
                                                        </div>
                                                        <div class="col-3">
                                                            <small class="text-muted">BC</small>
                                                            <div class="badge bg-success"><?php echo $supplier['bc_count']; ?></div>
                                                        </div>
                                                        <div class="col-3">
                                                            <small class="text-muted">BE</small>
                                                            <div class="badge bg-info"><?php echo $supplier['be_count']; ?></div>
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <div class="d-flex justify-content-between">
                                                        <small class="text-muted">الإجمالي:</small>
                                                        <strong><?php echo number_format($supplier['total_amount'], 2, ',', ' '); ?> DH</strong>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Transactions -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-archive me-2"></i>
                                المعاملات المؤرشفة (<?php echo count($archived_transactions); ?>)
                            </h5>
                        </div>
                        <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                            <?php if (empty($archived_transactions)): ?>
                                <p class="text-muted">لا توجد معاملات مؤرشفة في هذا الشهر</p>
                            <?php else: ?>
                                <?php foreach ($archived_transactions as $transaction): ?>
                                    <div class="transaction-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="badge bg-<?php echo getTransactionTypeColor($transaction['transaction_type']); ?>">
                                                    <?php echo getTransactionTypeLabel($transaction['transaction_type']); ?>
                                                </span>
                                                <strong class="ms-2"><?php echo htmlspecialchars($transaction['reference']); ?></strong>
                                            </div>
                                            <div class="text-end">
                                                <?php if ($transaction['amount'] > 0): ?>
                                                    <div><?php echo number_format($transaction['amount'], 2, ',', ' '); ?> DH</div>
                                                <?php endif; ?>
                                                <small class="text-muted"><?php echo $transaction['archived_at']; ?></small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-clock me-2"></i>
                                المعاملات غير المؤرشفة (<?php echo count($non_archived); ?>)
                            </h5>
                        </div>
                        <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                            <?php if (empty($non_archived)): ?>
                                <p class="text-muted">جميع معاملات هذا الشهر مؤرشفة</p>
                            <?php else: ?>
                                <?php foreach ($non_archived as $transaction): ?>
                                    <div class="transaction-item" style="border-left-color: #ffc107;">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="badge bg-<?php echo getTransactionTypeColor($transaction['type']); ?>">
                                                    <?php echo getTransactionTypeLabel($transaction['type']); ?>
                                                </span>
                                                <strong class="ms-2"><?php echo htmlspecialchars($transaction['reference']); ?></strong>
                                                <?php if (isset($transaction['demandeur'])): ?>
                                                    <small class="text-muted ms-2"><?php echo htmlspecialchars($transaction['demandeur']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-end">
                                                <?php if (isset($transaction['amount']) && $transaction['amount'] > 0): ?>
                                                    <div><?php echo number_format($transaction['amount'], 2, ',', ' '); ?> DH</div>
                                                <?php endif; ?>
                                                <small class="text-muted"><?php echo $transaction['date']; ?></small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="btn-group">
                                <button class="btn btn-primary" onclick="archiveMonth()">
                                    <i class="fas fa-archive me-2"></i>
                                    أرشفة الشهر
                                </button>
                                <button class="btn btn-success" onclick="exportMonth()">
                                    <i class="fas fa-download me-2"></i>
                                    تصدير Excel
                                </button>
                                <button class="btn btn-info" onclick="generatePDF()">
                                    <i class="fas fa-file-pdf me-2"></i>
                                    إنشاء PDF
                                </button>
                                <button class="btn btn-warning" onclick="restoreTransactions()">
                                    <i class="fas fa-undo me-2"></i>
                                    استعادة المعاملات
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function changeMonth() {
            const month = document.getElementById('monthSelector').value;
            window.location.href = '?month=' + month;
        }
        
        function archiveMonth() {
            if (confirm('هل تريد أرشفة جميع معاملات هذا الشهر؟')) {
                fetch('api/archive/process.php', {
                    method: 'POST',
                    body: JSON.stringify({
                        action: 'archive_month',
                        month: '<?php echo $month; ?>'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('تم أرشفة الشهر بنجاح!');
                        location.reload();
                    } else {
                        alert('خطأ: ' + data.error);
                    }
                });
            }
        }
        
        function exportMonth() {
            window.open('archive_export.php?month=<?php echo $month; ?>&format=excel');
        }
        
        function generatePDF() {
            window.open('archive_export.php?month=<?php echo $month; ?>&format=pdf');
        }
        
        function restoreTransactions() {
            if (confirm('هل تريد استعادة المعاملات المؤرشفة لهذا الشهر؟')) {
                fetch('api/archive/restore.php', {
                    method: 'POST',
                    body: JSON.stringify({
                        month: '<?php echo $month; ?>'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('تم استعادة المعاملات بنجاح!');
                        location.reload();
                    } else {
                        alert('خطأ: ' + data.error);
                    }
                });
            }
        }
    </script>
</body>
</html>

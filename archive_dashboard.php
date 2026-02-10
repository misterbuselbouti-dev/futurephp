<?php
// FUTURE AUTOMOTIVE - Archive Dashboard
// لوحة تحكم الأرشيف الذكي

require_once 'config.php';
require_once 'config_achat_hostinger.php';
require_login();

$page_title = 'لوحة تحكم الأرشيف';

// Get archive statistics
try {
    $database_achat = new DatabaseAchat();
    $pdo = $database_achat->connect();
    
    // Get archive settings
    $stmt = $pdo->query("SELECT * FROM archive_settings");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    // Get monthly summaries
    $stmt = $pdo->query("
        SELECT year_month, 
               SUM(da_count) as total_da,
               SUM(dp_count) as total_dp,
               SUM(bc_count) as total_bc,
               SUM(be_count) as total_be,
               SUM(total_amount) as total_amount,
               COUNT(DISTINCT supplier_id) as suppliers_count
        FROM monthly_transactions_summary 
        GROUP BY year_month 
        ORDER BY year_month DESC 
        LIMIT 12
    ");
    $monthly_data = $stmt->fetchAll();
    
    // Get archive counts
    $archive_counts = $pdo->query("
        SELECT transaction_type, COUNT(*) as count
        FROM transaction_archive
        GROUP BY transaction_type
    ")->fetchAll();
    
    // Get total counts from main tables
    $main_counts = $pdo->query("
        SELECT 'DA' as type, COUNT(*) as count, SUM(is_archived) as archived FROM demandes_achat
        UNION ALL
        SELECT 'DP' as type, COUNT(*) as count, SUM(is_archived) as archived FROM demandes_prix
        UNION ALL
        SELECT 'BC' as type, COUNT(*) as count, SUM(is_archived) as archived FROM bons_commande
        UNION ALL
        SELECT 'BE' as type, COUNT(*) as count, SUM(is_archived) as archived FROM bons_entree
    ")->fetchAll();
    
} catch (Exception $e) {
    $error_message = "Erreur lors du chargement des données: " . $e->getMessage();
    $monthly_data = [];
    $archive_counts = [];
    $main_counts = [];
    $settings = [];
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
        .archive-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: transform 0.2s;
        }
        .archive-card:hover {
            transform: translateY(-2px);
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .progress-ring {
            width: 120px;
            height: 120px;
            margin: 0 auto;
        }
        .month-card {
            border-left: 4px solid #007bff;
            transition: all 0.2s;
        }
        .month-card:hover {
            border-left-color: #0056b3;
            background-color: #f8f9fa;
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
                    <h1 class="mb-2">
                        <i class="fas fa-archive me-3"></i>
                        <?php echo $page_title; ?>
                    </h1>
                    <p class="text-muted">نظام الأرشيف الذكي للمعاملات الشرائية</p>
                </div>
            </div>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Archive Statistics -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo array_sum(array_column($main_counts, 'count')); ?></div>
                        <div>إجمالي المعاملات</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo array_sum(array_column($main_counts, 'archived')); ?></div>
                        <div>المعاملات المؤرشفة</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($monthly_data); ?></div>
                        <div>أشهر مسجلة</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-number">
                            <?php 
                            $total = array_sum(array_column($main_counts, 'count'));
                            $archived = array_sum(array_column($main_counts, 'archived'));
                            echo $total > 0 ? round(($archived / $total) * 100, 1) : 0; 
                            ?>%
                        </div>
                        <div>نسبة الأرشفة</div>
                    </div>
                </div>
            </div>
            
            <!-- Transaction Type Statistics -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card archive-card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-pie me-2"></i>
                                إحصائيات حسب نوع المعاملة
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($main_counts as $count): ?>
                                    <div class="col-md-3 mb-3">
                                        <div class="text-center">
                                            <h4 class="text-<?php echo $count['type'] === 'DA' ? 'primary' : ($count['type'] === 'DP' ? 'warning' : ($count['type'] === 'BC' ? 'success' : 'info')); ?>">
                                                <?php echo $count['type']; ?>
                                            </h4>
                                            <div class="progress mb-2" style="height: 8px;">
                                                <?php 
                                                $percentage = $count['count'] > 0 ? ($count['archived'] / $count['count']) * 100 : 0;
                                                ?>
                                                <div class="progress-bar bg-<?php echo $percentage > 50 ? 'success' : 'warning'; ?>" 
                                                     style="width: <?php echo $percentage; ?>%"></div>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo $count['archived']; ?> / <?php echo $count['count']; ?> مؤرشفة
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Monthly Data -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card archive-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-calendar-alt me-2"></i>
                                البيانات الشهرية
                            </h5>
                            <div class="btn-group">
                                <button class="btn btn-outline-primary btn-sm" onclick="refreshData()">
                                    <i class="fas fa-sync-alt"></i> تحديث
                                </button>
                                <button class="btn btn-outline-success btn-sm" onclick="exportData()">
                                    <i class="fas fa-download"></i> تصدير
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($monthly_data)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">لا توجد بيانات شهرية متاحة</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>الشهر</th>
                                                <th>DA</th>
                                                <th>DP</th>
                                                <th>BC</th>
                                                <th>BE</th>
                                                <th>الموردين</th>
                                                <th>الإجمالي</th>
                                                <th>الإجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($monthly_data as $month): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($month['year_month']); ?></strong>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary"><?php echo $month['total_da']; ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-warning"><?php echo $month['total_dp']; ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success"><?php echo $month['total_bc']; ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info"><?php echo $month['total_be']; ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary"><?php echo $month['suppliers_count']; ?></span>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo number_format($month['total_amount'], 2, ',', ' '); ?> DH</strong>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <button class="btn btn-outline-primary" onclick="viewMonthDetails('<?php echo $month['year_month']; ?>')">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <button class="btn btn-outline-success" onclick="exportMonth('<?php echo $month['year_month']; ?>')">
                                                                <i class="fas fa-download"></i>
                                                            </button>
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
            
            <!-- Archive Settings -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card archive-card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-cog me-2"></i>
                                إعدادات الأرشفة
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="archiveSettingsForm">
                                <div class="mb-3">
                                    <label class="form-label">الأرشفة التلقائية</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="autoArchiveEnabled" 
                                               <?php echo ($settings['auto_archive_enabled'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="autoArchiveEnabled">
                                            تفعيل الأرشفة التلقائية
                                        </label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">فترة الأرشفة (أشهر)</label>
                                    <input type="number" class="form-control" id="autoArchiveMonths" 
                                           value="<?php echo $settings['auto_archive_months'] ?? '6'; ?>" min="1" max="24">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">فترة الاحتفاظ (سنوات)</label>
                                    <input type="number" class="form-control" id="retentionYears" 
                                           value="<?php echo $settings['archive_retention_years'] ?? '5'; ?>" min="1" max="20">
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>
                                    حفظ الإعدادات
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card archive-card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-tools me-2"></i>
                                إجراءات الأرشفة
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary" onclick="runArchiveProcess()">
                                    <i class="fas fa-play me-2"></i>
                                    تشغيل الأرشفة الآن
                                </button>
                                <button class="btn btn-outline-warning" onclick="archiveSpecificMonth()">
                                    <i class="fas fa-calendar-plus me-2"></i>
                                    أرشفة شهر محدد
                                </button>
                                <button class="btn btn-outline-info" onclick="generateReports()">
                                    <i class="fas fa-chart-bar me-2"></i>
                                    إنشاء تقارير شهرية
                                </button>
                                <button class="btn btn-outline-danger" onclick="cleanupOldArchives()">
                                    <i class="fas fa-trash me-2"></i>
                                    تنظيف الأرشيف القديم
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
        function viewMonthDetails(yearMonth) {
            window.location.href = 'archive_monthly.php?month=' + yearMonth;
        }
        
        function exportMonth(yearMonth) {
            window.open('archive_export.php?month=' + yearMonth + '&format=excel');
        }
        
        function exportData() {
            window.open('archive_export.php?format=excel');
        }
        
        function refreshData() {
            location.reload();
        }
        
        function runArchiveProcess() {
            if (confirm('هل تريد تشغيل عملية الأرشفة الآن؟')) {
                fetch('api/archive/process.php', {
                    method: 'POST',
                    body: JSON.stringify({action: 'run_archive'})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('تم تشغيل الأرشفة بنجاح!');
                        location.reload();
                    } else {
                        alert('خطأ: ' + data.error);
                    }
                });
            }
        }
        
        function archiveSpecificMonth() {
            const month = prompt('أدخل الشهر (صيغة: YYYY-MM):');
            if (month) {
                fetch('api/archive/process.php', {
                    method: 'POST',
                    body: JSON.stringify({action: 'archive_month', month: month})
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
        
        function generateReports() {
            fetch('api/archive/generate_reports.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('تم إنشاء التقارير بنجاح!');
                    location.reload();
                } else {
                    alert('خطأ: ' + data.error);
                }
            });
        }
        
        function cleanupOldArchives() {
            if (confirm('هل تريد تنظيف الأرشيف القديم؟')) {
                fetch('api/archive/cleanup.php', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('تم تنظيف الأرشيف بنجاح!');
                        location.reload();
                    } else {
                        alert('خطأ: ' + data.error);
                    }
                });
            }
        }
        
        // Archive settings form
        document.getElementById('archiveSettingsForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = {
                auto_archive_enabled: document.getElementById('autoArchiveEnabled').checked,
                auto_archive_months: document.getElementById('autoArchiveMonths').value,
                archive_retention_years: document.getElementById('retentionYears').value
            };
            
            fetch('api/archive/settings.php', {
                method: 'POST',
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('تم حفظ الإعدادات بنجاح!');
                } else {
                    alert('خطأ: ' + data.error);
                }
            });
        });
    </script>
</body>
</html>

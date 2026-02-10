<?php
// FUTURE AUTOMOTIVE - Create Notifications Table
// إنشاء جدول الإشعارات

require_once '../config.php';
require_once '../includes/functions.php';

// Check authentication
if (!is_logged_in()) {
    header('Location: ../login.php');
    exit();
}

$user = get_logged_in_user();
$role = $user['role'] ?? '';

// Only admin can access this tool
if ($role !== 'admin') {
    http_response_code(403);
    echo 'Accès refusé.';
    exit();
}

$page_title = 'Create Notifications Table';
$database = new Database();
$pdo = $database->connect();

$error_message = '';
$success_message = '';

// Create notifications table
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_table'])) {
    try {
        // Create the notifications table
        $sql = "CREATE TABLE IF NOT EXISTS `notifications` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            entity_type VARCHAR(100) NOT NULL,
            entity_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT,
            is_read TINYINT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            read_at TIMESTAMP NULL,
            priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
            action_url VARCHAR(255),
            action_text VARCHAR(255),
            icon VARCHAR(50)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $pdo->exec($sql);
        
        // Add indexes
        $indexes = [
            "CREATE INDEX IF NOT EXISTS idx_notifications_user_unread ON notifications(user_id, is_read)",
            "CREATE INDEX IF NOT EXISTS idx_notifications_entity ON notifications(entity_type, entity_id)",
            "CREATE INDEX IF NOT EXISTS idx_notifications_created_at ON notifications(created_at)",
            "CREATE INDEX IF NOT EXISTS idx_notifications_priority ON notifications(priority)"
        ];
        
        foreach ($indexes as $index) {
            $pdo->exec($index);
        }
        
        // Insert sample data if table is empty
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM notifications");
        $count = $stmt->fetch()['count'];
        
        if ($count == 0) {
            $sample_data = [
                [1, 'work_order', 1, 'أمر عمل جديد', 'تم إنشاء أمر عمل جديد', 'medium', 'fas fa-wrench'],
                [1, 'work_order', 2, 'أمر عمل منتهي', 'تم إنهاء أمر العمل بنجاح', 'high', 'fas fa-check-circle'],
                [1, 'system', 1, 'صيانة النظام', 'تم تحديث النظام بنجاح', 'low', 'fas fa-cog'],
                [1, 'audit', 1, 'تدقيق النظام', 'تم إجراء تدقيق على النظام', 'medium', 'fas fa-shield-alt'],
                [1, 'backup', 1, 'نسخة احتياطي', 'تم إنشاء نسخة احتياطي بنجاح', 'high', 'fas fa-save']
            ];
            
            $insert_sql = "INSERT INTO notifications (user_id, entity_type, entity_id, title, message, priority, icon) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($insert_sql);
            
            foreach ($sample_data as $data) {
                $stmt->execute($data);
            }
        }
        
        $success_message = "✅ تم إنشاء جدول notifications بنجاح!";
        
    } catch (Exception $e) {
        $error_message = "❌ خطأ: " . $e->getMessage();
    }
}

// Check if table exists
$table_exists = false;
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    $table_exists = $stmt->rowCount() > 0;
    
    if ($table_exists) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM notifications");
        $count = $stmt->fetch()['count'];
    }
} catch (Exception $e) {
    $error_message = "❌ خطأ في فحص الجدول: " . $e->getMessage();
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
    <link rel="stylesheet" href="../assets/css/simple-theme.css">
    
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
        
        .btn-primary-custom {
            background-color: var(--primary);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: var(--radius);
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .btn-primary-custom:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.3);
            color: white;
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
    <?php include '../includes/header_simple.php'; ?>
    
    <!-- Include sidebar -->
    <?php include '../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-6">
                <div>
                    <h1 class="mb-2">
                        <i class="fas fa-bell me-3"></i>
                        Create Notifications Table
                    </h1>
                    <p class="text-muted mb-0">Bienvenue, <?php echo htmlspecialchars($user['full_name']); ?></p>
                </div>
                <div class="d-flex gap-3">
                    <button class="btn btn-outline-primary" onclick="window.location.href='../quick_audit.php'">
                        <i class="fas fa-clipboard-check me-2"></i>Audit
                    </button>
                    <button class="btn btn-outline-success" onclick="window.location.href='../remove_unnecessary_files.php'">
                        <i class="fas fa-trash-alt me-2"></i>Nettoyer
                    </button>
                    <button class="btn btn-primary" onclick="window.location.href='../dashboard_simple.php'">
                        <i class="fas fa-home me-2"></i>Dashboard
                    </button>
                </div>
            </div>

            <div class="workshop-card">
                <h2 class="mb-4">📢 إنشاء جدول الإشعارات</h2>
                
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
                
                <div class="mb-4">
                    <h3>حالة جدول notifications:</h3>
                    <?php if ($table_exists): ?>
                        <div class="status-success mb-3">
                            <i class="fas fa-check-circle me-2"></i>
                            جدول notifications موجود بالفعل
                        </div>
                        <div class="mb-3">
                            <strong>عدد السجلات:</strong> <?php echo $count ?? 0; ?>
                        </div>
                    <?php else: ?>
                        <div class="status-warning mb-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            جدول notifications غير موجود
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!$table_exists): ?>
                    <form method="POST">
                        <button type="submit" name="create_table" class="btn btn-primary-custom btn-lg">
                            <i class="fas fa-database me-2"></i>
                            إنشاء جدول notifications
                        </button>
                    </form>
                <?php else: ?>
                    <div class="mt-4">
                        <h3>الخطوات التالية:</h3>
                        <ol>
                            <li><a href="../notifications.php" class="btn btn-outline-primary">عرض الإشعارات</a></li>
                            <li><a href="../dashboard_simple.php" class="btn btn-outline-success">العودة للوحة التحكم</a></li>
                            <li><a href="simple_theme_update.php" class="btn btn-outline-info">تحديث تيم الصفحات</a></li>
                        </ol>
                    </div>
                <?php endif; ?>
                
                <hr>
                
                <div class="mt-4">
                    <h3>معلومات الجدول:</h3>
                    <div class="row">
                        <div class="col-md-6">
                            <h4>هيكل الجدول:</h4>
                            <ul>
                                <li><strong>id:</strong> المفتاح الأساسي</li>
                                <li><strong>user_id:</strong> معرف المستخدم</li>
                                <li><strong>entity_type:</strong> نوع الكيان</li>
                                <li><strong>entity_id:</strong> معرف الكيان</li>
                                <li><strong>title:</strong> عنوان الإشعار</li>
                                <li><strong>message:</strong> نص الإشعار</li>
                                <li><strong>is_read:</strong> حالة القراءة</li>
                                <li><strong>created_at:</strong> تاريخ الإنشاء</li>
                                <li><strong>read_at:</strong> تاريخ القراءة</li>
                                <li><strong>priority:</strong> الأولوية</li>
                                <li><strong>action_url:</strong> رابط الإجراء</li>
                                <li><strong>action_text:</strong> نص الإجراء</li>
                                <li><strong>icon:</strong> الأيقونة</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h4>الفهارس:</h4>
                            <ul>
                                <li><strong>idx_notifications_user_unread:</strong> المستخدم + حالة القراءة</li>
                                <li><strong>idx_notifications_entity:</strong> الكيان</li>
                                <li><strong>idx_notifications_created_at:</strong> تاريخ الإنشاء</li>
                                <li><strong>idx_notifications_priority:</strong> الأولوية</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// FUTURE AUTOMOTIVE - Database Setup with Connection Form
// إعداد قاعدة البيانات مع نموذج اتصال

require_once '../config.php';
require_once '../includes/functions.php';

// Check authentication
if (!is_logged_in()) {
    header('Location: ../login.php');
    exit();
}

$user = get_logged_in_user();
$role = $user['role'] ?? '';

// Only admin can access database setup
if ($role !== 'admin') {
    http_response_code(403);
    echo 'Accès refusé.';
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - <?php echo APP_NAME; ?></title>
    
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
        
        .form-control, .form-select {
            border-radius: var(--radius);
            border: 1px solid var(--border);
            transition: all 0.2s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(var(--primary-rgb), 0.25);
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
                        <i class="fas fa-database me-3"></i>
                        Database Setup
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
                <h2 class="mb-4">🔧 إعداد قاعدة البيانات</h2>

<?php

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_database'])) {
    $host = $_POST['host'] ?? 'localhost';
    $dbname = $_POST['dbname'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    echo "<h3>محاولة الاتصال بقاعدة البيانات...</h3>";
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "<div class='status-success mb-3'>✅ الاتصال بقاعدة البيانات نجح!</div>";
        echo "<div class='mb-2'><strong>Host:</strong> $host</div>";
        echo "<div class='mb-2'><strong>Database:</strong> $dbname</div>";
        echo "<div class='mb-3'><strong>Username:</strong> $username</div>";
        
        // Now create the tables
        echo "<h3>إنشاء جداول الورشة...</h3>";
        
        // Drop existing tables first
        $tables_to_drop = ['work_orders', 'work_order_parts', 'work_order_timeline'];
        foreach ($tables_to_drop as $table) {
            try {
                $pdo->exec("DROP TABLE IF EXISTS $table");
                echo "<div class='status-warning mb-2'>⚠️ تم حذف $table</div>";
            } catch (Exception $e) {
                echo "<div class='status-info mb-2'>ℹ️ $table غير موجود</div>";
            }
        }
        
        // Create work_orders table
        try {
            $sql = "CREATE TABLE work_orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ref_ot VARCHAR(50) UNIQUE NOT NULL,
                bus_id INT NOT NULL,
                technician_id INT NOT NULL,
                work_description TEXT NOT NULL,
                work_type VARCHAR(100) DEFAULT 'Maintenance',
                priority ENUM('Faible', 'Normal', 'Urgent', 'Très Urgent') DEFAULT 'Normal',
                estimated_hours DECIMAL(5,2) DEFAULT 0,
                actual_hours DECIMAL(5,2) DEFAULT 0,
                status ENUM('En attente', 'En cours', 'En pause', 'Terminé', 'Annulé') DEFAULT 'En attente',
                created_by INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            $pdo->exec($sql);
            echo "<div class='status-success mb-2'>✅ تم إنشاء work_orders</div>";
            
            // Insert sample data
            $sql = "INSERT INTO work_orders (ref_ot, bus_id, technician_id, work_description, work_type, priority, status, created_by) VALUES 
            ('OT-20250209-001', 1, 1, 'Changement huile moteur', 'Maintenance', 'Normal', 'Terminé', 1),
            ('OT-20250209-002', 2, 1, 'Réparation freins', 'Réparation', 'Urgent', 'En cours', 1)";
            
            $pdo->exec($sql);
            echo "<div class='status-success mb-2'>✅ تم إدخال بيانات work_orders</div>";
            
        } catch (Exception $e) {
            echo "<div class='status-error mb-2'>❌ خطأ في work_orders: " . $e->getMessage() . "</div>";
        }
        
        // Create work_order_parts table
        try {
            $sql = "CREATE TABLE work_order_parts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                work_order_id INT NOT NULL,
                ref_article VARCHAR(50) NOT NULL,
                designation VARCHAR(255) NOT NULL,
                quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
                unit_cost DECIMAL(10,2) DEFAULT 0,
                total_cost DECIMAL(10,2) DEFAULT 0,
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            $pdo->exec($sql);
            echo "<div class='status-success mb-2'>✅ تم إنشاء work_order_parts</div>";
            
        } catch (Exception $e) {
            echo "<div class='status-error mb-2'>❌ خطأ في work_order_parts: " . $e->getMessage() . "</div>";
        }
        
        // Create work_order_timeline table
        try {
            $sql = "CREATE TABLE work_order_timeline (
                id INT AUTO_INCREMENT PRIMARY KEY,
                work_order_id INT NOT NULL,
                action VARCHAR(100) NOT NULL,
                description TEXT,
                performed_by INT NOT NULL,
                performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            $pdo->exec($sql);
            echo "<div class='status-success mb-2'>✅ تم إنشاء work_order_timeline</div>";
            
        } catch (Exception $e) {
            echo "<div class='status-error mb-2'>❌ خطأ في work_order_timeline: " . $e->getMessage() . "</div>";
        }
        
        // Test the query
        echo "<h3>اختبار الاستعلام:</h3>";
        try {
            $stmt = $pdo->query("
                SELECT wo.*, 
                       b.bus_number, b.license_plate,
                       u.full_name as technician_name
                FROM work_orders wo
                LEFT JOIN buses b ON wo.bus_id = b.id
                LEFT JOIN users u ON wo.technician_id = u.id
                LIMIT 3
            ");
            $results = $stmt->fetchAll();
            
            echo "<div class='status-success mb-2'>✅ الاستعلام يعمل!</div>";
            echo "<div class='mb-3'>عدد النتائج: " . count($results) . "</div>";
            
        } catch (Exception $e) {
            echo "<div class='status-error mb-2'>❌ خطأ في الاستعلام: " . $e->getMessage() . "</div>";
        }
        
        echo "<hr>";
        echo "<div class='status-success text-center mb-4' style='font-size: 18px;'>";
        echo "🎉 تم الإعداد بنجاح!";
        echo "</div>";
        
        echo "<h3>الخطوات التالية:</h3>";
        echo "<ol>";
        echo "<li><a href='admin_breakdowns_workshop.php' class='btn btn-outline-primary'>اذهب إلى إدارة الورشة</a></li>";
        echo "<li>اختبر إنشاء أمر عمل جديد</li>";
        echo "</ol>";
        
        // Store connection info in session for future use
        session_start();
        $_SESSION['db_config'] = [
            'host' => $host,
            'dbname' => $dbname,
            'username' => $username,
            'password' => $password
        ];
        
    } catch (PDOException $e) {
        echo "<div class='status-error mb-3'>❌ خطأ في الاتصال: " . $e->getMessage() . "</div>";
        echo "<h3>الحلول المقترحة:</h3>";
        echo "<ol>";
        echo "<li>تأكد من اسم قاعدة البيانات</li>";
        echo "<li>تأكد من اسم المستخدم</li>";
        echo "<li>تأكد من كلمة المرور</li>";
        echo "<li>تأكد من أن قاعدة البيانات موجودة</li>";
        echo "<li>جرب استخدام 127.0.0.1 بدلاً من localhost</li>";
        echo "</ol>";
        echo "<p><a href='database_setup.php' class='btn btn-outline-secondary'>عد وحاول مرة أخرى</a></p>";
    }
    
} else {
    // Show the connection form
    echo "<h3 class='mb-4'>أدخل بيانات الاتصال بقاعدة البيانات:</h3>";
    
    echo "<form method='post' class='row g-3'>";
    echo "<div class='col-md-6'>";
    echo "<label class='form-label'>Host:</label>";
    echo "<input type='text' class='form-control' name='host' value='localhost'>";
    echo "</div>";
    echo "<div class='col-md-6'>";
    echo "<label class='form-label'>Database Name:</label>";
    echo "<input type='text' class='form-control' name='dbname' value='u442210176_Futur2'>";
    echo "</div>";
    echo "<div class='col-md-6'>";
    echo "<label class='form-label'>Username:</label>";
    echo "<input type='text' class='form-control' name='username' value='u442210176_Futur2'>";
    echo "</div>";
    echo "<div class='col-md-6'>";
    echo "<label class='form-label'>Password:</label>";
    echo "<input type='password' class='form-control' name='password' placeholder='أدخل كلمة المرور'>";
    echo "</div>";
    echo "<div class='col-12'>";
    echo "<button type='submit' name='setup_database' class='btn btn-primary-custom btn-lg'>اتصل وأنشئ الجداول</button>";
    echo "</div>";
    echo "</form>";
    
    echo "<hr>";
    echo "<h3>معلومات مساعدة:</h3>";
    echo "<h4>للحصول على بيانات الاتصال الصحيحة:</h4>";
    echo "<ol>";
    echo "<li>سجل دخول إلى لوحة تحكم Hostinger</li>";
    echo "<li>اذهب إلى <strong>Databases</strong></li>";
    echo "<li>اختر قاعدة البيانات الخاصة بك</li>";
    echo "<li>ابحث عن <strong>Connection Details</strong> أو <strong>Database Details</strong></li>";
    echo "<li>ستجد هناك Host, Database Name, Username, Password</li>";
    echo "</ol>";
    
    echo "<h4>بيانات الاتصال الشائعة في Hostinger:</h4>";
    echo "<ul>";
    echo "<li><strong>Host:</strong> localhost أو 127.0.0.1</li>";
    echo "<li><strong>Database Name:</strong> u442210176_Futur2</li>";
    echo "<li><strong>Username:</strong> u442210176_Futur2</li>";
    echo "<li><strong>Password:</strong> كلمة المرور التي قمت بإنشائها</li>";
    echo "</ul>";
    
    echo "<h4>إذا نسيت كلمة المرور:</h4>";
    echo "<ol>";
    echo "<li>اذهب إلى لوحة تحكم Hostinger</li>";
    echo "<li>اذهب إلى Databases</li>";
    echo "<li>اختر قاعدة البيانات</li>";
    echo "<li>اضغط على <strong>Change Password</strong> أو <strong>Reset Password</strong></li>";
    echo "</ol>";
}
?>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

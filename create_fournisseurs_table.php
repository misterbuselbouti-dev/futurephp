<?php
// FUTURE AUTOMOTIVE - Create Fournisseurs Table
// إنشاء جدول الموردين

require_once 'config_achat_hostinger.php';
require_once 'config.php';

// Check authentication
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

$page_title = 'Create Fournisseurs Table';
$database = new DatabaseAchat();
$conn = $database->connect();

$error_message = '';
$success_message = '';

// Create fournisseurs table
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_table'])) {
    try {
        // Create the fournisseurs table
        $sql = "CREATE TABLE IF NOT EXISTS `fournisseurs` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(255) NOT NULL,
            ice VARCHAR(255) NOT NULL,
            rc VARCHAR(255) NOT NULL,
            telephone VARCHAR(50),
            email VARCHAR(255),
            adresse TEXT,
            ville VARCHAR(100),
            pays VARCHAR(100),
            code_postal VARCHAR(20),
            statut ENUM('actif', 'inactif', 'suspendu') DEFAULT 'actif',
            date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            notes TEXT,
            contact_personne VARCHAR(255),
            delai_livraison INT DEFAULT 30,
            conditions_paiement TEXT,
            rib VARCHAR(34),
            banque VARCHAR(100),
            iban VARCHAR(34),
            swift VARCHAR(11),
            devise VARCHAR(10) DEFAULT 'MAD'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $conn->exec($sql);
        
        // Add indexes
        $indexes = [
            "CREATE INDEX IF NOT EXISTS idx_fournisseurs_nom ON fournisseurs(nom)",
            "CREATE INDEX IF NOT EXISTS idx_fournisseurs_ice ON fournisseurs(ice)",
            "CREATE INDEX IF NOT EXISTS idx_fournisseurs_statut ON fournisseurs(statut)",
            "CREATE INDEX IF NOT EXISTS idx_fournisseurs_date_creation ON fournisseurs(date_creation)"
        ];
        
        foreach ($indexes as $index) {
            $conn->exec($index);
        }
        
        // Insert sample data if table is empty
        $stmt = $conn->query("SELECT COUNT(*) as count FROM fournisseurs");
        $count = $stmt->fetch()['count'];
        
        if ($count == 0) {
            $sample_data = [
                ['ALLIANCE AUTO PARTS', 'ALLIANCE2023', 'ALLIANCE2023RC', '0522-123456', 'contact@alliance.ma', '123 Avenue Hassan II, Tanger', 'Tanger', 'Maroc', '90000', 'actif', 'Mohamed Ali', 30, '30 jours fin de mois', '123456789012345678901234567890', 'BMCE', 'MA000123456789012345678901234567890', 'BMCEMA', 'MAD'],
                ['AUTO PIECES MAROC', 'AUTO2023', 'AUTO2023RC', '0522-789012', 'info@autopieces.ma', '456 Boulevard Mohammed V, Casablanca', 'Casablanca', 'Maroc', '20000', 'actif', 'Fatima Zahra', 45, '60 jours fin de mois', '9876543210987654321098765432109', 'CIH', 'MA0009876543210987654321098765432109', 'CIHMA', 'MAD'],
                ['MECANIC PRO SERVICES', 'MECA2023', 'MECA2023RC', '0522-456789', 'support@mecanic.ma', '789 Rue industrielle, Rabat', 'Rabat', 'Maroc', '10100', 'actif', 'Youssef Ahmed', 30, '45 jours fin de mois', '567890123456789012345678901234567890', 'CIH', 'MA000567890123456789012345678901234567890', 'CIHMA', 'MAD'],
                ['ELECTRONICS SOLUTIONS', 'ELEC2023', 'ELEC2023RC', '0522-345678', 'sales@electronics.ma', '321 Rue des technopoles, Fès', 'Fès', 'Maroc', '30000', 'actif', 'Sara Mohammed', 60, '30 jours fin de mois', '234567890123456789012345678901234567890', 'CIH', 'MA000234567890123456789012345678901234567890', 'CIHMA', 'MAD'],
                ['HYDRAULIC SYSTEMS', 'HYDR2023', 'HYDR2023RC', '0522-234567', 'info@hydraulic.ma', '654 Rue du port, Agadir', 'Agadir', 'Maroc', '15000', 'actif', 'Karim Omar', 30, '30 jours fin de mois', '3456789012345678901234567890123456789', 'CIH', 'MA0003456789012345678901234567890123456789', 'CIHMA', 'MAD']
            ];
            
            $insert_sql = "INSERT INTO fournisseurs (nom, ice, rc, telephone, email, adresse, ville, pays, code_postal, statut, contact_personne, delai_livraison, conditions_paiement, rib, banque, iban, swift, devise) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            
            foreach ($sample_data as $data) {
                $stmt->execute($data);
            }
        }
        
        $success_message = "✅ تم إنشاء جدول fournisseurs بنجاح!";
        
    } catch (Exception $e) {
        $error_message = "❌ خطأ: " . $e->getMessage();
    }
}

// Check if table exists
$table_exists = false;
try {
    $stmt = $conn->query("SHOW TABLES LIKE 'fournisseurs'");
    $table_exists = $stmt->rowCount() > 0;
    
    if ($table_exists) {
        $stmt = $conn->query("SELECT COUNT(*) as count FROM fournisseurs");
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
                        <i class="fas fa-store me-3"></i>
                        Create Fournisseurs Table
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

            <div class="workshop-card">
                <h2 class="mb-4">🏪 إنشاء جدول الموردين</h2>
                
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
                    <h3>حالة جدول fournisseurs:</h3>
                    <?php if ($table_exists): ?>
                        <div class="status-success mb-3">
                            <i class="fas fa-check-circle me-2"></i>
                            جدول fournisseurs موجود بالفعل
                        </div>
                        <div class="mb-3">
                            <strong>عدد السجلات:</strong> <?php echo $count ?? 0; ?>
                        </div>
                    <?php else: ?>
                        <div class="status-warning mb-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            جدول fournisseurs غير موجود
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!$table_exists): ?>
                    <form method="POST">
                        <button type="submit" name="create_table" class="btn btn-primary-custom btn-lg">
                            <i class="fas fa-database me-2"></i>
                            إنشاء جدول fournisseurs
                        </button>
                    </form>
                <?php else: ?>
                    <div class="mt-4">
                        <h3>الخطوات التالية:</h3>
                        <ol>
                            <li><a href="check_database_data.php" class="btn btn-outline-primary">فحص جميع الجداول</a></li>
                            <li><a href="achat_bc.php" class="btn btn-outline-success">العودة لـ Bon de Commande</a></li>
                            <li><a href="dashboard_simple.php" class="btn btn-outline-info">العودة للوحة التحكم</a></li>
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
                                <li><strong>nom:</strong> اسم المورد</li>
                                <li><strong>ice:</strong> رقم الهوية الضريبية</li>
                                <li><strong>rc:</strong> رقم السجل التجاري</li>
                                <li><strong>telephone:</strong> رقم الهاتف</li>
                                <li><strong>email:</strong> البريد الإلكتروني</li>
                                <li><strong>adresse:</strong> العنوان</li>
                                <li><strong>ville:</strong> المدينة</li>
                                <li><strong>pays:</strong> الدولة</li>
                                <li><strong>code_postal:</strong> الرمز البريدي</li>
                                <li><strong>statut:</strong> الحالة (actif, inactif, suspendu)</li>
                                <li><strong>contact_personne:</strong> شخص الاتصال</li>
                                <li><strong>delai_livraison:</strong> مدة التسليم</li>
                                <li><strong>conditions_paiement:</strong> شروط الدفع</li>
                                <li><strong>rib:</strong> رقم الحساب البنكي</li>
                                <li><strong>banque:</strong> البنك</li>
                                <li><strong>iban:</strong> رقم IBAN</li>
                                <li><strong>swift:</strong> كود SWIFT</li>
                                <li><strong>devise:</strong> العملة</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h4>الفهارس:</h4>
                            <ul>
                                <li><strong>idx_fournisseurs_nom:</strong> اسم المورد</li>
                                <li><strong>idx_fournisseurs_ice:</strong> رقم ICE</li>
                                <li><strong>idx_fournisseurs_statut:</strong> الحالة</li>
                                <li><strong>idx_fournisseurs_date_creation:</strong> تاريخ الإنشاء</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <h3>الموردين التجريبيون:</h3>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>الاسم</th>
                                    <th>ICE</th>
                                    <th>RC</th>
                                    <th>الهاتف</th>
                                    <th>البريد الإلكتروني</th>
                                    <th>المدينة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>ALLIANCE AUTO PARTS</strong></td>
                                    <td>ALLIANCE2023</td>
                                    <td>ALLIANCE2023RC</td>
                                    <td>0522-123456</td>
                                    <td>contact@alliance.ma</td>
                                    <td>Tanger</td>
                                </tr>
                                <tr>
                                    <td><strong>AUTO PIECES MAROC</strong></td>
                                    <td>AUTO2023</td>
                                    <td>AUTO2023RC</td>
                                    <td>0522-789012</td>
                                    <td>info@autopieces.ma</td>
                                    <td>Casablanca</td>
                                </tr>
                                <tr>
                                    <td><strong>MECANIC PRO SERVICES</strong></td>
                                    <td>MECA2023</td>
                                    <td>MECA2023RC</td>
                                    <td>0522-456789</td>
                                    <td>support@mecanic.ma</td>
                                    <td>Rabat</td>
                                </tr>
                                <tr>
                                    <td><strong>ELECTRONICS SOLUTIONS</strong></td>
                                    <td>ELEC2023</td>
                                    <td>ELEC2023RC</td>
                                    <td>0522-345678</td>
                                    <td>sales@electronics.ma</td>
                                    <td>Fès</td>
                                </tr>
                                <tr>
                                    <td><strong>HYDRAULIC SYSTEMS</strong></td>
                                    <td>HYDR2023</td>
                                    <td>HYDR2023RC</td>
                                    <td>0522-234567</td>
                                    <td>info@hydraulic.ma</td>
                                    <td>Agadir</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

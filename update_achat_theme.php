<?php
// FUTURE AUTOMOTIVE - Update Achat Service Pages to Simple Theme
// تحديث صفحات خدمة المشتريات إلى الثيم البسيط

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

$page_title = 'Update Achat Service Theme';
$achat_files = [
    'achat_da.php',
    'achat_dp.php', 
    'achat_be.php',
    'achat_bc.php',
    'achat_da_edit.php',
    'achat_dp_edit.php',
    'achat_bc_edit.php',
    'achat_da_view.php',
    'achat_dp_view.php',
    'achat_bc_view.php',
    'achat_be_view.php'
];

$updated_files = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_theme'])) {
    foreach ($achat_files as $file) {
        $file_path = __DIR__ . '/' . $file;
        
        if (!file_exists($file_path)) {
            $errors[] = "File not found: $file";
            continue;
        }
        
        try {
            $content = file_get_contents($file_path);
            
            // Update CSS includes to use simple theme
            $content = preg_replace('/<link rel="stylesheet" href="assets\/css\/style\.css">/', '<link rel="stylesheet" href="assets/css/simple-theme.css">', $content);
            
            // Add simple theme CSS if not present
            if (strpos($content, 'simple-theme.css') === false) {
                $content = str_replace('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">', 
                    '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/simple-theme.css">', $content);
            }
            
            // Update main-content margin-left to 250px
            $content = preg_replace('/margin-left:\s*260px;/', 'margin-left: 250px;', $content);
            
            // Update main-content padding to 20px
            $content = preg_replace('/padding:\s*2rem;/', 'padding: 20px;', $content);
            
            // Replace old card classes with workshop-card
            $content = preg_replace('/class="[^"]*card[^"]*"/', 'class="workshop-card"', $content);
            
            // Replace old modal classes with workshop-card
            $content = preg_replace('/class="[^"]*modal[^"]*"/', 'class="workshop-card"', $content);
            
            // Update page headers to use simple row structure
            $content = preg_replace('/<div class="page-header">.*?<\/div>/s', '<div class="row mb-4">
                <div class="col-12">
                    <h1 class="mb-2">
                        <i class="fas fa-file-alt me-3"></i>
                        Service Achat
                    </h1>
                    <p class="text-muted">Gestion du service achat</p>
                </div>
            </div>', $content);
            
            // Add simple theme CSS variables if not present
            if (strpos($content, ':root') === false) {
                $css_vars = '
        :root {
            --primary-color: #1e3a8a;
            --secondary-color: #f59e0b;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
            --light-bg: #f8f9fa;
            --dark-color: #1f2937;
        }
        
        body {
            font-family: \'Poppins\', sans-serif;
            background: var(--light-bg);
        }';
                
                $content = preg_replace('/<style>/', '<style>' . $css_vars, $content);
            }
            
            // Save the updated content
            if (file_put_contents($file_path, $content)) {
                $updated_files[] = $file;
            } else {
                $errors[] = "Failed to update: $file";
            }
            
        } catch (Exception $e) {
            $errors[] = "Error updating $file: " . $e->getMessage();
        }
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
        
        .file-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
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
                        <i class="fas fa-paint-brush me-3"></i>
                        Update Achat Service Theme
                    </h1>
                    <p class="text-muted mb-0">Apply simple theme to all achat service pages</p>
                </div>
                <div class="d-flex gap-3">
                    <button class="btn btn-outline-primary" onclick="window.location.href='dashboard_simple.php'">
                        <i class="fas fa-home me-2"></i>Dashboard
                    </button>
                </div>
            </div>

            <div class="workshop-card">
                <h2 class="mb-4">🎨 Service Achat Theme Update</h2>
                
                <?php if (!empty($updated_files)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        Successfully updated <?php echo count($updated_files); ?> files!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo count($errors); ?> errors occurred:
                        <ul class="mb-0 mt-2">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="mb-4">
                    <h3>Files to be updated:</h3>
                    <div class="file-list">
                        <ul class="list-group">
                            <?php foreach ($achat_files as $file): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>
                                        <i class="fas fa-file-code me-2"></i>
                                        <?php echo htmlspecialchars($file); ?>
                                    </span>
                                    <?php if (in_array($file, $updated_files)): ?>
                                        <span class="badge bg-success">Updated</span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                
                <div class="mb-4">
                    <h3>Changes that will be applied:</h3>
                    <div class="row">
                        <div class="col-md-6">
                            <h5>CSS Updates:</h5>
                            <ul>
                                <li>Replace style.css with simple-theme.css</li>
                                <li>Update margin-left from 260px to 250px</li>
                                <li>Update padding from 2rem to 20px</li>
                                <li>Add CSS variables for colors</li>
                                <li>Update font to Poppins</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5>Structure Updates:</h5>
                            <ul>
                                <li>Replace card classes with workshop-card</li>
                                <li>Replace modal classes with workshop-card</li>
                                <li>Update page headers to simple row structure</li>
                                <li>Add consistent spacing and layout</li>
                                <li>Apply modern design patterns</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <?php if (empty($updated_files)): ?>
                    <form method="POST">
                        <button type="submit" name="update_theme" class="btn btn-primary btn-lg">
                            <i class="fas fa-paint-brush me-2"></i>
                            Update All Achat Service Pages
                        </button>
                    </form>
                <?php else: ?>
                    <div class="mt-4">
                        <h3>Next Steps:</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <a href="achat_da.php" class="btn btn-outline-primary btn-sm d-block mb-2">
                                    <i class="fas fa-file-alt me-2"></i>Check Demande d'Achat
                                </a>
                                <a href="achat_dp.php" class="btn btn-outline-primary btn-sm d-block mb-2">
                                    <i class="fas fa-file-invoice me-2"></i>Check Demande de Prix
                                </a>
                                <a href="achat_bc.php" class="btn btn-outline-primary btn-sm d-block mb-2">
                                    <i class="fas fa-file-invoice-dollar me-2"></i>Check Bon de Commande
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="achat_be.php" class="btn btn-outline-primary btn-sm d-block mb-2">
                                    <i class="fas fa-truck-loading me-2"></i>Check Bon d'Entrée
                                </a>
                                <a href="dashboard_simple.php" class="btn btn-outline-success btn-sm d-block mb-2">
                                    <i class="fas fa-home me-2"></i>Back to Dashboard
                                </a>
                                <button class="btn btn-outline-warning btn-sm d-block mb-2" onclick="location.reload()">
                                    <i class="fas fa-redo me-2"></i>Run Again
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

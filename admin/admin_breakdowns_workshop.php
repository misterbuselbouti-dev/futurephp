<?php
// FUTURE AUTOMOTIVE - Workshop Management (Gestion Atelier)
// إدارة الورشة بنفس نمط قسم الشراء

require_once '../config.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = 'admin_breakdowns_workshop.php';
    header('Location: ../login.php');
    exit();
}

// Obtenir les informations de l'utilisateur
$user = get_logged_in_user();
$full_name = $user['full_name'] ?? $_SESSION['full_name'] ?? 'Administrateur';
$role = $_SESSION['role'] ?? 'admin';

// Vérifier les autorisations
if (!in_array($role, ['admin', 'maintenance_manager'], true)) {
    http_response_code(403);
    echo 'Accès refusé.';
    exit;
}

$page_title = 'Gestion Atelier - Maintenance';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $database = new Database();
        $pdo = $database->connect();
        
        // Générer la référence de l'ordre de travail
        $ref_ot = generateReference('OT', 'work_orders', 'ref_ot');
        
        // Validation des champs obligatoires
        $validation_errors = [];
        
        if (empty($_POST['bus_id'])) {
            $validation_errors[] = "Le bus est obligatoire";
        }
        
        if (empty($_POST['technician_id'])) {
            $validation_errors[] = "Le technicien est obligatoire";
        }
        
        if (empty($_POST['work_description'])) {
            $validation_errors[] = "La description du travail est obligatoire";
        }
        
        if (!empty($validation_errors)) {
            throw new Exception(implode('<br>', $validation_errors));
        }
        
        // Insérer l'ordre de travail principal
        $stmt = $pdo->prepare("
            INSERT INTO work_orders (ref_ot, bus_id, technician_id, work_description, work_type, priority, estimated_hours, status, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $ref_ot,
            $_POST['bus_id'],
            $_POST['technician_id'],
            $_POST['work_description'],
            $_POST['work_type'] ?? 'Maintenance',
            $_POST['priority'] ?? 'Normal',
            $_POST['estimated_hours'] ?? 0,
            'En attente',
            $user['id']
        ]);
        
        $ot_id = $pdo->lastInsertId();
        
        // Insérer les pièces utilisées si spécifiées
        if (!empty($_POST['parts'])) {
            foreach ($_POST['parts'] as $part) {
                if (!empty($part['ref_article']) && !empty($part['quantity'])) {
                    $stmt = $pdo->prepare("
                        INSERT INTO work_order_parts (work_order_id, ref_article, designation, quantity, unit_cost, total_cost, notes) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $total_cost = floatval($part['quantity']) * floatval($part['unit_cost'] ?? 0);
                    
                    $stmt->execute([
                        $ot_id,
                        $part['ref_article'],
                        $part['designation'] ?? '',
                        $part['quantity'],
                        $part['unit_cost'] ?? 0,
                        $total_cost,
                        $part['notes'] ?? ''
                    ]);
                }
            }
        }
        
        $_SESSION['success_message'] = "Ordre de travail $ref_ot créé avec succès!";
        header("Location: admin_breakdowns_workshop.php");
        exit();
        
    } catch (Exception $e) {
        $error_message = "Erreur lors de la création de l'ordre de travail: " . $e->getMessage();
    }
}

// Récupérer les ordres de travail existants
$work_orders = [];
$tables_exist = false;
try {
    $database = new Database();
    $pdo = $database->connect();
    
    // Check if tables exist first
    $stmt = $pdo->query("SHOW TABLES LIKE 'work_orders'");
    $work_orders_table_exists = $stmt->rowCount() > 0;
    
    if ($work_orders_table_exists) {
        $tables_exist = true;
        $stmt = $pdo->query("
            SELECT wo.*, 
                   b.bus_number, b.license_plate,
                   u.full_name as technician_name,
                   COUNT(wop.id) as parts_count,
                   SUM(wop.total_cost) as total_parts_cost,
                   CONCAT('OT-', YEAR(wo.created_at), '-', LPAD(wo.id, 4, '0')) as ref_ot
            FROM work_orders wo
            LEFT JOIN buses b ON wo.bus_id = b.id
            LEFT JOIN users u ON wo.technician_id = u.id
            LEFT JOIN work_order_parts wop ON wo.id = wop.work_order_id
            GROUP BY wo.id
            ORDER BY wo.created_at DESC
        ");
        $work_orders = $stmt->fetchAll();
    } else {
        $error_message = "جداول إدارة الورشة غير موجودة. <a href='check_workshop_tables.php'>اضغط هنا لإنشائها</a>";
    }
    
} catch (Exception $e) {
    $error_message = "Erreur lors du chargement des ordres de travail: " . $e->getMessage();
    if (strpos($e->getMessage(), 'Column') !== false) {
        $error_message .= "<br><a href='check_workshop_tables.php'>اضغط هنا لفحص وإصلاح الجداول</a>";
    }
}

// Récupérer les données pour les formulaires
$buses = [];
$technicians = [];
$parts_catalogue = [];

try {
    $database = new Database();
    $pdo = $database->connect();
    
    // Only try to get data if tables exist
    if ($tables_exist) {
        // Récupérer les bus
        $stmt = $pdo->query("SELECT id, bus_number, license_plate, make, model FROM buses WHERE status = 'active' ORDER BY bus_number");
        $buses = $stmt->fetchAll();
        
        // Récupérer les techniciens
        $stmt = $pdo->query("SELECT id, full_name FROM users WHERE role IN ('technician', 'agent') AND is_active = 1 ORDER BY full_name");
        $technicians = $stmt->fetchAll();
        
        // Récupérer le catalogue des pièces
        $stmt = $pdo->query("
            SELECT id, code_article, designation, categorie, prix_unitaire, 
                   CONCAT(code_article, ' - ', designation) as recherche_complet
            FROM articles_catalogue 
            ORDER BY code_article
        ");
        $parts_catalogue = $stmt->fetchAll();
    }
    
} catch (Exception $e) {
    // Continuer sans les données
    if (!$tables_exist) {
        // Don't show error if we already know tables don't exist
        $error_message = $error_message ?? '';
    } else {
        $error_message = ($error_message ?? '') . "Erreur lors du chargement des données: " . $e->getMessage();
    }
}

// Fonctions utilitaires
function getStatusBadgeClass($status) {
    $classes = [
        'En attente' => 'warning',
        'En cours' => 'info',
        'Terminé' => 'success',
        'Annulé' => 'danger',
        'En pause' => 'secondary'
    ];
    return $classes[$status] ?? 'secondary';
}

function getPriorityBadgeClass($priority) {
    $classes = [
        'Très Urgent' => 'danger',
        'Urgent' => 'warning',
        'Normal' => 'info',
        'Faible' => 'secondary'
    ];
    return $classes[$priority] ?? 'secondary';
}

function generateReference($prefix, $table, $field) {
    $date = date('Ymd');
    $random = str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    return $prefix . '-' . $date . '-' . $random;
}
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@x.x.x/dist/select2-bootstrap4.min.css" rel="stylesheet" />
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <style>
        .main-content {
            margin-left: 260px;
            padding: 2rem;
            transition: margin-left 0.3s ease;
        }
        
        .workshop-modal {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .workshop-modal .modal-title {
            color: #333;
            font-weight: 600;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #808000;
            box-shadow: 0 0 0 0.2rem rgba(128, 128, 0, 0.25);
        }
        
        .required-star {
            color: #dc3545;
            margin-left: 4px;
        }
        
        .btn-olive {
            background: linear-gradient(135deg, #808000, #6b6b00);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-olive:hover {
            background: linear-gradient(135deg, #6b6b00, #585800);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(128, 128, 0, 0.3);
            color: white;
        }
        
        .btn-add-part {
            background: #28a745;
            border: none;
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-left: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-add-part:hover {
            background: #218838;
            transform: scale(1.1);
        }
        
        .part-row {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid #e9ecef;
        }
        
        .btn-remove-part {
            background: #dc3545;
            border: none;
            color: white;
            border-radius: 5px;
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }
        
        .btn-remove-part:hover {
            background: #c82333;
        }
        
        .select2-container--bootstrap4 .select2-selection {
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            min-height: 45px;
        }
        
        .select2-container--bootstrap4 .select2-selection--single {
            padding: 0.5rem;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1e3a8a;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #64748b;
            font-size: 0.875rem;
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
    <?php include '../includes/header.php'; ?>
    
    <!-- Include sidebar -->
    <?php include '../includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <nav aria-label="breadcrumb" class="mb-2">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../buses.php">Accueil</a></li>
                    <li class="breadcrumb-item active">Gestion Atelier</li>
                </ol>
            </nav>
            
            <!-- Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="mb-2">
                        <i class="fas fa-wrench me-3"></i>
                        Gestion Atelier
                    </h1>
                    <p class="text-muted">Créer et gérer les ordres de travail de maintenance</p>
                </div>
            </div>
            
            <!-- Messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($work_orders); ?></div>
                    <div class="stat-label">Total Ordres</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo count(array_filter($work_orders, fn($wo) => $wo['status'] === 'En attente')); ?></div>
                    <div class="stat-label">En Attente</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo count(array_filter($work_orders, fn($wo) => $wo['status'] === 'En cours')); ?></div>
                    <div class="stat-label">En Cours</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo count(array_filter($work_orders, fn($wo) => $wo['status'] === 'Terminé')); ?></div>
                    <div class="stat-label">Terminés</div>
                </div>
            </div>
            
            <!-- Work Order Modal Form -->
            <div class="workshop-modal">
                <h2 class="modal-title">
                    <i class="fas fa-plus-circle me-2"></i>
                    Nouvel Ordre de Travail
                </h2>
                
                <form id="workOrderForm" method="POST" action="">
                    
                    <!-- Row 1: Bus and Technician -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="bus_id" class="form-label">
                                Bus <span class="required-star">*</span>
                            </label>
                            <select class="form-select" id="bus_id" name="bus_id" required>
                                <option value="">Sélectionner un bus</option>
                                <?php foreach ($buses as $bus): ?>
                                    <option value="<?php echo $bus['id']; ?>">
                                        <?php echo htmlspecialchars($bus['bus_number'] . ' - ' . $bus['make'] . ' ' . $bus['model']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="technician_id" class="form-label">
                                Technicien <span class="required-star">*</span>
                            </label>
                            <select class="form-select" id="technician_id" name="technician_id" required>
                                <option value="">Sélectionner un technicien</option>
                                <?php foreach ($technicians as $tech): ?>
                                    <option value="<?php echo $tech['id']; ?>">
                                        <?php echo htmlspecialchars($tech['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Row 2: Work Details -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="work_type" class="form-label">Type de Travail</label>
                            <select class="form-select" id="work_type" name="work_type">
                                <option value="Maintenance">Maintenance</option>
                                <option value="Réparation">Réparation</option>
                                <option value="Inspection">Inspection</option>
                                <option value="Nettoyage">Nettoyage</option>
                                <option value="Autre">Autre</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="priority" class="form-label">Priorité</label>
                            <select class="form-select" id="priority" name="priority">
                                <option value="Normal">Normal</option>
                                <option value="Urgent">Urgent</option>
                                <option value="Très Urgent">Très Urgent</option>
                                <option value="Faible">Faible</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="estimated_hours" class="form-label">Heures Estimées</label>
                            <input type="number" class="form-control" id="estimated_hours" name="estimated_hours" 
                                   min="0.5" step="0.5" placeholder="0.0">
                        </div>
                    </div>
                    
                    <!-- Work Description -->
                    <div class="mb-3">
                        <label for="work_description" class="form-label">
                            Description du Travail <span class="required-star">*</span>
                        </label>
                        <textarea class="form-control" id="work_description" name="work_description" rows="4" 
                                  placeholder="Décrire le travail à effectuer..." required></textarea>
                    </div>
                    
                    <!-- Parts Section -->
                    <div class="mb-4">
                        <h5 class="mb-3 d-flex justify-content-between align-items-center">
                            <span>
                                <i class="fas fa-tools me-2"></i>
                                Pièces Utilisées
                            </span>
                            <button type="button" class="btn btn-sm btn-success" onclick="showAddPartModal()">
                                <i class="fas fa-plus me-1"></i>Ajouter pièce catalogue
                            </button>
                        </h5>
                        <div id="partsContainer">
                            <!-- Parts will be added here dynamically -->
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="addPartBtn">
                            <i class="fas fa-plus me-2"></i>Ajouter une pièce
                        </button>
                    </div>
                    
                    <!-- Buttons -->
                    <div class="d-flex justify-content-between">
                        <div class="ms-auto">
                            <button type="button" class="btn btn-outline-secondary me-2" id="resetBtn">
                                <i class="fas fa-redo me-2"></i>Réinitialiser
                            </button>
                            <button type="submit" name="submit" value="1" class="btn btn-olive">
                                <i class="fas fa-paper-plane me-2"></i>Créer OT
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Existing Work Orders List -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        Ordres de Travail Existants
                    </h5>
                    <div>
                        <button class="btn btn-outline-success btn-sm" onclick="exportWorkOrders()">
                            <i class="fas fa-download me-1"></i>Exporter
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($work_orders)): ?>
                        <p class="text-muted">Aucun ordre de travail trouvé.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>Référence</th>
                                        <th>Bus</th>
                                        <th>Technicien</th>
                                        <th>Type</th>
                                        <th>Priorité</th>
                                        <th>Statut</th>
                                        <th>Heures</th>
                                        <th>Pièces</th>
                                        <th>Coût</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($work_orders as $wo): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($wo['ref_ot'] ?? ''); ?></strong></td>
                                            <td><?php echo htmlspecialchars($wo['bus_number'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($wo['technician_name'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($wo['work_type'] ?? ''); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo getPriorityBadgeClass($wo['priority'] ?? 'Normal'); ?>">
                                                    <?php echo htmlspecialchars($wo['priority'] ?? 'Normal'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo getStatusBadgeClass($wo['status'] ?? 'En attente'); ?>">
                                                    <?php echo htmlspecialchars($wo['status'] ?? 'En attente'); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $wo['estimated_hours'] ?? 0; ?>h</td>
                                            <td>
                                                <?php if ($wo['parts_count'] > 0): ?>
                                                    <span class="badge bg-info"><?php echo $wo['parts_count']; ?></span>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo number_format(floatval($wo['total_parts_cost'] ?? 0), 2, ',', ' '); ?> DH</td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($wo['created_at'])); ?></td>
                                            <td class="d-flex gap-2">
                                                <a href="work_order_view.php?id=<?php echo $wo['id']; ?>" class="btn btn-sm btn-outline-primary" title="Voir">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="work_order_edit.php?id=<?php echo $wo['id']; ?>" class="btn btn-sm btn-outline-warning" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button class="btn btn-sm btn-outline-success" onclick="updateStatus(<?php echo $wo['id']; ?>, 'Terminé')" title="Terminer">
                                                    <i class="fas fa-check"></i>
                                                </button>
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
    
    <!-- Add Part Modal -->
    <div class="modal fade" id="addPartModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>
                        Ajouter une pièce au catalogue
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addPartForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="newCodeArticle" class="form-label">
                                Code Article <span class="required-star">*</span>
                            </label>
                            <input type="text" class="form-control" id="newCodeArticle" name="code_article" required>
                        </div>
                        <div class="mb-3">
                            <label for="newDesignation" class="form-label">
                                Désignation <span class="required-star">*</span>
                            </label>
                            <input type="text" class="form-control" id="newDesignation" name="designation" required>
                        </div>
                        <div class="mb-3">
                            <label for="newCategorie" class="form-label">Catégorie</label>
                            <select class="form-select" id="newCategorie" name="categorie">
                                <option value="Moteur">Moteur</option>
                                <option value="Freinage">Freinage</option>
                                <option value="Suspension">Suspension</option>
                                <option value="Électrique">Électrique</option>
                                <option value="Filtres">Filtres</option>
                                <option value="Liquides">Liquides</option>
                                <option value="Accessoires">Accessoires</option>
                                <option value="Divers">Divers</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="newPrixUnitaire" class="form-label">Prix Unitaire</label>
                            <input type="number" class="form-control" id="newPrixUnitaire" name="prix_unitaire" 
                                   min="0" step="0.01" placeholder="0.00">
                        </div>
                        <div class="mb-3">
                            <label for="newDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="newDescription" name="description" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-plus me-2"></i>Ajouter la pièce
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Include footer -->
    <?php include '../includes/footer.php'; ?>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.form-select').select2({
                theme: 'bootstrap4',
                width: '100%'
            });
            
            let partCount = 0;

            // Add part function
            function addPart() {
                partCount++;
                const partHtml = `
                    <div class="part-row" data-part-id="${partCount}">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <label class="form-label">Réf <span class="required-star">*</span></label>
                                <select class="form-select" name="parts[${partCount}][ref_article]" required>
                                    <option value="">Sélectionner une pièce</option>
                                    <?php foreach ($parts_catalogue as $part): ?>
                                        <option value="<?php echo htmlspecialchars($part['code_article']); ?>">
                                            <?php echo htmlspecialchars($part['recherche_complet']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Quantité <span class="required-star">*</span></label>
                                <input type="number" class="form-control" name="parts[${partCount}][quantity]" 
                                       min="1" step="0.01" required placeholder="Qté">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Prix unitaire</label>
                                <input type="number" class="form-control" name="parts[${partCount}][unit_cost]" 
                                       min="0.01" step="0.01" placeholder="Prix">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Notes</label>
                                <input type="text" class="form-control" name="parts[${partCount}][notes]" 
                                       placeholder="Notes...">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">&nbsp;</label>
                                <button type="button" class="btn btn-remove-part w-100" onclick="removePart(${partCount})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                
                $('#partsContainer').append(partHtml);
                
                // Re-initialize Select2 for new elements
                $('#partsContainer .form-select').select2({
                    theme: 'bootstrap4',
                    width: '100%'
                });
            }

            // Remove part function
            function removePart(partId) {
                $(`.part-row[data-part-id="${partId}"]`).remove();
            }
            
            // Event handlers
            $('#addPartBtn').click(addPart);

            // Reset form
            $('#resetBtn').click(function() {
                if (confirm('Êtes-vous sûr de vouloir réinitialiser le formulaire?')) {
                    $('#workOrderForm')[0].reset();
                    $('#partsContainer').empty();
                    partCount = 0;
                }
            });
            
            // Show add part modal
            window.showAddPartModal = function() {
                $('#addPartModal').modal('show');
            };
            
            // Handle add part form submission
            $('#addPartForm').submit(function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: '../api/add_part_catalogue.php',
                    method: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Add new part to all select dropdowns
                            const newOption = `<option value="${response.part.code_article}">${response.part.recherche_complet}</option>`;
                            $('select[name*="ref_article"]').append(newOption);
                            
                            // Close modal and reset form
                            $('#addPartModal').modal('hide');
                            $('#addPartForm')[0].reset();
                            
                            // Show success message
                            alert('Pièce ajoutée avec succès!');
                        } else {
                            alert('Erreur: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Erreur lors de l\'ajout de la pièce');
                    }
                });
            });
            
            // Add first part by default
            addPart();
        });
        
        // Update status function
        function updateStatus(workOrderId, newStatus) {
            if (confirm('Changer le statut de cet ordre de travail?')) {
                // Implement status update logic here
                console.log('Update status for work order:', workOrderId, 'to:', newStatus);
            }
        }
        
        // Export work orders
        function exportWorkOrders() {
            let csv = 'Référence,Bus,Technicien,Type,Priorité,Statut,Heures,Pièces,Coût,Date\n';
            document.querySelectorAll('tbody tr').forEach(row => {
                let cells = row.querySelectorAll('td');
                let rowData = [
                    cells[0].textContent.trim(),
                    cells[1].textContent.trim(),
                    cells[2].textContent.trim(),
                    cells[3].textContent.trim(),
                    cells[4].textContent.trim(),
                    cells[5].textContent.trim(),
                    cells[6].textContent.trim(),
                    cells[7].textContent.trim(),
                    cells[8].textContent.trim(),
                    cells[9].textContent.trim()
                ];
                csv += rowData.map(cell => `"${cell}"`).join(',') + '\n';
            });
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'work_orders_export.csv';
            a.click();
        }
    </script>
</body>
</html>

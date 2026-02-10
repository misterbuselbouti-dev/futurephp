<?php
// FUTURE AUTOMOTIVE - Work Order Edit
// تعديل أمر العمل

require_once '../config.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!is_logged_in()) {
    header('Location: ../login.php');
    exit();
}

// Vérifier les autorisations
$user = get_logged_in_user();
$role = $user['role'] ?? '';
if (!in_array($role, ['admin', 'maintenance_manager'], true)) {
    http_response_code(403);
    echo 'Accès refusé.';
    exit();
}

// Récupérer l'ID de l'ordre de travail
$work_order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$work_order_id) {
    header('Location: admin_breakdowns_workshop.php');
    exit();
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $database = new Database();
        $pdo = $database->connect();
        
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
        
        // Mettre à jour l'ordre de travail
        $stmt = $pdo->prepare("
            UPDATE work_orders 
            SET bus_id = ?, technician_id = ?, work_description = ?, work_type = ?, 
                priority = ?, estimated_hours = ?, status = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        $stmt->execute([
            $_POST['bus_id'],
            $_POST['technician_id'],
            $_POST['work_description'],
            $_POST['work_type'] ?? 'Maintenance',
            $_POST['priority'] ?? 'Normal',
            $_POST['estimated_hours'] ?? 0,
            $_POST['status'] ?? 'En attente',
            $work_order_id
        ]);
        
        // Mettre à jour les pièces si spécifiées
        if (!empty($_POST['parts'])) {
            // Supprimer les anciennes pièces
            $stmt = $pdo->prepare("DELETE FROM work_order_parts WHERE work_order_id = ?");
            $stmt->execute([$work_order_id]);
            
            // Insérer les nouvelles pièces
            foreach ($_POST['parts'] as $part) {
                if (!empty($part['ref_article']) && !empty($part['quantity'])) {
                    $stmt = $pdo->prepare("
                        INSERT INTO work_order_parts (work_order_id, ref_article, designation, quantity, unit_cost, total_cost, notes) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $total_cost = floatval($part['quantity']) * floatval($part['unit_cost'] ?? 0);
                    
                    $stmt->execute([
                        $work_order_id,
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
        
        // Ajouter à la chronologie
        $stmt = $pdo->prepare("
            INSERT INTO work_order_timeline (work_order_id, action, description, performed_by) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $work_order_id,
            'Modification',
            "Ordre de travail modifié par {$user['full_name']}",
            $user['id']
        ]);
        
        $_SESSION['success_message'] = "Ordre de travail mis à jour avec succès!";
        header("Location: work_order_view.php?id=$work_order_id");
        exit();
        
    } catch (Exception $e) {
        $error_message = "Erreur lors de la mise à jour: " . $e->getMessage();
    }
}

// Récupérer les détails de l'ordre de travail
$work_order = null;
$parts = [];

try {
    $database = new Database();
    $pdo = $database->connect();
    
    // Récupérer l'ordre de travail
    $stmt = $pdo->prepare("
        SELECT wo.*, 
               b.bus_number, b.license_plate, b.make, b.model,
               u.full_name as technician_name
        FROM work_orders wo
        LEFT JOIN buses b ON wo.bus_id = b.id
        LEFT JOIN users u ON wo.technician_id = u.id
        WHERE wo.id = ?
    ");
    $stmt->execute([$work_order_id]);
    $work_order = $stmt->fetch();
    
    if (!$work_order) {
        header('Location: admin_breakdowns_workshop.php');
        exit();
    }
    
    // Récupérer les pièces
    $stmt = $pdo->prepare("SELECT * FROM work_order_parts WHERE work_order_id = ? ORDER BY created_at");
    $stmt->execute([$work_order_id]);
    $parts = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error_message = "Erreur lors du chargement: " . $e->getMessage();
}

// Récupérer les données pour les formulaires
$buses = [];
$technicians = [];
$parts_catalogue = [];

try {
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
    
} catch (Exception $e) {
    // Continuer sans les données
}
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Ordre de Travail #<?php echo $work_order_id; ?> - <?php echo APP_NAME; ?></title>
    
    <!-- Simple Clean Theme -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/simple-theme.css">
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@x.x.x/dist/select2-bootstrap4.min.css" rel="stylesheet" />
    
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
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(var(--primary-rgb), 0.25);
        }
        
        .required-star {
            color: var(--danger);
            margin-left: 4px;
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
        
        .btn-add-part {
            background-color: var(--success);
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
            transition: all 0.2s ease;
        }
        
        .btn-add-part:hover {
            background: var(--success-dark);
            transform: scale(1.1);
        }
        
        .part-row {
            background: var(--bg-light);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            padding: var(--space-3);
            margin-bottom: var(--space-3);
        }
        
        .btn-remove-part {
            background-color: var(--danger);
            border: none;
            color: white;
            border-radius: var(--radius);
            padding: 0.5rem;
            transition: all 0.2s ease;
        }
        
        .btn-remove-part:hover {
            background-color: var(--danger-dark);
            transform: scale(1.05);
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
                        <i class="fas fa-edit me-3"></i>
                        Modifier Ordre de Travail #<?php echo $work_order_id; ?>
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
                    <button class="btn btn-primary" onclick="window.location.href='work_order_view.php?id=<?php echo $work_order_id; ?>'">
                        <i class="fas fa-arrow-left me-2"></i>Retour
                    </button>
                </div>
            </div>
            
            <!-- Messages -->
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Edit Form -->
            <div class="workshop-card">
                <h5 class="mb-4">Modifier l'Ordre de Travail</h5>
                <form method="POST" action="">
                        
                        <!-- Row 1: Bus and Technician -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="bus_id" class="form-label">
                                    Bus <span class="required-star">*</span>
                                </label>
                                <select class="form-select" id="bus_id" name="bus_id" required>
                                    <option value="">Sélectionner un bus</option>
                                    <?php foreach ($buses as $bus): ?>
                                        <option value="<?php echo $bus['id']; ?>" <?php echo $work_order['bus_id'] == $bus['id'] ? 'selected' : ''; ?>>
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
                                        <option value="<?php echo $tech['id']; ?>" <?php echo $work_order['technician_id'] == $tech['id'] ? 'selected' : ''; ?>>
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
                                    <option value="Maintenance" <?php echo $work_order['work_type'] === 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                    <option value="Réparation" <?php echo $work_order['work_type'] === 'Réparation' ? 'selected' : ''; ?>>Réparation</option>
                                    <option value="Inspection" <?php echo $work_order['work_type'] === 'Inspection' ? 'selected' : ''; ?>>Inspection</option>
                                    <option value="Nettoyage" <?php echo $work_order['work_type'] === 'Nettoyage' ? 'selected' : ''; ?>>Nettoyage</option>
                                    <option value="Autre" <?php echo $work_order['work_type'] === 'Autre' ? 'selected' : ''; ?>>Autre</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="priority" class="form-label">Priorité</label>
                                <select class="form-select" id="priority" name="priority">
                                    <option value="Normal" <?php echo $work_order['priority'] === 'Normal' ? 'selected' : ''; ?>>Normal</option>
                                    <option value="Urgent" <?php echo $work_order['priority'] === 'Urgent' ? 'selected' : ''; ?>>Urgent</option>
                                    <option value="Très Urgent" <?php echo $work_order['priority'] === 'Très Urgent' ? 'selected' : ''; ?>>Très Urgent</option>
                                    <option value="Faible" <?php echo $work_order['priority'] === 'Faible' ? 'selected' : ''; ?>>Faible</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="estimated_hours" class="form-label">Heures Estimées</label>
                                <input type="number" class="form-control" id="estimated_hours" name="estimated_hours" 
                                       min="0.5" step="0.5" value="<?php echo $work_order['estimated_hours'] ?? 0; ?>">
                            </div>
                        </div>
                        
                        <!-- Status -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="status" class="form-label">Statut</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="En attente" <?php echo $work_order['status'] === 'En attente' ? 'selected' : ''; ?>>En attente</option>
                                    <option value="En cours" <?php echo $work_order['status'] === 'En cours' ? 'selected' : ''; ?>>En cours</option>
                                    <option value="En pause" <?php echo $work_order['status'] === 'En pause' ? 'selected' : ''; ?>>En pause</option>
                                    <option value="Terminé" <?php echo $work_order['status'] === 'Terminé' ? 'selected' : ''; ?>>Terminé</option>
                                    <option value="Annulé" <?php echo $work_order['status'] === 'Annulé' ? 'selected' : ''; ?>>Annulé</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Work Description -->
                        <div class="mb-3">
                            <label for="work_description" class="form-label">
                                Description du Travail <span class="required-star">*</span>
                            </label>
                            <textarea class="form-control" id="work_description" name="work_description" rows="4" 
                                      placeholder="Décrire le travail à effectuer..." required><?php echo htmlspecialchars($work_order['work_description'] ?? ''); ?></textarea>
                        </div>
                        
                        <!-- Parts Section -->
                        <div class="mb-4">
                            <h5 class="mb-3">
                                <i class="fas fa-tools me-2"></i>
                                Pièces Utilisées
                            </h5>
                            <div id="partsContainer">
                                <?php 
                                $partCount = 0;
                                foreach ($parts as $part): 
                                    $partCount++;
                                ?>
                                    <div class="part-row" data-part-id="<?php echo $partCount; ?>">
                                        <div class="row align-items-center">
                                            <div class="col-md-3">
                                                <label class="form-label">Réf <span class="required-star">*</span></label>
                                                <select class="form-select" name="parts[<?php echo $partCount; ?>][ref_article]" required>
                                                    <option value="">Sélectionner une pièce</option>
                                                    <?php foreach ($parts_catalogue as $part_cat): ?>
                                                        <option value="<?php echo htmlspecialchars($part_cat['code_article']); ?>" <?php echo $part['ref_article'] === $part_cat['code_article'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($part_cat['recherche_complet']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Quantité <span class="required-star">*</span></label>
                                                <input type="number" class="form-control" name="parts[<?php echo $partCount; ?>][quantity]" 
                                                       min="1" step="0.01" required value="<?php echo $part['quantity']; ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Prix unitaire</label>
                                                <input type="number" class="form-control" name="parts[<?php echo $partCount; ?>][unit_cost]" 
                                                       min="0.01" step="0.01" value="<?php echo $part['unit_cost']; ?>">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Notes</label>
                                                <input type="text" class="form-control" name="parts[<?php echo $partCount; ?>][notes]" 
                                                       value="<?php echo htmlspecialchars($part['notes'] ?? ''); ?>">
                                            </div>
                                            <div class="col-md-1">
                                                <label class="form-label">&nbsp;</label>
                                                <button type="button" class="btn btn-remove-part w-100" onclick="removePart(<?php echo $partCount; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="addPartBtn">
                                <i class="fas fa-plus me-2"></i>Ajouter une pièce
                            </button>
                        </div>
                        
                        <!-- Buttons -->
                        <div class="d-flex justify-content-between">
                            <div class="ms-auto">
                                <a href="work_order_view.php?id=<?php echo $work_order_id; ?>" class="btn btn-outline-secondary me-2">
                                    <i class="fas fa-times me-2"></i>Annuler
                                </a>
                                <button type="submit" name="submit" value="1" class="btn btn-primary-custom">
                                    <i class="fas fa-save me-2"></i>Enregistrer
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
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
            
            let partCount = <?php echo $partCount; ?>;

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
        });
    </script>
</body>
</html>

<?php
// FUTURE AUTOMOTIVE - Work Order View
// عرض تفاصيل أمر العمل

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
if (!in_array($role, ['admin', 'maintenance_manager', 'technician'], true)) {
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

// Récupérer les détails de l'ordre de travail
$work_order = null;
$parts = [];
$timeline = [];

try {
    $database = new Database();
    $pdo = $database->connect();
    
    // Récupérer l'ordre de travail
    $stmt = $pdo->prepare("
        SELECT wo.*, 
               b.bus_number, b.license_plate, b.make, b.model,
               u.full_name as technician_name,
               creator.full_name as created_by_name,
               CONCAT('OT-', YEAR(wo.created_at), '-', LPAD(wo.id, 4, '0')) as ref_ot
        FROM work_orders wo
        LEFT JOIN buses b ON wo.bus_id = b.id
        LEFT JOIN users u ON wo.technician_id = u.id
        LEFT JOIN users creator ON wo.created_by = creator.id
        WHERE wo.id = ?
    ");
    $stmt->execute([$work_order_id]);
    $work_order = $stmt->fetch();
    
    if (!$work_order) {
        header('Location: admin_breakdowns_workshop.php');
        exit();
    }
    
    // Récupérer les pièces utilisées
    $stmt = $pdo->prepare("
        SELECT * FROM work_order_parts 
        WHERE work_order_id = ?
        ORDER BY created_at
    ");
    $stmt->execute([$work_order_id]);
    $parts = $stmt->fetchAll();
    
    // Récupérer la chronologie
    $stmt = $pdo->prepare("
        SELECT wot.*, u.full_name as performed_by_name
        FROM work_order_timeline wot
        LEFT JOIN users u ON wot.performed_by = u.id
        WHERE wot.work_order_id = ?
        ORDER BY wot.performed_at
    ");
    $stmt->execute([$work_order_id]);
    $timeline = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error_message = "Erreur lors du chargement: " . $e->getMessage();
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

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_status') {
            $new_status = $_POST['new_status'] ?? '';
            $notes = $_POST['notes'] ?? '';
            
            $stmt = $pdo->prepare("
                UPDATE work_orders 
                SET status = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $stmt->execute([$new_status, $work_order_id]);
            
            // Ajouter à la chronologie
            $stmt = $pdo->prepare("
                INSERT INTO work_order_timeline (work_order_id, action, description, performed_by) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $work_order_id,
                'Changement de statut',
                "Statut changé vers: $new_status" . ($notes ? " - $notes" : ""),
                $user['id']
            ]);
            
            $_SESSION['success_message'] = "Statut mis à jour avec succès!";
            header("Location: work_order_view.php?id=$work_order_id");
            exit();
        }
        
        if ($action === 'add_part') {
            $ref_article = $_POST['ref_article'] ?? '';
            $quantity = $_POST['quantity'] ?? 0;
            $unit_cost = $_POST['unit_cost'] ?? 0;
            $notes = $_POST['notes'] ?? '';
            
            $total_cost = floatval($quantity) * floatval($unit_cost);
            
            $stmt = $pdo->prepare("
                INSERT INTO work_order_parts (work_order_id, ref_article, designation, quantity, unit_cost, total_cost, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $work_order_id,
                $ref_article,
                $_POST['designation'] ?? '',
                $quantity,
                $unit_cost,
                $total_cost,
                $notes
            ]);
            
            // Ajouter à la chronologie
            $stmt = $pdo->prepare("
                INSERT INTO work_order_timeline (work_order_id, action, description, performed_by) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $work_order_id,
                'Ajout de pièce',
                "Pièce ajoutée: $ref_article - Qté: $quantity",
                $user['id']
            ]);
            
            $_SESSION['success_message'] = "Pièce ajoutée avec succès!";
            header("Location: work_order_view.php?id=$work_order_id");
            exit();
        }
        
    } catch (Exception $e) {
        $error_message = "Erreur lors de l'action: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordre de Travail #<?php echo $work_order_id; ?> - <?php echo APP_NAME; ?></title>
    
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
        
        .workshop-card h6 {
            color: var(--text-light);
            font-size: var(--font-size-sm);
            margin-bottom: var(--space-2);
        }
        
        .workshop-card .value {
            font-size: var(--font-size-xl);
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--border);
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: var(--space-5);
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -25px;
            top: 5px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--text-muted);
        }
        
        .timeline-item.success::before {
            background: var(--success);
        }
        
        .timeline-item.info::before {
            background: var(--info);
        }
        
        .timeline-item.warning::before {
            background: var(--warning);
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
                        <i class="fas fa-wrench me-3"></i>
                        Ordre de Travail #<?php echo $work_order_id; ?>
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
                    <button class="btn btn-primary" onclick="window.location.href='admin_breakdowns_workshop.php'">
                        <i class="fas fa-arrow-left me-2"></i>Retour
                    </button>
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
            
            <!-- Quick Info Grid -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="workshop-card">
                        <h6>Bus</h6>
                        <div class="value"><?php echo htmlspecialchars($work_order['bus_number'] ?? ''); ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="workshop-card">
                        <h6>Technicien</h6>
                        <div class="value"><?php echo htmlspecialchars($work_order['technician_name'] ?? ''); ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="workshop-card">
                        <h6>Type</h6>
                        <div class="value"><?php echo htmlspecialchars($work_order['work_type'] ?? ''); ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="workshop-card">
                        <h6>Priorité</h6>
                        <div class="value">
                            <span class="badge bg-<?php echo getPriorityBadgeClass($work_order['priority'] ?? 'Normal'); ?>">
                                <?php echo htmlspecialchars($work_order['priority'] ?? 'Normal'); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Status Info Grid -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="workshop-card">
                        <h6>Statut</h6>
                        <div class="value">
                            <span class="badge bg-<?php echo getStatusBadgeClass($work_order['status'] ?? 'En attente'); ?>">
                                <?php echo htmlspecialchars($work_order['status'] ?? 'En attente'); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="workshop-card">
                        <h6>Heures Estimées</h6>
                        <div class="value"><?php echo $work_order['estimated_hours'] ?? 0; ?>h</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="workshop-card">
                        <h6>Heures Réelles</h6>
                        <div class="value"><?php echo $work_order['actual_hours'] ?? 0; ?>h</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="workshop-card">
                        <h6>Créé par</h6>
                        <div class="value"><?php echo htmlspecialchars($work_order['created_by_name'] ?? ''); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Description -->
            <div class="workshop-card mb-4">
                <h6 class="mb-3">Description du Travail</h6>
                <p><?php echo nl2br(htmlspecialchars($work_order['work_description'] ?? '')); ?></p>
            </div>
            
            <!-- Status Update -->
            <div class="workshop-card mb-4">
                <h6 class="mb-3">Mettre à Jour le Statut</h6>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_status">
                    <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Nouveau Statut</label>
                                <select class="form-select" name="new_status">
                                    <option value="En attente" <?php echo $work_order['status'] === 'En attente' ? 'selected' : ''; ?>>En attente</option>
                                    <option value="En cours" <?php echo $work_order['status'] === 'En cours' ? 'selected' : ''; ?>>En cours</option>
                                    <option value="En pause" <?php echo $work_order['status'] === 'En pause' ? 'selected' : ''; ?>>En pause</option>
                                    <option value="Terminé" <?php echo $work_order['status'] === 'Terminé' ? 'selected' : ''; ?>>Terminé</option>
                                    <option value="Annulé" <?php echo $work_order['status'] === 'Annulé' ? 'selected' : ''; ?>>Annulé</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" rows="1" placeholder="Notes sur le changement de statut..."></textarea>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Mettre à jour
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Parts Used -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-tools me-2"></i>
                        Pièces Utilisées (<?php echo count($parts); ?>)
                    </h6>
                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addPartModal">
                        <i class="fas fa-plus me-1"></i>Ajouter une pièce
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($parts)): ?>
                        <p class="text-muted">Aucune pièce utilisée.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Référence</th>
                                        <th>Désignation</th>
                                        <th>Quantité</th>
                                        <th>Prix Unitaire</th>
                                        <th>Total</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $total_cost = 0;
                                    foreach ($parts as $part): 
                                        $total_cost += $part['total_cost'];
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($part['ref_article']); ?></td>
                                            <td><?php echo htmlspecialchars($part['designation']); ?></td>
                                            <td><?php echo $part['quantity']; ?></td>
                                            <td><?php echo number_format($part['unit_cost'], 2, ',', ' '); ?> DH</td>
                                            <td><?php echo number_format($part['total_cost'], 2, ',', ' '); ?> DH</td>
                                            <td><?php echo htmlspecialchars($part['notes'] ?? '-'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-primary">
                                        <th colspan="4">Total Coût Pièces:</th>
                                        <th><?php echo number_format($total_cost, 2, ',', ' '); ?> DH</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Timeline -->
            <div class="workshop-card">
                <h6 class="mb-3">
                    <i class="fas fa-history me-2"></i>
                    Chronologie
                </h6>
                <div class="timeline">
                    <?php foreach ($timeline as $item): ?>
                        <div class="timeline-item <?php echo $item['action'] === 'Création' ? 'success' : ($item['action'] === 'Début' ? 'info' : ($item['action'] === 'Fin' ? 'success' : 'warning')); ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong><?php echo htmlspecialchars($item['action']); ?></strong>
                                    <p class="mb-0"><?php echo htmlspecialchars($item['description']); ?></p>
                                        <small class="text-muted">Par <?php echo htmlspecialchars($item['performed_by_name']); ?></small>
                                    </div>
                                    <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($item['performed_at'])); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
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
                        Ajouter une pièce
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_part">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Référence</label>
                            <input type="text" class="form-control" name="ref_article" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Désignation</label>
                            <input type="text" class="form-control" name="designation" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Quantité</label>
                                    <input type="number" class="form-control" name="quantity" step="0.01" min="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Prix Unitaire</label>
                                    <input type="number" class="form-control" name="unit_cost" step="0.01" min="0">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Ajouter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

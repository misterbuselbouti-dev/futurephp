<?php
// FUTURE AUTOMOTIVE - Work Orders Management Page
require_once 'config.php';
require_once 'includes/functions.php';

// Check authentication
require_login();

// Page title
$page_title = 'Work Orders';

// Get data from database
try {
    $database = new Database();
    $pdo = $database->connect();
    
    // Get customers
    $stmt = $pdo->query("SELECT id, name FROM customers ORDER BY name");
    $customers = $stmt->fetchAll();
    
    // Get mechanics (users with mecanicien role)
    $stmt = $pdo->query("SELECT id, full_name FROM users WHERE role = 'mecanicien' ORDER BY full_name");
    $mechanics = $stmt->fetchAll();
    
    // If no mechanics found, get all users as fallback
    if (empty($mechanics)) {
        $stmt = $pdo->query("SELECT id, full_name FROM users ORDER BY full_name");
        $mechanics = $stmt->fetchAll();
    }
    
    // Get cars
    $stmt = $pdo->query("
        SELECT car.id, car.make, car.model, car.plate_number, cus.name as customer_name 
        FROM cars car
        JOIN customers cus ON car.customer_id = cus.id
        ORDER BY car.make, car.model
    ");
    $cars = $stmt->fetchAll();
    
    // Get work orders
    $stmt = $pdo->query("
        SELECT wo.*, c.name as customer_name, ca.make, ca.model, u.full_name as mechanic_name 
        FROM work_orders wo
        JOIN customers c ON wo.customer_id = c.id
        JOIN cars ca ON wo.car_id = ca.id
        LEFT JOIN users u ON wo.mechanic_id = u.id
        ORDER BY wo.created_at DESC
    ");
    $work_orders = $stmt->fetchAll();
    
} catch (Exception $e) {
    $customers = [];
    $mechanics = [];
    $work_orders = [];
    $error_message = "Error loading data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="mb-2">
                        <i class="fas fa-wrench me-3"></i>
                        Work Orders
                    </h1>
                    <p class="text-muted">Manage repair and maintenance work orders</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Liste des Ordres de Travail</h5>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addWorkOrderModal">
                        <i class="fas fa-plus me-2"></i>Nouvel Ordre de Travail
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>OT #</th>
                                    <th>Date</th>
                                    <th>Client</th>
                                    <th>Véhicule</th>
                                    <th>Statut</th>
                                    <th>Priorité</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($error_message)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-danger">
                                            <?php echo htmlspecialchars($error_message); ?>
                                        </td>
                                    </tr>
                                <?php elseif (empty($work_orders)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">Aucun ordre de travail trouvé</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($work_orders as $work_order): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($work_order['work_order_number']); ?></strong></td>
                                            <td><?php echo date('d/m/Y', strtotime($work_order['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($work_order['customer_name']); ?></td>
                                            <td><?php echo htmlspecialchars($work_order['make'] . ' ' . $work_order['model']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $work_order['status'] === 'en_attente' ? 'warning' : 
                                                    ($work_order['status'] === 'en_cours' ? 'primary' : 
                                                    ($work_order['status'] === 'termine' ? 'success' : 'danger')); 
                                                ?>">
                                                    <?php 
                                                    $status_labels = [
                                                        'en_attente' => 'En attente',
                                                        'en_cours' => 'En cours',
                                                        'termine' => 'Terminé',
                                                        'annule' => 'Annulé'
                                                    ];
                                                    echo $status_labels[$work_order['status']] ?? $work_order['status'];
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $work_order['priority'] === 'urgent' ? 'danger' : 
                                                    ($work_order['priority'] === 'eleve' ? 'warning' : 
                                                    ($work_order['priority'] === 'faible' ? 'secondary' : 'info')); 
                                                ?>">
                                                    <?php 
                                                    $priority_labels = [
                                                        'faible' => 'Faible',
                                                        'moyen' => 'Moyen',
                                                        'eleve' => 'Élevé',
                                                        'urgent' => 'Urgent'
                                                    ];
                                                    echo $priority_labels[$work_order['priority']] ?? $work_order['priority'];
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-primary" onclick="editWorkOrder(<?php echo $work_order['id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-info" onclick="viewWorkOrder(<?php echo $work_order['id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-success" onclick="updateStatus(<?php echo $work_order['id']; ?>)">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteWorkOrder(<?php echo $work_order['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Work Order Modal -->
    <div class="modal fade" id="addWorkOrderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Créer un Nouvel Ordre de Travail</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addWorkOrderForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Client</label>
                                <select class="form-control" name="customer_id" required>
                                    <option value="">Sélectionner un client</option>
                                    <?php foreach ($customers as $customer): ?>
                                        <option value="<?php echo $customer['id']; ?>"><?php echo htmlspecialchars($customer['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Véhicule</label>
                                <select class="form-control" name="car_id" required>
                                    <option value="">Sélectionner un véhicule</option>
                                    <?php if (!empty($cars)): ?>
                                        <?php foreach ($cars as $car): ?>
                                            <option value="<?php echo $car['id']; ?>">
                                                <?php echo htmlspecialchars($car['make'] . ' ' . $car['model'] . ' - ' . $car['plate_number'] . ' (' . $car['customer_name'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="" disabled>Aucun véhicule disponible pour le moment</option>
                                    <?php endif; ?>
                                </select>
                                <?php if (empty($cars)): ?>
                                    <div class="alert alert-warning mt-2">
                                        <small>
                                            <i class="fas fa-exclamation-triangle"></i>
                                            Aucun véhicule enregistré pour le moment.
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Titre</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Priorité</label>
                                <select class="form-control" name="priority" required>
                                    <option value="faible">Faible</option>
                                    <option value="moyen" selected>Moyen</option>
                                    <option value="eleve">Élevé</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mécanicien</label>
                                <select class="form-control" name="mechanic_id">
                                    <option value="">Sélectionner un mécanicien (Optionnel)</option>
                                    <?php if (!empty($mechanics)): ?>
                                        <?php foreach ($mechanics as $mechanic): ?>
                                            <option value="<?php echo $mechanic['id']; ?>"><?php echo htmlspecialchars($mechanic['full_name']); ?></option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="" disabled>Aucun mécanicien disponible</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Coût Estimé</label>
                                <input type="number" class="form-control" name="estimated_cost" step="0.01">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" onclick="saveWorkOrder()">Créer l'Ordre de Travail</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        function saveWorkOrder() {
            const form = document.getElementById('addWorkOrderForm');
            const formData = new FormData(form);
            
            fetch('api/work_orders/save.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addWorkOrderModal'));
                    modal.hide();
                    
                    // Clear form
                    form.reset();
                    
                    // Show success message
                    showAlert('Ordre de travail créé avec succès! Numéro: ' + data.work_order_number, 'success');
                    
                    // Reload page after 2 seconds
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showAlert('Erreur: ' + data.error, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Erreur lors de la création de l\'ordre de travail', 'danger');
            });
        }
        
        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const mainContent = document.querySelector('.main-content');
            mainContent.insertBefore(alertDiv, mainContent.firstChild);
            
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }
    </script>
</body>
</html>

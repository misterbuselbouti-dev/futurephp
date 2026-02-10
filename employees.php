<?php
// FUTURE AUTOMOTIVE - Employees Management Page
require_once 'config.php';
require_once 'includes/functions.php';

// Check authentication
require_login();

// Page title
$page_title = 'Employees';

// Get employees from database
try {
    $database = new Database();
    $pdo = $database->connect();
    
    // Check if users table has data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $user_count = $stmt->fetch()['count'];
    
    if ($user_count == 0) {
        // Insert sample employees if table is empty
        $sample_employees = [
            ['admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@future.ma', 'admin', '0660000000', 1],
            ['maintenance', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maintenance Manager', 'maintenance@future.ma', 'maintenance_manager', '0661111111', 1],
            ['technician1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Technician One', 'tech1@future.ma', 'technician', '0662222222', 1],
            ['agent1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Agent One', 'agent1@future.ma', 'agent', '0663333333', 1],
        ];
        
        foreach ($sample_employees as $employee) {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, role, phone, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute($employee);
        }
    }
    
    $stmt = $pdo->query("
        SELECT u.id, u.username, u.full_name, u.email, u.role, u.phone, u.is_active, u.created_at
        FROM users u
        ORDER BY u.full_name
    ");
    $employees = $stmt->fetchAll();
} catch (Exception $e) {
    $employees = [];
    $error_message = "Error loading employees: " . $e->getMessage();
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
                        <i class="fas fa-user-tie me-3"></i>
                        Employees
                    </h1>
                    <p class="text-muted">Manage staff and employee information</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Liste des Employés</h5>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                        <i class="fas fa-plus me-2"></i>Ajouter un Employé
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Téléphone</th>
                                    <th>Rôle</th>
                                    <th>Spécialité</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($error_message)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-danger">
                                            <?php echo htmlspecialchars($error_message); ?>
                                        </td>
                                    </tr>
                                <?php elseif (empty($employees)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">Aucun employé trouvé</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($employees as $employee): ?>
                                        <?php 
                                        function getRoleDisplay($role) {
                                            $roles = [
                                                'mecanicien' => ['color' => 'success', 'specialty' => 'Mécanique', 'icon' => 'fa-wrench'],
                                                'electricien' => ['color' => 'warning', 'specialty' => 'Électricité', 'icon' => 'fa-bolt'],
                                                'tolier' => ['color' => 'info', 'specialty' => 'Tôlerie', 'icon' => 'fa-hammer'],
                                                'peintre' => ['color' => 'danger', 'specialty' => 'Peinture', 'icon' => 'fa-paint-brush'],
                                                'chef_atelier' => ['color' => 'dark', 'specialty' => 'Supervision', 'icon' => 'fa-user-tie'],
                                                'receptionniste' => ['color' => 'secondary', 'specialty' => 'Accueil', 'icon' => 'fa-user'],
                                                'technician' => ['color' => 'primary', 'specialty' => 'Technique', 'icon' => 'fa-tools'],
                                                'agent' => ['color' => 'light', 'specialty' => 'Service', 'icon' => 'fa-user'],
                                                'maintenance_manager' => ['color' => 'warning', 'specialty' => 'Maintenance', 'icon' => 'fa-cogs'],
                                                'admin' => ['color' => 'danger', 'specialty' => 'Administration', 'icon' => 'fa-shield-alt']
                                            ];
                                            return $roles[$role] ?? ['color' => 'secondary', 'specialty' => 'Autre', 'icon' => 'fa-user'];
                                        }
                                        $roleDisplay = getRoleDisplay($employee['role']); ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($employee['id']); ?></td>
                                            <td><?php echo htmlspecialchars($employee['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($employee['email']); ?></td>
                                            <td><?php echo htmlspecialchars($employee['phone'] ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $roleDisplay['color']; ?>">
                                                    <i class="fas <?php echo $roleDisplay['icon']; ?> me-1"></i>
                                                    <?php 
                                                    $roleLabels = [
                                                        'mecanicien' => 'Mécanicien',
                                                        'electricien' => 'Électricien',
                                                        'tolier' => 'Tôlier',
                                                        'peintre' => 'Peintre',
                                                        'chef_atelier' => 'Chef d\'Atelier',
                                                        'receptionniste' => 'Réceptionniste',
                                                        'technician' => 'Technicien',
                                                        'agent' => 'Agent',
                                                        'maintenance_manager' => 'Resp. Maintenance',
                                                        'admin' => 'Administrateur'
                                                    ];
                                                    echo $roleLabels[$employee['role']] ?? $employee['role'];
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark">
                                                    <?php echo $roleDisplay['specialty']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo ($employee['is_active'] ?? 1) ? 'success' : 'secondary'; ?>">
                                                    <?php echo ($employee['is_active'] ?? 1) ? 'Actif' : 'Inactif'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-primary" onclick="editEmployee(<?php echo $employee['id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-info" onclick="viewEmployee(<?php echo $employee['id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-warning" onclick="toggleEmployeeStatus(<?php echo $employee['id']; ?>)">
                                                        <i class="fas fa-<?php echo ($employee['is_active'] ?? 1) ? 'ban' : 'check'; ?>"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteEmployee(<?php echo $employee['id']; ?>)">
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

    <!-- Add Employee Modal -->
    <div class="modal fade" id="addEmployeeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un Nouvel Employé</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addEmployeeForm">
                        <div class="mb-3">
                            <label class="form-label">Nom Complet</label>
                            <input type="text" class="form-control" name="full_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nom d'utilisateur</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mot de passe</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" name="phone" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Poste</label>
                            <input type="text" class="form-control" name="position" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Département</label>
                            <select class="form-control" name="department" required>
                                <option value="">Sélectionner un département</option>
                                <option value="mechanics">Mécanique</option>
                                <option value="service">Service</option>
                                <option value="admin">Administration</option>
                                <option value="management">Direction</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rôle</label>
                            <select class="form-control" name="role" required>
                                <option value="">Sélectionner un rôle</option>
                                
                                <!-- Garage Workers -->
                                <optgroup label="Travailleurs du Garage">
                                    <option value="mecanicien">Mécanicien</option>
                                    <option value="electricien">Électricien</option>
                                    <option value="tolier">Tôlier</option>
                                    <option value="peintre">Peintre</option>
                                    <option value="chef_atelier">Chef d'Atelier</option>
                                </optgroup>
                                
                                <!-- Other Roles -->
                                <optgroup label="Autres Rôles">
                                    <option value="receptionniste">Réceptionniste</option>
                                    <option value="technician">Technicien</option>
                                    <option value="agent">Agent</option>
                                    <option value="maintenance_manager">Responsable Maintenance</option>
                                    <option value="admin">Administrateur</option>
                                </optgroup>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Salaire</label>
                            <input type="number" class="form-control" name="salary" step="0.01">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" onclick="saveEmployee()">Enregistrer l'Employé</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        function saveEmployee() {
            const form = document.getElementById('addEmployeeForm');
            const formData = new FormData(form);
            
            // Validate passwords match if needed
            const password = formData.get('password');
            if (password.length < 6) {
                showAlert('كلمة المرور يجب أن تكون 6 أحرف على الأقل', 'danger');
                return;
            }
            
            fetch('api/employees/save.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addEmployeeModal'));
                    modal.hide();
                    
                    // Clear form
                    form.reset();
                    
                    // Show success message
                    showAlert('تم إضافة الموظف بنجاح! رقم الموظف: ' + data.employee.employee_number, 'success');
                    
                    // Reload page after 2 seconds to show new employee
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showAlert('خطأ: ' + data.error, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('خطأ أثناء إضافة الموظف', 'danger');
            });
        }
        
        function showAlert(message, type) {
            // Create alert element
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            // Insert at the top of the main content
            const mainContent = document.querySelector('.main-content');
            mainContent.insertBefore(alertDiv, mainContent.firstChild);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }
    </script>
</body>
</html>

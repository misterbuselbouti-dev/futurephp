<?php
// FUTURE AUTOMOTIVE - Users Management
// إدارة المستخدمين - بناءً على تصميم صفحة الحافلات

require_once 'config.php';
require_once 'includes/functions.php';

// Check authentication
require_login();

// Get current user
$current_user = get_logged_in_user();
$current_role = $current_user['role'] ?? 'user';

// Only admin can access this page
if ($current_role !== 'admin') {
    $_SESSION['error_message'] = 'Accès refusé. Seul l\'administrateur peut gérer les utilisateurs.';
    redirect('dashboard.php');
}

$page_title = 'Gestion Utilisateurs';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $pdo = $database->connect();

    // Detect users schema (status vs is_active)
    $userCols = [];
    try {
        $userCols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        $userCols = [];
    }
    $hasIsActive = in_array('is_active', $userCols, true);
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $username = sanitize_input($_POST['username'] ?? '');
        $fullName = sanitize_input($_POST['full_name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';
        $phone = sanitize_input($_POST['phone'] ?? '');
        $isActive = (int)($_POST['is_active'] ?? 1);

        if ($password === '' || !ctype_digit($password) || strlen($password) < 4 || strlen($password) > 8) {
            $error_message = 'Code secret doit être des chiffres uniquement (4 à 8 chiffres).';
        }

        // Matricule must be unique
        if (!isset($error_message)) {
            $check = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            $check->execute([$username]);
            if ($check->fetch()) {
                $error_message = 'Matricule existe déjà. Veuillez choisir un autre.';
            }
        }

        if (!isset($error_message)) {
            $stmt = $pdo->prepare("
                INSERT INTO users (username, full_name, email, password, role, phone, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $username,
                $fullName,
                $email,
                $password,
                $role,
                $phone,
                $isActive
            ]);

            $_SESSION['message'] = 'Utilisateur ajouté avec succès';
            redirect('users_management.php');
        }
        
    } elseif ($action === 'edit') {
        $username = sanitize_input($_POST['username'] ?? '');
        $id = (int)($_POST['id'] ?? 0);

        $newPassword = $_POST['password'] ?? '';
        if ($newPassword !== '' && (!ctype_digit($newPassword) || strlen($newPassword) < 4 || strlen($newPassword) > 8)) {
            $error_message = 'Code secret doit être des chiffres uniquement (4 à 8 chiffres).';
        }

        // Matricule must be unique
        if (!isset($error_message)) {
            $check = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1");
            $check->execute([$username, $id]);
            if ($check->fetch()) {
                $error_message = 'Matricule existe déjà. Veuillez choisir un autre.';
            }
        }

        if (!isset($error_message)) {
        $stmt = $pdo->prepare("
            UPDATE users SET 
                username = ?, 
                full_name = ?, 
                email = ?, 
                role = ?, 
                phone = ?, 
                is_active = ?
            WHERE id = ?
        ");
        
        $params = [
            $username,
            sanitize_input($_POST['full_name']),
            sanitize_input($_POST['email']),
            $_POST['role'],
            sanitize_input($_POST['phone'] ?? ''),
            (int)($_POST['is_active'] ?? 1),
            $id
        ];
        
        // Update password only if provided
        if (!empty($_POST['password'])) {
            $stmt = $pdo->prepare("
                UPDATE users SET 
                    username = ?, 
                    full_name = ?, 
                    email = ?, 
                    password = ?, 
                    role = ?, 
                    phone = ?, 
                    is_active = ?
                WHERE id = ?
            ");
            array_splice($params, 3, 0, [$_POST['password']]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE users SET 
                    username = ?, 
                    full_name = ?, 
                    email = ?, 
                    role = ?, 
                    phone = ?, 
                    is_active = ?
                WHERE id = ?
            ");
        }
        
        $stmt->execute($params);
        
        $_SESSION['message'] = 'Utilisateur mis à jour avec succès';
        redirect('users_management.php');
        }
        
    } elseif ($action === 'delete') {
        // Prevent deleting the current admin
        if ($_POST['id'] == $current_user['id']) {
            $_SESSION['error_message'] = 'Vous ne pouvez pas supprimer votre propre compte';
            redirect('users_management.php');
        }
        
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        
        $_SESSION['message'] = 'Utilisateur supprimé avec succès';
        redirect('users_management.php');
    } elseif ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        $isActive = (int)($_POST['is_active'] ?? 1);

        // Prevent freezing current admin
        if ($id === (int)$current_user['id']) {
            $_SESSION['error_message'] = 'Vous ne pouvez pas désactiver votre propre compte';
            redirect('users_management.php');
        }

        if ($hasIsActive) {
            $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?")->execute([$isActive, $id]);
        }

        $_SESSION['message'] = $isActive === 1 ? 'Utilisateur activé' : 'Utilisateur désactivé';
        redirect('users_management.php');
    }
}

// Get statistics
    $total_users = 0;
    $active_users = 0;
    $admin_users = 0;
    
    try {
        $database = new Database();
        $pdo = $database->connect();
        
        // Get total users count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $total_users = (int)$stmt->fetch()['count'];
        
        // Get active users count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE is_active = 1");
        $active_users = (int)$stmt->fetch()['count'];
        
        // Get admin users count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
        $admin_users = (int)$stmt->fetch()['count'];
        
        // Get all users for display
        $selectCols = [
            'id',
            'username',
            'full_name',
            'email',
            'phone',
            'role',
            'created_at'
        ];
        
        // Check if is_active column exists
        $userCols = [];
        try {
            $userCols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            $userCols = [];
        }
        $hasIsActive = in_array('is_active', $userCols, true);
        
        if ($hasIsActive) {
            $selectCols[] = 'is_active';
        }
        
        $stmt = $pdo->query("SELECT " . implode(', ', $selectCols) . " FROM users ORDER BY created_at DESC");
        $users = $stmt->fetchAll();
        
    } catch (Exception $e) {
        $error_message = "Erreur lors du chargement des utilisateurs: " . $e->getMessage();
        $users = [];
        
        // Initialize variables on error
        $total_users = 0;
        $active_users = 0;
        $admin_users = 0;
    }

// Get user for editing
$edit_user = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_user = $stmt->fetch();
}

function getRoleBadgeClass($role) {
    $classes = [
        'admin' => 'danger',
        'achat_manager' => 'success',
        'maintenance_manager' => 'info',
        'technician' => 'secondary',
        'driver' => 'warning',
        // Legacy roles for compatibility
        'manager' => 'success',
        'user' => 'primary'
    ];
    return $classes[$role] ?? 'secondary';
}
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - FUTURE AUTOMOTIVE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .main-content {
            margin-right: 250px;
            padding: 20px;
            transition: margin-right 0.3s;
        }
        .sidebar-collapsed .main-content {
            margin-right: 70px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border: none;
        }
        .table th {
            background-color: #007bff;
            color: white;
            border: none;
        }
        .btn-group .btn {
            margin: 0 2px;
        }
        .status-active {
            color: #28a745;
        }
        .status-inactive {
            color: #dc3545;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
        }
        .role-badge {
            font-size: 0.8rem;
        }
        @media (max-width: 992px) { 
            .main-content { 
                margin-right: 0; 
            } 
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
                        <i class="fas fa-users me-3"></i>
                        Gestion Utilisateurs
                    </h1>
                    <p class="text-muted">Gestion des comptes utilisateurs et des droits d'accès</p>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0"><?php echo $total_users; ?></h4>
                                    <p class="mb-0">Total Utilisateurs</p>
                                </div>
                                <i class="fas fa-users fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0"><?php echo $active_users; ?></h4>
                                    <p class="mb-0">Utilisateurs Actifs</p>
                                </div>
                                <i class="fas fa-check-circle fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0"><?php echo $admin_users; ?></h4>
                                    <p class="mb-0">Administrateurs</p>
                                </div>
                                <i class="fas fa-user-shield fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0">
                                        <a href="purchase_performance.php" class="text-decoration-none text-info" title="Rapport Achats">
                                            <i class="fas fa-chart-line"></i>
                                        </a>
                                    </h4>
                                    <p class="mb-0">
                                        <a href="purchase_performance.php" class="text-decoration-none text-info" title="Rapport Achats">
                                            Rapport Achats
                                        </a>
                                    </p>
                                </div>
                                <i class="fas fa-chart-line fa-2x opacity-75 text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Alert Messages -->
            <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Liste Utilisateurs</h5>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="fas fa-plus me-2"></i>Ajouter Utilisateur
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($users)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-users fa-3x text-secondary mb-3"></i>
                        <p class="text-secondary">Aucun utilisateur pour le moment</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="fas fa-plus me-2"></i>Ajouter Nouvel Utilisateur
                        </button>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Matricule</th>
                                    <th>Nom Complet</th>
                                    <th>Email</th>
                                    <th>Téléphone</th>
                                    <th>Rôle</th>
                                    <th>Statut</th>
                                    <th>Date Création</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge role-badge bg-<?php echo getRoleBadgeClass($user['role']); ?>">
                                            <?php echo htmlspecialchars($user['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                             $rowActive = (int)($user['is_active'] ?? 0) === 1;
                                        ?>
                                        <span class="badge bg-<?php echo $rowActive ? 'success' : 'secondary'; ?>">
                                            <?php echo $rowActive ? 'Actif' : 'Inactif'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $user['created_at'] ? date('Y-m-d H:i', strtotime($user['created_at'])) : 'Inconnu'; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-primary" onclick="editUser(<?php echo $user['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($user['id'] != $current_user['id']): ?>
                                            <?php if ($rowActive): ?>
                                            <button class="btn btn-sm btn-outline-warning" onclick="toggleStatus(<?php echo $user['id']; ?>, 'inactive')" title="Désactiver">
                                                <i class="fas fa-user-lock"></i>
                                            </button>
                                            <?php else: ?>
                                            <button class="btn btn-sm btn-outline-success" onclick="toggleStatus(<?php echo $user['id']; ?>, 'active')" title="Activer">
                                                <i class="fas fa-user-check"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php if ($user['id'] != $current_user['id']): ?>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-outline-info" onclick="viewPurchasePerformance()" title="Rapport Achats">
                                                <i class="fas fa-chart-line"></i>
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

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus me-2"></i>
                        Ajouter Nouvel Utilisateur
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="username" class="form-label">Matricule</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Nom Complet</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Code secret</label>
                            <input type="password" class="form-control" id="password" name="password" required inputmode="numeric" pattern="\d{4,8}">
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Rôle</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="admin">Admin</option>
                                <option value="achat_manager">Responsable Achat</option>
                                <option value="maintenance_manager">Responsable Maintenance</option>
                                <option value="technician">Technicien</option>
                                <option value="driver">Chauffeur</option>
                                <option value="user">User (legacy)</option>
                                <option value="manager">Manager (legacy)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="phone" name="phone">
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                                <label class="form-check-label" for="is_active">
                                    Actif
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Ajouter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editUser(userId) {
            // Get user data
            const users = <?php echo json_encode($users); ?>;
            const user = users.find(u => u.id == userId);
            
            if (user) {
                document.getElementById('edit_user_id').value = user.id;
                document.getElementById('edit_username').value = user.username;
                document.getElementById('edit_full_name').value = user.full_name;
                document.getElementById('edit_email').value = user.email;
                document.getElementById('edit_role').value = user.role;
                document.getElementById('edit_phone').value = user.phone || '';
                
                const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
                modal.show();
            }
        }

        // Show/hide driver link based on role
        function toggleDriverLink(selectId, groupId) {
            // Driver linking removed - function disabled
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Driver linking removed - no initialization needed
        });

        function deleteUser(userId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${userId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function toggleStatus(userId, newStatus) {
            const msg = newStatus === 'inactive' ? 'Désactiver cet utilisateur ?' : 'Activer cet utilisateur ?';
            if (!confirm(msg)) return;
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="id" value="${userId}">
                <input type="hidden" name="new_status" value="${newStatus}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        function viewPurchasePerformance() {
            window.location.href = 'purchase_performance.php';
        }
    </script>
</body>
</html>

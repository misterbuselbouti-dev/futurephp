<?php
// FUTURE AUTOMOTIVE - Conducteurs (Chauffeurs)
// Structure: N° Conducteur | Nom | Prénom | Bus Affecté | Taux Horaire
require_once 'config.php';
require_once 'includes/functions.php';
require_login();

$page_title = 'Chauffeurs';
$pdo = null;

// Détecter structure: nom/prenom ou name
$has_nom = false;
try {
    $database = new Database();
    $pdo = $database->connect();
    $cols = $pdo->query("SHOW COLUMNS FROM drivers")->fetchAll(PDO::FETCH_COLUMN);
    $has_nom = in_array('nom', $cols) && in_array('prenom', $cols);
} catch (Exception $e) {}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $cin = trim($_POST['cin'] ?? '');
        if ($has_nom) {
            $num = (int)($_POST['numero_conducteur'] ?? 0);
            $nom = trim($_POST['nom'] ?? '');
            $prenom = trim($_POST['prenom'] ?? '');
            
            // Check if taux_horaire column exists
            $has_taux = false;
            try {
                $cols = $pdo->query("SHOW COLUMNS FROM drivers")->fetchAll(PDO::FETCH_COLUMN);
                $has_taux = in_array('taux_horaire', $cols);
            } catch (Exception $e) {}
            
            if ($has_taux) {
                $taux = (float)($_POST['taux_horaire'] ?? 15.48);
                $stmt = $pdo->prepare("INSERT INTO drivers (numero_conducteur, nom, prenom, taux_horaire, phone, email, cin, pin_code, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, '0000', 1)");
                $stmt->execute([$num ?: null, $nom, $prenom, $taux, $phone, $email, $cin]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO drivers (numero_conducteur, nom, prenom, phone, email, cin, pin_code, is_active) VALUES (?, ?, ?, ?, ?, ?, '0000', 1)");
                $stmt->execute([$num ?: null, $nom, $prenom, $phone, $email, $cin]);
            }
        } else {
            $name = trim($_POST['name'] ?? '');
            $stmt = $pdo->prepare("INSERT INTO drivers (name, phone, email, cin, pin_code, is_active) VALUES (?, ?, ?, ?, '0000', 1)");
            $stmt->execute([$name, $phone, $email, $cin]);
        }
        $_SESSION['message'] = 'Chauffeur ajouté (PIN: 0000)';
        header('Location: drivers.php');
        exit;
    }
    
    if ($action === 'edit') {
        $id = (int)$_POST['id'];
        $bus_id = isset($_POST['bus_id']) && $_POST['bus_id'] !== '' ? (int)$_POST['bus_id'] : null;
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $cin = trim($_POST['cin'] ?? '');
        if ($has_nom) {
            $nom = trim($_POST['nom'] ?? '');
            $prenom = trim($_POST['prenom'] ?? '');
            
            // Check if taux_horaire column exists
            $has_taux = false;
            try {
                $cols = $pdo->query("SHOW COLUMNS FROM drivers")->fetchAll(PDO::FETCH_COLUMN);
                $has_taux = in_array('taux_horaire', $cols);
            } catch (Exception $e) {}
            
            if ($has_taux) {
                $taux = (float)($_POST['taux_horaire'] ?? 15.48);
                $pdo->prepare("UPDATE drivers SET nom=?, prenom=?, taux_horaire=?, bus_id=?, phone=?, email=?, cin=? WHERE id=?")
                   ->execute([$nom, $prenom, $taux, $bus_id, $phone, $email, $cin, $id]);
            } else {
                $pdo->prepare("UPDATE drivers SET nom=?, prenom=?, bus_id=?, phone=?, email=?, cin=? WHERE id=?")
                   ->execute([$nom, $prenom, $bus_id, $phone, $email, $cin, $id]);
            }
        } else {
            $name = trim($_POST['name'] ?? '');
            $pdo->prepare("UPDATE drivers SET name=?, bus_id=?, phone=?, email=?, cin=? WHERE id=?")
               ->execute([$name, $bus_id, $phone, $email, $cin, $id]);
        }

        // Sync assignment (1 bus -> 1 driver)
        $pdo->beginTransaction();
        try {
            // Clear any bus currently pointing to this driver
            $pdo->prepare("UPDATE buses SET driver_id = NULL WHERE driver_id = ?")->execute([$id]);

            if (!empty($bus_id)) {
                // If this bus was assigned to another driver, unassign it there too
                $pdo->prepare("UPDATE drivers SET bus_id = NULL WHERE bus_id = ? AND id <> ?")->execute([$bus_id, $id]);
                $pdo->prepare("UPDATE buses SET driver_id = ? WHERE id = ?")->execute([$id, $bus_id]);
            } else {
                // If driver has no bus now, ensure no bus points to him
                $pdo->prepare("UPDATE buses SET driver_id = NULL WHERE driver_id = ?")->execute([$id]);
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        $_SESSION['message'] = 'Chauffeur mis à jour';
        header('Location: drivers.php');
        exit;
    }
    
    if ($action === 'toggle_driver_status') {
        $id = (int)($_POST['id'] ?? 0);
        $isActive = (int)($_POST['is_active'] ?? 0);
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("UPDATE drivers SET is_active = ? WHERE id = ?");
            $result = $stmt->execute([$isActive, $id]);
            if (!$result) {
                throw new Exception('Aucune ligne mise à jour');
            }
            $pdo->commit();
            $_SESSION['message'] = $isActive ? 'Chauffeur activé' : 'Chauffeur désactivé';
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error_message'] = 'Erreur: ' . $e->getMessage();
        }
        header('Location: drivers.php');
        exit;
    }
    if ($action === 'assign_bus') {
        $driver_id = (int)$_POST['driver_id'];
        $bus_id = $_POST['bus_id'] ? (int)$_POST['bus_id'] : null;

        $pdo->beginTransaction();
        try {
            // Detach current bus from this driver
            $pdo->prepare("UPDATE buses SET driver_id = NULL WHERE driver_id = ?")->execute([$driver_id]);

            if ($bus_id) {
                // Ensure the bus is not assigned to another driver
                $pdo->prepare("UPDATE drivers SET bus_id = NULL WHERE bus_id = ? AND id <> ?")->execute([$bus_id, $driver_id]);
                $pdo->prepare("UPDATE drivers SET bus_id = ? WHERE id = ?")->execute([$bus_id, $driver_id]);
                $pdo->prepare("UPDATE buses SET driver_id = ? WHERE id = ?")->execute([$driver_id, $bus_id]);
            } else {
                $pdo->prepare("UPDATE drivers SET bus_id = NULL WHERE id = ?")->execute([$driver_id]);
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        $_SESSION['message'] = 'Bus assigné';
        header('Location: drivers.php');
        exit;
    }
}

// Load drivers
$drivers = [];
$available_buses = [];
try {
    // First check if drivers table exists and has data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM drivers");
    $driver_count = $stmt->fetch()['count'];
    
    if ($driver_count == 0) {
        // Insert sample drivers if table is empty
        $sample_drivers = [
            ['ALAMI', 'Mohammed', 'DR-001', '0661234567', 'm.alami@future.ma', 'AB123456'],
            ['BENANI', 'Ahmed', 'DR-002', '0662345678', 'a.benani@future.ma', 'CD234567'],
            ['CHAKIR', 'Youssef', 'DR-003', '0663456789', 'y.chakir@future.ma', 'EF345678'],
            ['DAHMANI', 'Omar', 'DR-004', '0664567890', 'o.dahmani@future.ma', 'GH456789'],
            ['EL IDRISSI', 'Karim', 'DR-005', '0665678901', 'k.elidrissi@future.ma', 'IJ567890']
        ];
        
        foreach ($sample_drivers as $driver) {
            $stmt = $pdo->prepare("INSERT INTO drivers (nom, prenom, numero_conducteur, phone, email, cin, is_active, pin_code) VALUES (?, ?, ?, ?, ?, ?, 1, '1234')");
            $stmt->execute($driver);
        }
    }
    
    $stmt = $pdo->query("
        SELECT d.*, b.bus_number, b.make, b.model
        FROM drivers d 
        LEFT JOIN buses b ON d.bus_id = b.id 
        ORDER BY " . ($has_nom ? "d.numero_conducteur, d.nom" : "d.name")
    );
    $drivers = $stmt->fetchAll();
    $stmt = $pdo->query("SELECT id, bus_number, make, model FROM buses ORDER BY bus_number");
    $available_buses = $stmt->fetchAll();
} catch (Exception $e) {
    $error_message = $e->getMessage();
}

$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$edit_driver = null;
if ($edit_id) {
    foreach ($drivers as $d) { if ($d['id'] == $edit_id) { $edit_driver = $d; break; } }
}

function dv($d, $key, $def = '') { return isset($d[$key]) ? htmlspecialchars($d[$key]) : $def; }
function dn($d) {
    if (isset($d['nom']) && isset($d['prenom'])) return $d['nom'] . ' ' . $d['prenom'];
    return $d['name'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .main-content { margin-left: 260px; padding: 2rem; }
        .drivers-card { border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; }
        .drivers-card .card-header { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 1rem 1.25rem; font-weight: 600; }
        .table-drivers th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .th-identity { background: #dbeafe !important; color: #1d4ed8; }
        .th-bus { background: #dcfce7 !important; color: #166534; }
        .td-numero { background: #fff7ed; }
        .td-bus { background: #f0fdf4; }
        .breadcrumb { background: transparent; padding: 0; }
        @media (max-width: 992px) { .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <nav aria-label="breadcrumb" class="mb-2">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="buses.php">Accueil</a></li>
                    <li class="breadcrumb-item active">Chauffeurs</li>
                </ol>
            </nav>
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <h1 class="mb-0"><i class="fas fa-id-card me-2"></i>Chauffeurs</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#driverModal">
                    <i class="fas fa-plus me-1"></i>Ajouter un chauffeur
                </button>
            </div>

            <?php if (!empty($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            <?php if (!empty($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <div class="drivers-card card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Liste des chauffeurs</span>
                    <div class="input-group input-group-sm" style="max-width: 260px;">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="searchDrivers" placeholder="Chercher...">
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-drivers table-hover mb-0">
                            <thead>
                                <tr>
                                    <th class="th-identity">N° Conducteur</th>
                                    <th class="th-identity">Nom</th>
                                    <th class="th-identity">Prénom</th>
                                    <th class="th-bus">Bus Affecté</th>
                                    <th class="th-bus">Taux Horaire</th>
                                    <th>Connexion</th>
                                    <th>État</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($drivers)): ?>
                                <tr><td colspan="8" class="text-center py-4 text-muted">Aucun chauffeur</td></tr>
                                <?php else: ?>
                                <?php foreach ($drivers as $d): 
                                    $num = $has_nom ? ($d['numero_conducteur'] ?? $d['id']) : $d['id'];
                                    if ($has_nom) {
                                        $nom = dv($d,'nom');
                                        $prenom = dv($d,'prenom');
                                    } else {
                                        $parts = preg_split('/\s+/', trim(dn($d)), 2);
                                        $prenom = $parts[0] ?? '';
                                        $nom = $parts[1] ?? '';
                                    }
                                    $bus = $d['bus_number'] ?? null;
                                    $taux = $has_nom ? ($d['taux_horaire'] ?? 15.48) : 15.48;
                                ?>
                                <tr>
                                    <td class="td-numero"><?php echo $num; ?></td>
                                    <td><?php echo $nom ?: '-'; ?></td>
                                    <td><?php echo $prenom ?: '-'; ?></td>
                                    <td class="td-bus"><?php echo $bus ? $bus : '-'; ?></td>
                                    <td class="td-bus"><?php echo number_format($taux, 2); ?> MAD</td>
                                    <td><strong>N°: <?php echo $num; ?></strong><br><small>PIN: <?php echo htmlspecialchars($d['pin_code'] ?? '0000'); ?></small></td>
                                    <td><span class="badge bg-<?php echo ($d['is_active'] ?? 0) ? 'success' : 'secondary'; ?>"><?php echo ($d['is_active'] ?? 0) ? 'Actif' : 'Inactif'; ?></span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="editDriver(<?php echo $d['id']; ?>)" title="Modifier"><i class="fas fa-pen"></i></button>
                                        <button class="btn btn-sm btn-outline-success" onclick="assignBus(<?php echo $d['id']; ?>, '<?php echo addslashes(dn($d)); ?>')" title="Assigner bus"><i class="fas fa-bus"></i></button>
                                        <button class="btn btn-sm btn-outline-<?php echo ($d['is_active'] ?? 0) ? 'warning' : 'success'; ?>" onclick="toggleDriverStatus(<?php echo $d['id']; ?>, <?php echo ($d['is_active'] ?? 0) ? 0 : 1; ?>)" title="<?php echo ($d['is_active'] ?? 0) ? 'Désactiver' : 'Activer'; ?>">
                                            <i class="fas fa-<?php echo ($d['is_active'] ?? 0) ? 'pause' : 'play'; ?>"></i>
                                        </button>
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

    <!-- Modal Ajouter / Modifier -->
    <div class="modal fade" id="driverModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $edit_driver ? 'Modifier le chauffeur' : 'Ajouter un chauffeur'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="<?php echo $edit_driver ? 'edit' : 'add'; ?>">
                        <?php if ($edit_driver): ?><input type="hidden" name="id" value="<?php echo $edit_driver['id']; ?>"><?php endif; ?>
                        <?php if ($has_nom): ?>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">N° Conducteur</label>
                                <input type="number" class="form-control" name="numero_conducteur" value="<?php echo $edit_driver['numero_conducteur'] ?? ''; ?>">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Taux Horaire (MAD)</label>
                                <input type="number" class="form-control" name="taux_horaire" step="0.01" value="<?php echo $edit_driver['taux_horaire'] ?? 15.48; ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Nom *</label>
                                <input type="text" class="form-control" name="nom" required value="<?php echo dv($edit_driver ?? [], 'nom'); ?>">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Prénom *</label>
                                <input type="text" class="form-control" name="prenom" required value="<?php echo dv($edit_driver ?? [], 'prenom'); ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" name="phone" value="<?php echo dv($edit_driver ?? [], 'phone'); ?>">
                        </div>
                        <?php if ($edit_driver): ?>
                        <div class="mb-3">
                            <label class="form-label">Bus Affecté</label>
                            <select class="form-select" name="bus_id">
                                <option value="">-- Aucun --</option>
                                <?php foreach ($available_buses as $b): ?>
                                <option value="<?php echo $b['id']; ?>" <?php echo (isset($edit_driver['bus_id']) && $edit_driver['bus_id'] == $b['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($b['bus_number'] . ' - ' . ($b['make'] ?? '') . ' ' . ($b['model'] ?? '')); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <?php else: ?>
                        <div class="mb-3">
                            <label class="form-label">Nom complet *</label>
                            <input type="text" class="form-control" name="name" required value="<?php echo dv($edit_driver ?? [], 'name'); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" name="phone" value="<?php echo dv($edit_driver ?? [], 'phone'); ?>">
                        </div>
                        <?php if ($edit_driver): ?>
                        <div class="mb-3">
                            <label class="form-label">Bus Affecté</label>
                            <select class="form-select" name="bus_id">
                                <option value="">-- Aucun --</option>
                                <?php foreach ($available_buses as $b): ?>
                                <option value="<?php echo $b['id']; ?>" <?php echo (isset($edit_driver['bus_id']) && $edit_driver['bus_id'] == $b['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($b['bus_number']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Assigner Bus -->
    <div class="modal fade" id="assignBusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assigner un bus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="assign_bus">
                    <input type="hidden" name="driver_id" id="assignDriverId">
                    <div class="modal-body">
                        <p class="mb-2">Chauffeur: <strong id="assignDriverName"></strong></p>
                        <label class="form-label">Bus</label>
                        <select class="form-select" name="bus_id">
                            <option value="">-- Aucun --</option>
                            <?php foreach ($available_buses as $b): ?>
                            <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['bus_number']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Assigner</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <form method="POST" id="toggleStatusForm" style="display:none">
        <input type="hidden" name="action" value="toggle_driver_status">
        <input type="hidden" name="id" id="toggleId">
        <input type="hidden" name="is_active" id="toggleStatus">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editDriver(id) { window.location.href = 'drivers.php?edit=' + id; }
        function assignBus(id, name) {
            document.getElementById('assignDriverId').value = id;
            document.getElementById('assignDriverName').textContent = name;
            new bootstrap.Modal(document.getElementById('assignBusModal')).show();
        }
        function toggleDriverStatus(id, status) {
            document.getElementById('toggleId').value = id;
            document.getElementById('toggleStatus').value = status;
            document.getElementById('toggleStatusForm').submit();
        }
        // Refresh page after successful toggle
        <?php if (!empty($_SESSION['message']) && strpos($_SESSION['message'], 'activé') !== false): ?>
        window.location.reload();
        <?php endif; ?>
        document.getElementById('searchDrivers').addEventListener('input', function() {
            const q = this.value.toLowerCase();
            document.querySelectorAll('.table-drivers tbody tr').forEach(r => {
                const t = r.textContent.toLowerCase();
                r.style.display = t.includes(q) ? '' : 'none';
            });
        });
        <?php if ($edit_driver): ?>
        document.addEventListener('DOMContentLoaded', function() { new bootstrap.Modal(document.getElementById('driverModal')).show(); });
        <?php endif; ?>
    </script>
</body>
</html>

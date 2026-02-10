<?php
// FUTURE AUTOMOTIVE - Fournisseurs (Liste des fournisseurs)
// Structure: Code Fournisseur | Nom | GSM | Addresse | Ville
require_once 'config_achat_hostinger.php';
require_once 'config.php';
require_login();

$page_title = 'Fournisseurs';
$has_code = false;
$has_ville = false;
$has_ice = false;
$has_rc = false;
$pdo = null;

try {
    $db = new DatabaseAchat();
    $pdo = $db->connect();
    $cols = $pdo->query("SHOW COLUMNS FROM suppliers")->fetchAll(PDO::FETCH_COLUMN);
    $has_code = in_array('code_fournisseur', $cols);
    $has_ville = in_array('ville', $cols);
    $has_ice = in_array('ice', $cols);
    $has_rc = in_array('rc', $cols);
} catch (Exception $e) {}

// POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $code = trim($_POST['code_fournisseur'] ?? '');
        $nom = trim($_POST['nom_fournisseur'] ?? '');
        $gsm = trim($_POST['telephone'] ?? '');
        $adresse = trim($_POST['adresse'] ?? '');
        $ville = trim($_POST['ville'] ?? '');
        $ice = trim($_POST['ice'] ?? '');
        $rc = trim($_POST['rc'] ?? '');
        
        if ($nom === '') { 
            $_SESSION['error'] = 'Nom obligatoire'; 
        } else {
            // Check for duplicate code_fournisseur
            if ($has_code && $code !== '') {
                $check_code = $pdo->prepare("SELECT id FROM suppliers WHERE code_fournisseur = ?");
                $check_code->execute([$code]);
                if ($check_code->fetch()) {
                    $_SESSION['error'] = 'Code fournisseur existe déjà';
                    header('Location: fournisseurs.php');
                    exit;
                }
            }
            
            // Check for duplicate ICE
            if ($has_ice && $ice !== '') {
                $check_ice = $pdo->prepare("SELECT id FROM suppliers WHERE ice = ?");
                $check_ice->execute([$ice]);
                if ($check_ice->fetch()) {
                    $_SESSION['error'] = 'ICE existe déjà';
                    header('Location: fournisseurs.php');
                    exit;
                }
            }
            
            // Check for duplicate RC
            if ($has_rc && $rc !== '') {
                $check_rc = $pdo->prepare("SELECT id FROM suppliers WHERE rc = ?");
                $check_rc->execute([$rc]);
                if ($check_rc->fetch()) {
                    $_SESSION['error'] = 'RC existe déjà';
                    header('Location: fournisseurs.php');
                    exit;
                }
            }
            
            // Build insert query based on available columns
            $insert_fields = ['nom_fournisseur', 'telephone', 'adresse'];
            $insert_values = [$nom, $gsm, $adresse];
            $insert_placeholders = '???';
            
            if ($has_code) {
                $insert_fields[] = 'code_fournisseur';
                $insert_values[] = $code ?: null;
                $insert_placeholders .= ',?';
            }
            
            if ($has_ville) {
                $insert_fields[] = 'ville';
                $insert_values[] = $ville;
                $insert_placeholders .= ',?';
            }
            
            if ($has_ice) {
                $insert_fields[] = 'ice';
                $insert_values[] = $ice ?: null;
                $insert_placeholders .= ',?';
            }
            
            if ($has_rc) {
                $insert_fields[] = 'rc';
                $insert_values[] = $rc ?: null;
                $insert_placeholders .= ',?';
            }
            
            $sql = "INSERT INTO suppliers (" . implode(', ', $insert_fields) . ") VALUES (" . $insert_placeholders . ")";
            $pdo->prepare($sql)->execute($insert_values);
            
            $_SESSION['message'] = 'Fournisseur ajouté';
        }
        header('Location: fournisseurs.php');
        exit;
    }
    if ($action === 'edit') {
        $id = (int)$_POST['id'];
        $code = trim($_POST['code_fournisseur'] ?? '');
        $nom = trim($_POST['nom_fournisseur'] ?? '');
        $gsm = trim($_POST['telephone'] ?? '');
        $adresse = trim($_POST['adresse'] ?? '');
        $ville = trim($_POST['ville'] ?? '');
        $ice = trim($_POST['ice'] ?? '');
        $rc = trim($_POST['rc'] ?? '');
        
        // Check for duplicate code_fournisseur (excluding current record)
        if ($has_code && $code !== '') {
            $check_code = $pdo->prepare("SELECT id FROM suppliers WHERE code_fournisseur = ? AND id != ?");
            $check_code->execute([$code, $id]);
            if ($check_code->fetch()) {
                $_SESSION['error'] = 'Code fournisseur existe déjà';
                header('Location: fournisseurs.php');
                exit;
            }
        }
        
        // Check for duplicate ICE (excluding current record)
        if ($has_ice && $ice !== '') {
            $check_ice = $pdo->prepare("SELECT id FROM suppliers WHERE ice = ? AND id != ?");
            $check_ice->execute([$ice, $id]);
            if ($check_ice->fetch()) {
                $_SESSION['error'] = 'ICE existe déjà';
                header('Location: fournisseurs.php');
                exit;
            }
        }
        
        // Check for duplicate RC (excluding current record)
        if ($has_rc && $rc !== '') {
            $check_rc = $pdo->prepare("SELECT id FROM suppliers WHERE rc = ? AND id != ?");
            $check_rc->execute([$rc, $id]);
            if ($check_rc->fetch()) {
                $_SESSION['error'] = 'RC existe déjà';
                header('Location: fournisseurs.php');
                exit;
            }
        }
        
        // Build update query based on available columns
        $update_fields = ['nom_fournisseur = ?', 'telephone = ?', 'adresse = ?'];
        $update_values = [$nom, $gsm, $adresse];
        
        if ($has_code) {
            $update_fields[] = 'code_fournisseur = ?';
            $update_values[] = $code ?: null;
        }
        
        if ($has_ville) {
            $update_fields[] = 'ville = ?';
            $update_values[] = $ville;
        }
        
        if ($has_ice) {
            $update_fields[] = 'ice = ?';
            $update_values[] = $ice ?: null;
        }
        
        if ($has_rc) {
            $update_fields[] = 'rc = ?';
            $update_values[] = $rc ?: null;
        }
        
        $update_values[] = $id;
        
        $sql = "UPDATE suppliers SET " . implode(', ', $update_fields) . " WHERE id=?";
        $pdo->prepare($sql)->execute($update_values);
        
        $_SESSION['message'] = 'Fournisseur mis à jour';
        header('Location: fournisseurs.php');
        exit;
    }
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM suppliers WHERE id = ?")->execute([$id]);
        $_SESSION['message'] = 'Fournisseur supprimé';
        header('Location: fournisseurs.php');
        exit;
    }
}

$fournisseurs = [];
try {
    $stmt = $pdo->query("SELECT * FROM suppliers ORDER BY nom_fournisseur");
    $fournisseurs = $stmt->fetchAll();
} catch (Exception $e) {
    $error_message = $e->getMessage();
}

$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$edit_f = null;
foreach ($fournisseurs as $f) { if ($f['id'] == $edit_id) { $edit_f = $f; break; } }
function fv($f, $k, $d = '') { return isset($f[$k]) ? htmlspecialchars($f[$k]) : $d; }
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo APP_NAME; ?></title>
    <!-- Simple Clean Theme -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/simple-theme.css">
    <style>
        .main-content { margin-left: 260px; padding: 2rem; }
        .fournisseurs-card { border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; }
        .fournisseurs-card .card-header { background: #dbeafe; color: #1d4ed8; font-weight: 600; padding: 1rem 1.25rem; }
        .table-fournisseurs th { font-size: 0.75rem; text-transform: uppercase; }
        .breadcrumb { background: transparent; padding: 0; }
        .row-alt:nth-child(even) { background: #f0fdf4; }
        @media (max-width: 992px) { .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header_simple.php'; ?>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <nav aria-label="breadcrumb" class="mb-2">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="buses.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="achat_da.php">Gestion Achat</a></li>
                    <li class="breadcrumb-item active">Fournisseurs</li>
                </ol>
            </nav>
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <h1 class="mb-0"><i class="fas fa-truck me-2"></i>Liste des Fournisseurs</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#fournisseurModal">
                    <i class="fas fa-plus me-1"></i>Ajouter un fournisseur
                </button>
            </div>

            <?php if (!empty($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <div class="fournisseurs-card card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Fournisseurs</span>
                    <div class="input-group input-group-sm" style="max-width: 260px;">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="searchFournisseurs" placeholder="Chercher...">
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-fournisseurs table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Code Fournisseur</th>
                                    <th>Nom</th>
                                    <th>GSM</th>
                                    <th>Addresse</th>
                                    <th>Ville</th>
                                    <?php if ($has_ice): ?><th>ICE</th><?php endif; ?>
                                    <?php if ($has_rc): ?><th>RC</th><?php endif; ?>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($fournisseurs)): ?>
                                <tr><td colspan="<?php echo 6 + ($has_ice ? 1 : 0) + ($has_rc ? 1 : 0); ?>" class="text-center py-4 text-muted">Aucun fournisseur</td></tr>
                                <?php else: ?>
                                <?php foreach ($fournisseurs as $i => $f): ?>
                                <tr class="<?php echo $i % 2 ? 'row-alt' : ''; ?>">
                                    <td><?php echo $has_code ? fv($f,'code_fournisseur','-') : '-'; ?></td>
                                    <td><strong><?php echo fv($f,'nom_fournisseur'); ?></strong></td>
                                    <td><?php echo fv($f,'telephone','-'); ?></td>
                                    <td><?php echo fv($f,'adresse','-'); ?></td>
                                    <td><?php echo $has_ville ? fv($f,'ville','-') : '-'; ?></td>
                                    <?php if ($has_ice): ?><td><?php echo fv($f,'ice','-'); ?></td><?php endif; ?>
                                    <?php if ($has_rc): ?><td><?php echo fv($f,'rc','-'); ?></td><?php endif; ?>
                                    <td>
                                        <a href="fournisseurs.php?edit=<?php echo $f['id']; ?>" class="btn btn-sm btn-outline-primary" title="Modifier"><i class="fas fa-pen"></i></a>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteFournisseur(<?php echo $f['id']; ?>,'<?php echo addslashes(fv($f,'nom_fournisseur')); ?>')" title="Supprimer"><i class="fas fa-trash"></i></button>
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

    <!-- Modal -->
    <div class="modal fade" id="fournisseurModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $edit_f ? 'Modifier le fournisseur' : 'Ajouter un fournisseur'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="<?php echo $edit_f ? 'edit' : 'add'; ?>">
                        <?php if ($edit_f): ?><input type="hidden" name="id" value="<?php echo $edit_f['id']; ?>"><?php endif; ?>
                        <div class="row">
                            <?php if ($has_code): ?>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Code Fournisseur</label>
                                <input type="text" class="form-control" name="code_fournisseur" value="<?php echo fv($edit_f ?? [],'code_fournisseur'); ?>" placeholder="F1XXX">
                            </div>
                            <?php endif; ?>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Nom *</label>
                                <input type="text" class="form-control" name="nom_fournisseur" required value="<?php echo fv($edit_f ?? [],'nom_fournisseur'); ?>">
                            </div>
                            <?php if ($has_ice): ?>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">ICE</label>
                                <input type="text" class="form-control" name="ice" value="<?php echo fv($edit_f ?? [],'ice'); ?>" placeholder="12345678901234567">
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">GSM</label>
                                <input type="text" class="form-control" name="telephone" value="<?php echo fv($edit_f ?? [],'telephone'); ?>" placeholder="0539971226">
                            </div>
                            <?php if ($has_rc): ?>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">RC</label>
                                <input type="text" class="form-control" name="rc" value="<?php echo fv($edit_f ?? [],'rc'); ?>" placeholder="123456789">
                            </div>
                            <?php endif; ?>
                            <?php if ($has_ville): ?>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Ville</label>
                                <input type="text" class="form-control" name="ville" value="<?php echo fv($edit_f ?? [],'ville'); ?>" placeholder="TETOUAN">
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Addresse</label>
                            <textarea class="form-control" name="adresse" rows="2"><?php echo fv($edit_f ?? [],'adresse'); ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <form method="POST" id="deleteForm" style="display:none">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteId">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteFournisseur(id, nom) {
            if (!confirm('Supprimer le fournisseur « ' + nom + ' » ?')) return;
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteForm').submit();
        }
        document.getElementById('searchFournisseurs').addEventListener('input', function() {
            const q = this.value.toLowerCase();
            document.querySelectorAll('.table-fournisseurs tbody tr').forEach(r => {
                r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
        <?php if ($edit_f): ?>
        document.addEventListener('DOMContentLoaded', function() { new bootstrap.Modal(document.getElementById('fournisseurModal')).show(); });
        <?php endif; ?>
    </script>
</body>
</html>

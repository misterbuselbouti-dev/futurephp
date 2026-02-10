<?php
// FUTURE AUTOMOTIVE - Fournisseurs (Liste des fournisseurs)
// Structure: Code Fournisseur | Nom | GSM | Addresse | Ville
require_once 'config_achat_hostinger.php';
require_once 'config.php';
require_login();

$page_title = 'Fournisseurs';
$has_code = false;
$has_ville = false;
$pdo = null;

try {
    $db = new DatabaseAchat();
    $pdo = $db->connect();
    $cols = $pdo->query("SHOW COLUMNS FROM suppliers")->fetchAll(PDO::FETCH_COLUMN);
    $has_code = in_array('code_fournisseur', $cols);
    $has_ville = in_array('ville', $cols);
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
        if ($nom === '') { $_SESSION['error'] = 'Nom obligatoire'; } else {
            if ($has_code && $has_ville) {
                $pdo->prepare("INSERT INTO suppliers (code_fournisseur, nom_fournisseur, telephone, adresse, ville) VALUES (?,?,?,?,?)")
                   ->execute([$code ?: null, $nom, $gsm, $adresse, $ville]);
            } else {
                $pdo->prepare("INSERT INTO suppliers (nom_fournisseur, telephone, adresse) VALUES (?,?,?)")
                   ->execute([$nom, $gsm, $adresse]);
            }
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
        if ($has_code && $has_ville) {
            $pdo->prepare("UPDATE suppliers SET code_fournisseur=?, nom_fournisseur=?, telephone=?, adresse=?, ville=? WHERE id=?")
               ->execute([$code ?: null, $nom, $gsm, $adresse, $ville, $id]);
        } else {
            $pdo->prepare("UPDATE suppliers SET nom_fournisseur=?, telephone=?, adresse=? WHERE id=?")
               ->execute([$nom, $gsm, $adresse, $id]);
        }
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="assets/css/style.css" rel="stylesheet">
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
    <?php include __DIR__ . '/includes/header.php'; ?>
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
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($fournisseurs)): ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">Aucun fournisseur</td></tr>
                                <?php else: ?>
                                <?php foreach ($fournisseurs as $i => $f): ?>
                                <tr class="<?php echo $i % 2 ? 'row-alt' : ''; ?>">
                                    <td><?php echo $has_code ? fv($f,'code_fournisseur','-') : '-'; ?></td>
                                    <td><strong><?php echo fv($f,'nom_fournisseur'); ?></strong></td>
                                    <td><?php echo fv($f,'telephone','-'); ?></td>
                                    <td><?php echo fv($f,'adresse','-'); ?></td>
                                    <td><?php echo $has_ville ? fv($f,'ville','-') : '-'; ?></td>
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
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Code Fournisseur</label>
                                <input type="text" class="form-control" name="code_fournisseur" value="<?php echo fv($edit_f ?? [],'code_fournisseur'); ?>" placeholder="F1XXX">
                            </div>
                            <?php endif; ?>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nom *</label>
                                <input type="text" class="form-control" name="nom_fournisseur" required value="<?php echo fv($edit_f ?? [],'nom_fournisseur'); ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">GSM</label>
                                <input type="text" class="form-control" name="telephone" value="<?php echo fv($edit_f ?? [],'telephone'); ?>" placeholder="0539971226">
                            </div>
                            <?php if ($has_ville): ?>
                            <div class="col-md-6 mb-3">
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

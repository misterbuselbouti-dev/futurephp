<?php
// ATEO Auto - Modification Bon de Commande
// Après enregistrement réussi, le BC passe en "Confirmé" et ne s'affiche plus dans la liste "à modifier"

require_once 'config.php';
require_once 'config_achat_hostinger.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit();
}

$bc_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$bc_id) {
    header('Location: achat_bc.php');
    exit();
}

$error_message = null;
$success_message = null;

try {
    $database = new DatabaseAchat();
    $conn = $database->connect();

    $stmt = $conn->prepare("
        SELECT bc.*, dp.ref_dp, da.ref_da, s.nom_fournisseur
        FROM bons_commande bc
        LEFT JOIN demandes_prix dp ON bc.dp_id = dp.id
        LEFT JOIN demandes_achat da ON dp.da_id = da.id
        LEFT JOIN suppliers s ON dp.fournisseur_id = s.id
        WHERE bc.id = ?
    ");
    $stmt->execute([$bc_id]);
    $bc = $stmt->fetch();

    if (!$bc) {
        header('Location: achat_bc.php');
        exit();
    }

    if ($bc['statut'] !== 'Commandé') {
        $_SESSION['success_message'] = 'Ce bon de commande n\'est plus modifiable (statut: ' . $bc['statut'] . ').';
        header('Location: achat_bc.php');
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $posted_bc_id = isset($_POST['bc_id']) ? (int)$_POST['bc_id'] : 0;
        if ($posted_bc_id !== $bc_id) {
            $error_message = 'Identifiant BC invalide.';
        } else {
            $date_commande = $_POST['date_commande'] ?? date('Y-m-d');

            $updated_confirme = false;
            try {
                $stmt = $conn->prepare("UPDATE bons_commande SET date_commande = ?, statut = 'Confirmé' WHERE id = ?");
                $stmt->execute([$date_commande, $bc_id]);
                if ($stmt->rowCount() > 1) {
                    $error_message = 'Erreur: plus d\'un enregistrement modifié.';
                } else {
                    $updated_confirme = (bool)$stmt->rowCount();
                }
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), '1265') !== false || strpos($e->getMessage(), 'Data truncated') !== false) {
                    $stmt = $conn->prepare("UPDATE bons_commande SET date_commande = ? WHERE id = ?");
                    $stmt->execute([$date_commande, $bc_id]);
                    $updated_confirme = false;
                } else {
                    throw $e;
                }
            }
        }

        if (!isset($error_message)) {
            logAchat("BC modifié", "BC ID: $bc_id, date: $date_commande" . ($updated_confirme ? ', statut=Confirmé' : ''));
        $_SESSION['success_message'] = $updated_confirme
            ? 'Bon de commande ' . $bc['ref_bc'] . ' enregistré. Il ne figure plus dans la liste des BC à modifier.'
            : 'Bon de commande ' . $bc['ref_bc'] . ' enregistré. Exécutez sql/bc_add_confirme_statut.sql pour que les BC modifiés sortent de la liste "à modifier".';
        header('Location: achat_bc.php');
        exit();
    }
} catch (Exception $e) {
    $error_message = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier BC - <?php echo htmlspecialchars($bc['ref_bc']); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .main-content { margin-left: 260px; padding: 20px; }
        @media (max-width: 768px) { .main-content { margin-left: 0; } }
    </style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="container-fluid">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="buses.php">Accueil</a></li>
                <li class="breadcrumb-item"><a href="achat_bc.php">Bon de commande</a></li>
                <li class="breadcrumb-item active">Modifier <?php echo htmlspecialchars($bc['ref_bc']); ?></li>
            </ol>
        </nav>

        <div class="card shadow-sm">
            <div class="card-body">
                <h3 class="mb-4"><i class="fas fa-edit me-2"></i>Modifier le bon de commande</h3>
                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>

                <div class="d-flex flex-wrap gap-2 mb-4">
                    <a class="btn btn-outline-primary" href="achat_bc_print.php?id=<?php echo (int) $bc_id; ?>" target="_blank" rel="noopener">
                        <i class="fas fa-print me-2"></i>Aperçu impression
                    </a>
                    <a class="btn btn-outline-danger" href="achat_bc_pdf.php?id=<?php echo (int) $bc_id; ?>" target="_blank" rel="noopener">
                        <i class="fas fa-file-pdf me-2"></i>Télécharger PDF
                    </a>
                </div>

                <form method="post">
                    <input type="hidden" name="bc_id" value="<?php echo (int)$bc_id; ?>">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Référence</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($bc['ref_bc']); ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">DP / DA</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($bc['ref_dp'] . ' / ' . $bc['ref_da']); ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fournisseur</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($bc['nom_fournisseur']); ?>" readonly>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="date_commande" class="form-label">Date de commande <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="date_commande" name="date_commande" required
                                   value="<?php echo htmlspecialchars(date('Y-m-d', strtotime($bc['date_commande']))); ?>">
                        </div>
                    </div>
                    <p class="text-muted small">Après enregistrement, ce BC ne s'affichera plus dans la liste des bons à modifier.</p>
                    <div class="d-flex gap-2">
                        <a href="achat_bc.php" class="btn btn-outline-secondary">Annuler</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-check me-2"></i>Enregistrer et confirmer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

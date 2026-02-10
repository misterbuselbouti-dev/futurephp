<?php
// ATEO Auto - Edition Demande de Prix
// Permet de modifier les informations principales d'une DP (fournisseur)

require_once 'config.php';
require_once 'config_achat_hostinger.php';

// Vérifier si l'utilisateur est connecté
if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = 'achat_dp.php';
    header('Location: login.php');
    exit();
}

// Récupérer l'ID de la DP
$dp_id = $_GET['id'] ?? 0;
if (!$dp_id) {
    header('Location: achat_dp.php');
    exit();
}

$success_message = null;
$error_message = null;

try {
    $database = new DatabaseAchat();
    $conn = $database->connect();

    // Charger la DP
    $stmt = $conn->prepare("
        SELECT dp.*, 
               da.ref_da,
               da.demandeur AS da_demandeur,
               s.nom_fournisseur
        FROM demandes_prix dp
        LEFT JOIN demandes_achat da ON dp.da_id = da.id
        LEFT JOIN suppliers s ON dp.fournisseur_id = s.id
        WHERE dp.id = ?
    ");
    $stmt->execute([$dp_id]);
    $dp = $stmt->fetch();

    // Vérifier si la DP peut être modifiée
    if (!in_array($dp['statut'], ['Accepté'])) {
        $_SESSION['error_message'] = "Cette demande de prix ne peut plus être modifiée (statut: " . $dp['statut'] . ")";
        header('Location: achat_dp.php');
        exit();
    }

    // Charger la liste des fournisseurs
    $stmt = $conn->query("SELECT id, nom_fournisseur FROM suppliers ORDER BY nom_fournisseur");
    $fournisseurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Traitement du formulaire de mise à jour
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validation des champs obligatoires
        $validation_errors = [];
        
        // Vérifier si le fournisseur existe
        $stmt = $conn->prepare("SELECT id FROM suppliers WHERE id = ?");
        $stmt->execute([$_POST['fournisseur_id']]);
        if (!$stmt->fetch()) {
            $validation_errors[] = "Le fournisseur sélectionné n'existe pas";
        }
        
        // Vérifier à nouveau si la DP peut être modifiée
        $stmt = $conn->prepare("SELECT statut FROM demandes_prix WHERE id = ?");
        $stmt->execute([$dp_id]);
        $current_dp = $stmt->fetch();
        
        if (!in_array($current_dp['statut'], ['Accepté'])) {
            $_SESSION['error_message'] = "Cette demande de prix ne peut plus être modifiée";
            header('Location: achat_dp.php');
            exit();
        }
        
        if (!empty($validation_errors)) {
            $error_message = implode('<br>', $validation_errors);
        } else {
            $nouveau_fournisseur_id = $_POST['fournisseur_id'] ?? null;

            if (!$nouveau_fournisseur_id) {
                $error_message = "Veuillez sélectionner un fournisseur.";
            } else {
                $stmt = $conn->prepare("UPDATE demandes_prix SET fournisseur_id = ? WHERE id = ?");
                $stmt->execute([$nouveau_fournisseur_id, $dp_id]);

                logAchat("Modification DP", "DP ID: $dp_id, nouveau fournisseur: $nouveau_fournisseur_id");

                $_SESSION['success_message'] = "Demande de prix modifiée avec succès!";
                header("Location: achat_dp.php");
                exit();
            }
        }
    }
} catch (Exception $e) {
    $error_message = "Erreur lors du chargement ou de la mise à jour de la demande de prix: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Demande de Prix - <?php echo htmlspecialchars($dp['ref_dp']); ?></title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1e3a8a;
            --secondary-color: #f59e0b;
            --light-bg: #f8f9fa;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--light-bg);
        }

        .main-content {
            margin-left: 260px;
            padding: 20px;
            min-height: 100vh;
        }

        .card-edit {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
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
<?php include __DIR__ . '/includes/header.php'; ?>
<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="container-fluid">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="buses.php">Accueil</a></li>
                <li class="breadcrumb-item"><a href="achat_dp.php">Demandes de Prix</a></li>
                <li class="breadcrumb-item active">Modifier <?php echo htmlspecialchars($dp['ref_dp']); ?></li>
            </ol>
        </nav>

        <?php include __DIR__ . '/includes/achat_tabs.php'; ?>

        <div class="card-edit">
            <h3 class="mb-4">
                <i class="fas fa-edit me-2"></i>
                Modifier la Demande de Prix
            </h3>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Référence DP</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($dp['ref_dp']); ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Référence DA</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($dp['ref_da']); ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Demandeur</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($dp['da_demandeur']); ?>" readonly>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Fournisseur</label>
                    <select name="fournisseur_id" class="form-select" required>
                        <option value="">Sélectionner un fournisseur</option>
                        <?php foreach ($fournisseurs as $f): ?>
                            <option value="<?php echo $f['id']; ?>" <?php echo ($f['id'] == $dp['fournisseur_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($f['nom_fournisseur']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="text-end">
                    <a href="achat_dp_view.php?id=<?php echo $dp_id; ?>" class="btn btn-outline-secondary me-2">
                        Annuler
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


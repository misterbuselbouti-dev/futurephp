<?php
// ATEO Auto - Valider une Demande d'Achat
// Change le statut de "En attente" à "Validé"

require_once 'config_achat_hostinger.php';
require_once 'config.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit();
}

$da_id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if (!$da_id) {
    $_SESSION['error_message'] = 'Demande d\'achat non spécifiée.';
    header('Location: achat_da.php');
    exit();
}

// Workflow simplifié: la DA est validée automatiquement lors de la création.
$_SESSION['success_message'] = "Workflow simplifié: la demande d'achat est acceptée automatiquement.";
header('Location: achat_da_view.php?id=' . $da_id);
exit();

// Code de validation supprimé: non utilisé dans le workflow actuel.

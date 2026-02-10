<?php
// ATEO Auto - Réponse Demande de Prix
// Permet de changer le statut d'une DP (Reçu / Accepté / Refusé)

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

// Workflow simplifié: la DP est acceptée automatiquement lors de sa création.
// Cette page n'est plus nécessaire; on redirige vers la vue.
$_SESSION['success_message'] = "Workflow simplifié: la demande de prix est acceptée automatiquement.";
header('Location: achat_dp_view.php?id=' . urlencode($dp_id));
exit();


<?php
// ATEO Auto - DA Delete Interface
// Interface pour supprimer les demandes d'achat (DA)

require_once 'config_achat_hostinger.php';
require_once 'config.php';

// Vérifier si l'utilisateur est connecté
if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = 'achat_da.php';
    header('Location: login.php');
    exit();
}

// Obtenir les informations de l'utilisateur
$user = get_logged_in_user();
$full_name = $user['full_name'] ?? $_SESSION['full_name'] ?? 'Administrateur';
$role = $_SESSION['role'] ?? 'admin';

// Vérifier l'ID de la DA
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "ID de demande d'achat invalide";
    header('Location: achat_da.php');
    exit();
}

$da_id = intval($_GET['id']);

try {
    $database = new DatabaseAchat();
    $conn = $database->connect();
    
    // Récupérer la DA principale
    $stmt = $conn->prepare("SELECT * FROM demandes_achat WHERE id = ?");
    $stmt->execute([$da_id]);
    $da = $stmt->fetch();
    
    if (!$da) {
        $_SESSION['error_message'] = "Demande d'achat non trouvée";
        header('Location: achat_da.php');
        exit();
    }
    
    // Vérifier si la DA peut être supprimée
    if (!in_array($da['statut'], ['Brouillon', 'En attente'])) {
        $_SESSION['error_message'] = "Cette demande d'achat ne peut plus être supprimée (statut: " . $da['statut'] . ")";
        header('Location: achat_da.php');
        exit();
    }
    
    // Vérifier s'il existe des DP pour cette DA
    $stmt = $conn->prepare("SELECT COUNT(*) as dp_count FROM demandes_prix WHERE da_id = ?");
    $stmt->execute([$da_id]);
    $dp_count = $stmt->fetch()['dp_count'];
    
    if ($dp_count > 0) {
        $_SESSION['error_message'] = "Impossible de supprimer cette demande d'achat car " . $dp_count . " demande(s) de prix ont été créées";
        header('Location: achat_da.php');
        exit();
    }
    
    // Supprimer les articles de la DA
    $stmt = $conn->prepare("DELETE FROM purchase_items WHERE parent_type = 'DA' AND parent_id = ?");
    $stmt->execute([$da_id]);
    
    // Supprimer la DA principale
    $stmt = $conn->prepare("DELETE FROM demandes_achat WHERE id = ?");
    $stmt->execute([$da_id]);
    
    // Logger l'action
    logAchat("Suppression DA", "Référence: " . $da['ref_da'] . ", Articles: " . $dp_count);
    
    $_SESSION['success_message'] = "Demande d'achat " . $da['ref_da'] . " supprimée avec succès!";
    header("Location: achat_da.php");
    exit();
    
} catch (Exception $e) {
    $_SESSION['error_message'] = "Erreur lors de la suppression: " . $e->getMessage();
    header("Location: achat_da.php");
    exit();
}
?>

<?php
// ATEO Auto - PDF Generation for Bon de Commande
// Generate professional BC PDF for suppliers

require_once 'config.php';
require_once 'config_achat_hostinger.php';
require_once __DIR__ . '/includes/bc_document.php';

// Vérifier si l'utilisateur est connecté
if (!is_logged_in()) {
    header('Location: login.php');
    exit();
}

// Récupérer l'ID du BC
$bc_id = $_GET['id'] ?? 0;
if (!$bc_id) {
    header('Location: achat_bc.php');
    exit();
}

try {
    [$bc, $articles, $company] = load_bc_document((int) $bc_id);
} catch (Exception $e) {
    die('Erreur lors du chargement du bon de commande: ' . $e->getMessage());
}

$html = render_bc_document(
    $bc,
    $articles,
    $company,
    [
        'show_actions' => false,
        'page_label' => 'Page 1 / 1',
        'brand_tag' => 'Bon de commande fournisseur'
    ]
);

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="BC-' . preg_replace('/[^A-Za-z0-9_-]/', '', $bc['ref_bc']) . '.pdf"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');
header('Expires: 0');

echo $html;
?>

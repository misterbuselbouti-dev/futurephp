<?php
/**
 * Script unique : réordonne les références DA en DA-YYYY-0001, 0002, 0003...
 * À exécuter UNE FOIS (ex: https://votresite.com/fix_da_ref_renumber.php)
 * Réservé aux admins. Supprimer ou renommer après exécution.
 */
require_once 'config_achat_hostinger.php';
require_once 'config.php';

if (!is_logged_in() || ($_SESSION['role'] ?? '') !== 'admin') {
    die('Accès réservé aux administrateurs.');
}

header('Content-Type: text/html; charset=utf-8');
echo '<h1>Correction des références DA</h1><pre>';

try {
    $database = new DatabaseAchat();
    $conn = $database->connect();

    // 1) Mettre des refs temporaires pour libérer les numéros
    $conn->exec("UPDATE demandes_achat SET ref_da = CONCAT('DA-TMP-', id) WHERE ref_da LIKE 'DA-%-%'");
    echo "Étape 1 : Références mises en temporaire (DA-TMP-id).\n";

    // 2) Récupérer les lignes par année et id
    $stmt = $conn->query("SELECT id, YEAR(date_creation) AS annee FROM demandes_achat ORDER BY YEAR(date_creation), id");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $updateStmt = $conn->prepare("UPDATE demandes_achat SET ref_da = ? WHERE id = ?");
    $prevYear = null;
    $rn = 0;

    foreach ($rows as $row) {
        $year = (int) $row['annee'];
        if ($prevYear !== $year) {
            $rn = 0;
            $prevYear = $year;
        }
        $rn++;
        $newRef = 'DA-' . $year . '-' . str_pad($rn, 4, '0', STR_PAD_LEFT);
        $updateStmt->execute([$newRef, $row['id']]);
    }

    echo "Étape 2 : " . count($rows) . " références réattribuées (DA-YYYY-0001, 0002, ...).\n";
    echo "\nTerminé. Vous pouvez supprimer ce fichier (fix_da_ref_renumber.php) pour la sécurité.\n";

} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
}
echo '</pre>';
exit;

<?php
// ATEO Auto - PDF Generation for Bon de Commande
// Generate professional BC PDF for suppliers

require_once 'config.php';
require_once 'config_achat_hostinger.php';

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

// Récupérer les détails du bon de commande
try {
    $database = new DatabaseAchat();
    $conn = $database->connect();
    
    // Récupérer le bon de commande principal
    $stmt = $conn->prepare("
        SELECT bc.*, 
               dp.ref_dp,
               da.ref_da,
               dp.fournisseur_id,
               s.nom_fournisseur,
               s.contact_nom as contact_nom,
               s.email as email_fournisseur,
               s.telephone as telephone_fournisseur,
               s.adresse as adresse_fournisseur,
               '' as siret_fournisseur
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
    
    // Récupérer les articles du BC
    $stmt = $conn->prepare("
        SELECT * FROM bc_items 
        WHERE bc_id = ?
        ORDER BY id
    ");
    $stmt->execute([$bc_id]);
    $articles = $stmt->fetchAll();
    
} catch (Exception $e) {
    die("Erreur lors du chargement du bon de commande: " . $e->getMessage());
}

// Préparer logo fournisseur (recherche fichier local)
$logoPath = '';
$slug = preg_replace('/[^a-z0-9_\\-]/', '_', strtolower($bc['nom_fournisseur'] ?? ''));
$candidates = [
    __DIR__ . '/assets/logos/supplier_' . ($bc['fournisseur_id'] ?? '') . '.png',
    __DIR__ . '/assets/logos/' . $slug . '.png',
    __DIR__ . '/assets/images/logo.png',
    __DIR__ . '/assets/favicon.ico'
];
foreach ($candidates as $c) {
    if (!empty($c) && file_exists($c)) { $logoPath = $c; break; }
}
$logoHtml = $logoPath ? '<img src=\"' . $logoPath . '\" style=\"max-width:140px; height:auto;\">' : '';
$supplier_ice = $bc['siret_fournisseur'] ?? '';
$service_line = $bc['service'] ?? '';

// HTML content for PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bon de Commande - ' . htmlspecialchars($bc['ref_bc']) . '</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #1e3a8a;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .company-info {
            text-align: center;
            margin-bottom: 30px;
        }
        .company-info h2 {
            color: #1e3a8a;
            margin-bottom: 10px;
        }
        .document-title {
            text-align: center;
            background: #1e3a8a;
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .document-title h1 {
            margin: 0;
            font-size: 28px;
        }
        .two-columns {
            display: flex;
            gap: 40px;
            margin-bottom: 30px;
        }
        .column {
            flex: 1;
        }
        .info-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .info-section h3 {
            margin-top: 0;
            color: #1e3a8a;
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 10px;
        }
        .info-item {
            margin-bottom: 8px;
            display: flex;
        }
        .info-label {
            font-weight: bold;
            width: 150px;
            flex-shrink: 0;
        }
        .info-value {
            flex: 1;
        }
        .articles-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .articles-table th {
            background: #1e3a8a;
            color: white;
            font-weight: bold;
            text-align: left;
            padding: 12px;
            border: 1px solid #ddd;
        }
        .articles-table td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .articles-table tr:nth-child(even) {
            background: #f9f9f9;
        }
        .total-row {
            background: #1e3a8a !important;
            color: white !important;
            font-weight: bold;
        }
        .financial-summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .financial-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            font-size: 16px;
        }
        .financial-item:last-child {
            border-bottom: none;
            font-size: 20px;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }
        .signature-section {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            width: 45%;
            text-align: center;
            border-top: 1px solid #333;
            padding-top: 20px;
        }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px;
            color: rgba(0,0,0,0.1);
            z-index: -1;
        }
        @media print {
            body { margin: 10px; }
            .no-print { display: none; }
            .watermark { display: none; }
        }
    </style>
</head>
<body>
    <div class="watermark">ATEO Auto</div>
    
    <div class="header">
        <div style="display:flex;align-items:center;gap:18px;">
            <div class="logo">' . $logoHtml . '</div>
            <div class="company-info" style="line-height:1.2;">
                <h2 style="margin:0;">' . htmlspecialchars($bc['nom_fournisseur']) . ($supplier_ice ? ' (ICE: ' . htmlspecialchars($supplier_ice) . ')' : '') . '</h2>
                <p style="margin:4px 0 0 0;">' . htmlspecialchars($bc['adresse_fournisseur'] ?? '') . '</p>
                <p style="margin:2px 0 0 0;">' . (!empty($bc['telephone_fournisseur']) ? 'Tél: ' . htmlspecialchars($bc['telephone_fournisseur']) : '') . ' ' . (!empty($bc['email_fournisseur']) ? ' | ' . htmlspecialchars($bc['email_fournisseur']) : '') . '</p>
            </div>
            <div style="margin-left:auto;text-align:right;">
                <h3 style="margin:0;">BON DE COMMANDE N° ' . htmlspecialchars($bc['ref_bc']) . '</h3>
                <p style="margin:6px 0 0 0;">Date: ' . date('d/m/Y', strtotime($bc['date_commande'])) . '</p>
                ' . (!empty($service_line) ? '<p style="margin:2px 0 0 0;">Service: ' . htmlspecialchars($service_line) . '</p>' : '') . '
                <p style="margin:2px 0 0 0;">Page 1 / 1</p>
            </div>
        </div>
    </div>
    
    <div style="height:10px;"></div>
    
    <div class="two-columns">
        <div class="column">
            <div class="info-section">
                <h3>Informations Commande</h3>
                <div class="info-item">
                    <span class="info-label">Référence DA:</span>
                    <span class="info-value">' . htmlspecialchars($bc['ref_da']) . '</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Référence DP:</span>
                    <span class="info-value">' . htmlspecialchars($bc['ref_dp']) . '</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Statut:</span>
                    <span class="info-value">' . htmlspecialchars($bc['statut']) . '</span>
                </div>
            </div>
        </div>
        
        <div class="column">
            <div class="info-section">
                <h3>Informations Fournisseur</h3>
                <div class="info-item">
                    <span class="info-label">Nom:</span>
                    <span class="info-value">' . htmlspecialchars($bc['nom_fournisseur']) . '</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Contact:</span>
                    <span class="info-value">' . htmlspecialchars($bc['contact_nom'] ?? 'Non spécifié') . '</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email:</span>
                    <span class="info-value">' . htmlspecialchars($bc['email_fournisseur'] ?? 'Non spécifié') . '</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Téléphone:</span>
                    <span class="info-value">' . htmlspecialchars($bc['telephone_fournisseur'] ?? 'Non spécifié') . '</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Adresse:</span>
                    <span class="info-value">' . htmlspecialchars($bc['adresse_fournisseur'] ?? 'Non spécifié') . '</span>
                </div>
                ' . (!empty($bc['siret_fournisseur']) ? '
                <div class="info-item">
                    <span class="info-label">SIRET:</span>
                    <span class="info-value">' . htmlspecialchars($bc['siret_fournisseur']) . '</span>
                </div>' : '') . '
            </div>
        </div>
    </div>
    
    <div class="info-section">
        <h3>Conditions de Livraison et Paiement</h3>
        <div class="two-columns">
            <div class="column">
                <div class="info-item">
                    <span class="info-label">Adresse livraison:</span>
                    <span class="info-value">' . htmlspecialchars($bc['delivery_address']) . '</span>
                </div>
            </div>
            <div class="column">
                <div class="info-item">
                    <span class="info-label">Conditions paiement:</span>
                    <span class="info-value">' . htmlspecialchars($bc['payment_terms']) . '</span>
                </div>
            </div>
        </div>
    </div>
    
    <h3 style="color: #1e3a8a; margin-bottom: 20px;">Détail des Articles Commandés</h3>
    <table class="articles-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Code Article</th>
                <th>Désignation</th>
                <th>Quantité</th>
                <th>Prix Unitaire (MAD)</th>
                <th>Total HT (MAD)</th>
                <th>TVA (%)</th>
                <th>Total TTC (MAD)</th>
            </tr>
        </thead>
        <tbody>
            ';
            
            $total_ht_check = 0;
            $total_tva_check = 0;
            $total_ttc_check = 0;
            foreach ($articles as $index => $article) {
                $total_ht_check += $article['total_price'];
                $total_tva_check += $article['tax_amount'];
                $total_ttc_check += $article['total_with_tax'];
                
                $html .= '
                <tr>
                    <td>' . ($index + 1) . '</td>
                    <td>' . htmlspecialchars($article['item_code'] ?? '-') . '</td>
                    <td>' . htmlspecialchars($article['item_description']) . '</td>
                    <td>' . number_format($article['quantity']) . '</td>
                    <td>' . number_format($article['unit_price'], 2, ',', ' ') . '</td>
                    <td>' . number_format($article['total_price'], 2, ',', ' ') . '</td>
                    <td>' . number_format($article['tax_rate'], 1, ',', ' ') . '</td>
                    <td>' . number_format($article['total_with_tax'], 2, ',', ' ') . '</td>
                </tr>';
            }
            
            $html .= '
        </tbody>
        <tfoot>
            <tr class="total-row">
                <th colspan="5" style="text-align: right;">TOTAL GÉNÉRAL:</th>
                <th>' . number_format($total_ht_check, 2, ',', ' ') . '</th>
                <th>' . number_format($total_tva_check, 2, ',', ' ') . '</th>
                <th>' . number_format($total_ttc_check, 2, ',', ' ') . '</th>
            </tr>
        </tfoot>
    </table>
    
    <div class="financial-summary">
        <h3 style="margin-top: 0; margin-bottom: 20px;">Récapitulatif Financier</h3>
        <div class="financial-item">
            <span>Total HT:</span>
            <span>' . number_format($bc['total_ht'], 2, ',', ' ') . ' MAD</span>
        </div>
        <div class="financial-item">
            <span>TVA (' . number_format($bc['tva_rate'], 1, ',', ' ') . '%):</span>
            <span>' . number_format($bc['tva'], 2, ',', ' ') . ' MAD</span>
        </div>
        ' . ($bc['tva_rate'] == 0 ? '
        <div class="financial-item" style="background: rgba(255,255,255,0.1); padding: 10px; border-radius: 5px; margin: 10px 0;">
            <span style="font-size: 14px; font-style: italic;">Exonéré de TVA - Régime de l\'Auto-entrepreneur</span>
        </div>' : '') . '
        <div class="financial-item">
            <span>Total TTC:</span>
            <span>' . number_format($bc['total_ttc'], 2, ',', ' ') . ' MAD</span>
        </div>
    </div>
    
    <div class="signature-section">
        <div class="signature-box">
            <p><strong>Signature Fournisseur</strong></p>
            <p>Date: _______________</p>
        </div>
        <div class="signature-box">
            <p><strong>Signature ATEO Auto</strong></p>
            <p>Date: _______________</p>
        </div>
    </div>
    
    <div class="footer">
        <p><strong>ATEO Auto - Atelier Mécanique Spécialisé</strong></p>
        <p>Zone Industrielle, Tanger - Maroc | Téléphone: 0661586071</p>
        <p>Email: contact@ateoauto.ma | Site: www.ateoauto.ma</p>
        <p>Généré le ' . date('d/m/Y H:i:s') . ' | Référence: ' . htmlspecialchars($bc['ref_bc']) . '</p>
    </div>
</body>
</html>';

// Set headers for PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="BC-' . htmlspecialchars($bc['ref_bc']) . '.pdf"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');
header('Expires: 0');

// Output HTML
echo $html;
?>

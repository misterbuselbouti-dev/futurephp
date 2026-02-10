<?php
// ATEO Auto - PDF Generation for Bon d'Entrée
// Generate PDF for BE documents

require_once 'config.php';
require_once 'config_achat_hostinger.php';

// Vérifier si l'utilisateur est connecté
if (!is_logged_in()) {
    header('Location: login.php');
    exit();
}

// Récupérer l'ID du BE
$be_id = $_GET['id'] ?? 0;
if (!$be_id) {
    header('Location: achat_be.php');
    exit();
}

// Récupérer les détails du bon d'entrée
try {
    $database = new DatabaseAchat();
    $conn = $database->connect();
    
    // Récupérer le bon d'entrée principal
    $stmt = $conn->prepare("
        SELECT be.*, 
               bc.ref_bc,
               bc.date_commande,
               bc.total_ttc as bc_total,
               dp.ref_dp,
               da.ref_da,
               dp.fournisseur_id,
               s.nom_fournisseur,
               s.contact_nom as contact_nom,
               s.email as email_fournisseur,
               s.telephone as telephone_fournisseur,
               s.adresse as adresse_fournisseur,
               u.full_name as created_by_name
        FROM bons_entree be
        LEFT JOIN bons_commande bc ON be.bc_id = bc.id
        LEFT JOIN demandes_prix dp ON bc.dp_id = dp.id
        LEFT JOIN demandes_achat da ON dp.da_id = da.id
        LEFT JOIN suppliers s ON dp.fournisseur_id = s.id
        LEFT JOIN users u ON be.created_by = u.id
        WHERE be.id = ?
    ");
    $stmt->execute([$be_id]);
    $be = $stmt->fetch();
    
    if (!$be) {
        header('Location: achat_be.php');
        exit();
    }
    
    // Récupérer les articles du BE
    $stmt = $conn->prepare("
        SELECT bei.*, 
               bci.item_code,
               bci.item_description,
               bci.quantity as quantite_commandee,
               bci.unit_price
        FROM be_items bei
        LEFT JOIN bc_items bci ON bei.bc_item_id = bci.id
        WHERE bei.be_id = ?
        ORDER BY bei.id
    ");
    $stmt->execute([$be_id]);
    $articles = $stmt->fetchAll();
    
} catch (Exception $e) {
    die("Erreur lors du chargement du bon d'entrée: " . $e->getMessage());
}

// HTML content for PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bon d\'Entrée - ' . htmlspecialchars($be['ref_be']) . '</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #10b981;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .company-info {
            text-align: center;
            margin-bottom: 30px;
        }
        .company-info h2 {
            color: #10b981;
            margin-bottom: 10px;
        }
        .document-title {
            text-align: center;
            background: #10b981;
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
            color: #10b981;
            border-bottom: 2px solid #10b981;
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
            background: #10b981;
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
            background: #10b981 !important;
            color: white !important;
            font-weight: bold;
        }
        .condition-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .condition-bon {
            background: #d1fae5;
            color: #059669;
        }
        .condition-endommage {
            background: #fef3c7;
            color: #d97706;
        }
        .condition-abime {
            background: #fee2e2;
            color: #dc2626;
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
        <div class="company-info">
            <h2>ATEO Auto</h2>
            <p><strong>Atelier Mécanique Spécialisé</strong></p>
            <p>Zone Industrielle, Tanger - Maroc</p>
            <p>Téléphone: 0661586071 | Email: contact@ateoauto.ma</p>
        </div>
    </div>
    
    <div class="document-title">
        <h1>BON D\'ENTÉE</h1>
        <h2>' . htmlspecialchars($be['ref_be']) . '</h2>
    </div>
    
    <div class="two-columns">
        <div class="column">
            <div class="info-section">
                <h3>Informations Réception</h3>
                <div class="info-item">
                    <span class="info-label">Référence:</span>
                    <span class="info-value">' . htmlspecialchars($be['ref_be']) . '</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Date de réception:</span>
                    <span class="info-value">' . date('d/m/Y', strtotime($be['reception_date'])) . '</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Réceptionnaire:</span>
                    <span class="info-value">' . htmlspecialchars($be['receptionnaire']) . '</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Statut:</span>
                    <span class="info-value">' . htmlspecialchars($be['statut']) . '</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Créé par:</span>
                    <span class="info-value">' . htmlspecialchars($be['created_by_name'] ?? 'Inconnu') . '</span>
                </div>
            </div>
        </div>
        
        <div class="column">
            <div class="info-section">
                <h3>Informations Commande</h3>
                <div class="info-item">
                    <span class="info-label">Bon de Commande:</span>
                    <span class="info-value">' . htmlspecialchars($be['ref_bc']) . '</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Date commande:</span>
                    <span class="info-value">' . date('d/m/Y', strtotime($be['date_commande'])) . '</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Total TTC:</span>
                    <span class="info-value">' . number_format($be['bc_total'], 2, ',', ' ') . ' MAD</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="info-section">
        <h3>Informations Fournisseur</h3>
        <div class="row">
            <div class="col-md-6">
                <div class="info-item">
                    <span class="info-label">Nom:</span>
                    <span class="info-value">' . htmlspecialchars($be['nom_fournisseur']) . '</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Contact:</span>
                    <span class="info-value">' . htmlspecialchars($be['contact_nom'] ?? 'Non spécifié') . '</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email:</span>
                    <span class="info-value">' . htmlspecialchars($be['email_fournisseur'] ?? 'Non spécifié') . '</span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-item">
                    <span class="info-label">Téléphone:</span>
                    <span class="info-value">' . htmlspecialchars($be['telephone_fournisseur'] ?? 'Non spécifié') . '</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Adresse:</span>
                    <span class="info-value">' . htmlspecialchars($be['adresse_fournisseur'] ?? 'Non spécifié') . '</span>
                </div>
            </div>
        </div>
    </div>
    
    ' . (!empty($be['notes']) ? '
    <div style="background: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 30px;">
        <h4><i class="fas fa-comment"></i>Notes</h4>
        <p>' . nl2br(htmlspecialchars($be['notes'])) . '</p>
    </div>' : '') . '
    
    <h3 style="color: #10b981; margin-bottom: 20px;">Détail des Articles Reçus</h3>
    <table class="articles-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Code Article</th>
                <th>Désignation</th>
                <th>Quantité Commandée</th>
                <th>Quantité Reçue</th>
                <th>Prix Unitaire (MAD)</th>
                <th>Total (MAD)</th>
                <th>État</th>
                <th>Emplacement</th>
            </tr>
        </thead>
        <tbody>
            ';
            
            $total_recu = 0;
            $total_value = 0;
            foreach ($articles as $index => $article) {
                $item_total = $article['quantite_recue'] * $article['unit_price'];
                $total_recu += $article['quantite_recue'];
                $total_value += $item_total;
                
                $html .= '
                <tr>
                    <td>' . ($index + 1) . '</td>
                    <td>' . htmlspecialchars($article['item_code'] ?? '-') . '</td>
                    <td>' . htmlspecialchars($article['item_description']) . '</td>
                    <td>' . number_format($article['quantite_commandee']) . '</td>
                    <td><strong>' . number_format($article['quantite_recue']) . '</strong></td>
                    <td>' . number_format($article['unit_price'], 2, ',', ' ') . '</td>
                    <td>' . number_format($item_total, 2, ',', ' ') . '</td>
                    <td><span class="condition-badge condition-' . strtolower($article['condition_status']) . '">' . htmlspecialchars($article['condition_status']) . '</span></td>
                    <td>' . htmlspecialchars($article['emplacement']) . '</td>
                </tr>';
                
                if (!empty($article['batch_number'])) {
                    $html .= '
                    <tr>
                        <td colspan="9" style="background: #f8f9fa; font-size: 0.9rem;">
                            <strong>Numéro de lot:</strong> ' . htmlspecialchars($article['batch_number']) . '
                            ' . (!empty($article['expiry_date']) ? '| <strong>Date d\'expiration:</strong> ' . date('d/m/Y', strtotime($article['expiry_date'])) : '') . '
                        </td>
                    </tr>';
                }
                
                if (!empty($article['notes'])) {
                    $html .= '
                    <tr>
                        <td colspan="9" style="background: #f8f9fa; font-size: 0.9rem;">
                            <strong>Notes:</strong> ' . htmlspecialchars($article['notes']) . '
                        </td>
                    </tr>';
                }
            }
            
            $html .= '
        </tbody>
        <tfoot>
            <tr class="total-row">
                <th colspan="5" style="text-align: right;">Total Général:</th>
                <th>' . number_format($total_recu) . '</th>
                <th>' . number_format($total_value, 2, ',', ' ') . ' MAD</th>
                <th colspan="2"></th>
            </tr>
        </tfoot>
    </table>
    
    <div class="signature-section">
        <div class="signature-box">
            <p><strong>Signature Réceptionnaire</strong></p>
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
        <p>Généré le ' . date('d/m/Y H:i:s') . ' | Référence: ' . htmlspecialchars($be['ref_be']) . '</p>
    </div>
</body>
</html>';

// Set headers for PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="BE-' . htmlspecialchars($be['ref_be']) . '.pdf"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');
header('Expires: 0');

// Output HTML
echo $html;
?>

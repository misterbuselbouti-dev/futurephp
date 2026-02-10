<?php
// ATEO Auto - PDF Generation for Demande de Prix
// Generate PDF for DP requests

require_once 'config.php';
require_once 'config_achat_hostinger.php';

// Vérifier si l'utilisateur est connecté
if (!is_logged_in()) {
    header('Location: login.php');
    exit();
}

// Récupérer l'ID de la DP
$dp_id = $_GET['id'] ?? 0;
if (!$dp_id) {
    header('Location: achat_dp.php');
    exit();
}

// Récupérer les détails de la demande de prix
try {
    $database = new DatabaseAchat();
    $conn = $database->connect();
    
    // Récupérer la demande principale
    $stmt = $conn->prepare("
        SELECT dp.*, 
               da.ref_da,
               da.demandeur as da_demandeur,
               s.nom_fournisseur,
               s.contact_nom as contact_nom,
               s.email as email_fournisseur,
               s.telephone as telephone_fournisseur
        FROM demandes_prix dp
        LEFT JOIN demandes_achat da ON dp.da_id = da.id
        LEFT JOIN suppliers s ON dp.fournisseur_id = s.id
        WHERE dp.id = ?
    ");
    $stmt->execute([$dp_id]);
    $dp = $stmt->fetch();
    
    if (!$dp) {
        header('Location: achat_dp.php');
        exit();
    }
    
    // Récupérer les articles
    $stmt = $conn->prepare("
        SELECT * FROM purchase_items 
        WHERE parent_type = 'DP' AND parent_id = ?
        ORDER BY id
    ");
    $stmt->execute([$dp_id]);
    $articles = $stmt->fetchAll();
    
    // Récupérer les réponses des fournisseurs
    $responses = [];
    try {
        $stmt = $conn->prepare("
            SELECT * FROM dp_responses 
            WHERE dp_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$dp_id]);
        $responses = $stmt->fetchAll();
        
    } catch (Exception $e) {
        // Table dp_responses n'existe peut-être encore
    }
    
} catch (Exception $e) {
    die("Erreur lors du chargement de la demande de prix: " . $e->getMessage());
}

// HTML content for PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Demande de Prix - ' . htmlspecialchars($dp['ref_dp']) . '</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .company-info {
            text-align: center;
            margin-bottom: 30px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .info-item {
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
            color: #1e3a8a;
        }
        .supplier-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-envoye {
            background: #dbeafe;
            color: #2563eb;
        }
        .status-recu {
            background: #d1fae5;
            color: #059669;
        }
        .status-accepte {
            background: #dcfce7;
            color: #059669;
        }
        .status-refuse {
            background: #fee2e2;
            color: #dc2626;
        }
        .articles-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .articles-table th,
        .articles-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .articles-table th {
            background: #1e3a8a;
            color: white;
            font-weight: bold;
        }
        .articles-table tr:nth-child(even) {
            background: #f9f9f9;
        }
        .total-row {
            background: #1e3a8a !important;
            color: white !important;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            margin-top: 50px;
            font-size: 12px;
            color: #666;
        }
        @media print {
            body { margin: 10px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Demande de Prix</h1>
        <h2>' . htmlspecialchars($dp['ref_dp']) . '</h2>
    </div>
    
    <div class="company-info">
        <h3>ATEO Auto</h3>
        <p>Atelier Mécanique Spécialisé - Tanger, Maroc</p>
        <p>Téléphone: 0661586071</p>
    </div>
    
    <div class="info-grid">
        <div>
            <div class="info-item">
                <span class="info-label">Référence DP:</span>
                ' . htmlspecialchars($dp['ref_dp']) . '
            </div>
            <div class="info-item">
                <span class="info-label">Référence DA:</span>
                ' . htmlspecialchars($dp['ref_da']) . '
            </div>
            <div class="info-item">
                <span class="info-label">Date d'envoi:</span>
                ' . date('d/m/Y H:i', strtotime($dp['date_envoi'])) . '
            </div>
        </div>
        <div>
            <div class="info-item">
                <span class="info-label">Statut:</span>
                <span class="status-badge status-<?php echo strtolower($dp['statut']); ?>">
                    ' . htmlspecialchars($dp['statut']) . '
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Nombre d'articles:</span>
                ' . count($articles) . '
            </div>
        </div>
    </div>
    
    <div class="supplier-info">
        <h4><i class="fas fa-store"></i>Informations Fournisseur</h4>
        <div class="row">
            <div class="col-md-6">
                <div class="info-item">
                    <span class="info-label">Nom:</span>
                    ' . htmlspecialchars($dp['nom_fournisseur']) . '
                </div>
                <div class="info-item">
                    <span class="info-label">Contact:</span>
                    ' . htmlspecialchars($dp['contact_nom'] ?? 'Non spécifié') . '
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-item">
                    <span class="info-label">Email:</span>
                    ' . htmlspecialchars($dp['email_fournisseur'] ?? 'Non spécifié') . '
                </div>
                <div class="info-item">
                    <span class="info-label">Téléphone:</span>
                    ' . htmlspecialchars($dp['telephone_fournisseur'] ?? 'Non spécifié') . '
                </div>
            </div>
        </div>
    </div>
    
    ' . (!empty($dp['commentaires']) ? '
    <div style="background: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 30px;">
        <h4><i class="fas fa-comment"></i>Commentaires</h4>
        <p>' . nl2br(htmlspecialchars($dp['commentaires'])) . '</p>
    </div>
    ' : '') . '
    
    <h3>Articles Demandés</h3>
    <table class="articles-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Désignation</th>
                <th>Quantité</th>
                <th>Prix Unitaire (MAD)</th>
                <th>Total (MAD)</th>
            </tr>
        </thead>
        <tbody>
            ';
            
            $total_general = 0;
            foreach ($articles as $index => $article) {
                $total_general += $article['total_ligne'];
                $html .= '
                <tr>
                    <td>' . ($index + 1) . '</td>
                    <td>' . htmlspecialchars($article['designation']) . '</td>
                    <td>' . number_format($article['quantite']) . '</td>
                    <td>' . number_format($article['prix_unitaire'], 2, ',', ' ') . '</td>
                    <td>' . number_format($article['total_ligne'], 2, ',', ' ') . '</td>
                </tr>';
            }
            
            $html .= '
            </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4" style="text-align: right;">Total Général:</td>
                <td>' . number_format($total_general, 2, ',', ' ') . '</td>
            </tr>
        </tfoot>
    </table>
    
    ' . (!empty($responses) ? '
    <h3>Réponses des Fournisseurs</h3>
    <div style="background: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 30px;">
        ';
        
        foreach ($responses as $response) {
            $html .= '
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 5px; padding: 15px; margin-bottom: 15px;">
                <div class="d-flex justify-content-between align-items-center">
                    <strong>Réponse de ' . htmlspecialchars($response['supplier_name']) . '</strong>
                    <span style="font-size: 0.9rem; color: #666;">
                        ' . date('d/m/Y H:i', strtotime($response['created_at'])) . '
                    </span>
                </div>
                <div class="mt-2">
                    <strong>Prix unitaire:</strong> ' . number_format($response['price_unitaire'], 2, ',', ' ') . ' MAD<br>
                    <strong>Délai de livraison:</strong> ' . ($response['delivery_time'] ?? 'Non spécifié') . ' jours<br>
                </div>
                <div class="mt-2">
                    <strong>Commentaires:</strong><br>
                    ' . nl2br(htmlspecialchars($response['comments'])) . '
                </div>
            </div>';
        }
        
        $html .= '
    </div>
    ' : '') . '
    
    <div class="footer">
        <p>Généré le ' . date('d/m/Y H:i:s') . ' | ATEO Auto Systeme d\'Achat</p>
    </div>
</body>
</html>';

// Set headers for PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="DP-' . htmlspecialchars($dp['ref_dp']) . '.pdf"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');
header('Expires: 0');

// Output HTML
echo $html;
?>

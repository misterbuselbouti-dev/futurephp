<?php
// FUTURE AUTOMOTIVE - Print Template Generator
// مولد قوالب الطباعة

// Include configuration
require_once '../config.php';
require_once '../includes/functions.php';

// Check authentication
require_login();

// Get document type and parameters
$doc_type = sanitize_input($_GET['type'] ?? 'bc'); // bc, be, da, dp
$doc_id = sanitize_input($_GET['id'] ?? $_GET['nbc'] ?? $_GET['nbe'] ?? $_GET['nda'] ?? $_GET['ndp'] ?? '');
$format = sanitize_input($_GET['format'] ?? 'html'); // html, pdf

// Validate document type
$allowed_types = ['bc', 'be', 'da', 'dp'];
if (!in_array($doc_type, $allowed_types)) {
    die('Type de document non valide');
}

// Get document data
$document_data = [];
$company_info = [];
$items = [];

try {
    $database = new Database();
    $pdo = $database->connect();
    
    // Get company information
    $stmt = $pdo->prepare("SELECT * FROM company_info LIMIT 1");
    $stmt->execute();
    $company_info = $stmt->fetch() ?: [];
    
    // Get document based on type
    $doc_table = '';
    $doc_number_field = '';
    $doc_date_field = '';
    $doc_items_table = '';
    $doc_id_field = '';
    $title = '';
    
    switch ($doc_type) {
        case 'bc':
            $doc_table = 'bons_commande';
            $doc_number_field = 'numero_bc';
            $doc_date_field = 'date_bc';
            $doc_items_table = 'bc_items';
            $doc_id_field = 'id_bc';
            $title = 'BON DE COMMANDE';
            break;
            
        case 'be':
            $doc_table = 'bons_expedition';
            $doc_number_field = 'numero_be';
            $doc_date_field = 'date_be';
            $doc_items_table = 'be_items';
            $doc_id_field = 'id_be';
            $title = 'BON D\'EXPÉDITION';
            break;
            
        case 'da':
            $doc_table = 'demandes_achat';
            $doc_number_field = 'numero_da';
            $doc_date_field = 'date_da';
            $doc_items_table = 'da_items';
            $doc_id_field = 'id_da';
            $title = 'DEMANDE D\'ACHAT';
            break;
            
        case 'dp':
            $doc_table = 'demandes_paiement';
            $doc_number_field = 'numero_dp';
            $doc_date_field = 'date_dp';
            $doc_items_table = 'dp_items';
            $doc_id_field = 'id_dp';
            $title = 'DEMANDE DE PAIEMENT';
            break;
    }
    
    // Get document
    $stmt = $pdo->prepare("SELECT d.*, c.nom_client, c.telephone, c.adresse, c.ville 
                          FROM $doc_table d 
                          LEFT JOIN clients c ON d.client_id = c.id 
                          WHERE d.$doc_number_field = ?");
    $stmt->execute([$doc_id]);
    $document_data = $stmt->fetch();
    
    if ($document_data) {
        // Get items
        $stmt = $pdo->prepare("SELECT * FROM $doc_items_table WHERE $doc_id_field = ? ORDER BY id");
        $stmt->execute([$document_data['id']]);
        $items = $stmt->fetchAll();
    }
    
} catch (Exception $e) {
    $error_message = "Error loading document: " . $e->getMessage();
}

// Generate HTML output
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - <?php echo APP_NAME; ?></title>
    <style>
        /* Simple Print Template - Same as buses_complete.php style */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background-color: #f5f5f5;
            padding: 20px;
        }
        
        .print-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header {
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .company-info {
            font-size: 11px;
            color: #666;
            margin: 2px 0;
        }
        
        .document-title {
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            text-transform: uppercase;
            color: #333;
        }
        
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 20px;
        }
        
        .info-box {
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            background: #f8f9fa;
        }
        
        .info-box h3 {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 5px;
        }
        
        .info-box p {
            font-size: 11px;
            margin: 5px 0;
            color: #555;
        }
        
        .info-box strong {
            color: #333;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
        }
        
        .items-table th {
            background: #f8f9fa;
            border: 1px solid #333;
            padding: 8px;
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            color: #333;
        }
        
        .items-table td {
            border: 1px solid #333;
            padding: 8px;
            font-size: 11px;
            color: #555;
        }
        
        .items-table .text-right {
            text-align: right;
        }
        
        .items-table .text-center {
            text-align: center;
        }
        
        .totals-section {
            margin: 20px 0;
            text-align: right;
        }
        
        .totals-table {
            width: 300px;
            border-collapse: collapse;
            margin-left: auto;
        }
        
        .totals-table td {
            padding: 5px 10px;
            font-size: 11px;
            border: none;
        }
        
        .totals-table .total-row td {
            border-top: 2px solid #333;
            font-weight: bold;
            color: #333;
        }
        
        .notes-section {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #f8f9fa;
        }
        
        .notes-section h4 {
            font-size: 12px;
            margin-bottom: 8px;
            color: #333;
        }
        
        .notes-section p {
            font-size: 10px;
            color: #666;
            line-height: 1.4;
        }
        
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            padding-top: 20px;
        }
        
        .signature-box {
            flex: 1;
            text-align: center;
            margin: 0 10px;
        }
        
        .signature-line {
            border-bottom: 1px solid #333;
            margin: 30px 0 5px 0;
            height: 1px;
        }
        
        .signature-label {
            font-size: 10px;
            color: #666;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        
        /* Badge styles - same as buses_complete.php */
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-success {
            background: #28a745;
            color: white;
        }
        
        .badge-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .badge-danger {
            background: #dc3545;
            color: white;
        }
        
        /* Print styles */
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .print-container {
                box-shadow: none;
                border: none;
                margin: 0;
                padding: 0;
            }
            
            .no-print {
                display: none !important;
            }
        }
        
        /* Button styles - same as buses_complete.php */
        .btn {
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            border: none;
            cursor: pointer;
            margin: 5px;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
            border: 1px solid #007bff;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
            border: 1px solid #28a745;
        }
        
        .btn:hover {
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="print-container">
        <!-- Header -->
        <div class="header">
            <div class="company-name"><?php echo htmlspecialchars($company_info['company_name'] ?? APP_NAME); ?></div>
            <div class="company-info"><?php echo htmlspecialchars($company_info['address'] ?? ''); ?></div>
            <div class="company-info">Tél: <?php echo htmlspecialchars($company_info['phone'] ?? ''); ?> | Email: <?php echo htmlspecialchars($company_info['email'] ?? ''); ?></div>
            <div class="company-info">IF: <?php echo htmlspecialchars($company_info['ice'] ?? ''); ?> | RC: <?php echo htmlspecialchars($company_info['rc'] ?? ''); ?></div>
        </div>
        
        <!-- Document Title -->
        <div class="document-title"><?php echo $title; ?></div>
        
        <?php if ($document_data): ?>
        <!-- Document Information -->
        <div class="info-section">
            <div class="info-box">
                <h3>Informations Document</h3>
                <p><strong>Numéro:</strong> <?php echo htmlspecialchars($document_data[$doc_number_field] ?? $doc_id); ?></p>
                <p><strong>Date:</strong> <?php echo date('d/m/Y', strtotime($document_data[$doc_date_field] ?? $document_data['date'] ?? date('Y-m-d'))); ?></p>
                <p><strong>Statut:</strong> 
                    <span class="badge badge-<?php 
                        echo ($document_data['statut'] ?? 'en_attente') === 'valide' ? 'success' : 
                        (($document_data['statut'] ?? 'en_attente') === 'annule' ? 'danger' : 'warning'); 
                    ?>">
                        <?php echo ucfirst($document_data['statut'] ?? 'En attente'); ?>
                    </span>
                </p>
            </div>
            
            <div class="info-box">
                <h3>Informations Client</h3>
                <p><strong>Nom:</strong> <?php echo htmlspecialchars($document_data['nom_client'] ?? ''); ?></p>
                <p><strong>Téléphone:</strong> <?php echo htmlspecialchars($document_data['telephone'] ?? ''); ?></p>
                <p><strong>Adresse:</strong> <?php echo htmlspecialchars($document_data['adresse'] ?? ''); ?></p>
                <p><strong>Ville:</strong> <?php echo htmlspecialchars($document_data['ville'] ?? ''); ?></p>
            </div>
        </div>
        
        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th width="10%">Référence</th>
                    <th width="40%">Désignation</th>
                    <th width="10%">Quantité</th>
                    <th width="15%">Prix Unitaire</th>
                    <th width="15%">Total</th>
                    <th width="10%">Remise</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $grand_total = 0;
                foreach ($items as $item):
                    $item_total = ($item['quantite'] ?? 0) * ($item['prix_unitaire'] ?? 0);
                    $grand_total += $item_total;
                ?>
                <tr>
                    <td class="text-center"><?php echo htmlspecialchars($item['reference'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($item['designation'] ?? ''); ?></td>
                    <td class="text-center"><?php echo number_format($item['quantite'] ?? 0, 2); ?></td>
                    <td class="text-right"><?php echo number_format($item['prix_unitaire'] ?? 0, 2, ',', ' '); ?> DH</td>
                    <td class="text-right"><?php echo number_format($item_total, 2, ',', ' '); ?> DH</td>
                    <td class="text-center"><?php echo number_format($item['remise'] ?? 0, 2); ?>%</td>
                </tr>
                <?php
                endforeach;
                
                if (empty($items)):
                ?>
                <tr>
                    <td colspan="6" class="text-center">Aucun article trouvé</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Totals -->
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td>Total HT:</td>
                    <td class="text-right"><?php echo number_format($grand_total, 2, ',', ' '); ?> DH</td>
                </tr>
                <tr>
                    <td>TVA (20%):</td>
                    <td class="text-right"><?php echo number_format($grand_total * 0.2, 2, ',', ' '); ?> DH</td>
                </tr>
                <tr class="total-row">
                    <td>Total TTC:</td>
                    <td class="text-right"><?php echo number_format($grand_total * 1.2, 2, ',', ' '); ?> DH</td>
                </tr>
            </table>
        </div>
        
        <!-- Notes -->
        <?php if (!empty($document_data['notes'])): ?>
        <div class="notes-section">
            <h4>Notes:</h4>
            <p><?php echo nl2br(htmlspecialchars($document_data['notes'])); ?></p>
        </div>
        <?php endif; ?>
        
        <?php else: ?>
        <div style="text-align: center; padding: 50px;">
            <h3>Document non trouvé</h3>
            <p>Le document demandé n'existe pas ou n'est pas accessible.</p>
        </div>
        <?php endif; ?>
        
        <!-- Signatures -->
        <div class="signatures">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">Signature Client</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">Signature Vendeur</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">Cachet et Signature</div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>Document généré le <?php echo date('d/m/Y H:i:s'); ?> par <?php echo APP_NAME; ?></p>
        </div>
        
        <!-- Print Button -->
        <div style="text-align: center; margin-top: 20px;" class="no-print">
            <button class="btn btn-primary" onclick="window.print()">
                🖨️ Imprimer
            </button>
            <button class="btn btn-success" onclick="window.close()">
                ✅ Fermer
            </button>
        </div>
    </div>
</body>
</html>

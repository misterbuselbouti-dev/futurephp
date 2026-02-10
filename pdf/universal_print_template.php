<?php
// FUTURE AUTOMOTIVE - Universal Print Template
// نموذج طباعة موحد لجميع الصفحات

// Include configuration
require_once '../config.php';
require_once '../includes/functions.php';

// Check authentication
require_login();

// Set headers for PDF printing
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Get document type and ID
$doc_type = sanitize_input($_GET['type'] ?? 'bc'); // bc, be, da, dp
$doc_id = sanitize_input($_GET['id'] ?? $_GET['nbc'] ?? $_GET['nbe'] ?? $_GET['nda'] ?? $_GET['ndp'] ?? '');

// Get document data based on type
$document_data = [];
$title = '';
$company_info = [];

try {
    $database = new Database();
    $pdo = $database->connect();
    
    // Get company information
    $stmt = $pdo->prepare("SELECT * FROM company_info LIMIT 1");
    $stmt->execute();
    $company_info = $stmt->fetch() ?: [];
    
    // Get document based on type
    switch ($doc_type) {
        case 'bc':
            $stmt = $pdo->prepare("SELECT bc.*, c.nom_client, c.telephone, c.adresse, c.ville 
                                 FROM bons_commande bc 
                                 LEFT JOIN clients c ON bc.client_id = c.id 
                                 WHERE bc.numero_bc = ?");
            $stmt->execute([$doc_id]);
            $document_data = $stmt->fetch();
            $title = 'BON DE COMMANDE';
            break;
            
        case 'be':
            $stmt = $pdo->prepare("SELECT be.*, c.nom_client, c.telephone, c.adresse, c.ville 
                                 FROM bons_expedition be 
                                 LEFT JOIN clients c ON be.client_id = c.id 
                                 WHERE be.numero_be = ?");
            $stmt->execute([$doc_id]);
            $document_data = $stmt->fetch();
            $title = 'BON D\'EXPÉDITION';
            break;
            
        case 'da':
            $stmt = $pdo->prepare("SELECT da.*, c.nom_client, c.telephone, c.adresse, c.ville 
                                 FROM demandes_achat da 
                                 LEFT JOIN clients c ON da.client_id = c.id 
                                 WHERE da.numero_da = ?");
            $stmt->execute([$doc_id]);
            $document_data = $stmt->fetch();
            $title = 'DEMANDE D\'ACHAT';
            break;
            
        case 'dp':
            $stmt = $pdo->prepare("SELECT dp.*, c.nom_client, c.telephone, c.adresse, c.ville 
                                 FROM demandes_paiement dp 
                                 LEFT JOIN clients c ON dp.client_id = c.id 
                                 WHERE dp.numero_dp = ?");
            $stmt->execute([$doc_id]);
            $document_data = $stmt->fetch();
            $title = 'DEMANDE DE PAIEMENT';
            break;
    }
    
} catch (Exception $e) {
    $error_message = "Error loading document: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - <?php echo APP_NAME; ?></title>
    <style>
        /* Print Template Styles - Simple and Clean */
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
            background: white;
        }
        
        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 15mm;
            background: white;
            box-shadow: none;
        }
        
        .header {
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .company-info {
            text-align: center;
            margin-bottom: 15px;
        }
        
        .company-info h1 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .company-info p {
            font-size: 11px;
            margin: 2px 0;
        }
        
        .document-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin: 15px 0;
            text-transform: uppercase;
        }
        
        .document-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .info-block {
            border: 1px solid #ddd;
            padding: 10px;
            width: 48%;
        }
        
        .info-block h3 {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 8px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        
        .info-block p {
            font-size: 11px;
            margin: 3px 0;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .items-table th {
            background: #f8f9fa;
            border: 1px solid #333;
            padding: 8px;
            text-align: center;
            font-weight: bold;
            font-size: 11px;
        }
        
        .items-table td {
            border: 1px solid #333;
            padding: 6px;
            font-size: 11px;
        }
        
        .items-table .text-right {
            text-align: right;
        }
        
        .items-table .text-center {
            text-align: center;
        }
        
        .totals {
            margin-top: 20px;
            text-align: right;
        }
        
        .totals table {
            width: 300px;
            border-collapse: collapse;
            float: right;
        }
        
        .totals td {
            padding: 5px;
            font-size: 11px;
            border: none;
        }
        
        .totals .total-row td {
            border-top: 2px solid #333;
            font-weight: bold;
        }
        
        .footer {
            margin-top: 30px;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
        
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
        }
        
        .signature-block {
            width: 30%;
            text-align: center;
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
        
        .notes {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #eee;
            background: #f9f9f9;
        }
        
        .notes h4 {
            font-size: 12px;
            margin-bottom: 5px;
        }
        
        .notes p {
            font-size: 10px;
            color: #666;
        }
        
        /* Print specific styles */
        @media print {
            .page {
                margin: 0;
                padding: 10mm;
                box-shadow: none;
            }
            
            .no-print {
                display: none;
            }
        }
        
        /* Simple theme colors */
        .text-primary {
            color: #007bff;
        }
        
        .text-muted {
            color: #6c757d;
        }
        
        .badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }
        
        .badge-primary {
            background: #007bff;
            color: white;
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
    </style>
</head>
<body>
    <div class="page">
        <!-- Header -->
        <div class="header">
            <div class="company-info">
                <h1><?php echo htmlspecialchars($company_info['company_name'] ?? APP_NAME); ?></h1>
                <p><?php echo htmlspecialchars($company_info['address'] ?? ''); ?></p>
                <p>Tél: <?php echo htmlspecialchars($company_info['phone'] ?? ''); ?> | Email: <?php echo htmlspecialchars($company_info['email'] ?? ''); ?></p>
                <p>IF: <?php echo htmlspecialchars($company_info['ice'] ?? ''); ?> | RC: <?php echo htmlspecialchars($company_info['rc'] ?? ''); ?></p>
            </div>
        </div>
        
        <!-- Document Title -->
        <div class="document-title">
            <?php echo $title; ?>
        </div>
        
        <?php if ($document_data): ?>
        <!-- Document Information -->
        <div class="document-info">
            <div class="info-block">
                <h3>INFORMATIONS DOCUMENT</h3>
                <p><strong>Numéro:</strong> <?php echo htmlspecialchars($document_data['numero_' . $doc_type] ?? $doc_id); ?></p>
                <p><strong>Date:</strong> <?php echo date('d/m/Y', strtotime($document_data['date_' . $doc_type] ?? $document_data['date'] ?? date('Y-m-d'))); ?></p>
                <p><strong>Statut:</strong> 
                    <span class="badge badge-<?php 
                        echo ($document_data['statut'] ?? 'en_attente') === 'valide' ? 'success' : 
                        (($document_data['statut'] ?? 'en_attente') === 'annule' ? 'danger' : 'warning'); 
                    ?>">
                        <?php echo ucfirst($document_data['statut'] ?? 'En attente'); ?>
                    </span>
                </p>
            </div>
            
            <div class="info-block">
                <h3>INFORMATIONS CLIENT</h3>
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
                // Get items based on document type
                $items_table = $doc_type . '_items';
                $doc_field = 'id_' . $doc_type;
                
                try {
                    $stmt = $pdo->prepare("SELECT * FROM $items_table WHERE $doc_field = ? ORDER BY id");
                    $stmt->execute([$document_data['id']]);
                    $items = $stmt->fetchAll();
                    
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
                } catch (Exception $e) {
                    echo '<tr><td colspan="6" class="text-center">Error loading items</td></tr>';
                }
                ?>
            </tbody>
        </table>
        
        <!-- Totals -->
        <div class="totals">
            <table>
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
        <div class="notes">
            <h4>Notes:</h4>
            <p><?php echo nl2br(htmlspecialchars($document_data['notes'])); ?></p>
        </div>
        <?php endif; ?>
        
        <?php else: ?>
        <div class="text-center" style="padding: 50px;">
            <h3>Document non trouvé</h3>
            <p>Le document demandé n'existe pas ou n'est pas accessible.</p>
        </div>
        <?php endif; ?>
        
        <!-- Footer -->
        <div class="footer">
            <div class="signatures">
                <div class="signature-block">
                    <div class="signature-line"></div>
                    <div class="signature-label">Signature Client</div>
                </div>
                <div class="signature-block">
                    <div class="signature-line"></div>
                    <div class="signature-label">Signature Vendeur</div>
                </div>
                <div class="signature-block">
                    <div class="signature-line"></div>
                    <div class="signature-label">Cachet et Signature</div>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 20px; font-size: 10px; color: #666;">
                <p>Document généré le <?php echo date('d/m/Y H:i:s'); ?> par <?php echo APP_NAME; ?></p>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-print functionality
        window.onload = function() {
            // Optional: Auto print when page loads
            // window.print();
            
            // Add print button
            const printBtn = document.createElement('button');
            printBtn.innerHTML = '🖨️ Imprimer';
            printBtn.style.cssText = 'position: fixed; top: 10px; right: 10px; padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; z-index: 1000;';
            printBtn.className = 'no-print';
            printBtn.onclick = function() {
                window.print();
            };
            document.body.appendChild(printBtn);
        };
    </script>
</body>
</html>

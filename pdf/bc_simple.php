<?php
// FUTURE AUTOMOTIVE - BC Print Template (Simple Design)
// نموذج طباعة BC بالتصميم البسيط

// Include configuration
require_once '../config.php';
require_once '../includes/functions.php';

// Check authentication
require_login();

// Get BC number
$nbc = sanitize_input($_GET['nbc'] ?? '');

// Get BC data
$bc_data = [];
$client_data = [];
$items = [];
$company_info = [];

try {
    $database = new Database();
    $pdo = $database->connect();
    
    // Get company information
    $stmt = $pdo->prepare("SELECT * FROM company_info LIMIT 1");
    $stmt->execute();
    $company_info = $stmt->fetch() ?: [];
    
    // Get BC data
    $stmt = $pdo->prepare("SELECT bc.*, c.nom_client, c.telephone, c.adresse, c.ville 
                          FROM bons_commande bc 
                          LEFT JOIN clients c ON bc.client_id = c.id 
                          WHERE bc.numero_bc = ?");
    $stmt->execute([$nbc]);
    $bc_data = $stmt->fetch();
    
    if ($bc_data) {
        // Get BC items
        $stmt = $pdo->prepare("SELECT * FROM bc_items WHERE id_bc = ? ORDER BY id");
        $stmt->execute([$bc_data['id']]);
        $items = $stmt->fetchAll();
    }
    
} catch (Exception $e) {
    $error_message = "Error loading BC: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BON DE COMMANDE - <?php echo $nbc; ?> - <?php echo APP_NAME; ?></title>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <style>
        /* Same simple style as buses_complete.php */
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .header h1 {
            font-size: 18px;
            font-weight: bold;
            margin: 0 0 5px 0;
            color: #333;
        }
        
        .header p {
            font-size: 11px;
            margin: 2px 0;
            color: #666;
        }
        
        .document-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin: 20px 0;
            text-transform: uppercase;
            color: #333;
        }
        
        .info-grid {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
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
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
        }
        
        .table th {
            background: #f8f9fa;
            border: 1px solid #333;
            padding: 8px;
            text-align: center;
            font-weight: 600;
            font-size: 11px;
            color: #333;
        }
        
        .table td {
            border: 1px solid #333;
            padding: 8px;
            font-size: 11px;
            color: #555;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .totals {
            text-align: right;
            margin: 20px 0;
        }
        
        .totals table {
            width: 300px;
            border-collapse: collapse;
            margin-left: auto;
        }
        
        .totals td {
            padding: 5px 10px;
            font-size: 11px;
            border: none;
        }
        
        .totals .total-row td {
            border-top: 2px solid #333;
            font-weight: bold;
            color: #333;
        }
        
        .notes {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #f8f9fa;
        }
        
        .notes h4 {
            font-size: 12px;
            margin-bottom: 8px;
            color: #333;
        }
        
        .notes p {
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
        
        .no-print {
            display: block;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .container {
                box-shadow: none;
                border: none;
                margin: 0;
                padding: 0;
            }
            
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><?php echo htmlspecialchars($company_info['company_name'] ?? APP_NAME); ?></h1>
            <p><?php echo htmlspecialchars($company_info['address'] ?? ''); ?></p>
            <p>Tél: <?php echo htmlspecialchars($company_info['phone'] ?? ''); ?> | Email: <?php echo htmlspecialchars($company_info['email'] ?? ''); ?></p>
            <p>IF: <?php echo htmlspecialchars($company_info['ice'] ?? ''); ?> | RC: <?php echo htmlspecialchars($company_info['rc'] ?? ''); ?></p>
        </div>
        
        <!-- Document Title -->
        <div class="document-title">BON DE COMMANDE</div>
        
        <?php if ($bc_data): ?>
        <!-- Document Information -->
        <div class="info-grid">
            <div class="info-box">
                <h3>Informations Document</h3>
                <p><strong>Numéro:</strong> <?php echo htmlspecialchars($bc_data['numero_bc']); ?></p>
                <p><strong>Date:</strong> <?php echo date('d/m/Y', strtotime($bc_data['date_bc'])); ?></p>
                <p><strong>Statut:</strong> 
                    <span class="badge badge-<?php 
                        echo ($bc_data['statut'] ?? 'en_attente') === 'valide' ? 'success' : 
                        (($bc_data['statut'] ?? 'en_attente') === 'annule' ? 'danger' : 'warning'); 
                    ?>">
                        <?php echo ucfirst($bc_data['statut'] ?? 'En attente'); ?>
                    </span>
                </p>
            </div>
            
            <div class="info-box">
                <h3>Informations Client</h3>
                <p><strong>Nom:</strong> <?php echo htmlspecialchars($bc_data['nom_client'] ?? ''); ?></p>
                <p><strong>Téléphone:</strong> <?php echo htmlspecialchars($bc_data['telephone'] ?? ''); ?></p>
                <p><strong>Adresse:</strong> <?php echo htmlspecialchars($bc_data['adresse'] ?? ''); ?></p>
                <p><strong>Ville:</strong> <?php echo htmlspecialchars($bc_data['ville'] ?? ''); ?></p>
            </div>
        </div>
        
        <!-- Items Table -->
        <table class="table">
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
        <?php if (!empty($bc_data['notes'])): ?>
        <div class="notes">
            <h4>Notes:</h4>
            <p><?php echo nl2br(htmlspecialchars($bc_data['notes'])); ?></p>
        </div>
        <?php endif; ?>
        
        <?php else: ?>
        <div style="text-align: center; padding: 50px;">
            <h3>Bon de commande non trouvé</h3>
            <p>Le bon de commande demandé n'existe pas ou n'est pas accessible.</p>
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

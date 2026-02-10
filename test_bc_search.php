<?php
// FUTURE AUTOMOTIVE - Test BC Data for Search
// اختبار بيانات BC للبحث

require_once 'config_achat_hostinger.php';
require_once 'config.php';

// Check authentication
if (!is_logged_in()) {
    header('Location: login.php');
    exit();
}

$user = get_logged_in_user();
$role = $_SESSION['role'] ?? '';

// Only admin can access this tool
if ($role !== 'admin') {
    http_response_code(403);
    echo 'Accès refusé.';
    exit();
}

$page_title = 'Test BC Data';
$database = new DatabaseAchat();
$conn = $database->connect();

// Get archive BC data
$bons_commande = [];
try {
    $stmt = $conn->query("
        SELECT bc.*, 
               dp.ref_dp,
               da.ref_da,
               s.nom_fournisseur,
               COUNT(bci.id) as nombre_articles,
               SUM(bci.total_with_tax) as montant_total
        FROM bons_commande bc
        LEFT JOIN demandes_prix dp ON bc.dp_id = dp.id
        LEFT JOIN demandes_achat da ON dp.da_id = da.id
        LEFT JOIN suppliers s ON dp.fournisseur_id = s.id
        LEFT JOIN bc_items bci ON bc.id = bci.bc_id
        WHERE bc.statut != 'Commandé'
        GROUP BY bc.id
        ORDER BY bc.date_commande DESC
        LIMIT 5
    ");
    $bons_commande = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error_message = "Erreur: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/simple-theme.css">
    
    <style>
        .main-content {
            margin-left: 260px;
            padding: 2rem;
        }
        
        .workshop-card {
            background-color: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: var(--space-6);
            margin-bottom: var(--space-6);
        }
        
        .data-sample {
            background: #f8f9fa;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .amount-test {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Include header -->
    <?php include 'includes/header.php'; ?>
    
    <!-- Include sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-6">
                <div>
                    <h1 class="mb-2">
                        <i class="fas fa-search me-3"></i>
                        Test BC Data for Search
                    </h1>
                    <p class="text-muted mb-0">Testing BC data structure for search functionality</p>
                </div>
                <div class="d-flex gap-3">
                    <button class="btn btn-outline-primary" onclick="window.location.href='achat_bc.php'">
                        <i class="fas fa-arrow-left me-2"></i>Back to BC
                    </button>
                </div>
            </div>

            <div class="workshop-card">
                <h2 class="mb-4">🔍 BC Data Analysis</h2>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <div class="mb-4">
                    <h3>Archive BC Data (First 5 records):</h3>
                    
                    <?php if (empty($bons_commande)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No archive BC data found. You need some BCs with status other than 'Commandé' to test search.
                        </div>
                    <?php else: ?>
                        <?php foreach ($bons_commande as $index => $bc): ?>
                            <div class="data-sample">
                                <h5>BC #<?php echo $index + 1; ?>: <?php echo htmlspecialchars($bc['ref_bc']); ?></h5>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Raw Data:</strong>
                                        <ul>
                                            <li><strong>ref_bc:</strong> <?php echo htmlspecialchars($bc['ref_bc']); ?></li>
                                            <li><strong>ref_dp:</strong> <?php echo htmlspecialchars($bc['ref_dp'] ?? 'N/A'); ?></li>
                                            <li><strong>nom_fournisseur:</strong> <?php echo htmlspecialchars($bc['nom_fournisseur'] ?? 'N/A'); ?></li>
                                            <li><strong>statut:</strong> <?php echo htmlspecialchars($bc['statut']); ?></li>
                                            <li><strong>nombre_articles:</strong> <?php echo intval($bc['nombre_articles'] ?? 0); ?></li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Amount Analysis:</strong>
                                        <ul>
                                            <li><strong>montant_total (raw):</strong> <?php echo var_export($bc['montant_total'], true); ?></li>
                                            <li><strong>montant_total (floatval):</strong> <?php echo floatval($bc['montant_total'] ?? 0); ?></li>
                                            <li><strong>Formatted amount:</strong> <?php echo number_format(floatval($bc['montant_total'] ?? 0), 2, ',', ' '); ?> MAD</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="amount-test">
                                    <h6>Search Tests:</h6>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <strong>Reference Search:</strong><br>
                                            - Search for "<?php echo substr(htmlspecialchars($bc['ref_bc']), -3); ?>"<br>
                                            - Should match: ✅
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Supplier Search:</strong><br>
                                            - Search for "<?php echo htmlspecialchars(substr($bc['nom_fournisseur'] ?? '', 0, 3)); ?>"<br>
                                            - Should match: ✅
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Amount Search:</strong><br>
                                            <?php 
                                            $amount = floatval($bc['montant_total'] ?? 0);
                                            $formatted = number_format($amount, 2, ',', ' ');
                                            $clean = preg_replace('/[^\d.,]/', '', $formatted);
                                            $clean = str_replace(',', '.', $clean);
                                            ?>
                                            - Search for "<?php echo substr($clean, -2); ?>"<br>
                                            - Original: "<?php echo $formatted; ?>"<br>
                                            - Clean: "<?php echo $clean; ?>"<br>
                                            - Should match: ✅
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="mt-4">
                    <h3>JavaScript Data Extraction Test:</h3>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Open browser console (F12) and click the test button below to see how JavaScript extracts data from the table.
                    </div>
                    
                    <button class="btn btn-primary" onclick="testJavaScriptExtraction()">
                        <i class="fas fa-play me-2"></i>Test JavaScript Data Extraction
                    </button>
                    
                    <div id="testResults" class="mt-3" style="display: none;">
                        <div class="alert alert-success">
                            <h5>JavaScript Test Results:</h5>
                            <pre id="resultsContent"></pre>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <h3>Troubleshooting Steps:</h3>
                    <ol>
                        <li><strong>Check if you have archive BCs:</strong> Make sure you have BCs with status other than 'Commandé'</li>
                        <li><strong>Test with different amounts:</strong> Try searching for parts of amounts (last 2 digits)</li>
                        <li><strong>Check console logs:</strong> Open F12 and look for console messages when searching</li>
                        <li><strong>Verify data format:</strong> Check how amounts are formatted in your database</li>
                        <li><strong>Test the extraction:</strong> Use the button above to test JavaScript data extraction</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function testJavaScriptExtraction() {
            console.log('=== TESTING JAVASCRIPT DATA EXTRACTION ===');
            
            // Get table rows like the search function does
            const bcTableRows = document.querySelectorAll('.bc-table tbody tr');
            console.log('Found table rows:', bcTableRows.length);
            
            const extractedData = [];
            
            bcTableRows.forEach((row, index) => {
                const cells = row.querySelectorAll('td');
                if (cells.length >= 5) {
                    const data = {
                        ref: cells[0] ? cells[0].textContent.trim() : '',
                        details: cells[1] ? cells[1].textContent.trim() : '',
                        statut: cells[2] ? cells[2].textContent.trim() : '',
                        articles: cells[3] ? cells[3].textContent.trim() : '',
                        montant: cells[4] ? cells[4].textContent.trim() : '',
                        element: row,
                        type: 'table'
                    };
                    
                    console.log(`Row ${index} data:`, data);
                    extractedData.push(data);
                    
                    // Test amount search specifically
                    const cleanMontant = data.montant.replace(/[^\d.,]/g, '').replace(',', '.');
                    console.log(`Row ${index} amount test:`, {
                        original: data.montant,
                        clean: cleanMontant,
                        last2digits: cleanMontant.substring(cleanMontant.length - 2),
                        search60: cleanMontant.includes('60'),
                        searchAny: cleanMontant.length > 2
                    });
                }
            });
            
            // Display results
            const resultsDiv = document.getElementById('testResults');
            const resultsContent = document.getElementById('resultsContent');
            
            resultsDiv.style.display = 'block';
            resultsContent.textContent = JSON.stringify(extractedData, null, 2);
            
            console.log('Extraction complete. Found', extractedData.length, 'BC records.');
        }
    </script>
</body>
</html>

<?php
// FUTURE AUTOMOTIVE - Manual bc_document.php Creator
// Create the file directly if missing

echo "<!DOCTYPE html><html><head><title>Manual bc_document.php Creator</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;background:#f5f5f5;}";
echo ".container{max-width:800px;margin:0 auto;background:white;padding:20px;border-radius:10px;}";
echo ".success{color:green;font-weight:bold;}";
echo ".error{color:red;font-weight:bold;}";
echo ".warning{color:orange;font-weight:bold;}";
echo ".file-info{background:#e3f2fd;padding:15px;margin:10px 0;border-radius:5px;}";
echo ".code-block{background:#f5f5f5;padding:10px;border-radius:5px;font-family:monospace;max-height:300px;overflow-y:auto;}";
echo "</style></head><body>";

echo "<div class='container'>";
echo "<h1>🔧 Manual bc_document.php Creator</h1>";

$filePath = __DIR__ . '/includes/bc_document.php';

echo "<h2>📁 Creating bc_document.php...</h2>";

// The complete bc_document.php content
$bcDocumentContent = '<?php
// FUTURE AUTOMOTIVE - Shared helpers for BC print/PDF rendering

if (!function_exists(\'load_bc_document\')) {
    /**
     * Load BC details with related supplier and article data.
     *
     * @param int $bc_id
     * @return array [array $bc, array $articles, array $company]
     * @throws Exception
     */
    function load_bc_document(int $bc_id): array
    {
        if (!$bc_id) {
            throw new InvalidArgumentException(\'Identifiant BC invalide.\');
        }

        $database = new DatabaseAchat();
        $conn = $database->connect();

        // Load BC details
        $stmt = $conn->prepare("
            SELECT bc.*, 
                   f.nom_fournisseur, f.telephone, f.email, f.adresse, f.ice, f.rc,
                   u.full_name as created_by_name
            FROM bons_commande bc
            LEFT JOIN fournisseurs f ON bc.fournisseur_id = f.id
            LEFT JOIN users u ON bc.created_by = u.id
            WHERE bc.id = ?
        ");
        $stmt->execute([$bc_id]);
        $bc = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$bc) {
            throw new Exception(\'Bon de commande non trouvé.\');
        }

        // Load BC articles
        $stmt = $conn->prepare("
            SELECT bca.*, 
                   a.designation, a.unite, a.prix_unitaire,
                   ac.code_article
            FROM bons_commande_articles bca
            LEFT JOIN articles a ON bca.article_id = a.id
            LEFT JOIN articles_catalogue ac ON bca.article_id = ac.id
            WHERE bca.bc_id = ?
            ORDER BY bca.id
        ");
        $stmt->execute([$bc_id]);
        $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Company information
        $company = [
            \'name\' => \'FUTURE AUTOMOTIVE\',
            \'address\' => \'Tétouan, Maroc\',
            \'phone\' => \'+212 5XXX-XXXXXX\',
            \'email\' => \'info@futureautomotive.net\',
            \'ice\' => \'XXXXXXXXXXX\',
            \'rc\' => \'XXXXXXXXXX\'
        ];

        return [$bc, $articles, $company];
    }
}

if (!function_exists(\'format_bc_reference\')) {
    /**
     * Format BC reference for display.
     *
     * @param string $reference
     * @return string
     */
    function format_bc_reference(string $reference): string
    {
        return strtoupper($reference);
    }
}

if (!function_exists(\'calculate_bc_total\')) {
    /**
     * Calculate total amount for BC.
     *
     * @param array $articles
     * @return float
     */
    function calculate_bc_total(array $articles): float
    {
        $total = 0;
        foreach ($articles as $article) {
            $total += ($article[\'quantite\'] * $article[\'prix_unitaire\']);
        }
        return $total;
    }
}

if (!function_exists(\'get_bc_status_label\')) {
    /**
     * Get status label for BC.
     *
     * @param string $status
     * @return array
     */
    function get_bc_status_label(string $status): array
    {
        $statuses = [
            \'draft\' => [\'label\' => \'Brouillon\', \'color\' => \'secondary\'],
            \'sent\' => [\'label\' => \'Envoyé\', \'color\' => \'info\'],
            \'approved\' => [\'label\' => \'Approuvé\', \'color\' => \'success\'],
            \'rejected\' => [\'label\' => \'Rejeté\', \'color\' => \'danger\'],
            \'cancelled\' => [\'label\' => \'Annulé\', \'color\' => \'warning\']
        ];

        return $statuses[$status] ?? [\'label\' => $status, \'color\' => \'secondary\'];
    }
}
?>';

// Create the file
if (file_put_contents($filePath, $bcDocumentContent)) {
    echo "<div class='file-info'>";
    echo "<p class='success'>✅ File created successfully: " . htmlspecialchars($filePath) . "</p>";
    echo "<p>📊 File size: " . filesize($filePath) . " bytes</p>";
    echo "<p>📅 Created at: " . date('Y-m-d H:i:s') . "</p>";
    echo "</div>";
    
    echo "<h2>✅ File Content Created:</h2>";
    echo "<div class='code-block'>";
    echo htmlspecialchars($bcDocumentContent);
    echo "</div>";
    
    echo "<h2>🔧 Functions Available:</h2>";
    echo "<ul>";
    echo "<li><strong>load_bc_document()</strong> - Load BC with supplier and article data</li>";
    echo "<li><strong>format_bc_reference()</strong> - Format BC reference</li>";
    echo "<li><strong>calculate_bc_total()</strong> - Calculate BC total amount</li>";
    echo "<li><strong>get_bc_status_label()</strong> - Get status label with color</li>";
    echo "</ul>";
    
} else {
    echo "<div class='error'>";
    echo "<p class='error'>❌ Failed to create file: " . htmlspecialchars($filePath) . "</p>";
    echo "</div>";
}

echo "<h2>🚀 Next Steps:</h2>";
echo "<div class='file-info'>";
echo "<ol>";
echo "<li>Verify file exists: <a href='check_bc_document.php'>Run Status Check</a></li>";
echo "<li>Test BC PDF generation: <a href='achat_bc_pdf.php?id=1'>Test PDF</a></li>";
echo "<li>Deploy to server if needed</li>";
echo "<li>Clear server cache</li>";
echo "</ol>";
echo "</div>";

echo "</div>";
echo "</body></html>";
?>

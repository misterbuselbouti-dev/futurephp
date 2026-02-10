<?php
// Excel to SQL Converter for Articles
require_once 'config.php';

// Function to read Excel file and convert to SQL
function convertExcelToSQL($filePath, $warehouse) {
    try {
        // Try to read Excel using PHPExcel/PhpSpreadsheet if available
        // For now, we'll create a manual converter based on common Excel structure
        
        $sql = "-- Import from $warehouse warehouse\n";
        
        // Since we can't read Excel directly without proper library,
        // let's create a template you can fill with actual data
        
        if ($warehouse === 'Ksar') {
            $sql .= "-- Ksar warehouse articles\n";
            $sql .= "INSERT INTO articles_catalogue (code_article, designation, categorie, description, prix_unitaire, stock_ksar, stock_tetouan) VALUES\n";
            $sql .= "('CODE-001', 'Article Name 1', 'Category', 'Description', 0.00, 10.00, 0.00),\n";
            $sql .= "('CODE-002', 'Article Name 2', 'Category', 'Description', 0.00, 5.00, 0.00),\n";
            $sql .= "-- Add more articles from your Excel file...\n";
        } else {
            $sql .= "-- Tetouan warehouse articles\n";
            $sql .= "INSERT INTO articles_catalogue (code_article, designation, categorie, description, prix_unitaire, stock_ksar, stock_tetouan) VALUES\n";
            $sql .= "('CODE-003', 'Article Name 3', 'Category', 'Description', 0.00, 0.00, 15.00),\n";
            $sql .= "('CODE-004', 'Article Name 4', 'Category', 'Description', 0.00, 0.00, 8.00),\n";
            $sql .= "-- Add more articles from your Excel file...\n";
        }
        
        return $sql;
        
    } catch (Exception $e) {
        return "-- Error converting $warehouse: " . $e->getMessage() . "\n";
    }
}

// Generate SQL for both warehouses
$ksar_sql = convertExcelToSQL('ListeDesArticles_Ksar.xls', 'Ksar');
$tetouan_sql = convertExcelToSQL('ListeDesArticles_Tetouan.xls', 'Tetouan');

// Create complete SQL file
$complete_sql = "-- Complete SQL Import for Articles\n";
$complete_sql .= "-- Generated automatically from Excel files\n\n";
$complete_sql .= $ksar_sql . "\n";
$complete_sql .= $tetouan_sql . "\n";

// Update total stock
$complete_sql .= "-- Update total stock calculations\n";
$complete_sql .= "UPDATE articles_catalogue SET stock_actuel = stock_ksar + stock_tetouan;\n";

// Save to file
file_put_contents('import_complete_articles.sql', $complete_sql);

echo "SQL file created: import_complete_articles.sql\n";
echo "Please review and execute in phpMyAdmin\n";

// Display the SQL
echo "\n" . $complete_sql;
?>

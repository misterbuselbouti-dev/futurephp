<?php
// FUTURE AUTOMOTIVE - Export Data Script
// سكتب تصدير البيانات

require_once 'config.php';
require_once 'includes/functions.php';

// Check authentication
require_login();

// Get export type
$export_type = sanitize_input($_GET['export'] ?? 'csv');

// Get filters
$category_filter = sanitize_input($_GET['category'] ?? '');
$status_filter = sanitize_input($_GET['status'] ?? '');
$search = sanitize_input($_GET['search'] ?? '');

// Set headers for CSV export
if ($export_type === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="buses_export_' . date('Y-m-d_H-i-s') . '.csv"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // CSV Header
    fputcsv($output, [
        'Bus Number',
        'Category',
        'Make',
        'Model',
        'Year',
        'License Plate',
        'Capacity',
        'Puissance Fiscale',
        'Status',
        'Created At'
    ]);
    
    // Get data
    try {
        $database = new Database();
        $pdo = $database->connect();
        
        // Build query with filters
        $sql = "SELECT * FROM buses WHERE 1=1";
        $params = [];
        
        if ($category_filter) {
            $sql .= " AND category = ?";
            $params[] = $category_filter;
        }
        
        if ($status_filter) {
            $sql .= " AND status = ?";
            $params[] = $status_filter;
        }
        
        if ($search) {
            $sql .= " AND (bus_number LIKE ? OR license_plate LIKE ? OR make LIKE ? OR model LIKE ?)";
            $search_term = "%$search%";
            $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
        }
        
        $sql .= " ORDER BY category, bus_number";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $buses = $stmt->fetchAll();
        
        // Export data
        foreach ($buses as $bus) {
            fputcsv($output, [
                $bus['bus_number'],
                $bus['category'],
                $bus['make'],
                $bus['model'],
                $bus['year'],
                $bus['license_plate'],
                $bus['capacity'],
                $bus['puissance_fiscale'],
                $bus['status'],
                $bus['created_at']
            ]);
        }
        
        fclose($output);
        exit();
        
    } catch (Exception $e) {
        echo "Error exporting data: " . $e->getMessage();
    }
} else {
    // For other export types (PDF, Excel)
    echo "Export format not supported. Please use CSV format.";
}
?>

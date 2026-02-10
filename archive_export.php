<?php
// FUTURE AUTOMOTIVE - Archive Export
// تصدير بيانات الأرشيف

require_once 'config.php';
require_once 'config_achat_hostinger.php';
require_login();

$month = $_GET['month'] ?? '';
$format = $_GET['format'] ?? 'excel';

try {
    $database_achat = new DatabaseAchat();
    $pdo = $database_achat->connect();
    
    if ($format === 'excel') {
        exportToExcel($pdo, $month);
    } elseif ($format === 'pdf') {
        exportToPDF($pdo, $month);
    } else {
        throw new Exception('Format non supporté');
    }
    
} catch (Exception $e) {
    die('Erreur: ' . $e->getMessage());
}

function exportToExcel($pdo, $month) {
    // Get data
    if (!empty($month)) {
        $stmt = $pdo->prepare("
            SELECT ta.*, s.nom_fournisseur
            FROM transaction_archive ta
            LEFT JOIN suppliers s ON ta.supplier_id = s.id
            WHERE ta.year_month = ?
            ORDER BY ta.transaction_date DESC, ta.transaction_type
        ");
        $stmt->execute([$month]);
        $data = $stmt->fetchAll();
        $filename = "archive_{$month}_" . date('Y-m-d') . '.csv';
    } else {
        $stmt = $pdo->query("
            SELECT ta.*, s.nom_fournisseur
            FROM transaction_archive ta
            LEFT JOIN suppliers s ON ta.supplier_id = s.id
            ORDER BY ta.year_month DESC, ta.transaction_date DESC, ta.transaction_type
        ");
        $data = $stmt->fetchAll();
        $filename = "archive_complete_" . date('Y-m-d') . '.csv';
    }
    
    // Create CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fwrite($output, "\xEF\xBB\xBF");
    
    // Headers
    fputcsv($output, [
        'نوع المعاملة',
        'المرجع',
        'المورد',
        'المبلغ',
        'التاريخ',
        'الحالة',
        'تاريخ الأرشفة'
    ]);
    
    // Data
    foreach ($data as $row) {
        $typeLabels = [
            'DA' => 'طلب شراء',
            'DP' => 'طلب سعر',
            'BC' => 'أمر شراء',
            'BE' => 'إيصال استلام'
        ];
        
        fputcsv($output, [
            $typeLabels[$row['transaction_type']] ?? $row['transaction_type'],
            $row['reference'],
            $row['nom_fournisseur'] ?? 'غير محدد',
            number_format($row['amount'], 2, ',', ' ') . ' DH',
            $row['transaction_date'],
            $row['status'],
            $row['archived_at']
        ]);
    }
    
    fclose($output);
    exit;
}

function exportToPDF($pdo, $month) {
    // Get summary data
    $stmt = $pdo->prepare("
        SELECT year_month, 
               SUM(da_count) as total_da,
               SUM(dp_count) as total_dp,
               SUM(bc_count) as total_bc,
               SUM(be_count) as total_be,
               SUM(total_amount) as total_amount,
               COUNT(DISTINCT supplier_id) as suppliers_count
        FROM monthly_transactions_summary 
        WHERE year_month = ?
    ");
    $stmt->execute([$month]);
    $summary = $stmt->fetch();
    
    // Get supplier data
    $stmt = $pdo->prepare("
        SELECT mts.*, s.nom_fournisseur
        FROM monthly_transactions_summary mts
        LEFT JOIN suppliers s ON mts.supplier_id = s.id
        WHERE mts.year_month = ?
        ORDER BY mts.total_amount DESC
    ");
    $stmt->execute([$month]);
    $suppliers = $stmt->fetchAll();
    
    // Create HTML for PDF
    $html = '
    <!DOCTYPE html>
    <html dir="rtl">
    <head>
        <meta charset="UTF-8">
        <title>تقرير الأرشيف الشهري - ' . $month . '</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { text-align: center; margin-bottom: 30px; }
            .summary { background: #f5f5f5; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
            .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: right; }
            .table th { background: #f2f2f2; font-weight: bold; }
            .total { font-weight: bold; background: #e8f5e8; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>تقرير الأرشيف الشهري</h1>
            <h2>' . $month . '</h2>
            <p>تاريخ الإنشاء: ' . date('Y-m-d H:i:s') . '</p>
        </div>
        
        <div class="summary">
            <h3>ملخص الشهر</h3>
            <table class="table">
                <tr>
                    <td>طلبات الشراء (DA)</td>
                    <td>' . $summary['total_da'] . '</td>
                </tr>
                <tr>
                    <td>طلبات الأسعار (DP)</td>
                    <td>' . $summary['total_dp'] . '</td>
                </tr>
                <tr>
                    <td>أوامر الشراء (BC)</td>
                    <td>' . $summary['total_bc'] . '</td>
                </tr>
                <tr>
                    <td>إيصالات الاستلام (BE)</td>
                    <td>' . $summary['total_be'] . '</td>
                </tr>
                <tr>
                    <td>عدد الموردين</td>
                    <td>' . $summary['suppliers_count'] . '</td>
                </tr>
                <tr class="total">
                    <td>الإجمالي</td>
                    <td>' . number_format($summary['total_amount'], 2, ',', ' ') . ' DH</td>
                </tr>
            </table>
        </div>
        
        <h3>تفاصيل الموردين</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>المورد</th>
                    <th>DA</th>
                    <th>DP</th>
                    <th>BC</th>
                    <th>BE</th>
                    <th>الإجمالي</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($suppliers as $supplier) {
        $html .= '
                <tr>
                    <td>' . htmlspecialchars($supplier['nom_fournisseur'] ?? 'غير محدد') . '</td>
                    <td>' . $supplier['da_count'] . '</td>
                    <td>' . $supplier['dp_count'] . '</td>
                    <td>' . $supplier['bc_count'] . '</td>
                    <td>' . $supplier['be_count'] . '</td>
                    <td>' . number_format($supplier['total_amount'], 2, ',', ' ') . ' DH</td>
                </tr>';
    }
    
    $html .= '
            </tbody>
        </table>
    </body>
    </html>';
    
    // Generate PDF using mPDF or similar library
    // For now, output as HTML (you can integrate mPDF later)
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="archive_' . $month . '.pdf"');
    
    // Simple HTML to PDF conversion (you should use a proper PDF library)
    echo $html;
    exit;
}
?>

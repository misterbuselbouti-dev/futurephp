<?php
// FUTURE AUTOMOTIVE - Sales Reports API
// Returns sales reports data in JSON format

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Include database configuration
require_once '../../config.php';

try {
    // Connect to database
    $database = new Database();
    $db = $database->connect();
    
    // Get report parameters
    $period = $_GET['period'] ?? 'month'; // day, week, month, year
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date = $_GET['end_date'] ?? date('Y-m-d');
    
    $reports = [];
    
    // Sales by period
    if ($period === 'day') {
        $query = "SELECT DATE(created_at) as period, COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as revenue
                 FROM invoices 
                 WHERE created_at BETWEEN ? AND ? AND status = 'paid'
                 GROUP BY DATE(created_at)
                 ORDER BY period";
    } elseif ($period === 'week') {
        $query = "SELECT YEARWEEK(created_at) as period, COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as revenue
                 FROM invoices 
                 WHERE created_at BETWEEN ? AND ? AND status = 'paid'
                 GROUP BY YEARWEEK(created_at)
                 ORDER BY period";
    } elseif ($period === 'month') {
        $query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as period, COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as revenue
                 FROM invoices 
                 WHERE created_at BETWEEN ? AND ? AND status = 'paid'
                 GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                 ORDER BY period";
    } else { // year
        $query = "SELECT YEAR(created_at) as period, COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as revenue
                 FROM invoices 
                 WHERE created_at BETWEEN ? AND ? AND status = 'paid'
                 GROUP BY YEAR(created_at)
                 ORDER BY period";
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute([$start_date, $end_date]);
    $reports['sales_by_period'] = $stmt->fetchAll();
    
    // Top services
    $stmt = $db->prepare("
        SELECT wo.problem_description as service, COUNT(*) as count, 
               AVG(inv.total_amount) as avg_revenue
        FROM work_orders wo
        LEFT JOIN invoices inv ON wo.id = inv.work_order_id
        WHERE wo.created_at BETWEEN ? AND ? AND inv.status = 'paid'
        GROUP BY wo.problem_description
        ORDER BY count DESC
        LIMIT 10
    ");
    $stmt->execute([$start_date, $end_date]);
    $reports['top_services'] = $stmt->fetchAll();
    
    // Customer summary
    $stmt = $db->prepare("
        SELECT c.name, COUNT(inv.id) as orders_count, COALESCE(SUM(inv.total_amount), 0) as total_spent
        FROM customers c
        LEFT JOIN invoices inv ON c.id = inv.customer_id
        WHERE inv.created_at BETWEEN ? AND ? AND inv.status = 'paid'
        GROUP BY c.id, c.name
        ORDER BY total_spent DESC
        LIMIT 10
    ");
    $stmt->execute([$start_date, $end_date]);
    $reports['top_customers'] = $stmt->fetchAll();
    
    // Revenue summary
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_orders,
            COALESCE(SUM(total_amount), 0) as total_revenue,
            AVG(total_amount) as avg_order_value
        FROM invoices 
        WHERE created_at BETWEEN ? AND ? AND status = 'paid'
    ");
    $stmt->execute([$start_date, $end_date]);
    $reports['summary'] = $stmt->fetch();
    
    // Payment status breakdown
    $stmt = $db->prepare("
        SELECT status, COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total
        FROM invoices 
        WHERE created_at BETWEEN ? AND ?
        GROUP BY status
    ");
    $stmt->execute([$start_date, $end_date]);
    $reports['payment_status'] = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $reports,
        'period' => $period,
        'start_date' => $start_date,
        'end_date' => $end_date
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

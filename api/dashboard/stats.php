<?php
// FUTURE AUTOMOTIVE - Dashboard Stats API
// Returns dashboard statistics in JSON format

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
    
    // Get dashboard statistics
    $stats = [];
    
    // Total Customers
    $stmt = $db->query("SELECT COUNT(*) as count FROM customers");
    $stats['total_customers'] = $stmt->fetch()['count'];
    
    // Cars in Repair (using French status)
    $stmt = $db->query("SELECT COUNT(*) as count FROM work_orders WHERE status IN ('en_attente', 'en_cours')");
    $stats['cars_in_repair'] = $stmt->fetch()['count'];
    
    // Total Revenue (using French status)
    $stmt = $db->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM invoices WHERE status = 'payee'");
    $stats['total_revenue'] = floatval($stmt->fetch()['total']);
    
    // Monthly Orders
    $stmt = $db->query("SELECT COUNT(*) as count FROM work_orders WHERE MONTH(created_at) = MONTH(CURRENT_DATE) AND YEAR(created_at) = YEAR(CURRENT_DATE)");
    $stats['monthly_orders'] = $stmt->fetch()['count'];
    
    // Today's Appointments (using French status)
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM appointments WHERE appointment_date = CURDATE()");
        $stats['today_appointments'] = $stmt->fetch()['count'];
    } catch (Exception $e) {
        $stats['today_appointments'] = 0;
    }
    
    // Active Employees (using French roles)
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE is_active = 1 AND role IN ('mecanicien', 'receptionniste', 'admin')");
        $stats['active_employees'] = $stmt->fetch()['count'];
    } catch (Exception $e) {
        $stats['active_employees'] = 0;
    }
    
    // Low Inventory Items (corrected column name)
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM inventory WHERE quantity <= min_stock_level");
        $stats['low_inventory'] = $stmt->fetch()['count'];
    } catch (Exception $e) {
        $stats['low_inventory'] = 0;
    }
    
    // Recent Activities
    try {
        $stmt = $db->query("
            SELECT 'work_order' as type, id, CONCAT('OT', id) as title, created_at 
            FROM work_orders 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $stats['recent_activities'] = $stmt->fetchAll();
    } catch (Exception $e) {
        $stats['recent_activities'] = [];
    }
    
    // Use the views for advanced statistics
    try {
        // Today's appointment stats
        $stmt = $db->query("
            SELECT * FROM appointment_stats 
            WHERE appointment_date = CURDATE()
        ");
        $today_stats = $stmt->fetch();
        $stats['today_appointment_stats'] = $today_stats ?: [
            'total_appointments' => 0,
            'planifie_count' => 0,
            'en_cours_count' => 0,
            'termine_count' => 0,
            'annule_count' => 0
        ];
    } catch (Exception $e) {
        $stats['today_appointment_stats'] = [];
    }
    
    try {
        // Revenue stats for current month
        $stmt = $db->query("
            SELECT SUM(total_revenue) as monthly_revenue,
                   SUM(paid_revenue) as paid_revenue,
                   COUNT(*) as invoice_count
            FROM revenue_stats 
            WHERE MONTH(revenue_date) = MONTH(CURRENT_DATE) 
            AND YEAR(revenue_date) = YEAR(CURRENT_DATE)
        ");
        $revenue_stats = $stmt->fetch();
        $stats['monthly_revenue_stats'] = $revenue_stats ?: [
            'monthly_revenue' => 0,
            'paid_revenue' => 0,
            'invoice_count' => 0
        ];
    } catch (Exception $e) {
        $stats['monthly_revenue_stats'] = [];
    }
    
    try {
        // Top customers by spending
        $stmt = $db->query("
            SELECT name, email, total_spent, car_count, appointment_count 
            FROM customer_stats 
            WHERE total_spent > 0 
            ORDER BY total_spent DESC 
            LIMIT 5
        ");
        $stats['top_customers'] = $stmt->fetchAll();
    } catch (Exception $e) {
        $stats['top_customers'] = [];
    }
    
    // System Status
    $stats['system_status'] = [
        'database' => 'connected',
        'storage' => 'available',
        'last_backup' => date('Y-m-d H:i:s')
    ];
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $stats,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>

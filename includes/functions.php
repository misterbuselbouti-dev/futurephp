<?php
// FUTURE AUTOMOTIVE - Helper Functions
// Common utility functions for the application

// Sanitize input
function sanitize_input($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

// Redirect function
function redirect($url) {
    header("Location: $url");
    exit;
}

// Require login
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        redirect('login.php');
    }
}

// Get logged in user
function get_logged_in_user() {
    return $_SESSION['user'] ?? null;
}

// Get status class for badges
function getStatusClass($status) {
    $statusClasses = [
        'pending' => 'warning',
        'in_progress' => 'info',
        'completed' => 'success',
        'draft' => 'secondary',
        'sent' => 'primary',
        'paid' => 'success',
        'overdue' => 'danger'
    ];
    
    return $statusClasses[$status] ?? 'secondary';
}

if (!function_exists('getStatutBadgeClass')) {
    function getStatutBadgeClass($statut) {
        $classes = [
            'Brouillon' => 'secondary',
            'En attente' => 'warning',
            'Validé' => 'success',
            'Rejeté' => 'danger',
            'Annulé' => 'dark',
            'Envoyé' => 'info',
            'Reçu' => 'primary',
            'Accepté' => 'success',
            'Refusé' => 'danger',
            'Commandé' => 'info',
            'Confirmé' => 'primary',
            'Livré partiellement' => 'warning',
            'Livré totalement' => 'success'
        ];

        return $classes[$statut] ?? 'secondary';
    }
}

// Format currency
function formatCurrency($amount, $currency = null) {
    // Always use Moroccan Dirham
    return number_format($amount, 2) . ' DH';
}

// Format date
function formatDate($date, $format = 'd/m/Y') {
    if (!$date) return '-';
    
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date($format, $timestamp);
}

// Format date time
function formatDateTime($datetime) {
    return formatDate($datetime, 'd/m/Y H:i');
}

// Get inventory status class
function getInventoryStatusClass($quantity, $minQuantity) {
    if ($quantity == 0) return 'danger';
    if ($quantity <= $minQuantity) return 'warning';
    return 'success';
}

// Get notification status color
function getNotificationColor($type) {
    $colors = [
        'appointment_reminder' => 'primary',
        'work_order_update' => 'info',
        'payment_reminder' => 'warning',
        'promotion' => 'success',
        'system' => 'secondary',
        'custom' => 'dark',
        'general' => 'info'
    ];
    return $colors[$type] ?? 'dark';
}

// Generate invoice number
function generateInvoiceNumber() {
    return 'INV-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

// Calculate work order total
function calculateWorkOrderTotal($workOrderId, $db) {
    // Get labor cost
    $stmt = $db->prepare("SELECT labor_cost FROM work_orders WHERE id = ?");
    $stmt->execute([$workOrderId]);
    $workOrder = $stmt->fetch();
    
    $total = $workOrder['labor_cost'] ?? 0;
    
    // Get parts cost
    $stmt = $db->prepare("SELECT SUM(total_price) as total FROM work_order_parts WHERE work_order_id = ?");
    $stmt->execute([$workOrderId]);
    $parts = $stmt->fetch();
    
    $total += $parts['total'] ?? 0;
    
    return $total;
}

// Update inventory stock
function updateInventoryStock($inventoryId, $quantityUsed, $db) {
    $stmt = $db->prepare("UPDATE inventory SET quantity = quantity - ? WHERE id = ? AND quantity >= ?");
    return $stmt->execute([$quantityUsed, $inventoryId, $quantityUsed]);
}

// Get customer cars count
function getCustomerCarsCount($customerId, $db) {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM cars WHERE customer_id = ?");
    $stmt->execute([$customerId]);
    $result = $stmt->fetch();
    return $result['count'] ?? 0;
}

// Get car work orders count
function getCarWorkOrdersCount($carId, $db) {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM work_orders WHERE car_id = ?");
    $stmt->execute([$carId]);
    $result = $stmt->fetch();
    return $result['count'] ?? 0;
}

// Search customers
function searchCustomers($search, $db) {
    $search = "%{$search}%";
    $stmt = $db->prepare("SELECT * FROM customers WHERE name LIKE ? OR phone LIKE ? OR email LIKE ? ORDER BY name");
    $stmt->execute([$search, $search, $search]);
    return $stmt->fetchAll();
}

// Search cars
function searchCars($search, $db) {
    $search = "%{$search}%";
    $stmt = $db->prepare("
        SELECT c.*, cu.name as customer_name 
        FROM cars c 
        JOIN customers cu ON c.customer_id = cu.id 
        WHERE c.plate_number LIKE ? OR c.make LIKE ? OR c.model LIKE ? OR cu.name LIKE ? 
        ORDER BY c.plate_number
    ");
    $stmt->execute([$search, $search, $search, $search]);
    return $stmt->fetchAll();
}

// Search work orders
function searchWorkOrders($search, $db) {
    $search = "%{$search}%";
    $stmt = $db->prepare("
        SELECT wo.*, c.name as customer_name, ca.plate_number 
        FROM work_orders wo 
        JOIN customers c ON wo.customer_id = c.id 
        JOIN cars ca ON wo.car_id = ca.id 
        WHERE wo.id LIKE ? OR c.name LIKE ? OR ca.plate_number LIKE ? OR wo.problem_description LIKE ? 
        ORDER BY wo.created_at DESC
    ");
    $stmt->execute([$search, $search, $search, $search]);
    return $stmt->fetchAll();
}

// Get dashboard statistics
function getDashboardStats($db) {
    $stats = [];
    
    // Total customers
    $stmt = $db->query("SELECT COUNT(*) as total FROM customers");
    $stats['total_customers'] = $stmt->fetch()['total'];
    
    // Cars in repair
    $stmt = $db->query("SELECT COUNT(*) as total FROM work_orders WHERE status IN ('pending', 'in_progress')");
    $stats['cars_in_repair'] = $stmt->fetch()['total'];
    
    // Total revenue
    $stmt = $db->query("SELECT SUM(total_amount) as total FROM invoices WHERE status = 'paid'");
    $stats['total_revenue'] = $stmt->fetch()['total'] ?? 0;
    
    // Monthly orders
    $stmt = $db->query("SELECT COUNT(*) as total FROM work_orders WHERE MONTH(order_date) = MONTH(CURRENT_DATE)");
    $stats['monthly_orders'] = $stmt->fetch()['total'];
    
    // Low inventory items
    $stmt = $db->query("SELECT COUNT(*) as total FROM inventory WHERE quantity <= min_quantity");
    $stats['low_inventory'] = $stmt->fetch()['total'];
    
    return $stats;
}

// Get recent activities
function getRecentActivities($db, $limit = 10) {
    $activities = [];
    
    // Recent work orders
    $stmt = $db->query("
        SELECT 'work_order' as type, wo.id, wo.created_at, 
               CONCAT('Work Order #', wo.id, ' for ', c.name) as description
        FROM work_orders wo 
        JOIN customers c ON wo.customer_id = c.id 
        ORDER BY wo.created_at DESC 
        LIMIT $limit
    ");
    $activities = array_merge($activities, $stmt->fetchAll());
    
    // Recent invoices
    $stmt = $db->query("
        SELECT 'invoice' as type, i.id, i.created_at, 
               CONCAT('Invoice ', i.invoice_number, ' for ', c.name) as description
        FROM invoices i 
        JOIN work_orders wo ON i.work_order_id = wo.id 
        JOIN customers c ON wo.customer_id = c.id 
        ORDER BY i.created_at DESC 
        LIMIT $limit
    ");
    $activities = array_merge($activities, $stmt->fetchAll());
    
    // Sort by date
    usort($activities, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    return array_slice($activities, 0, $limit);
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Validate phone number
function validatePhone($phone) {
    // Simple phone validation - can be enhanced based on requirements
    return preg_match('/^[\d\s\-\+\(\)]+$/', $phone);
}

// Validate license plate
function validateLicensePlate($plate) {
    // Basic validation - can be customized for different countries
    return preg_match('/^[A-Z0-9\s\-]{2,10}$/i', $plate);
}

// Generate random password
function generateRandomPassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    return substr(str_shuffle($chars), 0, $length);
}

// Log activity
function logActivity($action, $description, $userId = null) {
    // This would typically log to a database table
    // For now, we can use error_log for basic logging
    $logMessage = sprintf(
        "[%s] %s: %s (User: %s)",
        date('Y-m-d H:i:s'),
        $action,
        $description,
        $userId ?? 'System'
    );
    error_log($logMessage);
}

// Get user permissions
function getUserPermissions($userId, $db) {
    $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    $permissions = [
        'admin' => ['read', 'write', 'delete', 'manage_users'],
        'mechanic' => ['read', 'write'],
        'receptionist' => ['read', 'write']
    ];
    
    return $permissions[$user['role']] ?? ['read'];
}

// Check user permission
function checkPermission($permission, $userId = null) {
    if (!$userId) {
        $userId = $_SESSION['user_id'] ?? null;
    }
    
    if (!$userId) {
        return false;
    }
    
    $userPermissions = getUserPermissions($userId, $GLOBALS['db'] ?? null);
    return in_array($permission, $userPermissions);
}

// Export data to CSV
function exportToCSV($data, $filename, $headers = []) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Add headers if provided
    if (!empty($headers)) {
        fputcsv($output, $headers);
    }
    
    // Add data rows
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

// Create breadcrumb navigation
function createBreadcrumb($items) {
    $breadcrumb = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';
    
    foreach ($items as $index => $item) {
        $active = $index === count($items) - 1;
        $class = $active ? 'breadcrumb-item active' : 'breadcrumb-item';
        
        if ($active) {
            $breadcrumb .= '<li class="' . $class . '">' . $item['title'] . '</li>';
        } else {
            $breadcrumb .= '<li class="' . $class . '"><a href="' . $item['url'] . '">' . $item['title'] . '</a></li>';
        }
    }
    
    $breadcrumb .= '</ol></nav>';
    return $breadcrumb;
}

// Get month name
function getMonthName($month) {
    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];
    return $months[$month] ?? '';
}

// Calculate days between dates
function daysBetween($date1, $date2) {
    $datetime1 = new DateTime($date1);
    $datetime2 = new DateTime($date2);
    $interval = $datetime1->diff($datetime2);
    return $interval->days;
}

// Get file size in human readable format
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

// Sanitize filename
function sanitizeFilename($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9\-_\.]/', '', $filename);
    return $filename;
}

// Create directory if not exists
function ensureDirectoryExists($path) {
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
        return true;
    }
    return false;
}

// Get MIME type
function getMimeType($filename) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filename);
    finfo_close($finfo);
    return $mimeType;
}

// Generate unique filename
function generateUniqueFilename($originalName, $directory) {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $basename = pathinfo($originalName, PATHINFO_FILENAME);
    $counter = 1;
    
    $filename = $originalName;
    while (file_exists($directory . '/' . $filename)) {
        $filename = $basename . '_' . $counter . '.' . $extension;
        $counter++;
    }
    
    return $filename;
}
?>

<?php
// FUTURE AUTOMOTIVE - Archive API Processor
// معالج عمليات الأرشيف

require_once 'config.php';
require_once 'config_achat_hostinger.php';

header('Content-Type: application/json');

// Check authentication
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

try {
    $database_achat = new DatabaseAchat();
    $pdo = $database_achat->connect();
    
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'run_archive':
            $result = runAutoArchive($pdo);
            break;
            
        case 'archive_month':
            $month = $input['month'] ?? '';
            if (empty($month) || !preg_match('/^\d{4}-\d{2}$/', $month)) {
                throw new Exception('Month invalide');
            }
            $result = archiveSpecificMonth($pdo, $month);
            break;
            
        default:
            throw new Exception('Action non reconnue');
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function runAutoArchive($pdo) {
    // Get archive settings
    $stmt = $pdo->query("SELECT setting_value FROM archive_settings WHERE setting_key = 'auto_archive_months'");
    $months = (int)($stmt->fetch()['setting_value'] ?? 6);
    
    // Calculate target month
    $target_date = new DateTime();
    $target_date->sub(new DateInterval("P{$months}M"));
    $target_month = $target_date->format('Y-m');
    
    // Run archive procedure
    $stmt = $pdo->prepare("CALL ArchiveMonthlyTransactions(?)");
    $stmt->execute([$target_month]);
    $result = $stmt->fetch();
    
    // Update last archive date
    $updateStmt = $pdo->prepare("UPDATE archive_settings SET setting_value = ? WHERE setting_key = 'last_archive_date'");
    $updateStmt->execute([date('Y-m-d H:i:s')]);
    
    return [
        'success' => true,
        'message' => "تم أرشفة {$result['archived_count']} معاملة للشهر {$target_month}",
        'archived_count' => $result['archived_count'],
        'month' => $target_month
    ];
}

function archiveSpecificMonth($pdo, $month) {
    // Check if month is already archived
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM monthly_transactions_summary WHERE year_month = ?");
    $stmt->execute([$month]);
    $existing = $stmt->fetch();
    
    if ($existing['count'] > 0) {
        // Update existing archive
        $stmt = $pdo->prepare("DELETE FROM monthly_transactions_summary WHERE year_month = ?");
        $stmt->execute([$month]);
    }
    
    // Run archive procedure
    $stmt = $pdo->prepare("CALL ArchiveMonthlyTransactions(?)");
    $stmt->execute([$month]);
    $result = $stmt->fetch();
    
    return [
        'success' => true,
        'message' => "تم أرشفة {$result['archived_count']} معاملة للشهر {$month}",
        'archived_count' => $result['archived_count'],
        'month' => $month
    ];
}
?>

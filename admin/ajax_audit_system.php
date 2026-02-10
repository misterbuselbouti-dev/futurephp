<?php
// Audit and Logging System for Breakdown Management
require_once '../config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check authentication
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

$database = new Database();
$pdo = $database->connect();

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_audit_logs':
            $breakdownId = (int)$_POST['breakdown_id'];
            $limit = (int)($_POST['limit'] ?? 50);
            $offset = (int)($_POST['offset'] ?? 0);
            $actionFilter = $_POST['action_filter'] ?? '';
            $dateFrom = $_POST['date_from'] ?? '';
            $dateTo = $_POST['date_to'] ?? '';
            
            // Build query
            $sql = "
                SELECT 
                    bal.*,
                    u.full_name as performed_by_name,
                    u.role as performed_by_role,
                    DATE_FORMAT(bal.performed_at, '%d/%m/%Y %H:%i:%s') as formatted_time,
                    CASE 
                        WHEN bal.action_type = 'assignment' THEN 'Assignation'
                        WHEN bal.action_type = 'work_started' THEN 'Début travail'
                        WHEN bal.action_type = 'work_ended' THEN 'Fin travail'
                        WHEN bal.action_type = 'session_started' THEN 'Session démarrée'
                        WHEN bal.action_type = 'session_paused' THEN 'Session mise en pause'
                        WHEN bal.action_type = 'session_resumed' THEN 'Session reprise'
                        WHEN bal.action_type = 'session_ended' THEN 'Session terminée'
                        WHEN bal.action_type = 'item_added' THEN 'Pièce ajoutée'
                        WHEN bal.action_type = 'item_removed' THEN 'Pièce retirée'
                        WHEN bal.action_type = 'status_changed' THEN 'Statut modifié'
                        ELSE bal.action_type
                    END as action_display
                FROM breakdown_audit_log bal
                JOIN users u ON bal.performed_by_user_id = u.id
                WHERE bal.breakdown_id = ?
            ";
            
            $params = [$breakdownId];
            
            if (!empty($actionFilter)) {
                $sql .= " AND bal.action_type = ?";
                $params[] = $actionFilter;
            }
            
            if (!empty($dateFrom)) {
                $sql .= " AND DATE(bal.performed_at) >= ?";
                $params[] = $dateFrom;
            }
            
            if (!empty($dateTo)) {
                $sql .= " AND DATE(bal.performed_at) <= ?";
                $params[] = $dateTo;
            }
            
            $sql .= " ORDER BY bal.performed_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $logs = $stmt->fetchAll();
            
            // Get total count for pagination
            $countSql = "
                SELECT COUNT(*) as total
                FROM breakdown_audit_log bal
                WHERE bal.breakdown_id = ?
            ";
            $countParams = [$breakdownId];
            
            if (!empty($actionFilter)) {
                $countSql .= " AND bal.action_type = ?";
                $countParams[] = $actionFilter;
            }
            
            if (!empty($dateFrom)) {
                $countSql .= " AND DATE(bal.performed_at) >= ?";
                $countParams[] = $dateFrom;
            }
            
            if (!empty($dateTo)) {
                $countSql .= " AND DATE(bal.performed_at) <= ?";
                $countParams[] = $dateTo;
            }
            
            $stmt = $pdo->prepare($countSql);
            $stmt->execute($countParams);
            $total = $stmt->fetch()['total'];
            
            echo json_encode([
                'success' => true, 
                'logs' => $logs,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset
            ]);
            break;
            
        case 'get_breakdown_history':
            $breakdownId = (int)$_POST['breakdown_id'];
            
            $stmt = $pdo->prepare("
                SELECT 
                    br.*,
                    b.bus_number,
                    d.nom as driver_nom,
                    d.prenom as driver_prenom,
                    u.full_name as assigned_technician,
                    ba.assigned_at,
                    ba.started_at,
                    ba.ended_at,
                    ba.actual_hours,
                    ba.total_cost,
                    COUNT(bwi.id) as items_count
                FROM breakdown_reports br
                LEFT JOIN buses b ON br.bus_id = b.id
                LEFT JOIN drivers d ON br.driver_id = d.id
                LEFT JOIN breakdown_assignments ba ON br.id = ba.report_id
                LEFT JOIN users u ON ba.assigned_to_user_id = u.id
                LEFT JOIN breakdown_work_items bwi ON ba.id = bwi.assignment_id
                WHERE br.id = ?
                GROUP BY br.id
            ");
            $stmt->execute([$breakdownId]);
            $history = $stmt->fetch();
            
            echo json_encode(['success' => true, 'history' => $history]);
            break;
            
        case 'export_audit_logs':
            $breakdownId = (int)$_POST['breakdown_id'];
            $format = $_POST['format'] ?? 'csv';
            
            if ($format === 'csv') {
                // Export to CSV
                $stmt = $pdo->prepare("
                    SELECT 
                        bal.performed_at,
                        bal.action_type,
                        bal.field_name,
                        bal.old_value,
                        bal.new_value,
                        u.full_name as performed_by_name,
                        u.role as performed_by_role,
                        bal.ip_address
                    FROM breakdown_audit_log bal
                    JOIN users u ON bal.performed_by_user_id = u.id
                    WHERE bal.breakdown_id = ?
                    ORDER BY bal.performed_at
                ");
                $stmt->execute([$breakdownId]);
                $logs = $stmt->fetchAll();
                
                $csv = "Date,Action,Champ,Ancienne valeur,Nouvelle valeur,Utilisateur,Rôle,Adresse IP\n";
                foreach ($logs as $log) {
                    $csv .= implode(',', [
                        $log['performed_at'],
                        $log['action_type'],
                        $log['field_name'] ?? '',
                        str_replace(["\n", "\r", ","], [" ", " ", ";"], $log['old_value'] ?? ''),
                        str_replace(["\n", "\r", ","], [" ", " ", ";"], $log['new_value'] ?? ''),
                        $log['performed_by_name'],
                        $log['performed_by_role'],
                        $log['ip_address'] ?? ''
                    ]) . "\n";
                }
                
                echo json_encode([
                    'success' => true,
                    'csv' => $csv,
                    'filename' => "breakdown_{$breakdownId}_audit_logs.csv"
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Format non supporté']);
            }
            break;
            
        case 'get_system_statistics':
            $dateFrom = $_POST['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
            $dateTo = $_POST['date_to'] ?? date('Y-m-d');
            
            $stats = [];
            
            // Total breakdowns
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total FROM breakdown_reports 
                WHERE DATE(created_at) BETWEEN ? AND ?
            ");
            $stmt->execute([$dateFrom, $dateTo]);
            $stats['total_breakdowns'] = $stmt->fetch()['total'];
            
            // Breakdowns by status
            $stmt = $pdo->prepare("
                SELECT status, COUNT(*) as count 
                FROM breakdown_reports 
                WHERE DATE(created_at) BETWEEN ? AND ?
                GROUP BY status
            ");
            $stmt->execute([$dateFrom, $dateTo]);
            $stats['by_status'] = $stmt->fetchAll();
            
            // Breakdowns by urgency
            $stmt = $pdo->prepare("
                SELECT urgency, COUNT(*) as count 
                FROM breakdown_reports 
                WHERE DATE(created_at) BETWEEN ? AND ? AND urgency IS NOT NULL
                GROUP BY urgency
            ");
            $stmt->execute([$dateFrom, $dateTo]);
            $stats['by_urgency'] = $stmt->fetchAll();
            
            // Most active technicians
            $stmt = $pdo->prepare("
                SELECT 
                    u.full_name,
                    COUNT(ba.id) as assignments,
                    SUM(ba.actual_hours) as total_hours,
                    AVG(ba.actual_hours) as avg_hours
                FROM users u
                JOIN breakdown_assignments ba ON u.id = ba.assigned_to_user_id
                JOIN breakdown_reports br ON ba.report_id = br.id
                WHERE DATE(br.created_at) BETWEEN ? AND ?
                GROUP BY u.id, u.full_name
                ORDER BY assignments DESC
                LIMIT 10
            ");
            $stmt->execute([$dateFrom, $dateTo]);
            $stats['top_technicians'] = $stmt->fetchAll();
            
            // Most used parts
            $stmt = $pdo->prepare("
                SELECT 
                    ac.designation,
                    SUM(bwi.quantity_used) as total_quantity,
                    SUM(bwi.total_cost) as total_cost,
                    COUNT(bwi.id) as usage_count
                FROM breakdown_work_items bwi
                JOIN articles_catalogue ac ON bwi.article_id = ac.id
                JOIN breakdown_reports br ON bwi.breakdown_id = br.id
                WHERE DATE(br.created_at) BETWEEN ? AND ?
                GROUP BY ac.id, ac.designation
                ORDER BY total_quantity DESC
                LIMIT 10
            ");
            $stmt->execute([$dateFrom, $dateTo]);
            $stats['most_used_parts'] = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'statistics' => $stats]);
            break;
            
        case 'log_action':
            $breakdownId = (int)$_POST['breakdown_id'];
            $assignmentId = (int)$_POST['assignment_id'] ?? null);
            $actionType = $_POST['action_type'];
            $fieldName = $_POST['field_name'] ?? '';
            $oldValue = $_POST['old_value'] ?? '';
            $newValue = $_POST['new_value'] ?? '';
            
            $stmt = $pdo->prepare("
                INSERT INTO breakdown_audit_log 
                (breakdown_id, assignment_id, action_type, field_name, old_value, new_value, performed_by_user_id, ip_address)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $breakdownId,
                $assignmentId,
                $actionType,
                $fieldName,
                $oldValue,
                $newValue,
                $_SESSION['user_id'],
                $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Action logged successfully']);
            break;
            
        case 'get_field_history':
            $breakdownId = (int)$_POST['breakdown_id'];
            $fieldName = $_POST['field_name'];
            
            $stmt = $pdo->prepare("
                SELECT 
                    bal.old_value,
                    bal.new_value,
                    bal.performed_at,
                    u.full_name as performed_by_name,
                    CASE 
                        WHEN bal.action_type = 'assignment' THEN 'Assignation'
                        WHEN bal.action_type = 'work_started' THEN 'Début travail'
                        WHEN bal.action_type = 'work_ended' THEN 'Fin travail'
                        ELSE bal.action_type
                    END as action_display
                FROM breakdown_audit_log bal
                JOIN users u ON bal.performed_by_user_id = u.id
                WHERE bal.breakdown_id = ? AND bal.field_name = ?
                ORDER BY bal.performed_at DESC
            ");
            $stmt->execute([$breakdownId, $fieldName]);
            $history = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'history' => $history]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

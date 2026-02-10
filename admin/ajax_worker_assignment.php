<?php
// AJAX Handler for Worker Assignment System
require_once '../config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check authentication
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

$user = get_logged_in_user();
$role = $user['role'] ?? '';
if (!in_array($role, ['admin', 'maintenance_manager'], true)) {
    echo json_encode(['success' => false, 'message' => 'Accès refusé']);
    exit;
}

$database = new Database();
$pdo = $database->connect();

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'assign_technician':
            $breakdownId = (int)$_POST['breakdown_id'];
            $technicianId = (int)$_POST['technician_id'];
            $notes = trim($_POST['notes'] ?? '');
            
            $pdo->beginTransaction();
            
            // Check if assignment already exists
            $stmt = $pdo->prepare("SELECT id FROM breakdown_assignments WHERE report_id = ? AND assigned_to_user_id = ?");
            $stmt->execute([$breakdownId, $technicianId]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update existing assignment
                $stmt = $pdo->prepare("UPDATE breakdown_assignments SET assigned_by_user_id = ?, assigned_at = NOW(), notes = ? WHERE id = ?");
                $stmt->execute([$user['id'], $notes, $existing['id']]);
                $assignmentId = $existing['id'];
            } else {
                // Create new assignment
                $stmt = $pdo->prepare("INSERT INTO breakdown_assignments (report_id, assigned_to_user_id, assigned_by_user_id, assigned_at, notes) VALUES (?, ?, ?, NOW(), ?)");
                $stmt->execute([$breakdownId, $technicianId, $user['id'], $notes]);
                $assignmentId = $pdo->lastInsertId();
            }
            
            // Update breakdown status
            $stmt = $pdo->prepare("UPDATE breakdown_reports SET status = 'assigne' WHERE id = ?");
            $stmt->execute([$breakdownId]);
            
            // Log the action
            $stmt = $pdo->prepare("INSERT INTO breakdown_audit_log (breakdown_id, assignment_id, action_type, new_value, performed_by_user_id) VALUES (?, ?, 'assignment', ?, ?)");
            $stmt->execute([$breakdownId, $assignmentId, json_encode(['technician_id' => $technicianId, 'notes' => $notes]), $user['id']]);
            
            // Create notification
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, entity_type, entity_id, is_read) VALUES (?, 'breakdown_assigned', 'breakdown_report', ?, 0)");
            $stmt->execute([$technicianId, $breakdownId]);
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Technicien assigné avec succès',
                'assignment_id' => $assignmentId
            ]);
            break;
            
        case 'start_work':
            $breakdownId = (int)$_POST['breakdown_id'];
            $assignmentId = (int)$_POST['assignment_id'];
            $notes = trim($_POST['notes'] ?? '');
            
            $pdo->beginTransaction();
            
            // Log start time
            $stmt = $pdo->prepare("INSERT INTO breakdown_time_logs (breakdown_id, assignment_id, user_id, action_type, notes, created_by_user_id) VALUES (?, ?, ?, 'start', ?, ?)");
            $stmt->execute([$breakdownId, $assignmentId, $user['id'], $notes, $user['id']]);
            
            // Update assignment
            $stmt = $pdo->prepare("UPDATE breakdown_assignments SET started_at = NOW(), work_status = 'in_progress' WHERE id = ?");
            $stmt->execute([$assignmentId]);
            
            // Update breakdown status
            $stmt = $pdo->prepare("UPDATE breakdown_reports SET status = 'en_cours' WHERE id = ?");
            $stmt->execute([$breakdownId]);
            
            // Log the action
            $stmt = $pdo->prepare("INSERT INTO breakdown_audit_log (breakdown_id, assignment_id, action_type, new_value, performed_by_user_id) VALUES (?, ?, 'work_started', ?, ?)");
            $stmt->execute([$breakdownId, $assignmentId, json_encode(['notes' => $notes]), $user['id']]);
            
            $pdo->commit();
            
            echo json_encode(['success' => true, 'message' => 'Travail démarré']);
            break;
            
        case 'end_work':
            $breakdownId = (int)$_POST['breakdown_id'];
            $assignmentId = (int)$_POST['assignment_id'];
            $notes = trim($_POST['notes'] ?? '');
            
            $pdo->beginTransaction();
            
            // Log end time
            $stmt = $pdo->prepare("INSERT INTO breakdown_time_logs (breakdown_id, assignment_id, user_id, action_type, notes, created_by_user_id) VALUES (?, ?, ?, 'end', ?, ?)");
            $stmt->execute([$breakdownId, $assignmentId, $user['id'], $notes, $user['id']]);
            
            // Update assignment
            $stmt = $pdo->prepare("UPDATE breakdown_assignments SET ended_at = NOW(), work_status = 'completed' WHERE id = ?");
            $stmt->execute([$assignmentId]);
            
            // Update breakdown status
            $stmt = $pdo->prepare("UPDATE breakdown_reports SET status = 'termine' WHERE id = ?");
            $stmt->execute([$breakdownId]);
            
            // Calculate total duration
            $stmt = $pdo->prepare("CALL CalculateWorkDuration(?, ?)");
            $stmt->execute([$breakdownId, $assignmentId]);
            
            // Log the action
            $stmt = $pdo->prepare("INSERT INTO breakdown_audit_log (breakdown_id, assignment_id, action_type, new_value, performed_by_user_id) VALUES (?, ?, 'work_ended', ?, ?)");
            $stmt->execute([$breakdownId, $assignmentId, json_encode(['notes' => $notes]), $user['id']]);
            
            $pdo->commit();
            
            echo json_encode(['success' => true, 'message' => 'Travail terminé']);
            break;
            
        case 'get_available_technicians':
            $breakdownId = (int)$_POST['breakdown_id'];
            
            // Get technicians with their current workload
            $stmt = $pdo->prepare("
                SELECT 
                    u.id, u.full_name, u.role,
                    COUNT(ba.id) as current_workload,
                    COUNT(CASE WHEN br.status IN ('nouveau', 'assigne', 'en_cours') THEN 1 END) as active_assignments
                FROM users u
                LEFT JOIN breakdown_assignments ba ON u.id = ba.assigned_to_user_id
                LEFT JOIN breakdown_reports br ON ba.report_id = br.id
                WHERE u.role IN ('technician', 'agent') AND u.is_active = 1
                GROUP BY u.id, u.full_name, u.role
                ORDER BY u.full_name
            ");
            $stmt->execute();
            $technicians = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'technicians' => $technicians]);
            break;
            
        case 'get_assignment_details':
            $breakdownId = (int)$_POST['breakdown_id'];
            
            $stmt = $pdo->prepare("
                SELECT 
                    ba.*,
                    u.full_name as technician_name,
                    u.role as technician_role,
                    COUNT(bwi.id) as items_used_count,
                    SUM(bwi.total_cost) as total_material_cost
                FROM breakdown_assignments ba
                LEFT JOIN users u ON ba.assigned_to_user_id = u.id
                LEFT JOIN breakdown_work_items bwi ON ba.id = bwi.assignment_id
                WHERE ba.report_id = ?
                GROUP BY ba.id
            ");
            $stmt->execute([$breakdownId]);
            $assignments = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'assignments' => $assignments]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

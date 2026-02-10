<?php
// Advanced Time Tracking System for Breakdown Management
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
        case 'start_session':
            $breakdownId = (int)$_POST['breakdown_id'];
            $assignmentId = (int)$_POST['assignment_id'];
            $notes = trim($_POST['notes'] ?? '');
            
            $pdo->beginTransaction();
            
            // Check if there's already an active session
            $stmt = $pdo->prepare("
                SELECT id FROM breakdown_time_logs 
                WHERE breakdown_id = ? AND assignment_id = ? AND action_type = 'start'
                AND NOT EXISTS (
                    SELECT 1 FROM breakdown_time_logs tl2 
                    WHERE tl2.breakdown_id = ? AND tl2.assignment_id = ? 
                    AND tl2.action_time > (
                        SELECT MAX(action_time) FROM breakdown_time_logs 
                        WHERE breakdown_id = ? AND assignment_id = ? AND action_type = 'start'
                    )
                )
            ");
            $stmt->execute([$breakdownId, $assignmentId, $breakdownId, $assignmentId, $breakdownId, $assignmentId]);
            $activeSession = $stmt->fetch();
            
            if ($activeSession) {
                throw new Exception('Une session de travail est déjà active');
            }
            
            // Start new session
            $stmt = $pdo->prepare("
                INSERT INTO breakdown_time_logs 
                (breakdown_id, assignment_id, user_id, action_type, notes, created_by_user_id) 
                VALUES (?, ?, ?, 'start', ?, ?)
            ");
            $stmt->execute([$breakdownId, $assignmentId, $_SESSION['user_id'], $notes, $_SESSION['user_id']]);
            
            // Update assignment status
            $stmt = $pdo->prepare("UPDATE breakdown_assignments SET started_at = NOW(), work_status = 'in_progress' WHERE id = ?");
            $stmt->execute([$assignmentId]);
            
            // Update breakdown status
            $stmt = $pdo->prepare("UPDATE breakdown_reports SET status = 'en_cours' WHERE id = ?");
            $stmt->execute([$breakdownId]);
            
            // Log action
            $stmt = $pdo->prepare("
                INSERT INTO breakdown_audit_log 
                (breakdown_id, assignment_id, action_type, new_value, performed_by_user_id) 
                VALUES (?, ?, 'session_started', ?, ?)
            ");
            $stmt->execute([$breakdownId, $assignmentId, json_encode(['notes' => $notes]), $_SESSION['user_id']]);
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Session de travail démarrée',
                'session_id' => $pdo->lastInsertId()
            ]);
            break;
            
        case 'pause_session':
            $breakdownId = (int)$_POST['breakdown_id'];
            $assignmentId = (int)$_POST['assignment_id'];
            $notes = trim($_POST['notes'] ?? '');
            
            $pdo->beginTransaction();
            
            // Check if there's an active session
            $stmt = $pdo->prepare("
                SELECT id, action_time FROM breakdown_time_logs 
                WHERE breakdown_id = ? AND assignment_id = ? AND action_type = 'start'
                ORDER BY action_time DESC LIMIT 1
            ");
            $stmt->execute([$breakdownId, $assignmentId]);
            $lastStart = $stmt->fetch();
            
            if (!$lastStart) {
                throw new Exception('Aucune session de travail active trouvée');
            }
            
            // Pause the session
            $stmt = $pdo->prepare("
                INSERT INTO breakdown_time_logs 
                (breakdown_id, assignment_id, user_id, action_type, notes, created_by_user_id) 
                VALUES (?, ?, ?, 'pause', ?, ?)
            ");
            $stmt->execute([$breakdownId, $assignmentId, $_SESSION['user_id'], $notes, $_SESSION['user_id']]);
            
            // Update assignment status
            $stmt = $pdo->prepare("UPDATE breakdown_assignments SET work_status = 'paused' WHERE id = ?");
            $stmt->execute([$assignmentId]);
            
            // Calculate partial duration
            $stmt = $pdo->prepare("
                SELECT TIMESTAMPDIFF(MINUTE, ?, NOW()) as partial_duration
            ");
            $stmt->execute([$lastStart['action_time']]);
            $partialDuration = $stmt->fetch()['partial_duration'];
            
            // Log action
            $stmt = $pdo->prepare("
                INSERT INTO breakdown_audit_log 
                (breakdown_id, assignment_id, action_type, new_value, performed_by_user_id) 
                VALUES (?, ?, 'session_paused', ?, ?)
            ");
            $stmt->execute([$breakdownId, $assignmentId, json_encode(['partial_duration_minutes' => $partialDuration]), $_SESSION['user_id']]);
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Session mise en pause',
                'partial_duration' => $partialDuration
            ]);
            break;
            
        case 'resume_session':
            $breakdownId = (int)$_POST['breakdown_id'];
            $assignmentId = (int)$_POST['assignment_id'];
            $notes = trim($_POST['notes'] ?? '');
            
            $pdo->beginTransaction();
            
            // Check if there's a paused session
            $stmt = $pdo->prepare("
                SELECT id, action_time FROM breakdown_time_logs 
                WHERE breakdown_id = ? AND assignment_id = ? AND action_type = 'pause'
                ORDER BY action_time DESC LIMIT 1
            ");
            $stmt->execute([$breakdownId, $assignmentId]);
            $lastPause = $stmt->fetch();
            
            if (!$lastPause) {
                throw new Exception('Aucune session en pause trouvée');
            }
            
            // Resume the session
            $stmt = $pdo->prepare("
                INSERT INTO breakdown_time_logs 
                (breakdown_id, assignment_id, user_id, action_type, notes, created_by_user_id) 
                VALUES (?, ?, ?, 'resume', ?, ?)
            ");
            $stmt->execute([$breakdownId, $assignmentId, $_SESSION['user_id'], $notes, $_SESSION['user_id']]);
            
            // Update assignment status
            $stmt = $pdo->prepare("UPDATE breakdown_assignments SET work_status = 'in_progress' WHERE id = ?");
            $stmt->execute([$assignmentId]);
            
            // Log action
            $stmt = $pdo->prepare("
                INSERT INTO breakdown_audit_log 
                (breakdown_id, assignment_id, action_type, new_value, performed_by_user_id) 
                VALUES (?, ?, 'session_resumed', ?, ?)
            ");
            $stmt->execute([$breakdownId, $assignmentId, json_encode(['notes' => $notes]), $_SESSION['user_id']]);
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Session reprise'
            ]);
            break;
            
        case 'end_session':
            $breakdownId = (int)$_POST['breakdown_id'];
            $assignmentId = (int)$_POST['assignment_id'];
            $notes = trim($_POST['notes'] ?? '');
            
            $pdo->beginTransaction();
            
            // Get the last start time
            $stmt = $pdo->prepare("
                SELECT action_time FROM breakdown_time_logs 
                WHERE breakdown_id = ? AND assignment_id = ? AND action_type IN ('start', 'resume')
                ORDER BY action_time DESC LIMIT 1
            ");
            $stmt->execute([$breakdownId, $assignmentId]);
            $lastStart = $stmt->fetch();
            
            if (!$lastStart) {
                throw new Exception('Aucune session de travail active trouvée');
            }
            
            // End the session
            $stmt = $pdo->prepare("
                INSERT INTO breakdown_time_logs 
                (breakdown_id, assignment_id, user_id, action_type, notes, created_by_user_id) 
                VALUES (?, ?, ?, 'end', ?, ?)
            ");
            $stmt->execute([$breakdownId, $assignmentId, $_SESSION['user_id'], $notes, $_SESSION['user_id']]);
            
            // Update assignment
            $stmt = $pdo->prepare("UPDATE breakdown_assignments SET ended_at = NOW(), work_status = 'completed' WHERE id = ?");
            $stmt->execute([$assignmentId]);
            
            // Update breakdown status
            $stmt = $pdo->prepare("UPDATE breakdown_reports SET status = 'termine' WHERE id = ?");
            $stmt->execute([$breakdownId]);
            
            // Calculate total duration
            $stmt = $pdo->prepare("CALL CalculateWorkDuration(?, ?)");
            $stmt->execute([$breakdownId, $assignmentId]);
            
            // Get final duration
            $stmt = $pdo->prepare("SELECT actual_hours FROM breakdown_assignments WHERE id = ?");
            $stmt->execute([$assignmentId]);
            $finalDuration = $stmt->fetch()['actual_hours'];
            
            // Log action
            $stmt = $pdo->prepare("
                INSERT INTO breakdown_audit_log 
                (breakdown_id, assignment_id, action_type, new_value, performed_by_user_id) 
                VALUES (?, ?, 'session_ended', ?, ?)
            ");
            $stmt->execute([$breakdownId, $assignmentId, json_encode(['total_hours' => $finalDuration, 'notes' => $notes]), $_SESSION['user_id']]);
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Session terminée',
                'total_duration' => $finalDuration
            ]);
            break;
            
        case 'get_time_logs':
            $breakdownId = (int)$_POST['breakdown_id'];
            
            $stmt = $pdo->prepare("
                SELECT 
                    tl.*,
                    u.full_name,
                    DATE_FORMAT(tl.action_time, '%d/%m/%Y %H:%i:%s') as formatted_time
                FROM breakdown_time_logs tl
                JOIN users u ON tl.user_id = u.id
                WHERE tl.breakdown_id = ?
                ORDER BY tl.action_time
            ");
            $stmt->execute([$breakdownId]);
            $logs = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'logs' => $logs]);
            break;
            
        case 'get_session_status':
            $breakdownId = (int)$_POST['breakdown_id'];
            $assignmentId = (int)$_POST['assignment_id'];
            
            $stmt = $pdo->prepare("
                SELECT 
                    work_status,
                    started_at,
                    ended_at,
                    actual_hours,
                    (SELECT action_type FROM breakdown_time_logs 
                     WHERE breakdown_id = ? AND assignment_id = ? 
                     ORDER BY action_time DESC LIMIT 1) as last_action
                FROM breakdown_assignments 
                WHERE id = ?
            ");
            $stmt->execute([$breakdownId, $assignmentId, $assignmentId]);
            $status = $stmt->fetch();
            
            if ($status) {
                echo json_encode([
                    'success' => true, 
                    'status' => $status['work_status'],
                    'last_action' => $status['last_action'],
                    'started_at' => $status['started_at'],
                    'ended_at' => $status['ended_at'],
                    'actual_hours' => $status['actual_hours']
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Assignment non trouvé']);
            }
            break;
            
        case 'get_time_summary':
            $breakdownId = (int)$_POST['breakdown_id'];
            
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(CASE WHEN action_type = 'start' THEN 1 END) as sessions_count,
                    SUM(CASE WHEN action_type = 'start' THEN 1 ELSE 0 END) as total_sessions,
                    MIN(action_time) as first_start,
                    MAX(action_time) as last_action,
                    ba.actual_hours,
                    ba.work_status
                FROM breakdown_time_logs tl
                JOIN breakdown_assignments ba ON tl.assignment_id = ba.id
                WHERE tl.breakdown_id = ?
            ");
            $stmt->execute([$breakdownId]);
            $summary = $stmt->fetch();
            
            echo json_encode(['success' => true, 'summary' => $summary]);
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

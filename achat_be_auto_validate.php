<?php
// FUTURE AUTOMOTIVE - Auto Validate BE on View
// التحقق التلقائي من Bon d'Entrée عند العرض

require_once 'config.php';
require_once 'config_achat_hostinger.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Get BE ID
$be_id = $_GET['id'] ?? 0;
if (!$be_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'BE ID not provided']);
    exit();
}

// Auto-validate BE if status is "Reçu"
try {
    $database = new DatabaseAchat();
    $conn = $database->connect();
    
    // Check current status
    $stmt = $conn->prepare("SELECT id, statut, ref_be FROM bons_entree WHERE id = ?");
    $stmt->execute([$be_id]);
    $be = $stmt->fetch();
    
    if (!$be) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'BE not found']);
        exit();
    }
    
    if ($be['statut'] === 'Reçu') {
        // Auto-validate the BE
        $stmt = $conn->prepare("UPDATE bons_entree SET statut = 'Validé', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$be_id]);
        
        // Log the auto-validation
        $stmt = $conn->prepare("
            INSERT INTO be_history (be_id, action, action_date, created_by, notes) 
            VALUES (?, 'Auto-Validation', NOW(), ?, 'Validated automatically on view')
        ");
        $stmt->execute([$be_id, $_SESSION['user_id']]);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'BE auto-validated successfully', 'ref_be' => $be['ref_be']]);
        exit();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'BE is not in "Reçu" status', 'current_status' => $be['statut']]);
        exit();
    }
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Auto-validation error: ' . $e->getMessage()]);
    exit();
}
?>

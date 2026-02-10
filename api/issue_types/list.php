<?php
// FUTURE AUTOMOTIVE - Issue Types List API
// واجهة برمجية لجلب قائمة أنواع الأعطال

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'طريقة غير مسموح بها']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->connect();
    
    $category = $_GET['category'] ?? '';
    
    $sql = "SELECT * FROM issue_types";
    $params = [];
    
    if ($category) {
        $sql .= " WHERE category = ?";
        $params[] = $category;
    }
    
    $sql .= " ORDER BY category, name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $issue_types = $stmt->fetchAll();
    
    // Group by category
    $grouped_issues = [];
    foreach ($issue_types as $issue) {
        if (!isset($grouped_issues[$issue['category']])) {
            $grouped_issues[$issue['category']] = [];
        }
        $grouped_issues[$issue['category']][] = [
            'id' => $issue['id'],
            'name' => $issue['name'],
            'description' => $issue['description'],
            'priority' => $issue['priority'],
            'category' => $issue['category']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'issue_types' => $grouped_issues,
        'total_count' => count($issue_types)
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

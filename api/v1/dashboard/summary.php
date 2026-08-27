<?php
// api/v1/dashboard/summary.php
header('Content-Type: application/json');
require_once '../../../db.php';
require_once '../../../auth.php';

$user = verifyJWT();

try {
    $pdo = getDbConnection();
    // Simplified summary query
    $data = [
        'inventory_count' => $pdo->query("SELECT COUNT(*) FROM inventory_items")->fetchColumn(),
        'animal_count' => $pdo->query("SELECT COUNT(*) FROM animals")->fetchColumn(),
        'total_transactions' => $pdo->query("SELECT SUM(amount) FROM transactions")->fetchColumn()
    ];
    
    echo json_encode(['success' => true, 'data' => $data, 'error' => null]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'data' => null, 'error' => 'Failed to fetch dashboard summary']);
}
?>

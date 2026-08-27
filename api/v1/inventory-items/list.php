<?php
// api/v1/inventory-items/list.php
header('Content-Type: application/json');
require_once '../../../db.php';
require_once '../../../auth.php';

// Protected endpoint
$user = verifyJWT();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'data' => null, 'error' => 'Method not allowed']);
    exit;
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT * FROM inventory_items");
    $items = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'data' => $items, 'error' => null]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'data' => null, 'error' => 'Failed to fetch items']);
}
?>

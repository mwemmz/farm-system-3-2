<?php
// api/v1/inventory-items/low-stock.php
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

$threshold = $_GET['threshold'] ?? 10; // Default threshold

try {
    $pdo = getDbConnection();
    
    // Find items with low stock
    $stmt = $pdo->prepare("
        SELECT i.*, 
        ((SELECT SUM(quantity) FROM stock_movements WHERE item_id = i.id AND type = 'in') - 
         (SELECT SUM(quantity) FROM stock_movements WHERE item_id = i.id AND type = 'out')) as balance
        FROM inventory_items i
        HAVING balance < ?
    ");
    $stmt->execute([$threshold]);
    $items = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'data' => $items, 'error' => null]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'data' => null, 'error' => 'Failed to fetch low stock items']);
}
?>

<?php
// api/v1/inventory-items/stock-out.php
header('Content-Type: application/json');
require_once '../../../db.php';
require_once '../../../auth.php';

// Protected endpoint
$user = verifyJWT();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'data' => null, 'error' => 'Method not allowed']);
    exit;
}

$itemId = $_GET['id'] ?? null;
$data = json_decode(file_get_contents('php://input'), true);

if (!$itemId || empty($data['quantity'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'data' => null, 'error' => 'Missing item ID or quantity']);
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Calculate current stock
    $stmt = $pdo->prepare("
        SELECT 
            (SELECT SUM(quantity) FROM stock_movements WHERE item_id = ? AND type = 'in') - 
            (SELECT SUM(quantity) FROM stock_movements WHERE item_id = ? AND type = 'out') 
        as balance
    ");
    $stmt->execute([$itemId, $itemId]);
    $balance = (float)($stmt->fetchColumn() ?: 0);

    if ($balance < $data['quantity']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'data' => null, 'error' => 'Insufficient stock']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO stock_movements (item_id, type, quantity, notes) VALUES (?, 'out', ?, ?)");
    $stmt->execute([$itemId, $data['quantity'], $data['notes'] ?? '']);
    
    logAudit($user['id'], 'STOCK_OUT', 'inventory_items');
    
    echo json_encode(['success' => true, 'data' => ['message' => 'Stock updated'], 'error' => null]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'data' => null, 'error' => 'Failed to deduct stock']);
}
?>

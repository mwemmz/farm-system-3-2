<?php
// api/v1/inventory-items/create.php
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

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['farm_id']) || empty($data['category']) || empty($data['name']) || empty($data['unit'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'data' => null, 'error' => 'Missing required fields']);
    exit;
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("INSERT INTO inventory_items (farm_id, category, name, unit, expiry_date) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['farm_id'],
        $data['category'],
        $data['name'],
        $data['unit'],
        $data['expiry_date'] ?? null
    ]);
    
    $itemId = $pdo->lastInsertId();
    logAudit($user['id'], 'CREATE_INVENTORY_ITEM', 'inventory_items');
    
    echo json_encode(['success' => true, 'data' => ['id' => $itemId], 'error' => null]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'data' => null, 'error' => 'Failed to create item']);
}
?>

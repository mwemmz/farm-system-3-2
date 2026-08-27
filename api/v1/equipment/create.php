<?php
// api/v1/equipment/create.php
header('Content-Type: application/json');
require_once '../../../db.php';
require_once '../../../auth.php';

$user = verifyJWT();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'data' => null, 'error' => 'Method not allowed']));
}

$data = json_decode(file_get_contents('php://input'), true);

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("INSERT INTO equipment (farm_id, name, type, purchase_date, purchase_cost) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$data['farm_id'], $data['name'], $data['type'], $data['purchase_date'], $data['purchase_cost']]);
    
    logAudit($user['id'], 'CREATE_EQUIPMENT', 'equipment');
    
    echo json_encode(['success' => true, 'data' => ['id' => $pdo->lastInsertId()], 'error' => null]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'data' => null, 'error' => 'Failed to create equipment']);
}
?>

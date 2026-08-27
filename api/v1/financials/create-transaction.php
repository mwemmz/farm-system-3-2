<?php
// api/v1/financials/create-transaction.php
header('Content-Type: application/json');
require_once '../../../db.php';
require_once '../../../auth.php';

$user = verifyJWT();

$data = json_decode(file_get_contents('php://input'), true);

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("INSERT INTO transactions (farm_id, type, category, amount, date) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$data['farm_id'], $data['type'], $data['category'], $data['amount'], $data['date']]);
    
    logAudit($user['id'], 'CREATE_TRANSACTION', 'transactions');
    
    echo json_encode(['success' => true, 'data' => ['id' => $pdo->lastInsertId()], 'error' => null]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'data' => null, 'error' => 'Failed to create transaction']);
}
?>

<?php
// api/v1/employees/create.php
header('Content-Type: application/json');
require_once '../../../db.php';
require_once '../../../auth.php';

$user = verifyJWT();

$data = json_decode(file_get_contents('php://input'), true);

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("INSERT INTO employees (farm_id, name, role, contact, wage_rate) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$data['farm_id'], $data['name'], $data['role'], $data['contact'], $data['wage_rate']]);
    
    logAudit($user['id'], 'CREATE_EMPLOYEE', 'employees');
    
    echo json_encode(['success' => true, 'data' => ['id' => $pdo->lastInsertId()], 'error' => null]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'data' => null, 'error' => 'Failed to create employee']);
}
?>

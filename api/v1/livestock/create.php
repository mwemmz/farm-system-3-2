<?php
// api/v1/livestock/create.php
header('Content-Type: application/json');
require_once '../../../db.php';
require_once '../../../auth.php';

$user = verifyJWT();

$data = json_decode(file_get_contents('php://input'), true);

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("INSERT INTO animals (farm_id, tag_id, breed, dob, sex, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$data['farm_id'], $data['tag_id'], $data['breed'], $data['dob'], $data['sex'], $data['status']]);
    
    logAudit($user['id'], 'CREATE_ANIMAL', 'animals');
    
    echo json_encode(['success' => true, 'data' => ['id' => $pdo->lastInsertId()], 'error' => null]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'data' => null, 'error' => 'Failed to create animal']);
}
?>

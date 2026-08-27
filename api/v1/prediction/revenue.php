<?php
// api/v1/prediction/revenue.php
header('Content-Type: application/json');
require_once '../../../db.php';
require_once '../../../auth.php';

$user = verifyJWT();

$plantingId = $_GET['planting_id'] ?? null;

if (!$plantingId) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'data' => null, 'error' => 'Missing planting_id']));
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM revenue_forecasts WHERE planting_id = ?");
    $stmt->execute([$plantingId]);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(), 'error' => null]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'data' => null, 'error' => 'Failed to fetch revenue forecast']);
}
?>

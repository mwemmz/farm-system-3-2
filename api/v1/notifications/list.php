<?php
// api/v1/notifications/list.php
header('Content-Type: application/json');
require_once '../../../db.php';
require_once '../../../auth.php';

$user = verifyJWT();

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(), 'error' => null]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'data' => null, 'error' => 'Failed to fetch notifications']);
}
?>

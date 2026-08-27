<?php
// api/v1/financials/list-transactions.php
header('Content-Type: application/json');
require_once '../../../db.php';
require_once '../../../auth.php';

$user = verifyJWT();

try {
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT * FROM transactions");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(), 'error' => null]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'data' => null, 'error' => 'Failed to fetch transactions']);
}
?>

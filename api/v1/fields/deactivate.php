<?php
require_once __DIR__ . '/../farms/farm_helpers.php';
farmRun('POST', function ($pdo, $user) {
    $id = farmId($_GET['id'] ?? null);
    fieldFind($pdo, $id, $user);
    $stmt = $pdo->prepare("UPDATE fields SET status = 'inactive' WHERE id = ?");
    $stmt->execute([$id]);
    logAudit($user['id'], 'DEACTIVATE_FIELD', 'fields');
    farmRespond(200, ['id' => $id, 'status' => 'inactive']);
});

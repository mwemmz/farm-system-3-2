<?php
require_once __DIR__ . '/irrigation_helpers.php';
farmRun('POST', function ($pdo, $user) {
    $id = farmId($_GET['id'] ?? null);
    irrigationSystem($pdo, $id, $user);
    $stmt = $pdo->prepare("UPDATE irrigation_systems SET status = 'inactive' WHERE id = ?");
    $stmt->execute([$id]);
    logAudit($user['id'], 'DEACTIVATE_IRRIGATION_SYSTEM', 'irrigation_systems');
    farmRespond(200, ['id'=>$id,'status'=>'inactive']);
});

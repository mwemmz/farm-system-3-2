<?php
require_once __DIR__ . '/harvest_helpers.php';
farmRun('POST', function ($pdo, $user) {
    $id = farmId($_GET['id'] ?? null);
    harvestFind($pdo, $id, $user);
    $stmt = $pdo->prepare("UPDATE harvests SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([$id]);
    logAudit($user['id'], 'CANCEL_HARVEST', 'harvests');
    farmRespond(200, ['id'=>$id,'status'=>'cancelled']);
});

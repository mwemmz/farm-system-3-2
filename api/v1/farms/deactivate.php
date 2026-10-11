<?php
require_once __DIR__ . '/farm_helpers.php';
farmRun('POST', function ($pdo, $user) {
    $id = farmId($_GET['id'] ?? null);
    farmFind($pdo, $id, $user);
    $stmt = $pdo->prepare("UPDATE farms SET status = 'inactive' WHERE id = ?");
    $stmt->execute([$id]);
    logAudit($user['id'], 'DEACTIVATE_FARM', 'farms');
    farmRespond(200, ['id' => $id, 'status' => 'inactive']);
});

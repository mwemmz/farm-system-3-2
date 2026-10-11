<?php
require_once __DIR__ . '/irrigation_helpers.php';
farmRun('PATCH', function ($pdo, $user) {
    $id = farmId($_GET['id'] ?? null);
    $system = irrigationSystem($pdo, $id, $user);
    $values = irrigationSystemValues($pdo, farmJson(), $system['farm_id']);
    farmUpdateColumns($pdo, 'irrigation_systems', $id, $values);
    logAudit($user['id'], 'UPDATE_IRRIGATION_SYSTEM', 'irrigation_systems');
    farmRespond(200, ['id'=>$id]);
});

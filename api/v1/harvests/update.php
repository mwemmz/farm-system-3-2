<?php
require_once __DIR__ . '/harvest_helpers.php';
farmRun('PATCH', function ($pdo, $user) {
    $id = farmId($_GET['id'] ?? null);
    $harvest = harvestFind($pdo, $id, $user);
    $field = fieldFind($pdo, $harvest['field_id'], $user);
    $values = harvestValues(farmJson());
    if (!$values) throw new FarmApiError(400, 'No editable fields supplied');
    harvestValidateFinal(array_merge($harvest, $values), $field);
    farmUpdateColumns($pdo, 'harvests', $id, $values);
    logAudit($user['id'], 'UPDATE_HARVEST', 'harvests');
    farmRespond(200, ['id'=>$id]);
});

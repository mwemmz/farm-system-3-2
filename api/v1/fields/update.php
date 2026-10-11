<?php
require_once __DIR__ . '/../farms/farm_helpers.php';
// PATCH updates only supplied field properties; farm_id is immutable here.
farmRun('PATCH', function ($pdo, $user) {
    $id = farmId($_GET['id'] ?? null);
    $field = fieldFind($pdo, $id, $user);
    $values = fieldProfile(farmJson());
    if (array_key_exists('code', $values)) farmFieldCodeAvailable($pdo, $field['farm_id'], $values['code'], $id);
    farmUpdateColumns($pdo, 'fields', $id, $values);
    logAudit($user['id'], 'UPDATE_FIELD', 'fields');
    farmRespond(200, ['id' => $id]);
});

<?php
require_once __DIR__ . '/farm_helpers.php';
// PATCH updates only supplied profile properties.
farmRun('PATCH', function ($pdo, $user) {
    $id = farmId($_GET['id'] ?? null);
    farmFind($pdo, $id, $user);
    $values = farmProfile(farmJson());
    farmUpdateColumns($pdo, 'farms', $id, $values);
    logAudit($user['id'], 'UPDATE_FARM', 'farms');
    farmRespond(200, ['id' => $id]);
});

<?php
require_once __DIR__ . '/farm_helpers.php';
farmRun('POST', function ($pdo, $user) {
    $values = farmProfile(farmJson(), true);
    $stmt = $pdo->prepare('INSERT INTO farms (owner_user_id, name, location_name, latitude, longitude, total_area_hectares, description) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$user['id'], $values['name'], $values['location_name'] ?? null, $values['latitude'] ?? null, $values['longitude'] ?? null, $values['total_area_hectares'] ?? null, $values['description'] ?? null]);
    $id = (int)$pdo->lastInsertId();
    logAudit($user['id'], 'CREATE_FARM', 'farms');
    farmRespond(201, ['id' => $id]);
});

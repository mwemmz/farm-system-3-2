<?php
require_once __DIR__ . '/../farms/farm_helpers.php';
farmRun('POST', function ($pdo, $user) {
    $data = farmJson();
    $farmId = farmId($data['farm_id'] ?? null, 'farm_id');
    farmFind($pdo, $farmId, $user);
    $values = fieldProfile($data, true);
    farmFieldCodeAvailable($pdo, $farmId, $values['code'] ?? null);
    $stmt = $pdo->prepare('INSERT INTO fields (farm_id, name, code, area_hectares, latitude, longitude, boundary_geojson, land_use, land_use_notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$farmId, $values['name'], $values['code'] ?? null, $values['area_hectares'], $values['latitude'] ?? null, $values['longitude'] ?? null, $values['boundary_geojson'] ?? null, $values['land_use'] ?? null, $values['land_use_notes'] ?? null]);
    $id = (int)$pdo->lastInsertId();
    logAudit($user['id'], 'CREATE_FIELD', 'fields');
    farmRespond(201, ['id' => $id]);
});

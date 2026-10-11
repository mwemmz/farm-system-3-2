<?php
require_once __DIR__ . '/../../farms/farm_helpers.php';
farmRun('POST', function ($pdo, $user) {
    $data = farmJson();
    $fieldId = farmId($data['field_id'] ?? null, 'field_id');
    fieldFind($pdo, $fieldId, $user);
    $cropName = farmText($data['crop_name'] ?? null, 'crop_name', 255, true);
    $season = farmText($data['season'] ?? null, 'season', 100);
    $plantedAt = farmDate($data['planted_at'] ?? null, 'planted_at');
    $harvestedAt = farmDate($data['harvested_at'] ?? null, 'harvested_at');
    $notes = farmText($data['notes'] ?? null, 'notes', 65535);
    if ($plantedAt !== null && $harvestedAt !== null && $harvestedAt < $plantedAt) {
        throw new FarmApiError(400, 'harvested_at must be on or after planted_at');
    }
    $stmt = $pdo->prepare('INSERT INTO crop_rotation_history (field_id, crop_name, season, planted_at, harvested_at, notes) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$fieldId, $cropName, $season, $plantedAt, $harvestedAt, $notes]);
    $id = (int)$pdo->lastInsertId();
    logAudit($user['id'], 'CREATE_ROTATION_HISTORY', 'crop_rotation_history');
    farmRespond(201, ['id' => $id]);
});

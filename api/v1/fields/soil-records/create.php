<?php
require_once __DIR__ . '/../../farms/farm_helpers.php';
farmRun('POST', function ($pdo, $user) {
    $data = farmJson();
    $fieldId = farmId($data['field_id'] ?? null, 'field_id');
    fieldFind($pdo, $fieldId, $user);
    $soilType = farmText($data['soil_type'] ?? null, 'soil_type', 100);
    $ph = farmNumber($data['ph_level'] ?? null, 'ph_level', 0, 14);
    $moisture = farmNumber($data['moisture_percent'] ?? null, 'moisture_percent', 0, 100);
    $notes = farmText($data['condition_notes'] ?? null, 'condition_notes', 65535);
    $recordedAt = farmDate($data['recorded_at'] ?? null, 'recorded_at', 'Y-m-d H:i:s');
    if ($soilType === null && $ph === null && $moisture === null && $notes === null) throw new FarmApiError(400, 'Supply soil information');
    if ($recordedAt === null) throw new FarmApiError(400, 'Missing recorded_at');
    $stmt = $pdo->prepare('INSERT INTO soil_records (field_id, soil_type, ph_level, moisture_percent, condition_notes, recorded_at) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$fieldId, $soilType, $ph, $moisture, $notes, $recordedAt]);
    $id = (int)$pdo->lastInsertId();
    logAudit($user['id'], 'CREATE_SOIL_RECORD', 'soil_records');
    farmRespond(201, ['id' => $id]);
});

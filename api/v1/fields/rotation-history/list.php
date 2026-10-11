<?php
require_once __DIR__ . '/../../farms/farm_helpers.php';
farmRun('GET', function ($pdo, $user) {
    $fieldId = farmId($_GET['field_id'] ?? null, 'field_id');
    fieldFind($pdo, $fieldId, $user);
    $stmt = $pdo->prepare('SELECT * FROM crop_rotation_history WHERE field_id = ? ORDER BY COALESCE(planted_at, harvested_at) DESC, id DESC');
    $stmt->execute([$fieldId]);
    farmRespond(200, $stmt->fetchAll());
});

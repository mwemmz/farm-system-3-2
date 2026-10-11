<?php
require_once __DIR__ . '/yield_prediction_helpers.php';
farmRun('GET', function ($pdo, $user) {
    $fieldId = farmId($_GET['field_id'] ?? null, 'field_id');
    fieldFind($pdo, $fieldId, $user);
    $sql = 'SELECT id,field_id,crop_name_snapshot,variety_snapshot,target_area_hectares,predicted_yield_kg_per_hectare,predicted_quantity_kg,sample_count,confidence_level,prediction_method,created_at FROM yield_predictions WHERE field_id = ?';
    $params = [$fieldId];
    $cropKey = isset($_GET['crop_name_snapshot']) ? yieldKey($_GET['crop_name_snapshot']) : null;
    if (isset($_GET['confidence_level'])) {
        if (!in_array($_GET['confidence_level'], ['low','medium','high'], true)) throw new FarmApiError(400, 'Invalid confidence_level');
        $sql .= ' AND confidence_level = ?';
        $params[] = $_GET['confidence_level'];
    }
    $stmt = $pdo->prepare($sql . ' ORDER BY created_at DESC,id DESC');
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    if ($cropKey !== null) $rows = array_values(array_filter($rows, fn($row) => yieldKey($row['crop_name_snapshot']) === $cropKey));
    farmRespond(200, $rows);
});

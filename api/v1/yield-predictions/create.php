<?php
require_once __DIR__ . '/yield_prediction_helpers.php';
farmRun('POST', function ($pdo, $user) {
    $data = farmJson();
    $fieldId = farmId($data['field_id'] ?? null, 'field_id');
    $field = fieldFind($pdo, $fieldId, $user);
    $crop = yieldCrop($data['crop_name_snapshot'] ?? null);
    $variety = farmText($data['variety_snapshot'] ?? null, 'variety_snapshot', 255);
    $area = array_key_exists('target_area_hectares', $data)
        ? farmNumber($data['target_area_hectares'], 'target_area_hectares', 0.0001, YIELD_DECIMAL_MAX)
        : (float)$field['area_hectares'];
    if ($area === null || $area <= 0 || $area > (float)$field['area_hectares']) throw new FarmApiError(400, 'Invalid target_area_hectares');
    $samples = yieldHistory($pdo, $fieldId, $crop);
    if (count($samples) < 2) farmRespond(200, ['status'=>'insufficient_data','required_minimum'=>2,'sample_count'=>count($samples)]);
    $stats = yieldStatistics($samples);
    $rawMean = array_sum(array_column($samples, 'yield_kg_per_hectare')) / count($samples);
    $mean = $stats['mean_yield_kg_per_hectare'];
    $quantity = yieldDecimal($rawMean * $area, 'Predicted quantity');
    $context = yieldFactors($pdo, $field);
    $confidence = yieldConfidence($stats, $samples);
    $stmt = $pdo->prepare('INSERT INTO yield_predictions (field_id,crop_name_snapshot,variety_snapshot,target_area_hectares,predicted_yield_kg_per_hectare,predicted_quantity_kg,historical_mean_yield_kg_per_hectare,historical_min_yield_kg_per_hectare,historical_max_yield_kg_per_hectare,historical_stddev,coefficient_of_variation,sample_count,confidence_level,prediction_method,factor_context_json,created_by_user_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([$fieldId,$crop,$variety,$area,$mean,$quantity,$mean,$stats['min_yield_kg_per_hectare'],$stats['max_yield_kg_per_hectare'],$stats['standard_deviation'],$stats['coefficient_of_variation'],$stats['sample_count'],$confidence,'historical_mean_v1',json_encode($context, JSON_THROW_ON_ERROR),$user['id']]);
    $id = (int)$pdo->lastInsertId();
    logAudit($user['id'], 'CREATE_YIELD_PREDICTION', 'yield_predictions');
    farmRespond(201, ['id'=>$id,'status'=>'created','sample_count'=>$stats['sample_count'],'confidence_level'=>$confidence]);
});

<?php
require_once __DIR__ . '/yield_prediction_helpers.php';
farmRun('GET', function ($pdo, $user) {
    $prediction = yieldPredictionFind($pdo, farmId($_GET['prediction_id'] ?? null, 'prediction_id'), $user);
    $harvest = harvestFind($pdo, farmId($_GET['harvest_id'] ?? null, 'harvest_id'), $user);
    if ((int)$prediction['field_id'] !== (int)$harvest['field_id'] || yieldKey($prediction['crop_name_snapshot']) !== yieldKey($harvest['crop_name_snapshot'])) throw new FarmApiError(409, 'Prediction and harvest are not comparable');
    if ($harvest['status'] !== 'completed' || $harvest['actual_quantity_kg'] === null || (float)$harvest['area_harvested_hectares'] <= 0) throw new FarmApiError(409, 'Harvest is not completed');
    $predicted = (float)$prediction['predicted_quantity_kg'];
    $actual = (float)$harvest['actual_quantity_kg'];
    $signed = round($predicted - $actual, 4);
    $predictedYield = (float)$prediction['predicted_yield_kg_per_hectare'];
    $actualYield = $actual / (float)$harvest['area_harvested_hectares'];
    farmRespond(200, ['prediction_id'=>(int)$prediction['id'],'harvest_id'=>(int)$harvest['id'],
        'predicted_quantity_kg'=>$predicted,'actual_quantity_kg'=>$actual,
        'signed_error_kg'=>$signed,'absolute_error_kg'=>abs($signed),
        'absolute_percentage_error'=>$actual > 0 ? round(abs($signed) / $actual * 100, 4) : null,
        'predicted_yield_kg_per_hectare'=>$predictedYield,'actual_yield_kg_per_hectare'=>round($actualYield, 4),
        'yield_difference_kg_per_hectare'=>round($predictedYield - $actualYield, 4)]);
});

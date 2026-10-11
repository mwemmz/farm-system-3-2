<?php
require_once __DIR__ . '/yield_prediction_helpers.php';
farmRun('GET', function ($pdo, $user) {
    $fieldId = farmId($_GET['field_id'] ?? null, 'field_id');
    fieldFind($pdo, $fieldId, $user);
    $samples = yieldHistory($pdo, $fieldId);
    $groups = [];
    $total = 0;
    $losses = 0;
    $zero = 0;
    $recent = [];
    foreach ($samples as $row) {
        $key = yieldKey($row['crop_name_snapshot']);
        if (!isset($groups[$key])) $groups[$key] = ['crop_name_snapshot'=>$row['crop_name_snapshot'],'samples'=>[]];
        $groups[$key]['samples'][] = $row;
        $total += (float)$row['actual_quantity_kg'];
        $losses += (float)$row['loss_quantity_kg'];
        if ((float)$row['actual_quantity_kg'] === 0.0) $zero++;
        if (count($recent) < 10) $recent[] = ['harvest_id'=>(int)$row['id'],'crop_name_snapshot'=>$row['crop_name_snapshot'],'actual_harvest_date'=>$row['actual_harvest_date'],'actual_quantity_kg'=>(float)$row['actual_quantity_kg'],'yield_kg_per_hectare'=>round($row['yield_kg_per_hectare'], 4)];
    }
    $crops = [];
    foreach ($groups as $group) {
        $crops[] = ['crop_name_snapshot'=>$group['crop_name_snapshot'],'statistics'=>yieldStatistics($group['samples'])];
    }
    farmRespond(200, ['field_id'=>$fieldId,'completed_harvest_count'=>count($samples),
        'overall_production'=>['total_harvested_quantity_kg'=>round($total, 4),'total_recorded_losses_kg'=>round($losses, 4),'zero_yield_event_count'=>$zero],
        'crop_yield_statistics'=>$crops,'recent_harvests'=>$recent]);
});

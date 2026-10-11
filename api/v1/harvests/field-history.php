<?php
require_once __DIR__ . '/harvest_helpers.php';
farmRun('GET', function ($pdo, $user) {
    $fieldId = farmId($_GET['field_id'] ?? null, 'field_id');
    fieldFind($pdo, $fieldId, $user);
    $stmt = $pdo->prepare("SELECT * FROM harvests WHERE field_id = ? AND status = 'completed' ORDER BY actual_harvest_date DESC, id DESC");
    $stmt->execute([$fieldId]);
    $rows = [];
    foreach ($stmt->fetchAll() as $harvest) {
        $rows[] = ['id'=>(int)$harvest['id'],'crop_name_snapshot'=>$harvest['crop_name_snapshot'],'variety_snapshot'=>$harvest['variety_snapshot'],'actual_harvest_date'=>$harvest['actual_harvest_date'],'actual_quantity_kg'=>(float)$harvest['actual_quantity_kg'],'loss_quantity_kg'=>(float)$harvest['loss_quantity_kg'],'area_harvested_hectares'=>(float)$harvest['area_harvested_hectares'],'grade'=>$harvest['grade'],'analysis'=>harvestAnalysis($harvest)];
    }
    farmRespond(200, $rows);
});

<?php
require_once __DIR__ . '/harvest_helpers.php';
farmRun('POST', function ($pdo, $user) {
    $data = farmJson();
    $fieldId = farmId($data['field_id'] ?? null, 'field_id');
    $field = fieldFind($pdo, $fieldId, $user);
    $v = harvestValues($data, true);
    harvestValidateFinal(array_merge(['status'=>'planned','loss_quantity_kg'=>0,'actual_quantity_kg'=>null,'actual_harvest_date'=>null,'area_harvested_hectares'=>null], $v), $field);
    $stmt = $pdo->prepare('INSERT INTO harvests (field_id,crop_name_snapshot,variety_snapshot,expected_harvest_date,actual_harvest_date,expected_quantity_kg,actual_quantity_kg,loss_quantity_kg,area_harvested_hectares,grade,quality_notes,status,notes,created_by_user_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([$fieldId,$v['crop_name_snapshot'],$v['variety_snapshot']??null,$v['expected_harvest_date']??null,$v['actual_harvest_date']??null,$v['expected_quantity_kg']??null,$v['actual_quantity_kg']??null,$v['loss_quantity_kg']??0,$v['area_harvested_hectares']??null,$v['grade']??null,$v['quality_notes']??null,$v['status']??'planned',$v['notes']??null,$user['id']]);
    $id = (int)$pdo->lastInsertId();
    logAudit($user['id'], 'CREATE_HARVEST', 'harvests');
    farmRespond(201, ['id'=>$id]);
});

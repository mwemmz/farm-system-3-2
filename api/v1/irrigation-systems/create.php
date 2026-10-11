<?php
require_once __DIR__ . '/irrigation_helpers.php';
farmRun('POST', function ($pdo, $user) {
    $data = farmJson();
    $farmId = farmId($data['farm_id'] ?? null, 'farm_id');
    farmFind($pdo, $farmId, $user);
    $v = irrigationSystemValues($pdo, $data, $farmId, true);
    $stmt = $pdo->prepare('INSERT INTO irrigation_systems (farm_id,field_id,name,system_type,water_source_name,water_source_type,water_source_capacity_liters,pump_name,pump_status,flow_rate_lph,installation_date,notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([$farmId,$v['field_id']??null,$v['name'],$v['system_type'],$v['water_source_name']??null,$v['water_source_type']??null,$v['water_source_capacity_liters']??null,$v['pump_name']??null,$v['pump_status']??null,$v['flow_rate_lph']??null,$v['installation_date']??null,$v['notes']??null]);
    $id = (int)$pdo->lastInsertId();
    logAudit($user['id'], 'CREATE_IRRIGATION_SYSTEM', 'irrigation_systems');
    farmRespond(201, ['id'=>$id]);
});

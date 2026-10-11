<?php
require_once __DIR__ . '/../irrigation-systems/irrigation_helpers.php';
farmRun('POST', function ($pdo, $user) {
    $data = farmJson();
    $systemId = farmId($data['irrigation_system_id'] ?? null, 'irrigation_system_id');
    irrigationSystem($pdo, $systemId, $user);
    $v = irrigationScheduleValues($data, true);
    $stmt = $pdo->prepare('INSERT INTO irrigation_schedules (irrigation_system_id,scheduled_at,duration_minutes,planned_volume_liters,status,weather_adjusted,notes) VALUES (?,?,?,?,?,?,?)');
    $stmt->execute([$systemId,$v['scheduled_at'],$v['duration_minutes'],$v['planned_volume_liters']??null,$v['status']??'scheduled',$v['weather_adjusted']??0,$v['notes']??null]);
    $id = (int)$pdo->lastInsertId();
    logAudit($user['id'], 'CREATE_IRRIGATION_SCHEDULE', 'irrigation_schedules');
    farmRespond(201, ['id'=>$id]);
});

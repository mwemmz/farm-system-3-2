<?php
require_once __DIR__ . '/../irrigation_helpers.php';
farmRun('POST', function ($pdo, $user) {
    $data = farmJson();
    $systemId = farmId($data['irrigation_system_id'] ?? null, 'irrigation_system_id');
    irrigationSystem($pdo, $systemId, $user);
    $scheduleId = null;
    if (array_key_exists('schedule_id', $data) && $data['schedule_id'] !== null) {
        $scheduleId = farmId($data['schedule_id'], 'schedule_id');
        $schedule = irrigationSchedule($pdo, $scheduleId, $user);
        if ((int)$schedule['irrigation_system_id'] !== $systemId) throw new FarmApiError(409, 'Schedule belongs to another irrigation system');
    }
    $v = irrigationUsageValues($data);
    $stmt = $pdo->prepare('INSERT INTO water_usage_logs (irrigation_system_id,schedule_id,logged_at,volume_liters,duration_minutes,energy_kwh,cost_amount,pump_status,issue_notes) VALUES (?,?,?,?,?,?,?,?,?)');
    $stmt->execute([$systemId,$scheduleId,$v['logged_at'],$v['volume_liters'],$v['duration_minutes'],$v['energy_kwh']??null,$v['cost_amount']??null,$v['pump_status']??null,$v['issue_notes']??null]);
    $id = (int)$pdo->lastInsertId();
    logAudit($user['id'], 'CREATE_WATER_USAGE_LOG', 'water_usage_logs');
    farmRespond(201, ['id'=>$id]);
});

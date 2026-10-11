<?php
require_once __DIR__ . '/../irrigation-systems/irrigation_helpers.php';
farmRun('PATCH', function ($pdo, $user) {
    $id = farmId($_GET['id'] ?? null);
    irrigationSchedule($pdo, $id, $user);
    $values = irrigationScheduleValues(farmJson());
    farmUpdateColumns($pdo, 'irrigation_schedules', $id, $values);
    logAudit($user['id'], 'UPDATE_IRRIGATION_SCHEDULE', 'irrigation_schedules');
    farmRespond(200, ['id'=>$id]);
});

<?php
require_once __DIR__ . '/../pest-disease-catalog/pest_helpers.php';
farmRun('PATCH', function ($pdo, $user) {
    $id = farmId($_GET['id'] ?? null);
    $report = pestScout($pdo, $id, $user);
    $field = fieldFind($pdo, $report['field_id'], $user);
    $values = pestScoutValues($pdo, farmJson(), $user, $field);
    farmUpdateColumns($pdo, 'scouting_reports', $id, $values);
    logAudit($user['id'], 'UPDATE_SCOUTING_REPORT', 'scouting_reports');
    farmRespond(200, ['id'=>$id]);
});

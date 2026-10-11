<?php
require_once __DIR__ . '/../../pest-disease-catalog/pest_helpers.php';
farmRun('PATCH', function ($pdo, $user) {
    $id = farmId($_GET['id'] ?? null);
    $treatment = pestTreatment($pdo, $id, $user);
    $values = pestTreatmentValues(farmJson(), false, $treatment);
    farmUpdateColumns($pdo, 'treatments', $id, $values);
    logAudit($user['id'], 'UPDATE_PEST_DISEASE_TREATMENT', 'treatments');
    farmRespond(200, ['id'=>$id]);
});

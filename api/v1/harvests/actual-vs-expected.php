<?php
require_once __DIR__ . '/harvest_helpers.php';
farmRun('GET', function ($pdo, $user) {
    $harvest = harvestFind($pdo, farmId($_GET['harvest_id'] ?? null, 'harvest_id'), $user);
    farmRespond(200, ['harvest_id'=>(int)$harvest['id'],'field_id'=>(int)$harvest['field_id'],'status'=>$harvest['status'],'analysis'=>harvestAnalysis($harvest)]);
});

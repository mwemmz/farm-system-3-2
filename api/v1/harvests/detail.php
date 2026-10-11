<?php
require_once __DIR__ . '/harvest_helpers.php';
farmRun('GET', function ($pdo, $user) {
    $harvest = harvestFind($pdo, farmId($_GET['id'] ?? null), $user);
    $harvest['analysis'] = harvestAnalysis($harvest);
    farmRespond(200, $harvest);
});

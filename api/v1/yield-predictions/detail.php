<?php
require_once __DIR__ . '/yield_prediction_helpers.php';
farmRun('GET', function ($pdo, $user) {
    $prediction = yieldPredictionFind($pdo, farmId($_GET['id'] ?? null), $user);
    $prediction['method_category'] = 'historical/statistical';
    farmRespond(200, $prediction);
});

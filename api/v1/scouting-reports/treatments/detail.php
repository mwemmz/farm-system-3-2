<?php
require_once __DIR__ . '/../../pest-disease-catalog/pest_helpers.php';
farmRun('GET', function ($pdo, $user) {
    farmRespond(200, pestTreatment($pdo, farmId($_GET['id'] ?? null), $user));
});

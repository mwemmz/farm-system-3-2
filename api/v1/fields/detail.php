<?php
require_once __DIR__ . '/../farms/farm_helpers.php';
farmRun('GET', function ($pdo, $user) {
    farmRespond(200, fieldFind($pdo, farmId($_GET['id'] ?? null), $user));
});

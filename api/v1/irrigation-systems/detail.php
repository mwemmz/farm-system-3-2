<?php
require_once __DIR__ . '/irrigation_helpers.php';
farmRun('GET', function ($pdo, $user) {
    farmRespond(200, irrigationSystem($pdo, farmId($_GET['id'] ?? null), $user));
});

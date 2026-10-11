<?php
require_once __DIR__ . '/../irrigation-systems/irrigation_helpers.php';
farmRun('GET', function ($pdo, $user) {
    farmRespond(200, irrigationSchedule($pdo, farmId($_GET['id'] ?? null), $user));
});

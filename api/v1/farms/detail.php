<?php
require_once __DIR__ . '/farm_helpers.php';
farmRun('GET', function ($pdo, $user) {
    farmRespond(200, farmFind($pdo, farmId($_GET['id'] ?? null), $user));
});

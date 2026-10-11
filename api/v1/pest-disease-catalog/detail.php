<?php
require_once __DIR__ . '/pest_helpers.php';
farmRun('GET', function ($pdo, $user) {
    farmRespond(200, pestCatalog($pdo, farmId($_GET['id'] ?? null), $user));
});

<?php
require_once __DIR__ . '/irrigation_helpers.php';
farmRun('GET', function ($pdo, $user) {
    $farmId = farmId($_GET['farm_id'] ?? null, 'farm_id');
    farmFind($pdo, $farmId, $user);
    $stmt = $pdo->prepare('SELECT * FROM irrigation_systems WHERE farm_id = ? ORDER BY id DESC');
    $stmt->execute([$farmId]);
    farmRespond(200, $stmt->fetchAll());
});

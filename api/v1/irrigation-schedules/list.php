<?php
require_once __DIR__ . '/../irrigation-systems/irrigation_helpers.php';
farmRun('GET', function ($pdo, $user) {
    $systemId = farmId($_GET['irrigation_system_id'] ?? null, 'irrigation_system_id');
    irrigationSystem($pdo, $systemId, $user);
    $stmt = $pdo->prepare('SELECT * FROM irrigation_schedules WHERE irrigation_system_id = ? ORDER BY scheduled_at DESC, id DESC');
    $stmt->execute([$systemId]);
    farmRespond(200, $stmt->fetchAll());
});

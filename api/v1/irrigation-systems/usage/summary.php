<?php
require_once __DIR__ . '/../irrigation_helpers.php';
farmRun('GET', function ($pdo, $user) {
    $systemId = farmId($_GET['irrigation_system_id'] ?? null, 'irrigation_system_id');
    irrigationSystem($pdo, $systemId, $user);
    $stmt = $pdo->prepare('SELECT COUNT(*) AS usage_count, COALESCE(SUM(volume_liters),0) AS total_volume_liters, COALESCE(SUM(duration_minutes),0) AS total_duration_minutes, COALESCE(SUM(energy_kwh),0) AS total_energy_kwh, COALESCE(SUM(cost_amount),0) AS total_cost_amount, MAX(logged_at) AS latest_logged_at FROM water_usage_logs WHERE irrigation_system_id = ?');
    $stmt->execute([$systemId]);
    farmRespond(200, $stmt->fetch());
});

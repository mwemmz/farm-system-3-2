<?php
require_once __DIR__ . '/../../pest-disease-catalog/pest_helpers.php';
farmRun('GET', function ($pdo, $user) {
    $reportId = farmId($_GET['scouting_report_id'] ?? null, 'scouting_report_id');
    pestScout($pdo, $reportId, $user);
    $stmt = $pdo->prepare('SELECT * FROM treatments WHERE scouting_report_id = ? ORDER BY created_at DESC, id DESC');
    $stmt->execute([$reportId]);
    farmRespond(200, $stmt->fetchAll());
});

<?php
require_once __DIR__ . '/../pest-disease-catalog/pest_helpers.php';
farmRun('GET', function ($pdo, $user) {
    $report = pestScout($pdo, farmId($_GET['id'] ?? null), $user);
    $stmt = $pdo->prepare('SELECT recommended_control FROM pest_disease_catalog WHERE id = ?');
    $stmt->execute([$report['catalog_id']]);
    $report['catalog_recommended_control'] = $report['catalog_id'] === null ? null : $stmt->fetchColumn();
    farmRespond(200, $report);
});

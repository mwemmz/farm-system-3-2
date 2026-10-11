<?php
require_once __DIR__ . '/../pest-disease-catalog/pest_helpers.php';
farmRun('GET', function ($pdo, $user) {
    $fieldId = farmId($_GET['field_id'] ?? null, 'field_id');
    fieldFind($pdo, $fieldId, $user);
    $stmt = $pdo->prepare('SELECT * FROM scouting_reports WHERE field_id = ? ORDER BY observed_at DESC, id DESC');
    $stmt->execute([$fieldId]);
    farmRespond(200, $stmt->fetchAll());
});

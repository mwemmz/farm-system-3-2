<?php
require_once __DIR__ . '/harvest_helpers.php';
farmRun('GET', function ($pdo, $user) {
    $fieldId = farmId($_GET['field_id'] ?? null, 'field_id');
    fieldFind($pdo, $fieldId, $user);
    $stmt = $pdo->prepare('SELECT * FROM harvests WHERE field_id = ? ORDER BY COALESCE(actual_harvest_date, expected_harvest_date) DESC, id DESC');
    $stmt->execute([$fieldId]);
    farmRespond(200, $stmt->fetchAll());
});

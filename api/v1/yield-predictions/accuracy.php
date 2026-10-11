<?php
require_once __DIR__ . '/yield_prediction_helpers.php';
farmRun('GET', function ($pdo, $user) {
    $fieldId = farmId($_GET['field_id'] ?? null, 'field_id');
    fieldFind($pdo, $fieldId, $user);
    // No planting or explicit prediction-harvest link exists; pairing by crop alone is ambiguous.
    farmRespond(200, ['status'=>'explicit_comparison_required','field_id'=>$fieldId,
        'message'=>'Use compare.php with prediction_id and harvest_id; aggregate accuracy is unavailable without a reliable pair link.']);
});

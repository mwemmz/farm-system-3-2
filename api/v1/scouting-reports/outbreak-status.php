<?php
require_once __DIR__ . '/../pest-disease-catalog/pest_helpers.php';
farmRun('GET', function ($pdo, $user) {
    $farmId = farmId($_GET['farm_id'] ?? null, 'farm_id');
    farmFind($pdo, $farmId, $user);
    $stmt = $pdo->prepare("SELECT r.field_id, r.catalog_id, r.severity FROM scouting_reports r JOIN fields f ON f.id = r.field_id WHERE f.farm_id = ? AND r.status IN ('open','monitoring','treated') AND r.observed_at BETWEEN NOW() - INTERVAL 7 DAY AND NOW()");
    $stmt->execute([$farmId]);
    $reports = $stmt->fetchAll();
    $counts = ['low'=>0,'moderate'=>0,'high'=>0,'critical'=>0];
    $fields = [];
    $repeated = [];
    foreach ($reports as $report) {
        $counts[$report['severity']]++;
        $fields[$report['field_id']] = true;
        if ($report['catalog_id'] !== null && in_array($report['severity'], ['high','critical'], true)) {
            $repeated[$report['catalog_id']][$report['field_id']] = true;
        }
    }
    $reasons = [];
    if ($counts['critical'] > 0) $reasons[] = 'At least one unresolved critical report was observed in the past 7 days.';
    $repeatedCatalogIds = [];
    foreach ($repeated as $catalogId=>$affectedFields) {
        if (count($affectedFields) >= 2) $repeatedCatalogIds[] = (int)$catalogId;
    }
    if ($repeatedCatalogIds) $reasons[] = 'The same catalogue issue was reported high or critical on at least two fields in the past 7 days.';
    farmRespond(200, [
        'type'=>'rule_based_condition',
        'review_window_days'=>7,
        'possible_outbreak'=>count($reasons)>0,
        'basis'=>$reasons,
        'unresolved_recent_reports'=>count($reports),
        'counts_by_severity'=>$counts,
        'recent_high_reports'=>$counts['high'],
        'recent_critical_reports'=>$counts['critical'],
        'affected_field_ids'=>array_map('intval', array_keys($fields)),
        'repeated_catalog_ids'=>$repeatedCatalogIds
    ]);
});

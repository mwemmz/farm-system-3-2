<?php
require_once __DIR__ . '/../pest-disease-catalog/pest_helpers.php';
farmRun('POST', function ($pdo, $user) {
    $data = farmJson();
    $fieldId = farmId($data['field_id'] ?? null, 'field_id');
    $field = fieldFind($pdo, $fieldId, $user);
    $v = pestScoutValues($pdo, $data, $user, $field, true);
    $stmt = $pdo->prepare('INSERT INTO scouting_reports (field_id,catalog_id,observed_issue_name,observed_at,severity,affected_area_hectares,incidence_percent,symptoms_notes,recommended_action_notes,status,reported_by_user_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([$fieldId,$v['catalog_id'],$v['observed_issue_name'],$v['observed_at'],$v['severity'],$v['affected_area_hectares']??null,$v['incidence_percent']??null,$v['symptoms_notes']??null,$v['recommended_action_notes']??null,$v['status']??'open',$user['id']]);
    $id = (int)$pdo->lastInsertId();
    logAudit($user['id'], 'CREATE_SCOUTING_REPORT', 'scouting_reports');
    farmRespond(201, ['id'=>$id]);
});

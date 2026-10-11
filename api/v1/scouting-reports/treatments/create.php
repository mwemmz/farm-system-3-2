<?php
require_once __DIR__ . '/../../pest-disease-catalog/pest_helpers.php';
farmRun('POST', function ($pdo, $user) {
    $data = farmJson();
    $reportId = farmId($data['scouting_report_id'] ?? null, 'scouting_report_id');
    pestScout($pdo, $reportId, $user);
    $v = pestTreatmentValues($data, true);
    $stmt = $pdo->prepare('INSERT INTO treatments (scouting_report_id,treatment_type,product_or_method,active_ingredient,application_rate,application_rate_unit,quantity_used,quantity_unit,applied_at,status,effectiveness_percent,effectiveness_notes,follow_up_date,created_by_user_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([$reportId,$v['treatment_type'],$v['product_or_method'],$v['active_ingredient']??null,$v['application_rate']??null,$v['application_rate_unit']??null,$v['quantity_used']??null,$v['quantity_unit']??null,$v['applied_at']??null,$v['status']??'planned',$v['effectiveness_percent']??null,$v['effectiveness_notes']??null,$v['follow_up_date']??null,$user['id']]);
    $id = (int)$pdo->lastInsertId();
    logAudit($user['id'], 'CREATE_PEST_DISEASE_TREATMENT', 'treatments');
    farmRespond(201, ['id'=>$id]);
});

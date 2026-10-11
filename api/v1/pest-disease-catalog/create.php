<?php
require_once __DIR__ . '/pest_helpers.php';
farmRun('POST', function ($pdo, $user) {
    pestAdmin($user);
    $v = pestCatalogValues(farmJson(), true);
    pestCatalogUnique($pdo, $v['issue_type'], $v['common_name']);
    $stmt = $pdo->prepare('INSERT INTO pest_disease_catalog (issue_type,common_name,scientific_name,description,common_symptoms,recommended_control,prevention_notes,default_severity) VALUES (?,?,?,?,?,?,?,?)');
    $stmt->execute([$v['issue_type'],$v['common_name'],$v['scientific_name']??null,$v['description']??null,$v['common_symptoms']??null,$v['recommended_control']??null,$v['prevention_notes']??null,$v['default_severity']??null]);
    $id = (int)$pdo->lastInsertId();
    logAudit($user['id'], 'CREATE_PEST_DISEASE_CATALOG', 'pest_disease_catalog');
    farmRespond(201, ['id'=>$id]);
});

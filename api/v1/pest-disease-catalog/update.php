<?php
require_once __DIR__ . '/pest_helpers.php';
farmRun('PATCH', function ($pdo, $user) {
    pestAdmin($user);
    $id = farmId($_GET['id'] ?? null);
    $entry = pestCatalog($pdo, $id, $user);
    $values = pestCatalogValues(farmJson());
    pestCatalogUnique($pdo, $values['issue_type']??$entry['issue_type'], $values['common_name']??$entry['common_name'], $id);
    farmUpdateColumns($pdo, 'pest_disease_catalog', $id, $values);
    logAudit($user['id'], 'UPDATE_PEST_DISEASE_CATALOG', 'pest_disease_catalog');
    farmRespond(200, ['id'=>$id]);
});

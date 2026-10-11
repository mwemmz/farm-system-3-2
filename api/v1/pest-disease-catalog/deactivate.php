<?php
require_once __DIR__ . '/pest_helpers.php';
farmRun('POST', function ($pdo, $user) {
    pestAdmin($user);
    $id = farmId($_GET['id'] ?? null);
    pestCatalog($pdo, $id, $user);
    $stmt = $pdo->prepare("UPDATE pest_disease_catalog SET status = 'inactive' WHERE id = ?");
    $stmt->execute([$id]);
    logAudit($user['id'], 'DEACTIVATE_PEST_DISEASE_CATALOG', 'pest_disease_catalog');
    farmRespond(200, ['id'=>$id,'status'=>'inactive']);
});

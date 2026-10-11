<?php
require_once __DIR__ . '/pest_helpers.php';
farmRun('GET', function ($pdo, $user) {
    $status = $_GET['status'] ?? 'active';
    $status = pestChoice($status, 'status', ['active','inactive']);
    if ($status === 'inactive') pestAdmin($user);
    $type = null;
    if (isset($_GET['issue_type'])) $type = pestChoice($_GET['issue_type'], 'issue_type', ['pest','disease']);
    $stmt = $pdo->prepare('SELECT * FROM pest_disease_catalog WHERE status = ? AND (? IS NULL OR issue_type = ?) ORDER BY common_name, id');
    $stmt->execute([$status,$type,$type]);
    farmRespond(200, $stmt->fetchAll());
});

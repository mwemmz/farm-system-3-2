<?php
require_once __DIR__ . '/farm_helpers.php';
farmRun('GET', function ($pdo, $user) {
    if (($user['role'] ?? null) === 'admin') {
        $stmt = $pdo->query('SELECT * FROM farms ORDER BY id DESC');
    } else {
        $stmt = $pdo->prepare('SELECT * FROM farms WHERE owner_user_id = ? ORDER BY id DESC');
        $stmt->execute([$user['id']]);
    }
    farmRespond(200, $stmt->fetchAll());
});

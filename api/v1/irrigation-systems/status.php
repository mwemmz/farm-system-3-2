<?php
require_once __DIR__ . '/irrigation_helpers.php';
farmRun('GET', function ($pdo, $user) {
    $system = irrigationSystem($pdo, farmId($_GET['id'] ?? null), $user);
    $stmt = $pdo->prepare('SELECT pump_status, issue_notes, logged_at FROM water_usage_logs WHERE irrigation_system_id = ? ORDER BY logged_at DESC, id DESC LIMIT 1');
    $stmt->execute([$system['id']]);
    $latest = $stmt->fetch() ?: null;
    $flags = [];
    if ($system['status'] === 'inactive') $flags[] = 'system_inactive';
    if ($system['status'] === 'maintenance') $flags[] = 'system_maintenance';
    $pump = $latest['pump_status'] ?? $system['pump_status'];
    if (in_array($pump, ['maintenance','fault','offline'], true)) $flags[] = 'pump_' . $pump;
    if ($latest && $latest['issue_notes'] !== null && trim($latest['issue_notes']) !== '') $flags[] = 'latest_usage_issue';
    farmRespond(200, ['irrigation_system_id'=>(int)$system['id'],'flags'=>$flags,'pump_status'=>$pump,'latest_usage_at'=>$latest['logged_at']??null]);
});

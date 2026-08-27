<?php
// api/v1/weather/log.php
header('Content-Type: application/json');
require_once '../../../db.php';
require_once '../../../auth.php';

$user = verifyJWT();

$data = json_decode(file_get_contents('php://input'), true);

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("INSERT INTO weather_logs (farm_id, date, rainfall_mm, temp_c, humidity, wind_kmh) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$data['farm_id'], $data['date'], $data['rainfall_mm'], $data['temp_c'], $data['humidity'], $data['wind_kmh']]);
    
    logAudit($user['id'], 'LOG_WEATHER', 'weather_logs');
    
    echo json_encode(['success' => true, 'data' => ['id' => $pdo->lastInsertId()], 'error' => null]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'data' => null, 'error' => 'Failed to log weather']);
}
?>

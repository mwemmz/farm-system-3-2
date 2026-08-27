<?php
// api/v1/auth/register.php
header('Content-Type: application/json');
require_once '../../../db.php';
require_once '../../../auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'data' => null, 'error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['name']) || empty($data['email']) || empty($data['password']) || empty($data['role'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'data' => null, 'error' => 'Missing required fields']);
    exit;
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $data['name'],
        $data['email'],
        password_hash($data['password'], PASSWORD_DEFAULT),
        $data['role']
    ]);
    
    // Log audit
    logAudit($pdo->lastInsertId(), 'REGISTER', 'users');

    echo json_encode(['success' => true, 'data' => ['id' => $pdo->lastInsertId()], 'error' => null]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'data' => null, 'error' => 'Registration failed']);
}
?>

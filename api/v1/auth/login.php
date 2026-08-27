<?php
// api/v1/auth/login.php
header('Content-Type: application/json');
require_once '../../../db.php';
require_once '../../../auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'data' => null, 'error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['email']) || empty($data['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'data' => null, 'error' => 'Missing email or password']);
    exit;
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT id, password_hash, role FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);
    $user = $stmt->fetch();

    if ($user && password_verify($data['password'], $user['password_hash'])) {
        // TODO: Properly issue JWT
        $token = "mock-jwt-token-for-user-" . $user['id'];
        
        logAudit($user['id'], 'LOGIN', 'auth');
        
        echo json_encode(['success' => true, 'data' => ['token' => $token, 'role' => $user['role']], 'error' => null]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'data' => null, 'error' => 'Invalid credentials']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'data' => null, 'error' => 'Login failed']);
}
?>

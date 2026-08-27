<?php
// auth.php - Shared auth and audit helpers

// TODO: Replace with proper JWT library (e.g., firebase/php-jwt)
function verifyJWT() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        // Stub: accepting any token for now
        return ['id' => 1, 'role' => 'admin'];
    }
    
    http_response_code(401);
    echo json_encode(['success' => false, 'data' => null, 'error' => 'Unauthorized']);
    exit;
}

function logAudit($userId, $action, $resource) {
    require_once 'db.php';
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, resource, timestamp) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$userId, $action, $resource]);
    } catch (Exception $e) {
        // Fail silently for audit logs to not break main flow
    }
}
?>

<?php
header('Content-Type: application/json');
try {
    $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $decoded = \Firebase\JWT\JWT::decode(
        str_replace('Bearer ', '', $token),
        "c186b809ae926b7d55b0921297ebda88",
        ['HS256']
    );
    
    // Check if token has teacher role
    if ($decoded->role !== 'teacher') throw new Exception('Invalid role');
    
    echo json_encode(['valid' => true, 'user' => $decoded->user_name]);
} catch (Exception $e) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid token: ' . $e->getMessage()]);
}

// In api/validate-token.php
$decoded = JWT::decode($token, $secret, ['HS256']);

if ($decoded->role === 'student') {
    // Additional student-specific checks
    if ($decoded->ip !== $_SERVER['REMOTE_ADDR']) {
        throw new Exception('IP mismatch');
    }
    if ($decoded->room_id !== $requestedRoomId) {
        throw new Exception('Invalid room access');
    }
}
<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../vendor/autoload.php';

// 1. Validate Request
$data = json_decode(file_get_contents('php://input'), true);
$roomId = $data['room_id'] ?? '';
$studentName = $data['username'] ?? '';

if (empty($roomId) {
    http_response_code(400);
    die(json_encode(['error' => 'Room ID required']));
}

// 2. Verify Room Exists (Database check example)
$validRooms = ['math101', 'physics202']; // Replace with DB query
if (!in_array($roomId, $validRooms)) {
    http_response_code(404);
    die(json_encode(['error' => 'Invalid room ID']));
}

// 3. Generate Restricted Token
$payload = [
    'app_id' => 2096951377,
    'room_id' => $roomId,
    'user_id' => 'student_' . bin2hex(random_bytes(4)),
    'user_name' => $studentName ?: 'Student',
    'role' => 'student',
    'privileges' => [
        'screen_share' => false,
        'whiteboard_edit' => false,
        'chat' => true
    ],
    'exp' => time() + (3 * 3600) // 3-hour expiry
];

$token = \Firebase\JWT\JWT::encode(
    $payload,
    "c186b809ae926b7d55b0921297ebda88",
    'HS256'
);

// 4. Response
echo json_encode([
    'token' => $token,
    'room_id' => $roomId,
    'expires_at' => date('Y-m-d H:i:s', $payload['exp'])
]);
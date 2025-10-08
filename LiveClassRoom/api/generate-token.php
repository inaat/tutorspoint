<?php


header('Content-Type: application/json');
require_once __DIR__ . '/../../vendor/autoload.php'; // Adjust path as needed

// 1. Authenticate Teacher (Example with basic password)
$teacherUsername = $_POST['username'] ?? '';
$teacherPassword = $_POST['password'] ?? '';
$roomId = $_POST['room_id'] ?? '';

// Validate credentials (Replace with your DB check)
$validTeachers = [
    'admin' => password_hash('teach123', PASSWORD_BCRYPT)
];

if (!isset($validTeachers[$teacherUsername]) || 
    !password_verify($teacherPassword, $validTeachers[$teacherUsername])) {
    http_response_code(401);
    die(json_encode(['error' => 'Invalid teacher credentials']));
}

// 2. Generate Secure Token
$now = time();
$payload = [
    'app_id' => 2096951377,
    'room_id' => $roomId ?: 'class_' . bin2hex(random_bytes(4)),
    'user_id' => 'teacher_' . bin2hex(random_bytes(4)),
    'user_name' => $teacherUsername,
    'role' => 'teacher',
    'privileges' => [
        'screen_share' => true,
        'whiteboard_edit' => true,
        'kick_users' => true
    ],
    'exp' => $now + (8 * 3600) // 8-hour expiry
];

// Use your ServerSecret to sign
$token = \Firebase\JWT\JWT::encode(
    $payload,
    "c186b809ae926b7d55b0921297ebda88",
    'HS256'
);

// 3. Response
echo json_encode([
    'token' => $token,
    'room_id' => $payload['room_id'],
    'expires_at' => date('Y-m-d H:i:s', $payload['exp'])
]);


require_once(__DIR__ . '/../includes/db-handler.php');

header('Content-Type: application/json');

// 1. Validate Input
$data = json_decode(file_get_contents('php://input'), true);
if (empty($data['room_id'])) {
    wp_send_json_error('Room ID required', 400);
}

// 2. Generate Token
$payload = [
    'app_id' => 2096951377,
    'room_id' => sanitize_text_field($data['room_id']),
    'user_id' => 'user_' . bin2hex(random_bytes(4)),
    'user_name' => sanitize_text_field($data['username'] ?? 'Anonymous'),
    'role' => sanitize_text_field($data['role'] ?? 'student'),
    'exp' => time() + ($data['role'] === 'teacher' ? 28800 : 10800) // 8h/3h
];

$token = jwt_encode($payload); // Your JWT function

// 3. Log Analytics
LiveClassroom_Analytics::log_token([
    'token' => $token,
    'role' => $payload['role'],
    'room_id' => $payload['room_id'],
    'expires_at' => $payload['exp']
]);

// 4. Response
wp_send_json_success([
    'token' => $token,
    'room_id' => $payload['room_id'],
    'expires_at' => $payload['exp']
]);



header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// 1. Validate Input
$data = json_decode(file_get_contents('php://input'), true);
if (empty($data['room_id']) || empty($data['username'])) {
    http_response_code(400);
    die(json_encode(['error' => 'Name and Room Code required']));
}

// 2. Verify CAPTCHA (if enabled)
if (!verifyCaptcha($data['captcha_token'])) {
    http_response_code(403);
    die(json_encode(['error' => 'CAPTCHA verification failed']));
}

// 3. Generate Token
$token = generateJWT([
    'app_id' => 2096951377,
    'room_id' => $data['room_id'],
    'user_id' => 'user_' . bin2hex(random_bytes(4)),
    'user_name' => htmlspecialchars($data['username']),
    'role' => $data['role'] ?? 'student',
    'exp' => time() + (3600 * 3) // 3 hour expiry
]);

// 4. Log to wpC_liveclassroom_token_analytics
Database::logToken([
    'token' => $token,
    'role' => $data['role'] ?? 'student',
    'room_id' => $data['room_id'],
    'ip' => $_SERVER['REMOTE_ADDR'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
    'expires_at' => time() + (3600 * 3)
]);

// 5. Return Response
echo json_encode([
    'token' => $token,
    'room_id' => $data['room_id'],
    'expires_at' => time() + (3600 * 3)
]);



/*
header('Content-Type: application/json');
require_once 'vendor/autoload.php';

// Validate credentials first (add your own user authentication)
$user = $_POST['username'] ?? '';
$role = $_POST['role'] ?? 'student'; // teacher/student
$room = $_POST['room_id'] ?? '';

if (empty($user) || empty($room)) {
    die(json_encode(['error' => 'Invalid request']));
}

// Generate token (using Zego's server-side SDK)
$token = \Zego\ZegoCloud::generateToken(
    appId: 2096951377,
    serverSecret: "c186b809ae926b7d55b0921297ebda88",
    roomId: $room,
    userId: uniqid(),
    userName: $user,
    role: $role, // Different permissions
    expireIn: 3600 // 1 hour expiry
);

echo json_encode([
    'token' => $token,
    'room_id' => $room
]);*/
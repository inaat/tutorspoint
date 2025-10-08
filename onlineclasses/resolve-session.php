<?php
// ✅ Display errors during development (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

// ✅ 1. Get subject from query
$subject_name = isset($_GET['subject']) ? trim($_GET['subject']) : '';
if (!$subject_name) {
    echo json_encode(['success' => false, 'message' => '❌ Subject not specified']);
    exit;
}

// ✅ 2. Load WordPress
$wp_path = dirname(__DIR__) . '/wp-load.php';

//$wp_path = $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';
if (!file_exists($wp_path)) {
    echo json_encode(['success' => false, 'message' => '❌ wp-load.php not found']);
    exit;
}
require_once $wp_path;

global $wpdb;

// ✅ 3. Date/time
$today = current_time('Y-m-d');
$current_time = current_time('H:i:s');




// ✅ 4. Query session
$session = $wpdb->get_row("
    SELECT s.subject_id, s.SubjectName, ts.*
    FROM wpC_teacher_sessions ts
    JOIN wpC_teacher_allocated_subjects tas ON ts.teacher_allocated_subject_id = tas.teacher_allocated_subject_id
    JOIN wpC_subjects_level sl ON tas.subject_level_id = sl.subject_level_id
    JOIN wpC_subjects s ON sl.subject_Id = s.subject_id
    WHERE LOWER(s.SubjectName) = LOWER('Mathematics')
      AND ts.session_date = '2025-07-04'
      AND ts.status = 1
      AND '09:00:00' BETWEEN ts.start_time AND ts.end_time
    LIMIT 1
");


/*
$session = $wpdb->get_row($wpdb->prepare("
    SELECT s.subject_id, s.SubjectName, ts.*
   SELECT s.subject_id, s.SubjectName, ts.* FROM wpC_teacher_sessions ts JOIN wpC_teacher_allocated_subjects tas ON ts.teacher_allocated_subject_id = tas.teacher_allocated_subject_id JOIN wpC_subjects_level sl ON tas.subject_level_id = sl.subject_level_id JOIN wpC_subjects s ON sl.subject_Id = s.subject_id WHERE LOWER(s.SubjectName) = LOWER('Mathematics') AND ts.session_date = '2025-07-04' AND ts.status = 1 AND '09:00:00' BETWEEN ts.start_time AND ts.end_time LIMIT 1;
", $subject_name, $today, $current_time));
*/

if (!$session) {
    echo json_encode(['success' => false, 'message' => '❌ No active session found']);
    exit;
}

// ✅ 5. Zego config
$app_id = 2096951377;
$server_secret = 'c186b809ae926b7d55b0921297ebda88';
$room_id = 'class_' . $session->session_id;

// ✅ 6. User
if (is_user_logged_in()) {
    $user = wp_get_current_user();
    $user_id = 'user_' . $user->ID;
    $user_name = $user->display_name ?: $user->user_login;
    $role = 'student';
} else {
    $user_id = 'guest_' . rand(1000, 9999);
    $user_name = 'Guest';
    $role = 'student';
}

// ✅ 7. Token generation
$payload = [
    'app_id'       => $app_id,
    'room_id'      => $room_id,
    'user_id'      => $user_id,
    'user_name'    => $user_name,
    'privilege'    => ['login_room' => 1, 'publish_stream' => 1],
    'expire_time'  => time() + 3600,
    'create_time'  => time()
];

$json = json_encode($payload);
$hash = hash_hmac('sha256', $json, $server_secret, true);
$token = base64_encode($hash . $json);

// ✅ 8. Return response
$response = [
    'success'    => true,
    'token'      => $token,
    'room_id'    => $room_id,
    'user_id'    => $user_id,
    'user_name'  => $user_name,
    'role'       => $role
];

echo json_encode($response);
exit;
?>

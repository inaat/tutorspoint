<?php
/**
 * Zego Token server (Token04) – Tutors Point
 * Path: /api/zego_token.php
 */
declare(strict_types=1);

// Bootstrap WordPress
$root = dirname(__DIR__);
require_once $root . '/wp-load.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok'=>false,'error'=>'Method not allowed']); exit;
}
if (!is_user_logged_in()) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'error'=>'Unauthorized']); exit;
}

// === Config (define these in wp-config.php) ===
// define('ZEGO_APP_ID',  0000000000);
// define('ZEGO_SERVER_SECRET', 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
$appId  = defined('ZEGO_APP_ID') ? (int)ZEGO_APP_ID : 0;
$secret = defined('ZEGO_SERVER_SECRET') ? (string)ZEGO_SERVER_SECRET : '';

if (!$appId || !$secret) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'ZEGO credentials missing']); exit;
}

// Inputs
$userId = isset($_POST['user_id']) ? sanitize_text_field($_POST['user_id']) : '';
$roomId = isset($_POST['room_id']) ? sanitize_text_field($_POST['room_id']) : '';
$role   = isset($_POST['role'])    ? sanitize_text_field($_POST['role'])    : 'student';
$tpDev  = isset($_POST['tpDev'])   ? (int)$_POST['tpDev'] : 0;

if ($userId === '' || $roomId === '') {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'Missing user_id or room_id']); exit;
}
$role = (strtolower($role) === 'teacher') ? 'teacher' : 'student';

// Parse slot & occurrence date from roomId
$slotId = 0; $occDate = null;
$tz = wp_timezone();
if (preg_match('/^slot_(\d+)_([0-9]{8})/i', $roomId, $m)) {
  $slotId  = (int)$m[1];
  $occDate = DateTime::createFromFormat('Ymd', $m[2], $tz) ?: null;
} elseif (preg_match('/^slot_(\d+)/i', $roomId, $m)) {
  $slotId  = (int)$m[1];
}

// Lookup slot
global $wpdb;
$slot = $wpdb->get_row($wpdb->prepare(
  "SELECT teacher_id, session_date, start_time, end_time, student_id
   FROM wpC_teacher_generated_slots
   WHERE slot_id=%d LIMIT 1", $slotId
));
if (!$slot) {
  http_response_code(404);
  echo json_encode(['ok'=>false,'error'=>'Slot not found']); exit;
}

// Resolve occurrence date
$dateStr = null;
if ($occDate) {
  $dateStr = $occDate->format('Y-m-d');
} elseif (!empty($slot->session_date) && $slot->session_date !== '0000-00-00') {
  $dateStr = $slot->session_date;
}

// Build timing (used by client for UX + server checks if desired)
$startAt = $endAt = null;
if ($dateStr && $slot->start_time && $slot->end_time) {
  $startAt = new DateTime($dateStr.' '.$slot->start_time, $tz);
  $endAt   = new DateTime($dateStr.' '.$slot->end_time,   $tz);
  if ($endAt <= $startAt) { $endAt->modify('+1 hour'); }
}

// Security: role-based authorization
$current = wp_get_current_user();

// Teacher must own the slot
if ($role === 'teacher') {
  $teacher = $wpdb->get_row($wpdb->prepare(
    "SELECT teacher_id FROM wpC_teachers_main WHERE Email=%s LIMIT 1",
    $current->user_email
  ));
  if (!$teacher || (int)$teacher->teacher_id !== (int)$slot->teacher_id) {
    http_response_code(403);
    echo json_encode(['ok'=>false,'error'=>'Not your slot']); exit;
  }
} else {
  // Student must be the engaged student (be tolerant to historical WP/student ids)
  // Try student_register id first
  $sr = $wpdb->get_row($wpdb->prepare(
    "SELECT student_id FROM wpC_student_register WHERE email=%s LIMIT 1",
    $current->user_email
  ));
  $sid_reg = $sr ? (int)$sr->student_id : 0;
  $sid_wp  = (int)$current->ID;

  $isBooked = false;

  // Check slot row
  if ((int)$slot->student_id === $sid_reg || (int)$slot->student_id === $sid_wp) $isBooked = true;

  // Fallback: check student_lectures record for today’s occurrence
  if (!$isBooked && $dateStr && $slot->start_time) {
    $countLect = (int)$wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM wpC_student_lectures
       WHERE teacher_id=%d AND lecture_book_date=%s AND lecture_time=%s
         AND (student_id=%d OR student_id=%d)",
      (int)$slot->teacher_id, $dateStr, $slot->start_time, $sid_reg, $sid_wp
    ));
    $isBooked = $countLect > 0;
  }
  if (!$isBooked) {
    http_response_code(403);
    echo json_encode(['ok'=>false,'error'=>'No booking found for this session']); exit;
  }
}

// Optional: server-side time gating (keep lenient in tpDev mode)
if (!$tpDev && $startAt && $endAt) {
  $now   = new DateTime('now', $tz);

  // Teachers can always proceed; students keep a 2-min early window
  if ($role !== 'teacher') {
    $open  = (clone $startAt)->modify('-2 minutes');
    $close = (clone $endAt)->modify('+10 minutes');

    if ($now < $open) { http_response_code(423); echo json_encode(['ok'=>false,'error'=>'Too early to join']); exit; }
    if ($now > $close){ http_response_code(423); echo json_encode(['ok'=>false,'error'=>'Session ended']); exit; }
  }
}


// === Build Token04 ===
function tp_generate_token04(int $appId, string $userId, string $secret, int $ttl = 180, array $payload = []): string {
  $now = time();
  $nonce = random_int(100000, 999999);
  $data = [
    'ver'     => '04',
    'app_id'  => $appId,
    'user_id' => $userId,
    'nonce'   => $nonce,
    'ctime'   => $now,
    'expire'  => $ttl,
    'payload' => (object)$payload,
  ];
  // signature = HMAC-SHA256(app_id + user_id + nonce + ctime + expire + payload_json)
  $base = $appId . $userId . $nonce . $now . $ttl . json_encode($data['payload'], JSON_UNESCAPED_SLASHES);
  $sig  = hash_hmac('sha256', $base, $secret, true);
  $data['sig'] = base64_encode($sig);

  return '04' . base64_encode(json_encode($data, JSON_UNESCAPED_SLASHES));
}

// Short TTL (2–3 min). Client will fetch a new token on each join/refresh.
$ttl = 180; // seconds
$token = tp_generate_token04($appId, $userId, $secret, $ttl, [
  'room_id' => $roomId,
  'role'    => $role
]);

$out = [
  'ok'    => true,
  'token' => $token,
  'timing'=> [
    'start_at' => $startAt ? $startAt->format(DateTime::ATOM) : null,
    'end_at'   => $endAt ? $endAt->format(DateTime::ATOM) : null,
    'now_at'   => (new DateTime('now', $tz))->format(DateTime::ATOM),
    'minutes_until_start' => ($startAt ? max(0, (int)ceil(($startAt->getTimestamp() - time())/60)) : null),
  ],
];

echo json_encode($out);

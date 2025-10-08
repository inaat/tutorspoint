<?php
/**
 * AJAX endpoints for Zego room lifecycle + weekly slots
 * Path: wp-content/themes/astra-child/includes/tp-zego-ajax.php
 */
if (!defined('ABSPATH')) { exit; }

add_action('wp_ajax_tp_create_room',     'tp_ajax_create_room');
add_action('wp_ajax_tp_delete_room',     'tp_ajax_delete_room');
add_action('wp_ajax_tp_set_slot_active', 'tp_ajax_set_slot_active');

/* NEW: create / delete weekly session slots (no subject/level here) */
add_action('wp_ajax_tp_add_slot',        'tp_ajax_add_slot');
add_action('wp_ajax_tp_delete_session',  'tp_ajax_delete_session');

/* ---------- helpers ---------- */
function tp_current_teacher_id(): int {
  $u = wp_get_current_user();
  if (!$u || empty($u->user_email)) return 0;
  global $wpdb;
  $id = $wpdb->get_var($wpdb->prepare(
    "SELECT teacher_id FROM wpC_teachers_main WHERE Email = %s LIMIT 1",
    $u->user_email
  ));
  return (int)($id ?: 0);
}

function tp_json_ok(array $data = []) {
  wp_send_json_success($data);
}
function tp_json_err(string $msg, int $code = 400, array $data = []) {
  status_header($code);
  wp_send_json_error(array_merge(['message'=>$msg], $data));
}

function tp_norm_time_24($t) {
  $t = trim((string)$t);
  if ($t === '') return '';
  // Accept "HH:MM" or "h:mm AM/PM"
  if (preg_match('/^[0-9]{1,2}:[0-9]{2}(\:[0-9]{2})?$/', $t)) {
    $parts = explode(':', $t);
    return sprintf('%02d:%02d:00', (int)$parts[0], (int)$parts[1]);
  }
  $ts = strtotime($t);
  if ($ts === false) return '';
  return date('H:i:00', $ts);
}
function tp_label_from_times($start24, $end24) {
  $s = date('g:i A', strtotime($start24));
  $e = date('g:i A', strtotime($end24));
  return $s . ' – ' . $e;
}

/**
 * Compute next UK date for a weekday + start time (used by create_room)
 * Returns ['occYmd'=>'YYYYMMDD','occDateDash'=>'YYYY-MM-DD']
 */
function tp_next_occurrence_uk(string $day_of_week, string $start_time): array {
  $map = ['Sunday'=>0,'Monday'=>1,'Tuesday'=>2,'Wednesday'=>3,'Thursday'=>4,'Friday'=>5,'Saturday'=>6];
  $tz  = new DateTimeZone('Europe/London');
  $now = new DateTime('now', $tz);
  $dow = $map[$day_of_week] ?? (int)$now->format('w');
  $add = $dow - (int)$now->format('w');
  if ($add < 0) $add += 7;
  if ($add === 0 && $now->format('H:i:s') > $start_time) $add = 7;
  if ($add > 0) $now->modify("+{$add} days");
  return ['occYmd'=>$now->format('Ymd'), 'occDateDash'=>$now->format('Y-m-d')];
}

/* ---------- CREATE ROOM ---------- */
function tp_ajax_create_room() {
  check_ajax_referer('tp_zego_room','nonce');
  if (!is_user_logged_in()) tp_json_err('Unauthorized', 401);
  $teacher_id = tp_current_teacher_id();
  if (!$teacher_id) tp_json_err('Not a teacher', 403);

  $slot_id = isset($_POST['slot_id']) ? (int)$_POST['slot_id'] : 0;
  if (!$slot_id) tp_json_err('slot_id required');

  global $wpdb;
  $slot = $wpdb->get_row($wpdb->prepare(
    "SELECT slot_id, teacher_id, day_of_week, start_time, end_time, is_active, room_id
     FROM wpC_teacher_generated_slots WHERE slot_id=%d LIMIT 1", $slot_id));
  if (!$slot) tp_json_err('Slot not found', 404);
  if ((int)$slot->teacher_id !== $teacher_id) tp_json_err('Forbidden', 403);
  if ((int)$slot->is_active !== 1) tp_json_err('Slot is paused', 403);

  $occ = tp_next_occurrence_uk((string)$slot->day_of_week, (string)$slot->start_time);
  $room_id = (!empty($slot->room_id) && preg_match('/^slot_'.$slot_id.'_[0-9]{8}$/', $slot->room_id))
           ? $slot->room_id
           : ('slot_'.$slot_id.'_'.$occ['occYmd']);
 // $meeting_link = site_url('/liveclassroom/?roomID='.rawurlencode($room_id).'&role=teacher');
 $meeting_link = site_url('/vclassroom/?roomID='.rawurlencode($room_id).'&role=teacher');


  $wpdb->update('wpC_teacher_generated_slots',
    ['room_id'=>$room_id, 'meeting_link'=>$meeting_link],
    ['slot_id'=>$slot_id],
    ['%s','%s'], ['%d']
  );

  tp_json_ok([
    'slot_id'=>(int)$slot_id,
    'room_id'=>$room_id,
    'meeting_link'=>$meeting_link,
    'occurrence'=>$occ['occDateDash'],
    'state'=>'created'
  ]);
}

/* ---------- DELETE ROOM ---------- */
function tp_ajax_delete_room() {
  check_ajax_referer('tp_zego_room','nonce');
  if (!is_user_logged_in()) tp_json_err('Unauthorized', 401);
  $teacher_id = tp_current_teacher_id();
  if (!$teacher_id) tp_json_err('Not a teacher', 403);

  $slot_id = isset($_POST['slot_id']) ? (int)$_POST['slot_id'] : 0;
  if (!$slot_id) tp_json_err('slot_id required');

  global $wpdb;
  $own = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM wpC_teacher_generated_slots WHERE slot_id=%d AND teacher_id=%d",
    $slot_id, $teacher_id
  ));
  if (!$own) tp_json_err('Forbidden', 403);

  $wpdb->update('wpC_teacher_generated_slots',
    ['room_id'=>null,'meeting_link'=>null],
    ['slot_id'=>$slot_id],
    ['%s','%s'], ['%d']
  );
  tp_json_ok(['slot_id'=>(int)$slot_id,'state'=>'deleted']);
}

/* ---------- TOGGLE ACTIVE ---------- */
function tp_ajax_set_slot_active() {
  check_ajax_referer('tp_zego_room','nonce');
  if (!is_user_logged_in()) tp_json_err('Unauthorized', 401);
  $teacher_id = tp_current_teacher_id();
  if (!$teacher_id) tp_json_err('Not a teacher', 403);

  $slot_id = isset($_POST['slot_id']) ? (int)$_POST['slot_id'] : 0;
  $active  = isset($_POST['active'])  ? (int)$_POST['active']  : 0;
  if (!$slot_id) tp_json_err('slot_id required');

  global $wpdb;
  $own = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM wpC_teacher_generated_slots WHERE slot_id=%d AND teacher_id=%d",
    $slot_id, $teacher_id
  ));
  if (!$own) tp_json_err('Forbidden', 403);

  $wpdb->update('wpC_teacher_generated_slots',
    ['is_active'=>$active?1:0],
    ['slot_id'=>$slot_id],
    ['%d'], ['%d']
  );
  tp_json_ok(['slot_id'=>(int)$slot_id,'is_active'=>$active?1:0]);
}

/* ---------- ADD WEEKLY SLOT (NEW) ---------- */
function tp_ajax_add_slot() {
  check_ajax_referer('tp_zego_room','nonce');
  if (!is_user_logged_in()) tp_json_err('Unauthorized', 401);
  $teacher_id = tp_current_teacher_id();
  if (!$teacher_id) tp_json_err('Not a teacher', 403);

  $day = sanitize_text_field($_POST['day_of_week'] ?? '');
  $allowed = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
  if (!in_array($day, $allowed, true)) tp_json_err('Invalid day');

  $start24 = tp_norm_time_24($_POST['start_time'] ?? '');
  $end24   = tp_norm_time_24($_POST['end_time'] ?? '');
  if (!$start24 || !$end24) tp_json_err('Invalid start/end');
  if (strtotime($end24) <= strtotime($start24)) tp_json_err('End must be after start');

  global $wpdb;

  // ---- NEW: enforce next occurrence is in the future and ≥ 10 hours away ----
  $tz  = wp_timezone() ?: new DateTimeZone('Europe/London');
  $map = ['Sunday'=>0,'Monday'=>1,'Tuesday'=>2,'Wednesday'=>3,'Thursday'=>4,'Friday'=>5,'Saturday'=>6];

  $now = new DateTime('now', $tz);
  $target = new DateTime('now', $tz);
  $dow_now = (int)$now->format('w');
  $dow_dst = $map[$day];

  $add = $dow_dst - $dow_now;
  if ($add < 0) $add += 7; // upcoming instance of that weekday (this week or next)
  if ($add > 0) { $target->modify("+{$add} days"); }

  // set target start on that date at chosen time
  [$H,$M,$S] = array_pad(explode(':',$start24),3,'00');
  $target->setTime((int)$H,(int)$M,(int)$S);

  // If chosen weekday is today and chosen start time has already passed, push to next week
  if ($add === 0 && $target <= $now) {
    $target->modify('+7 days');
  }

  // Require at least 10 hours lead time
  $diffHours = ($target->getTimestamp() - $now->getTimestamp()) / 3600;
  if ($diffHours < 10) {
    tp_json_err('Start time must be at least 10 hours from now for its next occurrence.');
  }
  // ---- /NEW ----

  // per-day limit 10
  $perDay = (int)$wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM wpC_teacher_generated_slots WHERE teacher_id=%d AND day_of_week=%s",
    $teacher_id, $day
  ));
  if ($perDay >= 10) tp_json_err('Daily limit (10) reached');

  // overlap check (same weekday template)
  $rows = $wpdb->get_results($wpdb->prepare(
    "SELECT start_time,end_time FROM wpC_teacher_generated_slots
     WHERE teacher_id=%d AND day_of_week=%s", $teacher_id, $day
  ));
  $ns = strtotime($start24); $ne = strtotime($end24);
  foreach ($rows as $r) {
    $s = strtotime($r->start_time); $e = strtotime($r->end_time);
    if (!($ne <= $s || $ns >= $e)) tp_json_err('Time overlaps existing session');
  }

  // insert (weekly template row)
  $ok = $wpdb->insert('wpC_teacher_generated_slots', [
    'teacher_id'   => $teacher_id,
    'day_of_week'  => $day,
    'session_date' => '0000-00-00',
    'start_time'   => $start24,
    'end_time'     => $end24,
    'status'       => 'available',
    'is_active'    => 1,
    'room_id'      => null,
    'student_id'   => null,
    'meeting_link' => null,
    'created_at'   => current_time('mysql'),
    'updated_at'   => current_time('mysql'),
  ], ['%d','%s','%s','%s','%s','%s','%d','%s','%d','%s','%s','%s']);

  if (!$ok) tp_json_err('Insert failed');

  $slot_id = (int)$wpdb->insert_id;
  tp_json_ok([
    'slot' => [
      'slot_id'     => $slot_id,
      'day_of_week' => $day,
      'start_time'  => $start24,
      'end_time'    => $end24,
      'status'      => 'available',
      'is_active'   => 1,
      'time_label'  => tp_label_from_times($start24, $end24),
    ]
  ]);
}

/* ---------- DELETE WEEKLY SLOT (NEW) ---------- */
function tp_ajax_delete_session() {
  check_ajax_referer('tp_zego_room','nonce');
  if (!is_user_logged_in()) tp_json_err('Unauthorized', 401);
  $teacher_id = tp_current_teacher_id();
  if (!$teacher_id) tp_json_err('Not a teacher', 403);

  $slot_id = isset($_POST['slot_id']) ? (int)$_POST['slot_id'] : 0;
  if (!$slot_id) tp_json_err('slot_id required');

  global $wpdb;
  $row = $wpdb->get_row($wpdb->prepare(
    "SELECT teacher_id, status FROM wpC_teacher_generated_slots WHERE slot_id=%d LIMIT 1", $slot_id
  ));
  if (!$row) tp_json_err('Not found', 404);
  if ((int)$row->teacher_id !== $teacher_id) tp_json_err('Forbidden', 403);
  if ($row->status === 'booked') tp_json_err('Cannot delete a booked session');

  $wpdb->delete('wpC_teacher_generated_slots', ['slot_id'=>$slot_id], ['%d']);
  tp_json_ok(['slot_id'=>$slot_id]);
}

/* Minimum lead hours for next occurrence (default 3) */
function tp_slot_min_lead_hours(): int {
  $h = (int) get_option('tp_slot_min_lead_hours', 3);
  $h = (int) apply_filters('tp_slot_min_lead_hours', $h);
  if ($h < 0)   $h = 0;
  if ($h > 72)  $h = 72; // sanity cap
  return $h;
}




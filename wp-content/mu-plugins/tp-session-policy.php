<?php
/**
 * Plugin Name: TP Session Policy (Attendance + Finalize)
 * Description: Attendance pings, finalize cron, and mark-as-taught endpoint (Phase 1).
 */
if (!defined('ABSPATH')) exit;

add_action('init', function(){
  if (!wp_next_scheduled('tp_finalize_sessions')) {
    wp_schedule_event(time() + 600, 'tp_ten_minutes', 'tp_finalize_sessions');
  }
});
add_filter('cron_schedules', function($s){
  $s['tp_ten_minutes'] = ['interval'=>600, 'display'=>'Every 10 minutes'];
  return $s;
});

/* Detect wpC_ prefix or fall back to $wpdb->prefix */
function tp_prefix_detect($wpdb){
  $has = $wpdb->get_var("SHOW TABLES LIKE 'wpC_student_lectures'");
  return ($has === 'wpC_student_lectures') ? 'wpC_' : $wpdb->prefix;
}

/* --- AJAX: start attendance --- */
add_action('wp_ajax_tp_att_start', 'tp_att_start');
add_action('wp_ajax_nopriv_tp_att_start', 'tp_att_start');
function tp_att_start(){
  if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'tp_live_nonce')) {
    wp_send_json_error('Bad nonce', 403);
  }
  global $wpdb; $p = tp_prefix_detect($wpdb);
  $lecture_id = (int)($_POST['lecture_id'] ?? 0);
  $room_id    = substr(sanitize_text_field($_POST['room_id'] ?? ''), 0, 64);
  $role       = ($_POST['role'] ?? '') === 'teacher' ? 'teacher' : 'student';
  if (!$lecture_id || !$room_id) wp_send_json_error('Missing args', 400);

  $tbl = $p.'session_attendance';
  $wpdb->query($wpdb->prepare(
    "INSERT INTO {$tbl} (lecture_id, room_id, user_role, joined_at, last_seen, total_seconds)
     VALUES (%d,%s,%s,NOW(),NOW(),0)
     ON DUPLICATE KEY UPDATE last_seen=NOW()", $lecture_id, $room_id, $role
  ));
  wp_send_json_success();
}

/* --- AJAX: ping attendance (adds seconds if ping is timely) --- */
add_action('wp_ajax_tp_att_ping', 'tp_att_ping');
add_action('wp_ajax_nopriv_tp_att_ping', 'tp_att_ping');
function tp_att_ping(){
  if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'tp_live_nonce')) {
    wp_send_json_error('Bad nonce', 403);
  }
  global $wpdb; $p = tp_prefix_detect($wpdb);
  $lecture_id = (int)($_POST['lecture_id'] ?? 0);
  $room_id    = substr(sanitize_text_field($_POST['room_id'] ?? ''), 0, 64);
  $role       = ($_POST['role'] ?? '') === 'teacher' ? 'teacher' : 'student';
  if (!$lecture_id || !$room_id) wp_send_json_error('Missing args', 400);

  $tbl = $p.'session_attendance';
  // Add up to 40s per ping; ignores huge gaps/reloads
  $wpdb->query($wpdb->prepare(
    "UPDATE {$tbl}
     SET total_seconds = total_seconds + LEAST(TIMESTAMPDIFF(SECOND, last_seen, NOW()), 40),
         last_seen = NOW()
     WHERE lecture_id=%d AND room_id=%s AND user_role=%s",
    $lecture_id, $room_id, $role
  ));
  wp_send_json_success();
}

/* --- Teacher manual override: mark taught --- */
add_action('wp_ajax_tp_mark_taught', function(){
  if (!current_user_can('read')) wp_send_json_error('Auth');
  if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'tp_mark_taught_nonce')) {
    wp_send_json_error('Bad nonce', 403);
  }
  global $wpdb; $p = tp_prefix_detect($wpdb);
  $id = (int)($_POST['lecture_id'] ?? 0);
  if (!$id) wp_send_json_error('Missing id', 400);

  $wpdb->query($wpdb->prepare(
    "UPDATE {$p}student_lectures SET is_taught=1, status='completed' WHERE lecture_book_id=%d AND is_taught=0", $id
  ));
  wp_send_json_success();
});

/* --- Finalize cron: apply rules at end+grace --- */
add_action('tp_finalize_sessions', function(){
  global $wpdb; $p = tp_prefix_detect($wpdb);
  $lectures_tbl = $p.'student_lectures';
  $att_tbl      = $p.'session_attendance';

  // grace = 10 minutes
  $rows = $wpdb->get_results("
    SELECT lecture_book_id, lecture_book_date, lecture_time, duration, COALESCE(status,'booked') AS status
    FROM {$lectures_tbl}
    WHERE is_taught = 0
      AND COALESCE(status,'booked') IN ('booked')
      AND TIMESTAMP(lecture_book_date, lecture_time) + INTERVAL duration MINUTE <= NOW() - INTERVAL 10 MINUTE
    LIMIT 500
  ");
  if (!$rows) return;

  foreach ($rows as $r){
    $id = (int)$r->lecture_book_id;
    // Sum totals by role from attendance table
    $totals = $wpdb->get_results($wpdb->prepare("
      SELECT user_role, COALESCE(SUM(total_seconds),0) AS sec
      FROM {$att_tbl}
      WHERE lecture_id=%d
      GROUP BY user_role
    ", $id ), OBJECT_K);

    $t = isset($totals['teacher']) ? (int)$totals['teacher']->sec : 0;
    $s = isset($totals['student']) ? (int)$totals['student']->sec : 0;
    $overlap = min($t, $s); // approximation; exact intervals can replace this later

    $MIN_OVERLAP = 10 * 60; // 10 minutes

    if ($overlap >= $MIN_OVERLAP){
      $wpdb->query($wpdb->prepare(
        "UPDATE {$lectures_tbl}
         SET is_taught=1, status='completed'
         WHERE lecture_book_id=%d", $id
      ));
      continue;
    }
    // Decide who no-showed
    if ($t <= 0 && $s > 0){
      $wpdb->query($wpdb->prepare(
        "UPDATE {$lectures_tbl}
         SET status='teacher_no_show'
         WHERE lecture_book_id=%d", $id
      ));
    } elseif ($s <= 0 && $t > 0){
      $wpdb->query($wpdb->prepare(
        "UPDATE {$lectures_tbl}
         SET status='student_no_show'
         WHERE lecture_book_id=%d", $id
      ));
    } else {
      $wpdb->query($wpdb->prepare(
        "UPDATE {$lectures_tbl}
         SET status='no_show'
         WHERE lecture_book_id=%d", $id
      ));
    }
  }
});

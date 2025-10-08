<?php
/**
 * Teacher Dashboard → Dashboard tab (dynamic, no hardcoding)
 */

if (!defined('ABSPATH')) exit;

global $wpdb;
$current_user = wp_get_current_user();

/** 1) Resolve teacher_id from user email */
$teacher = $wpdb->get_row(
  $wpdb->prepare(
    "SELECT teacher_id, FullName FROM wpC_teachers_main WHERE Email = %s LIMIT 1",
    $current_user->user_email
  )
);
$teacher_id = $teacher ? (int)$teacher->teacher_id : 0;
if (!$teacher_id) {
  echo '<p>No teacher profile found for your account.</p>';
  return;
}

/** 2) Name fallback for greeting */
$first_name = get_user_meta($current_user->ID, 'first_name', true);
$last_name  = get_user_meta($current_user->ID, 'last_name', true);
$name = trim($first_name . ' ' . $last_name);
if ($name === '') {
  $name = $current_user->display_name ?: $current_user->user_login;
}

/** 3) "Today" in WP timezone */
$today_ts = current_time('timestamp');        // unix ts in site tz
$today    = date_i18n('Y-m-d',  $today_ts);   // e.g. 2025-09-23
$weekday  = date_i18n('l',      $today_ts);   // e.g. Tuesday

/** 4) Stats (upcoming = from today forward, in student_lectures only) */
$total_sessions = (int) $wpdb->get_var(
  $wpdb->prepare("
    SELECT COUNT(*)
    FROM wpC_student_lectures
    WHERE teacher_id = %d
      AND status = 'booked'
      AND lecture_book_date >= %s
  ", $teacher_id, $today)
);

$total_students = (int) $wpdb->get_var(
  $wpdb->prepare("
    SELECT COUNT(DISTINCT student_id)
    FROM wpC_student_lectures
    WHERE teacher_id = %d
      AND status = 'booked'
      AND lecture_book_date >= %s
  ", $teacher_id, $today)
);

$total_hours = (float) $wpdb->get_var(
  $wpdb->prepare("
    SELECT SUM(duration)
    FROM wpC_student_lectures
    WHERE teacher_id = %d
      AND is_taught = 1
  ", $teacher_id)
);

$unseen_sessions = (int) $wpdb->get_var(
  $wpdb->prepare("
    SELECT COUNT(*)
    FROM wpC_student_lectures
    WHERE teacher_id = %d
      AND status = 'booked'
      AND lecture_book_date >= %s
      AND seen_by_teacher = 0
  ", $teacher_id, $today)
);

/** 5) Today’s sessions: UNION of lectures + generated slots (booked/engaged) */
$sql = $wpdb->prepare("
  /* A) Booked/engaged lecture rows that are *dated* today */
  SELECT
    sl.lecture_book_id                 AS ref_id,
    CAST('lecture' AS CHAR)            AS ref_type,
    COALESCE(sl.topic,'')              AS topic,
    sl.lecture_time                    AS lecture_time,
    sl.lecture_book_date               AS lecture_book_date,
    COALESCE(sr.full_name, CONCAT('Student #', sl.student_id)) AS student_name,
    COALESCE(s.SubjectName,'-')        AS subject_name,
    sl.status                          AS status_name,
    NULL                               AS room_id
  FROM wpC_student_lectures sl
  LEFT JOIN wpC_student_register sr ON sr.student_id = sl.student_id
  LEFT JOIN wpC_subjects        s  ON s.subject_id   = sl.subject_id
  WHERE sl.teacher_id = %d
    AND sl.status IN ('booked','engaged')
    AND sl.lecture_book_date = %s

  UNION ALL

  /* B) Generated slots marked booked/engaged *today*, including recurring */
  SELECT
    tgs.slot_id                        AS ref_id,
    CAST('slot' AS CHAR)               AS ref_type,
    ''                                 AS topic,
    tgs.start_time                     AS lecture_time,
    CASE
      WHEN tgs.session_date IS NULL OR tgs.session_date = '0000-00-00' THEN %s
      ELSE tgs.session_date
    END                                AS lecture_book_date,
    COALESCE(sr2.full_name, CONCAT('Student #', tgs.student_id)) AS student_name,
    COALESCE(s2.SubjectName,'-')       AS subject_name,
    tgs.status                         AS status_name,
    tgs.room_id                        AS room_id
  FROM wpC_teacher_generated_slots tgs
  LEFT JOIN wpC_student_register sr2 ON sr2.student_id = tgs.student_id
  LEFT JOIN wpC_subjects        s2  ON s2.subject_id   = tgs.subject_id
  WHERE tgs.teacher_id = %d
    AND tgs.status IN ('engaged','booked')
    AND (
          tgs.session_date = %s
       OR ((tgs.session_date IS NULL OR tgs.session_date = '0000-00-00') AND tgs.day_of_week = %s)
        )
    /* Avoid double listing if a lecture row exists at the same time today */
    AND NOT EXISTS (
      SELECT 1
      FROM wpC_student_lectures sl2
      WHERE sl2.teacher_id = %d
        AND sl2.status IN ('booked','engaged')
        AND sl2.lecture_book_date = %s
        AND sl2.lecture_time      = tgs.start_time
    )

  ORDER BY subject_name, lecture_time
", $teacher_id, $today, $today, $teacher_id, $today, $weekday, $teacher_id, $today);

$today_rows = $wpdb->get_results($sql);

/** 6) Group rows by subject for the UI */
$grouped = [];
if ($today_rows) {
  foreach ($today_rows as $r) {
    $sub = $r->subject_name ?: '-';
    $grouped[$sub][] = $r;
  }
}

/** (Optional) Debug in HTML comments for admins
if ( current_user_can('manage_options') ) {
  echo '<!-- teacher_id=' . esc_html($teacher_id) .
       ' today=' . esc_html($today) .
       ' weekday=' . esc_html($weekday) .
       ' rows=' . (int)count($today_rows) . ' -->';
  if ($wpdb->last_error) echo '<!-- SQL: ' . esc_html($wpdb->last_error) . ' -->';
}
*/
?>

<h5>👋 Welcome back, <?= esc_html($name); ?>!</h5>

<div class="summary-cards">
  <div class="summary-card" onclick="window.dispatchEvent(new CustomEvent('tp-switch-tab',{detail:'booked'}));">
    <div class="card-icon">📘</div>
    <div class="card-content">
      <h5>Upcoming Booked Sessions
        <?php if ($unseen_sessions > 0): ?>
          <span class="notify"><?= esc_html($unseen_sessions) ?></span>
        <?php endif; ?>
      </h5>
      <p><?= esc_html($total_sessions) ?></p>
    </div>
  </div>

  <div class="summary-card">
    <div class="card-icon">🎓</div>
    <div class="card-content"><h5>Total Students</h5><p><?= esc_html($total_students) ?></p></div>
  </div>

  <div class="summary-card">
    <div class="card-icon">⏱️</div>
    <div class="card-content"><h5>Hours Taught</h5><p><?= esc_html($total_hours ?: 0) ?></p></div>
  </div>
</div>

<!-- Today’s Schedule -->
<div class="tp-today-schedule">
  <h5>📅 Today’s Schedule(s) - <?= esc_html( date_i18n('l, M j', $today_ts) ) ?></h5>

  <?php if (!empty($grouped)): ?>
    <?php foreach ($grouped as $subject => $rows): ?>
      <?php $slug = sanitize_title($subject ?: 'subject'); ?>
      <div class="subject-schedule">
        <button onclick="toggleSchedule('<?= esc_attr($slug) ?>')">＋ <?= esc_html($subject) ?></button>

        <table class="schedule-table hidden" id="sched-<?= esc_attr($slug) ?>">
          <thead>
            <tr>
              <th>Subject</th>
              <th>Topic</th>
              <th>Date</th>
              <th>Time</th>
              <th>Student</th>
              <th>Join Class</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $s): ?>
              <tr>
                <td><?= esc_html($subject) ?></td>
                <td><?= esc_html($s->topic) ?></td>
                <td><?= esc_html( date_i18n('M j', strtotime($s->lecture_book_date)) ) ?></td>
                <td><?= esc_html( date_i18n('g:i A', strtotime($s->lecture_time)) ) ?></td>
                <td><?= esc_html($s->student_name) ?></td>
                <td>
                  <?php
                  // Join link only for slot rows that already have a room_id
                  if ($s->ref_type === 'slot' && !empty($s->room_id)) {
                    $join = site_url('/vclassroom/?roomID=' . rawurlencode((string)$s->room_id) . '&role=teacher');
                    echo '<a class="join-btn" href="' . esc_url($join) . '">Join</a>';
                  } else {
                    echo '<span style="opacity:.6;">—</span>';
                  }
                  ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <p>No lessons scheduled for today.</p>
  <?php endif; ?>
</div>

<!-- Keep your original styles exactly as you had them -->
<style>
.summary-cards { display:flex; flex-wrap:wrap; gap:15px; margin-bottom:30px; }
.summary-card { flex:1 1 200px; background:#ADEED9; padding:15px; border-radius:10px; text-align:center; box-shadow:0 2px 4px rgba(0,0,0,0.1); cursor:pointer; position:relative; }
.card-icon { font-size:30px; margin-bottom:10px; }
.notify { background:red; color:#fff; font-size:10px; padding:2px 6px; border-radius:12px; position:absolute; top:8px; right:20px; }
.tp-schedule-list,.schedule-table { width:100%; border-collapse:collapse; margin-top:10px; }
.schedule-table th,.schedule-table td { border:1px solid #ddd; padding:8px; text-align:center; }
.subject-schedule { margin-bottom:20px; }
.schedule-table.hidden { display:none; }
.join-btn { background:#0abab5; color:#fff; border:none; padding:5px 10px; border-radius:5px; }
</style>

<script>
function toggleSchedule(slug){
  const t = document.getElementById('sched-' + slug);
  if (t) t.classList.toggle('hidden');
}
</script>

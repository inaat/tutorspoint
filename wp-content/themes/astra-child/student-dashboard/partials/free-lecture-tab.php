<?php
/**
 * Student Dashboard – Free Lecture tab (table view)
 * Path: astra-child/student-dashboard/partials/free-lecture-tab.php
 */
if (!defined('ABSPATH')) exit;

global $wpdb;

/* ---------------------------------------------
 * Helpers
 * -------------------------------------------*/

/** DB prefix detector (handles wpC_ vs site prefix) */
if (!function_exists('tp_sd_prefix')) {
  function tp_sd_prefix($wpdb){
    $has = $wpdb->get_var("SHOW TABLES LIKE 'wpC_student_lectures'");
    return ($has === 'wpC_student_lectures') ? 'wpC_' : $wpdb->prefix;
  }
}

/** Admin-configurable “join unlock window” (minutes before start) */
if (!function_exists('tp_join_lead_minutes')) {
  function tp_join_lead_minutes(){
    // 1) Option (Dashboard → Options table), default 2
    $min = (int) get_option('tp_join_lead_minutes', 600);
    // 2) Allow theme/plugin to override
    $min = (int) apply_filters('tp_join_lead_minutes', $min);
    // Bound it a bit for safety
    if ($min < 0)   $min = 0;
    if ($min > 600) $min = 600;
    return $min;
  }
}

/** Timestamp from date+time in site TZ */
if (!function_exists('tp_dt_ts')) {
  function tp_dt_ts($date, $time, DateTimeZone $tz){
    try { $dt = new DateTime(trim($date.' '.$time), $tz); return $dt->getTimestamp(); }
    catch (Exception $e) { return 0; }
  }
}

$P = tp_sd_prefix($wpdb);

/* ---------------------------------------------
 * Resolve the student's FK used by student_lectures (student_register.student_id)
 * -------------------------------------------*/
$current_user   = wp_get_current_user();
$student_email  = $current_user ? (string)$current_user->user_email : '';
$studRegTable   = $P.'student_register';
$student_reg_id = 0;

if ($student_email !== '') {
  $student_reg_id = (int)$wpdb->get_var(
    $wpdb->prepare("SELECT student_id FROM {$studRegTable} WHERE email=%s LIMIT 1", $student_email)
  );
}

/* No register row? */
if (!$student_reg_id){
  echo '<div class="flt-card"><div class="flt-head"><h2>Free Lectures</h2></div><p>No free lectures yet.</p></div>';
  return;
}

/* ---------------------------------------------
 * Fetch bookings (FREE)
 * -------------------------------------------*/
$tz = wp_timezone() ?: new DateTimeZone('Europe/London');
$join_lead_min = tp_join_lead_minutes();

$sql = $wpdb->prepare("
  SELECT
    sl.lecture_book_id,
    sl.teacher_id,
    sl.subject_id,
    sl.topic,
    sl.lecture_book_date,
    sl.lecture_time,
    sl.duration,
    sl.status,
    sl.is_taught,
    sl.is_paid,
    t.FullName      AS teacher_name,
    sub.SubjectName AS subject_name,
    s.room_id,
    s.meeting_link
  FROM {$P}student_lectures sl
  JOIN {$P}teachers_main t
    ON t.teacher_id = sl.teacher_id
  LEFT JOIN {$P}subjects sub
    ON sub.subject_id = sl.subject_id
  /* match a room (if teacher created one) */
  LEFT JOIN {$P}teacher_generated_slots s
    ON s.teacher_id = sl.teacher_id
   AND s.student_id = sl.student_id
   AND s.start_time = sl.lecture_time
   AND (
        s.session_date = sl.lecture_book_date
        OR (s.session_date = '0000-00-00' AND s.day_of_week = DAYNAME(sl.lecture_book_date))
       )
  WHERE sl.student_id = %d
    AND (
          sl.is_paid = 0            /* numeric schema: 0 = free */
          OR sl.is_paid = 'free'    /* legacy string schema */
          OR sl.discount_rate = 100 /* 100% off marked free */
          OR sl.final_price = 0     /* price zero treated free */
        )
  ORDER BY sl.lecture_book_date DESC, sl.lecture_time DESC
  LIMIT 300
", $student_reg_id);

$rows = $wpdb->get_results($sql);

/* ---------------------------------------------
 * View
 * -------------------------------------------*/
$lead_text = $join_lead_min === 1 ? '1 minute' : $join_lead_min.' minutes';
?>
<div class="flt-card">
  <div class="flt-head">
    <h2>Free Lectures</h2>
    <p class="flt-sub">Your free bookings, newest first. “Join” activates <?php echo esc_html($lead_text); ?> before start.</p>
  </div>

  <?php if (!$rows): ?>
    <p>No free lectures yet.</p>
  <?php else: ?>
    <div class="flt-table-wrap">
      <table class="flt-table" data-joinlead="<?php echo (int)$join_lead_min; ?>">
        <thead>
          <tr>
            <th>Sr.No.</th>
            <th>Teacher Name</th>
            <th>Subject Name</th>
            <th>Subject Topic</th>
            <th>Date</th>
            <th>Time</th>
            <th>Join</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $i = 1;
        $now_ts = (new DateTime('now', $tz))->getTimestamp();

        foreach ($rows as $r):
          $start_ts = tp_dt_ts($r->lecture_book_date, $r->lecture_time, $tz);
          $end_ts   = $start_ts + max(1, (int)$r->duration) * 60;
          $window_opens = $start_ts - ($join_lead_min * 60);

          // Status bubble
          if ((int)$r->is_taught === 1) {
            $status = 'Taken';
          } elseif ($now_ts >= $end_ts) {
            $status = 'Missed';
          } else {
            $status = 'Available';
          }

          // Prefer explicit meeting_link; else build from room_id
          $join_href = '';
          $room_id   = '';
          if (!empty($r->meeting_link)) {
            $join_href = $r->meeting_link;
          }
          if (!$join_href && !empty($r->room_id)) {
            $join_href = site_url('/liveclassroom/?roomID='.rawurlencode($r->room_id).'&role=student');
            $room_id   = (string)$r->room_id;
          } elseif (!empty($r->room_id)) {
            $room_id   = (string)$r->room_id;
          }

          $join_enabled = ($status === 'Available') && ($now_ts >= $window_opens) && ($now_ts < $end_ts);
          ?>
          <tr
            data-start="<?php echo esc_attr($start_ts); ?>"
            data-end="<?php echo esc_attr($end_ts); ?>"
            data-status="<?php echo esc_attr($status); ?>"
          >
            <td><?php echo (int)$i++; ?></td>
            <td><?php echo esc_html($r->teacher_name ?: '—'); ?></td>
            <td><?php echo esc_html($r->subject_name ?: '—'); ?></td>
            <td class="flt-topic"><?php echo esc_html(trim($r->topic) !== '' ? $r->topic : '—'); ?></td>
            <td><?php echo esc_html( date_i18n('D, M j, Y', $start_ts) ); ?></td>
            <td><?php echo esc_html( date_i18n('g:i A', $start_ts) ); ?></td>
            <td>
              <?php if ($join_href): ?>
                <a
                  class="flt-btn <?php echo $join_enabled ? '' : 'is-disabled'; ?>"
                  href="<?php echo $join_enabled ? esc_url($join_href) : '#'; ?>"
                  data-href="<?php echo esc_url($join_href); ?>"
                  data-role="student"
                  <?php if ($room_id !== ''): ?>
                    data-roomid="<?php echo esc_attr($room_id); ?>"
                  <?php endif; ?>
                  aria-disabled="<?php echo $join_enabled ? 'false' : 'true'; ?>"
                >Join</a>
              <?php else: ?>
                <span class="flt-note">Room pending</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="flt-chip <?php
                echo ($status==='Available') ? 'c-available' : (($status==='Taken') ? 'c-taken' : 'c-missed');
              ?>"><?php echo esc_html($status); ?></span>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<style>
.flt-card{background:#fff;border:1px solid #e6ecf1;border-radius:14px;padding:16px;box-shadow:0 8px 24px rgba(2,8,23,.06)}
.flt-head{display:flex;justify-content:space-between;align-items:end;gap:10px;flex-wrap:wrap;margin-bottom:10px}
.flt-head h2{margin:0;font-size:24px}
.flt-sub{margin:0;color:#64748b}
.flt-table-wrap{overflow:auto}
.flt-table{width:100%;border-collapse:separate;border-spacing:0}
.flt-table th,.flt-table td{padding:10px 12px;border-bottom:1px solid #eef3f8;text-align:left;vertical-align:middle}
.flt-table thead th{font-weight:700;color:#0f172a;background:#f8fafc;position:sticky;top:0;z-index:1}
.flt-topic{max-width:360px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.flt-btn{display:inline-flex;align-items:center;justify-content:center;padding:8px 12px;border-radius:10px;background:#0ea5e9;color:#fff;text-decoration:none;font-weight:600}
.flt-btn.is-disabled{pointer-events:none;opacity:.45;filter:grayscale(0.2)}
.flt-note{color:#94a3b8;font-size:12px}
.flt-chip{border-radius:999px;padding:6px 10px;font-size:12px}
.c-available{background:#eafff7;color:#0a9b60}
.c-taken{background:#eef2ff;color:#3730a3}
.c-missed{background:#fff1f2;color:#be123c}
@media(max-width:720px){.flt-topic{max-width:180px}}
</style>

<script>
/* Join unlock logic (client-side refresh) */
(function(){
  const table = document.querySelector('table.flt-table');
  if(!table) return;

  const leadMin = parseInt(table.getAttribute('data-joinlead') || '2', 10);
  const leadSec = isNaN(leadMin) ? 120 : Math.max(0, leadMin) * 60;

  // Cache original hrefs once
  document.querySelectorAll('.flt-btn').forEach(b=>{
    const h = b.getAttribute('href');
    if (!b.dataset.href && h && h !== '#') b.dataset.href = h;
  });

  function tick(){
    const now = Math.floor(Date.now()/1000);
    table.querySelectorAll('tbody tr').forEach(tr=>{
      const st = parseInt(tr.getAttribute('data-start') || '0', 10);
      const en = parseInt(tr.getAttribute('data-end')   || '0', 10);
      const status = (tr.getAttribute('data-status') || '').trim();
      const btn = tr.querySelector('.flt-btn'); if (!btn) return;

      const enable = (status === 'Available') && now >= (st - leadSec) && now < en;
      if (enable){
        btn.classList.remove('is-disabled');
        btn.setAttribute('href', btn.dataset.href || '#');
        btn.setAttribute('aria-disabled','false');
      } else {
        btn.classList.add('is-disabled');
        btn.setAttribute('href', '#');
        btn.setAttribute('aria-disabled','true');
      }
    });
  }

  tick();
  setInterval(tick, 15000);
})();
</script>

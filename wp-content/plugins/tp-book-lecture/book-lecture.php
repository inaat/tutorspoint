<?php
/**
 * Plugin Name: TP – Book Lecture
 * Description: Book upcoming teacher time slots by teacher_id, subject_id, level_id with secure student sign-up/login and booking flow.
 * Version:     1.0.0
 */

if (!defined('ABSPATH')) { exit; }

/* ----------------------------------------------------------------------------
 * CONFIG (adjust names if your schema differs)
 * ------------------------------------------------------------------------- */
const TP_SLOTS_TABLE           = 'wpC_teacher_generated_slots'; // has slot_id, teacher_id, day_of_week, session_date (Y-m-d), start_time, end_time, status, room_id, is_active
const TP_TEACHERS_TABLE        = 'wpC_teachers_main';           // has teacher_id, DisplayName, HourlyRate (if present)
const TP_STUDENT_REGISTER      = 'wpC_student_register';        // has (id?), fullname, email, password
const TP_BOOKINGS_TABLE        = 'wpC_slot_bookings';           // we'll create if not exists
const TP_DISCOUNT_FACTOR       = 0.50;                          // 50% for lectures 2–6 (after the free first)

/* ----------------------------------------------------------------------------
 * Helpers
 * ------------------------------------------------------------------------- */
function tp_now_london(): DateTime {
  $tz = new DateTimeZone('Europe/London');
  return new DateTime('now', $tz);
}

function tp_next_room_id_for_slot(array $slot): string {
  // If slot already has a room_id, reuse it
  if (!empty($slot['room_id'])) return (string)$slot['room_id'];
  // Else make deterministic based on slot + occurrence date
  $ymd = !empty($slot['session_date']) ? str_replace('-', '', $slot['session_date']) : date('Ymd');
  return 'slot_' . (int)$slot['slot_id'] . '_' . $ymd;
}

function tp_read_teacher($teacher_id) {
  global $wpdb;
  $row = $wpdb->get_row(
    $wpdb->prepare("SELECT * FROM " . TP_TEACHERS_TABLE . " WHERE teacher_id = %d", (int)$teacher_id),
    ARRAY_A
  );
  return $row ?: null;
}

function tp_teacher_rate($teacher_row): float {
  // Try common names; default £0 if not known
  foreach (['HourlyRate','rate','Rate','per_hour','hourly_rate'] as $k) {
    if (isset($teacher_row[$k]) && is_numeric($teacher_row[$k])) return (float)$teacher_row[$k];
  }
  return 0.0;
}

function tp_create_bookings_table_if_missing() {
  global $wpdb;
  $table = TP_BOOKINGS_TABLE;
  $charset = $wpdb->get_charset_collate();
  $sql = "CREATE TABLE IF NOT EXISTS `$table` (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    slot_id BIGINT UNSIGNED NOT NULL,
    teacher_id BIGINT UNSIGNED NOT NULL,
    student_wp_id BIGINT UNSIGNED NOT NULL,
    student_name VARCHAR(191) NOT NULL,
    student_email VARCHAR(191) NOT NULL,
    subject_id BIGINT UNSIGNED NULL,
    level_id BIGINT UNSIGNED NULL,
    session_date DATE NULL,
    start_time TIME NULL,
    end_time TIME NULL,
    room_id VARCHAR(191) NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_slot (slot_id),
    KEY idx_teacher_student (teacher_id, student_wp_id),
    KEY idx_date (session_date)
  ) $charset;";
  require_once ABSPATH . 'wp-admin/includes/upgrade.php';
  dbDelta($sql);
}

/* ----------------------------------------------------------------------------
 * Shortcode: [tp_book_lecture]
 * ------------------------------------------------------------------------- */
add_shortcode('tp_book_lecture', function () {
  if (!isset($_GET['teacher_id'], $_GET['subject_id'], $_GET['level_id'])) {
    return '<p>Please open this page with a teacher, subject and level selected.</p>';
  }

  global $wpdb;
  $teacher_id = (int)$_GET['teacher_id'];
  $subject_id = (int)$_GET['subject_id'];
  $level_id   = (int)$_GET['level_id'];

  // Fetch teacher (for name/rate card)
  $teacher = tp_read_teacher($teacher_id);
  if (!$teacher) {
    return '<p>Teacher not found.</p>';
  }

  // Upcoming, active, available slots for this teacher only
  // NOTE: We require session_date to be set; if your data uses weekly templates,
  //       populate session_date ahead of time (e.g., a 2–4 week rolling window).
  $today = tp_now_london()->format('Y-m-d');
  $timeNow = tp_now_london()->format('H:i:s');

  $slots = $wpdb->get_results($wpdb->prepare("
    SELECT slot_id, teacher_id, day_of_week, session_date, start_time, end_time, status, room_id, IFNULL(is_active,1) as is_active
    FROM " . TP_SLOTS_TABLE . "
    WHERE teacher_id = %d
      AND IFNULL(is_active,1) = 1
      AND (status IS NULL OR status = '' OR status = 'available')
      AND session_date IS NOT NULL
      AND session_date >= %s
    ORDER BY session_date ASC, start_time ASC
    LIMIT 150
  ", $teacher_id, $today), ARRAY_A);

  // Filter out "ongoing/past" slots on the current date (don't show if start_time <= now)
  $slots = array_values(array_filter($slots, function($s) use ($today, $timeNow) {
    if ($s['session_date'] !== $today) return true;
    return strcmp($s['start_time'], $timeNow) > 0;
  }));

  // Nonce for AJAX
  $nonce = wp_create_nonce('tp_book_lecture');

  ob_start();
  ?>
  <div id="tp-book-lecture" class="tpbl-wrap">
    <div class="tpbl-left">
      <div class="tpbl-card">
        <div class="tpbl-teacher-name">
          <?= esc_html($teacher['DisplayName'] ?? 'Teacher #'.$teacher_id) ?>
        </div>
        <div class="tpbl-teacher-meta">
          <span>Teacher ID: <?= (int)$teacher_id ?></span>
          <span>Subject ID: <?= (int)$subject_id ?></span>
          <span>Level ID: <?= (int)$level_id ?></span>
        </div>
        <div class="tpbl-rate">
          Rate: <strong>£<?= number_format(tp_teacher_rate($teacher), 2) ?>/hr</strong>
        </div>
        <div class="tpbl-note">1st lecture free • Next 5 at <?= (int)(TP_DISCOUNT_FACTOR*100) ?>% rate</div>
      </div>
      <div class="tpbl-topic">
        <label for="tpbl-topic-input">Topic</label>
        <input id="tpbl-topic-input" type="text" placeholder="What would you like to cover? e.g., Stoichiometry basics">
      </div>
    </div>

    <div class="tpbl-right">
      <h3>Available slots</h3>
      <?php if (!$slots): ?>
        <div class="tpbl-empty">No upcoming slots for this teacher, subject and level.</div>
      <?php else: ?>
        <div class="tpbl-slots">
          <?php foreach ($slots as $s):
            $dateLabel = date_i18n('D, j M Y', strtotime($s['session_date']));
            $timeLabel = date_i18n('g:i A', strtotime($s['start_time'])) . ' – ' . date_i18n('g:i A', strtotime($s['end_time']));
            $roomId = tp_next_room_id_for_slot($s);
          ?>
          <div class="tpbl-slot" id="slot-<?= (int)$s['slot_id'] ?>">
            <div class="tpbl-slot-time">
              <div class="tpbl-date"><?= esc_html($dateLabel) ?></div>
              <div class="tpbl-time"><?= esc_html($timeLabel) ?></div>
            </div>
            <div class="tpbl-slot-actions">
              <span class="tpbl-badge tpbl-available">Available</span>
              <button class="tpbl-engage"
                      data-slot="<?= (int)$s['slot_id'] ?>"
                      data-date="<?= esc_attr($s['session_date']) ?>"
                      data-start="<?= esc_attr($s['start_time']) ?>"
                      data-end="<?= esc_attr($s['end_time']) ?>"
                      data-room="<?= esc_attr($roomId) ?>">Engage</button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Sign-up modal (only shown for non-logged users on Engage) -->
  <div id="tpbl-modal" class="tpbl-modal" style="display:none;">
    <div class="tpbl-modal-dialog">
      <div class="tpbl-modal-head">
        <strong>Create your student account</strong>
        <button class="tpbl-close" aria-label="Close" onclick="TPBook.closeModal()">×</button>
      </div>
      <div class="tpbl-modal-body">
        <div class="tpbl-row">
          <label>Full name</label>
          <input id="tpbl-name" type="text" placeholder="Your name">
        </div>
        <div class="tpbl-row">
          <label>Email</label>
          <input id="tpbl-email" type="email" placeholder="you@example.com">
        </div>
        <div class="tpbl-row">
          <label>Password</label>
          <input id="tpbl-pass" type="password" placeholder="Choose a password">
        </div>
        <button id="tpbl-submit" class="tpbl-primary">Create & Continue</button>
      </div>
    </div>
  </div>

  <style>
    .tpbl-wrap{display:grid;grid-template-columns:1fr 1fr;gap:22px}
    .tpbl-card{background:#fff;border:1px solid #e7e7e7;border-radius:12px;padding:16px}
    .tpbl-teacher-name{font-size:20px;font-weight:700;margin-bottom:6px}
    .tpbl-teacher-meta span{display:inline-block;margin-right:10px;color:#667;}
    .tpbl-rate{margin-top:10px}
    .tpbl-note{margin-top:4px;color:#068a80;font-weight:600}
    .tpbl-topic{margin-top:16px}
    .tpbl-topic input{width:100%;padding:10px;border-radius:8px;border:1px solid #ccd;}
    .tpbl-right h3{margin:0 0 8px}
    .tpbl-empty{background:#fafbfc;border:1px dashed #ccd;padding:16px;border-radius:10px}
    .tpbl-slots{display:flex;flex-direction:column;gap:10px}
    .tpbl-slot{display:flex;justify-content:space-between;align-items:center;border:1px solid #e7e7e7;border-radius:12px;padding:10px 14px;background:#fff}
    .tpbl-date{font-weight:700}
    .tpbl-badge{display:inline-block;padding:6px 10px;border-radius:999px;font-weight:600}
    .tpbl-available{background:#e9f8ec;color:#137a13;border:1px solid #bde5c1}
    .tpbl-engage{background:#0ABAB5;color:#fff;border:none;border-radius:10px;padding:8px 14px}
    /* Modal */
    .tpbl-modal{position:fixed;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;z-index:99999}
    .tpbl-modal-dialog{background:#fff;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.2);width:min(520px,94vw);overflow:hidden}
    .tpbl-modal-head{display:flex;justify-content:space-between;align-items:center;padding:12px 14px;background:#f7f9fb;border-bottom:1px solid #e7e7e7}
    .tpbl-close{background:#e74c3c;color:#fff;border:none;width:36px;height:36px;border-radius:10px}
    .tpbl-modal-body{padding:14px}
    .tpbl-row{margin-bottom:10px}
    .tpbl-row input{width:100%;padding:10px;border-radius:8px;border:1px solid #ccd}
    .tpbl-primary{background:#0ABAB5;color:#fff;border:none;border-radius:10px;padding:10px 14px;width:100%}
    @media(max-width:900px){.tpbl-wrap{grid-template-columns:1fr}}
  </style>

  <script>
  (function(){
    const AJAX  = <?= json_encode(admin_url('admin-ajax.php')) ?>;
    const NONCE = <?= json_encode($nonce) ?>;
    const TEACHER_ID = <?= (int)$teacher_id ?>;
    const SUBJECT_ID = <?= (int)$subject_id ?>;
    const LEVEL_ID   = <?= (int)$level_id ?>;
    const IS_LOGGED  = <?= is_user_logged_in() ? 'true' : 'false' ?>;

    function qs(id){ return document.getElementById(id); }

    const TPBook = {
      pending: null,
      openModal(){
        document.getElementById('tpbl-modal').style.display='flex';
      },
      closeModal(){
        document.getElementById('tpbl-modal').style.display='none';
      },
      engageClick(btn){
        const slotId = btn.dataset.slot;
        TPBook.pending = {
          slot_id: slotId,
          session_date: btn.dataset.date,
          start_time: btn.dataset.start,
          end_time: btn.dataset.end,
          room_id: btn.dataset.room,
          topic: (document.getElementById('tpbl-topic-input').value||'').trim()
        };
        if (IS_LOGGED) {
          TPBook.sendEngage();
        } else {
          TPBook.openModal();
        }
      },
      async sendEngage() {
        const p = TPBook.pending;
        if (!p) return;

        const form = new FormData();
        form.append('action', 'tp_engage_slot');
        form.append('_ajax_nonce', NONCE);
        form.append('teacher_id', TEACHER_ID);
        form.append('subject_id', SUBJECT_ID);
        form.append('level_id',   LEVEL_ID);
        form.append('slot_id',    p.slot_id);
        form.append('session_date', p.session_date);
        form.append('start_time',   p.start_time);
        form.append('end_time',     p.end_time);
        form.append('room_id',      p.room_id);
        form.append('topic',        p.topic);

        // new student fields if not logged in
        if (!IS_LOGGED) {
          form.append('full_name', (qs('tpbl-name').value||'').trim());
          form.append('email',     (qs('tpbl-email').value||'').trim());
          form.append('password',  (qs('tpbl-pass').value||'').trim());
        }

        const btn = document.querySelector('.tpbl-engage[data-slot="'+p.slot_id+'"]');
        if (btn) { btn.disabled = true; btn.textContent = 'Engaging…'; }

        try {
          const res = await fetch(AJAX, { method:'POST', credentials:'same-origin', body: form });
          const j = await res.json();
          if (!j.success) throw new Error(j.data?.message || 'Booking failed');

          // UI -> Engaged
          const slotRow = document.getElementById('slot-'+p.slot_id);
          if (slotRow) {
            slotRow.querySelector('.tpbl-badge').textContent = 'Engaged';
            slotRow.querySelector('.tpbl-badge').className = 'tpbl-badge';
            slotRow.querySelector('.tpbl-engage').remove();
          }

          if (!IS_LOGGED && j.data?.redirect) {
            // new student is logged-in server-side; refresh to reflect state
            window.location.href = j.data.redirect;
            return;
          }

          if (j.data?.join_url) {
            // Optional: take student to the join page immediately
            window.location.href = j.data.join_url;
          }
        } catch(err) {
          alert(err.message || 'Could not engage slot');
          if (btn) { btn.disabled = false; btn.textContent = 'Engage'; }
        } finally {
          if (!IS_LOGGED) TPBook.closeModal();
          TPBook.pending = null;
        }
      }
    };

    window.TPBook = TPBook;
    // Wire up Engage buttons
    document.querySelectorAll('.tpbl-engage').forEach(btn => {
      btn.addEventListener('click', () => TPBook.engageClick(btn));
    });

    // Modal submit
    const submit = document.getElementById('tpbl-submit');
    if (submit) submit.addEventListener('click', () => TPBook.sendEngage());
  })();
  </script>
  <?php
  return ob_get_clean();
});

/* ----------------------------------------------------------------------------
 * AJAX: Engage a slot (creates student if needed, creates booking, marks slot)
 * ------------------------------------------------------------------------- */
add_action('wp_ajax_tp_engage_slot', 'tp_ajax_engage_slot');
add_action('wp_ajax_nopriv_tp_engage_slot', 'tp_ajax_engage_slot');

function tp_ajax_engage_slot() {
  check_ajax_referer('tp_book_lecture');

  global $wpdb;
  tp_create_bookings_table_if_missing();

  $teacher_id   = isset($_POST['teacher_id'])   ? (int)$_POST['teacher_id']   : 0;
  $subject_id   = isset($_POST['subject_id'])   ? (int)$_POST['subject_id']   : 0;
  $level_id     = isset($_POST['level_id'])     ? (int)$_POST['level_id']     : 0;
  $slot_id      = isset($_POST['slot_id'])      ? (int)$_POST['slot_id']      : 0;
  $session_date = isset($_POST['session_date']) ? sanitize_text_field($_POST['session_date']) : '';
  $start_time   = isset($_POST['start_time'])   ? sanitize_text_field($_POST['start_time'])   : '';
  $end_time     = isset($_POST['end_time'])     ? sanitize_text_field($_POST['end_time'])     : '';
  $room_id      = isset($_POST['room_id'])      ? sanitize_text_field($_POST['room_id'])      : '';
  $topic        = isset($_POST['topic'])        ? sanitize_text_field($_POST['topic'])        : '';

  if (!$teacher_id || !$slot_id || !$session_date || !$start_time || !$end_time) {
    wp_send_json_error(['message'=>'Missing required data']);
  }

  /* --- Ensure user (create if needed) --- */
  $current_user = wp_get_current_user();
  if (!$current_user || 0 == $current_user->ID) {
    $full_name = sanitize_text_field($_POST['full_name'] ?? '');
    $email     = sanitize_email($_POST['email'] ?? '');
    $pass      = (string)($_POST['password'] ?? '');

    if ($full_name === '' || !is_email($email) || strlen($pass) < 6) {
      wp_send_json_error(['message'=>'Please provide name, valid email and a password (min 6 chars).']);
    }

    // If user exists, log them in; else create
    $wp_user = get_user_by('email', $email);
    if (!$wp_user) {
      $username = sanitize_user(current(explode('@', $email)));
      if (username_exists($username)) {
        $username .= '_' . wp_generate_password(4, false, false);
      }
      $user_id = wp_create_user($username, $pass, $email);
      if (is_wp_error($user_id)) {
        wp_send_json_error(['message'=>'Could not create user: '.$user_id->get_error_message()]);
      }
      // Name + role student
      wp_update_user(['ID'=>$user_id, 'display_name'=>$full_name, 'first_name'=>$full_name]);
      $u = new WP_User($user_id);
      $u->set_role('subscriber'); // or 'student' if you registered such a role

      // Insert into custom register table (hash the password for safety)
      $hash = wp_hash_password($pass);
      $wpdb->replace(TP_STUDENT_REGISTER, [
        'fullname' => $full_name,
        'email'    => $email,
        'password' => $hash
      ], ['%s','%s','%s']);

      // Log them in
      wp_set_current_user($user_id);
      wp_set_auth_cookie($user_id, true);
      $current_user = get_user_by('id', $user_id);
      $redirect = add_query_arg([], wp_get_referer() ?: home_url('/booklecture/'));
    } else {
      // Try to sign in with provided password
      $signon = wp_signon(['user_login'=>$wp_user->user_login, 'user_password'=>$pass, 'remember'=>true], false);
      if (is_wp_error($signon)) {
        wp_send_json_error(['message'=>'Email already exists. Please login first.']);
      }
      $current_user = $signon;
      $redirect = add_query_arg([], wp_get_referer() ?: home_url('/booklecture/'));
    }

    // Allow client to refresh if desired
    $maybe_redirect = $redirect ?? null;
  }

  $student_wp_id = get_current_user_id();
  $student_name  = wp_get_current_user()->display_name ?: (wp_get_current_user()->user_login ?? 'Student');
  $student_email = wp_get_current_user()->user_email;

  /* --- Prevent double-book on same slot/date --- */
  $already = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM " . TP_BOOKINGS_TABLE . " WHERE slot_id=%d AND session_date=%s",
    $slot_id, $session_date
  ));
  if ($already) {
    wp_send_json_error(['message'=>'This slot has just been taken. Please refresh.']);
  }

  /* --- Price logic: 1st free, next 5 discounted, then full --- */
  $teacher_row = tp_read_teacher($teacher_id);
  $rate_full   = tp_teacher_rate($teacher_row);
  $past_count  = (int)$wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM " . TP_BOOKINGS_TABLE . " WHERE teacher_id=%d AND student_wp_id=%d",
    $teacher_id, $student_wp_id
  ));

  if ($past_count === 0) {
    $price = 0.00;
  } elseif ($past_count >= 1 && $past_count <= 5) {
    $price = round($rate_full * TP_DISCOUNT_FACTOR, 2);
  } else {
    $price = round($rate_full, 2);
  }

  /* --- Room id (deterministic & shared with teacher) --- */
  if ($room_id === '') {
    // Read current slot for room id first
    $slot_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . TP_SLOTS_TABLE . " WHERE slot_id=%d", $slot_id), ARRAY_A);
    if ($slot_row) {
      $room_id = tp_next_room_id_for_slot($slot_row);
    } else {
      $room_id = 'slot_' . $slot_id . '_' . str_replace('-', '', $session_date);
    }
  }

  /* --- Insert booking & mark slot (if per-occurrence) --- */
  $wpdb->insert(TP_BOOKINGS_TABLE, [
    'slot_id'        => $slot_id,
    'teacher_id'     => $teacher_id,
    'student_wp_id'  => $student_wp_id,
    'student_name'   => $student_name,
    'student_email'  => $student_email,
    'subject_id'     => $subject_id,
    'level_id'       => $level_id,
    'session_date'   => $session_date,
    'start_time'     => $start_time,
    'end_time'       => $end_time,
    'room_id'        => $room_id,
    'price'          => $price,
    'created_at'     => current_time('mysql', 1)
  ], ['%d','%d','%d','%s','%s','%d','%d','%s','%s','%s','%s','%s','%f','%s']);

  // If your slots table is per-occurrence (one row per date), mark it booked
  $wpdb->update(TP_SLOTS_TABLE,
    ['status' => 'booked', 'room_id' => $room_id],
    ['slot_id' => $slot_id],
    ['%s','%s'],
    ['%d']
  );

  $join_url = add_query_arg([
    'roomID' => rawurlencode($room_id),
    'role'   => 'student'
  ], home_url('/liveclassroom/'));

  $payload = ['join_url'=>$join_url];
  if (!empty($maybe_redirect)) $payload['redirect'] = $maybe_redirect;

  wp_send_json_success($payload);
}

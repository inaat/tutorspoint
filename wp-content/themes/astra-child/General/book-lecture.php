<?php
/**
 * File: General/book-lecture.php
 * Shortcode: [book_lecture]
 * URL: /booklecture/?teacher_id=<TID>&subject_id=<SID>&level_id=<LID>
 */
if (!defined('ABSPATH')) { exit; }

/* ---------------------------------------
 * Helpers
 * -------------------------------------*/
function tp_bl_detect_prefix($wpdb){
  $c1 = $wpdb->get_var("SHOW TABLES LIKE 'wpC_subjects_level'");
  return ($c1 === 'wpC_subjects_level') ? 'wpC_' : $wpdb->prefix;
}

/** Next occurrence for weekday/time (used when session_date = '0000-00-00') */
function tp_bl_next_occurrence($day_of_week, $start_time, $tz_string = null){
  $map = ['Sunday'=>0,'Monday'=>1,'Tuesday'=>2,'Wednesday'=>3,'Thursday'=>4,'Friday'=>5,'Saturday'=>6];
  $tz = $tz_string ?: get_option('timezone_string') ?: 'UTC';
  try { $tz = new DateTimeZone($tz); } catch(\Exception $e){ $tz = new DateTimeZone('UTC'); }
  $now = new DateTime('now', $tz);
  $target = isset($map[$day_of_week]) ? (int)$map[$day_of_week] : (int)$now->format('w');
  $curr   = (int)$now->format('w');
  $add    = $target - $curr;
  if ($add < 0) $add += 7;
  if ($add === 0 && $start_time && $now->format('H:i:s') >= $start_time) $add = 7;
  if ($add > 0) $now->modify("+{$add} day");
  return $now->format('Y-m-d');
}

/** Combine date+time and check future (site TZ) */
function tp_bl_is_future($date, $time){
  if (!$date || $date === '0000-00-00') return true;
  $tz = get_option('timezone_string') ?: 'UTC';
  try { $tz = new DateTimeZone($tz); } catch(\Exception $e){ $tz = new DateTimeZone('UTC'); }
  try{
    $dt = new DateTime("{$date} {$time}", $tz);
    $now = new DateTime('now', $tz);
    return $dt > $now;
  }catch(\Exception $e){ return true; }
}

/* ---------------------------------------
 * Shortcode view
 * -------------------------------------*/
function tp_book_lecture_shortcode(){
  if (!function_exists('wp_get_current_user')) return '<p>WordPress not loaded.</p>';
  global $wpdb;

  $teacher_id = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;
  $subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
  $level_id   = isset($_GET['level_id'])   ? (int)$_GET['level_id']   : 0;
  if (!$teacher_id || !$subject_id || !$level_id) return '<p>Missing teacher, subject or level.</p>';

  $p = tp_bl_detect_prefix($wpdb);
  $slTable  = $p.'subjects_level';
  $tasTable = $p.'teacher_allocated_subjects';
  $tmTable  = $p.'teachers_main';
  $thrTable = $p.'teacher_Hour_Rate';
  $tsTable  = $p.'teacher_generated_slots';
  $studLect = $p.'student_lectures';
  $subjects = $p.'subjects';
  $levels   = $p.'class_levels';

  $teacher = $wpdb->get_row($wpdb->prepare(
    "SELECT teacher_id, FullName, Country, Photo FROM {$tmTable} WHERE teacher_id=%d LIMIT 1",
    $teacher_id
  ));
  if (!$teacher) return '<p>Teacher not found.</p>';

  $subj_name  = (string)$wpdb->get_var($wpdb->prepare("SELECT SubjectName FROM {$subjects} WHERE subject_id=%d LIMIT 1", $subject_id));
  $level_name = (string)$wpdb->get_var($wpdb->prepare("SELECT level_name FROM {$levels} WHERE id=%d LIMIT 1", $level_id));

  // Guard: teacher must be allocated to this subject+level
  $sl_id = (int)$wpdb->get_var($wpdb->prepare(
    "SELECT subject_level_id FROM {$slTable} WHERE level_id=%d AND subject_id=%d LIMIT 1", $level_id, $subject_id
  ));
  if (!$sl_id) return '<p>No matching subject–level found.</p>';

  $is_alloc = (int)$wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tasTable} WHERE teacher_id=%d AND subject_level_id=%d", $teacher_id, $sl_id
  )) > 0;
  if (!$is_alloc) return '<p>This teacher is not allocated to the selected subject & level.</p>';

  // Rate + taught-hours
  $hourly_rate = (float)$wpdb->get_var($wpdb->prepare(
    "SELECT hourly_rate FROM {$thrTable}
     WHERE teacher_id=%d ORDER BY from_date DESC, hour_rate_id DESC LIMIT 1", $teacher_id
  ));
  $taught_minutes = (int)$wpdb->get_var($wpdb->prepare(
    "SELECT COALESCE(SUM(duration),0) FROM {$studLect} WHERE teacher_id=%d AND is_taught=1", $teacher_id
  ));
  $taught_hours = round($taught_minutes / 60, 1);

  // Slots: teacher-wide (no subject/level filter)
  $raw = $wpdb->get_results($wpdb->prepare(
    "SELECT slot_id, day_of_week, session_date, start_time, end_time, status, is_active, student_id
     FROM {$tsTable}
     WHERE teacher_id=%d AND is_active=1
       AND (student_id IS NULL OR student_id=0)
       AND (status IS NULL OR status IN ('available','open'))
     ORDER BY (session_date='0000-00-00'), session_date, start_time
     LIMIT 300",
    $teacher_id
  ));
  $site_tz = get_option('timezone_string') ?: 'UTC';
  $slots = [];
  foreach ($raw as $s){
    if (!tp_bl_is_future($s->session_date, $s->start_time)) continue;
    $date = ($s->session_date && $s->session_date !== '0000-00-00')
      ? $s->session_date
      : tp_bl_next_occurrence($s->day_of_week, $s->start_time, $site_tz);
    $slots[] = (object)[
      'slot_id'      => (int)$s->slot_id,
      'session_date' => $date,
      'start_time'   => $s->start_time,
      'end_time'     => $s->end_time,
    ];
  }
  usort($slots, fn($a,$b) => strcmp($a->session_date.' '.$a->start_time, $b->session_date.' '.$b->start_time));

  // Banner logic
  $is_logged = is_user_logged_in();
  $show_free  = !$is_logged;
  $show_30off = !$is_logged;
  if ($is_logged){
    // NOTE: only for display; real check is in AJAX after mapping to student_register
    $has_free = (int)$wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM {$studLect}
       WHERE teacher_id=%d AND subject_id=%d AND is_paid='free'",
      $teacher_id, $subject_id
    )) > 0;
    $show_free = !$has_free;
    $paid_count = (int)$wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM {$studLect} WHERE teacher_id=%d AND is_paid <> 'free'", $teacher_id
    ));
    $show_30off = ($paid_count < 3);
  }

  // View
  ob_start();
  $nonce = wp_create_nonce('book_lecture_nonce');
  $ajax  = admin_url('admin-ajax.php');
  // Use avatar if no photo
  $photo = $teacher->Photo ? esc_url($teacher->Photo) : 'https://ui-avatars.com/api/?name=' . urlencode($teacher->FullName) . '&size=160&background=3dba9f&color=ffffff&bold=true';
  $name  = esc_html($teacher->FullName ?: 'Tutor');
  $country = esc_html($teacher->Country ?: '—');
  $rate_fmt = $hourly_rate > 0 ? '£'.number_format($hourly_rate, 2) : '—';
  ?>
  <div class="bl-wrap">
    <header class="bl-hero">
      <div class="bl-hero-inner">
        <img class="bl-avatar" src="<?php echo $photo; ?>" alt="<?php echo $name; ?>">
        <div class="bl-txt">
          <h1 class="bl-name"><?php echo $name; ?></h1>
          <div class="bl-meta">
            <span class="bl-chip"><?php echo $country; ?></span>
            <span class="bl-chip"><?php echo esc_html($level_name ?: '—'); ?></span>
            <span class="bl-chip"><?php echo esc_html($subj_name ?: '—'); ?></span>
            <span class="bl-chip">Taught: <?php echo esc_html($taught_hours); ?> hrs</span>
          </div>
        </div>
        <div class="bl-rate">
          <?php if ($show_free): ?>
            <div class="bl-free">Free trial available</div>
            <?php if ($hourly_rate > 0): ?><div class="bl-cross"><?php echo $rate_fmt; ?>/hr</div><?php endif; ?>
            <?php if ($show_30off && $hourly_rate > 0): ?><div class="bl-discount">First 3 paid sessions: <strong>30% off</strong></div><?php endif; ?>
          <?php elseif ($show_30off && $hourly_rate > 0): ?>
            <div class="bl-discount">First 3 paid sessions: <strong>30% off</strong></div>
            <div class="bl-cross"><?php echo $rate_fmt; ?>/hr</div>
            <div class="bl-now">Now: £<?php echo number_format($hourly_rate*0.7,2); ?>/hr</div>
          <?php else: ?>
            <div class="bl-now">Rate: <?php echo $rate_fmt; ?>/hr</div>
          <?php endif; ?>
        </div>
      </div>
    </header>

    <section class="bl-body">
      <div class="bl-left card">
        <h3 class="bl-title">Topic <span class="bl-req">(optional)</span></h3>
        <input id="bl-topic" type="text" class="bl-input" placeholder="Optional (e.g., Stoichiometry basics)">
        <p class="bl-note">If left blank, we’ll book as <strong>Intro Lecture + <?php echo esc_html($subj_name ?: 'Subject'); ?></strong>.</p>
      </div>

      <div class="bl-right card">
        <h3 class="bl-title">Available slots</h3>
        <?php if (!$slots): ?>
          <p>No upcoming slots for this teacher, subject and level.</p>
        <?php else: ?>
          <div class="bl-slots">
            <?php foreach ($slots as $s):
              $date = esc_html( date_i18n('D, M j, Y', strtotime($s->session_date)) );
              $time = esc_html( date_i18n('g:i A', strtotime($s->start_time)).' – '.date_i18n('g:i A', strtotime($s->end_time)) );
            ?>
              <div class="bl-slot">
                <div class="bl-slot-info">
                  <div class="bl-slot-date"><?php echo $date; ?></div>
                  <div class="bl-slot-time"><?php echo $time; ?></div>
                </div>
                <button class="bl-book" data-session="<?php echo (int)$s->slot_id; ?>">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                  Engage
                </button>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <!-- Modal -->
    <div id="bl-modal" class="bl-modal" aria-hidden="true">
      <div class="bl-modal-box" role="dialog" aria-modal="true" aria-labelledby="bl-modal-title">
        <h3 id="bl-modal-title">Confirm booking</h3>
        <?php if ( is_user_logged_in() ) : ?>
          <p>We’ll book this slot for <strong><?php echo esc_html( wp_get_current_user()->display_name ?: wp_get_current_user()->user_email ); ?></strong>.</p>
        <?php else : ?>
          <div class="bl-grid">
            <input id="bl-name"  type="text"     class="bl-input" placeholder="Your full name">
            <input id="bl-email" type="email"    class="bl-input" placeholder="you@example.com">
            <div class="bl-pass-wrap">
              <input id="bl-pass"  type="password" class="bl-input" placeholder="Password (min 8 chars)">
              <div class="bl-meter" aria-hidden="true"><div class="bl-meter-bar"></div></div>
              <div class="bl-meter-label" id="bl-pass-label">Strength: –</div>
              <ul class="bl-pass-hints">
                <li id="p-len">At least 8 characters</li>
                <li id="p-mix">Letters & numbers</li>
                <li id="p-case">Upper & lower case</li>
                <li id="p-spec">Special character (!@#$…)</li>
              </ul>
            </div>
          </div>
        <?php endif; ?>
        <div class="bl-actions">
          <button id="bl-cancel" class="bl-btn bl-btn-ghost" type="button">Cancel</button>
          <button id="bl-confirm" class="bl-btn" type="button">Confirm</button>
        </div>
      </div>
    </div>
  </div>

  <style>
    .bl-wrap{max-width:1100px;margin:0 auto;padding:20px}
    .bl-hero{border:1px solid #eaeef4;border-radius:16px;background:linear-gradient(135deg,rgba(13,148,136,.06),rgba(14,165,233,.06));margin-bottom:18px}
    .bl-hero-inner{display:flex;gap:16px;align-items:center;padding:16px;flex-wrap:wrap}
    .bl-avatar{width:72px;height:72px;border-radius:50%;object-fit:cover;border:3px solid #fff;box-shadow:0 6px 16px rgba(2,8,23,.12)}
    .bl-txt{flex:1 1 300px}
    .bl-name{margin:0 0 6px;font-size:26px;line-height:1.1;color:#0f172a}
    .bl-meta{display:flex;gap:8px;flex-wrap:wrap}
    .bl-chip{background:#f1f5f9;border-radius:999px;padding:6px 10px;font-size:13px;color:#0f172a}
    .bl-rate{text-align:right;margin-left:auto}
    .bl-free{font-weight:700;color:#16a34a}
    .bl-discount{color:#0ea5e9;margin-bottom:4px}
    .bl-cross{text-decoration:line-through;color:#64748b}
    .bl-now{font-weight:700;color:#0f172a}
    .bl-body{display:grid;gap:16px;grid-template-columns:1fr}
    @media(min-width:900px){.bl-body{grid-template-columns:1.05fr .95fr}}
    .card{background:#fff;border:1px solid #eaeef4;border-radius:14px;padding:16px;box-shadow:0 10px 28px rgba(2,8,23,.05)}
    .bl-title{margin:0 0 10px;font-size:18px;color:#0f172a}
    .bl-req{color:#64748b;font-weight:400}
    .bl-input{width:100%;padding:10px 12px;border:1px solid #dfe6ef;border-radius:10px}
    .bl-note{color:#64748b;font-size:13px;margin-top:8px}
    .bl-slots{display:flex;flex-direction:column;gap:10px}
    .bl-slot{display:flex;justify-content:space-between;align-items:center;border:1px solid #eef2f7;border-radius:12px;padding:10px 12px}
    .bl-slot-date{font-weight:700;color:#0f172a}
    .bl-slot-time{color:#334155}
    .bl-book{display:inline-flex;gap:8px;align-items:center;background:#0ea5e9;color:#fff;border:0;border-radius:10px;padding:8px 12px;font-weight:600;cursor:pointer}
    .bl-book:hover{filter:brightness(.96)}
    .bl-modal{position:fixed;inset:0;background:rgba(0,0,0,.6);display:none;place-items:center;z-index:9999}
    .bl-modal.open{display:grid}
    .bl-modal-box{width:min(560px,92vw);background:#fff;border-radius:14px;padding:18px}
    .bl-grid{display:grid;gap:10px}
    @media(min-width:520px){.bl-grid{grid-template-columns:1fr 1fr}}
    .bl-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:12px}
    .bl-btn{background:#16a34a;color:#fff;border:0;border-radius:10px;padding:10px 14px;font-weight:700;cursor:pointer}
    .bl-btn-ghost{background:transparent;color:#0f172a;border:2px solid #cdd6e3}
    .bl-pass-wrap{display:flex;flex-direction:column;gap:6px}
    .bl-meter{height:8px;background:#e9eef5;border-radius:999px;overflow:hidden}
    .bl-meter-bar{height:100%;width:0%}
    .bl-meter-label{font-size:12px;color:#0f172a}
    .bl-pass-hints{margin:0;padding-left:16px;color:#64748b;font-size:12px;display:grid;grid-template-columns:1fr 1fr;gap:4px}
    .bl-ok{color:#0a9b60}.bl-bad{color:#dc2626}
  </style>

  <script>
  (function(){
    const ajaxUrl    = <?php echo json_encode($ajax); ?>;
    const nonce      = <?php echo json_encode($nonce); ?>;
    const teacher_id = <?php echo (int)$teacher_id; ?>;
    const subject_id = <?php echo (int)$subject_id; ?>;
    const level_id   = <?php echo (int)$level_id; ?>;
    const defaultTopic = "Intro Lecture + " + <?php echo json_encode($subj_name ?: 'Subject'); ?>;
    const isLogged   = <?php echo is_user_logged_in() ? 'true' : 'false'; ?>;

    let chosenSession = null;
    const qs  = s => document.querySelector(s);
    const qsa = s => document.querySelectorAll(s);
    const trim = v => (v||'').replace(/\s+/g,' ').trim();

    qsa('.bl-book').forEach(btn=>{
      btn.addEventListener('click', ()=>{
        chosenSession = btn.getAttribute('data-session');
        const modal = qs('#bl-modal'); if(!modal) return;
        modal.classList.add('open'); modal.setAttribute('aria-hidden','false');
        setTimeout(()=>{ qs('#bl-confirm')?.focus(); }, 0);
      });
    });
    qs('#bl-cancel')?.addEventListener('click', ()=>{
      const modal = qs('#bl-modal'); if(!modal) return;
      modal.classList.remove('open'); modal.setAttribute('aria-hidden','true');
    });

    // Strength meter
    const passInput = qs('#bl-pass'), bar = qs('.bl-meter-bar'), lab = qs('#bl-pass-label');
    function score(pw){ let s=0;if(!pw) return 0; if(/.{8,}/.test(pw))s++; if(/[A-Z]/.test(pw))s++; if(/[a-z]/.test(pw))s++; if(/\d/.test(pw))s++; if(/[^A-Za-z0-9]/.test(pw))s++; if(pw.length>=12)s++; return Math.min(s,6);}
    function paint(n){ const pct=[0,20,40,60,80,100][n]||0; bar.style.width=pct+'%'; bar.style.background=(n>=4)?'#16a34a':(n>=2)?'#f59e0b':'#ef4444'; lab.textContent='Strength: '+((n>=4)?'Strong':(n>=2)?'Medium':'Weak');}
    passInput?.addEventListener('input', e=>paint(score(e.target.value||'')));

    function swapHeader(){ const link = Array.from(document.querySelectorAll('a,button')).find(el=>/login|sign[\s-]?up/i.test((el.textContent||'').toLowerCase())); if(link){ link.textContent='Dashboard'; link.setAttribute('href','/student-dashboard/?tab=freelecturebook'); link.onclick=null; } }

    qs('#bl-confirm')?.addEventListener('click', async ()=>{
      if(!chosenSession) return;
      const topic = trim(qs('#bl-topic')?.value) || defaultTopic;

      const fd = new FormData();
      fd.append('action','tp_book_lecture');
      fd.append('_wpnonce', nonce);
      fd.append('teacher_id', teacher_id);
      fd.append('subject_id', subject_id);
      fd.append('level_id',   level_id);
      fd.append('session_id', chosenSession);
      fd.append('topic',      topic);

      if(!isLogged){
        const name  = trim(qs('#bl-name')?.value);
        const email = trim(qs('#bl-email')?.value);
        const pass  = trim(qs('#bl-pass')?.value);
        if(!name || !email || pass.length<8){ alert('Please enter your name, email and a password (min 8 chars).'); return; }
        fd.append('name', name); fd.append('email', email); fd.append('password', pass);
      }

      try{
        const r = await fetch(ajaxUrl, { method:'POST', body:fd, credentials:'same-origin', headers:{'Accept':'application/json'} });
        const text = await r.text();
        let data = null; try{ data = JSON.parse(text); }catch(_){}
        if(!data){ alert('Unexpected response. Please reload and try again.'); return; }
        if(!data.success){ alert(data.data && data.data.message ? data.data.message : 'Failed to book. Please try another slot.'); return; }
        swapHeader();
        window.location.href = (data.data && data.data.redirect) || '/student-dashboard/?tab=freelecturebook';
      }catch(e){ alert('Network error. Please try again.'); }
    });
  })();
  </script>
  <?php
  return ob_get_clean();
}
add_shortcode('book_lecture','tp_book_lecture_shortcode');

/* ---------------------------------------
 * AJAX: Create/Log-in Student & Book
 * -------------------------------------*/
function tp_book_lecture_ajax(){
  global $wpdb;

  // Robust nonce handling → always JSON
  if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'book_lecture_nonce')) {
    wp_send_json_error(['message'=>'Security check failed. Please reload and try again.'], 403);
  }

  $teacher_id = isset($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : 0;
  $subject_id = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;
  $level_id   = isset($_POST['level_id'])   ? (int)$_POST['level_id']   : 0;
  $slot_id    = isset($_POST['session_id']) ? (int)$_POST['session_id'] : 0;
  $topic_in   = isset($_POST['topic']) ? sanitize_text_field(wp_unslash($_POST['topic'])) : '';

  if (!$teacher_id || !$subject_id || !$level_id || !$slot_id) {
    wp_send_json_error(['message'=>'Missing parameters.']);
  }

  $p = tp_bl_detect_prefix($wpdb);
  $tmTable  = $p.'teachers_main';
  $thrTable = $p.'teacher_Hour_Rate';
  $tsTable  = $p.'teacher_generated_slots';
  $studLect = $p.'student_lectures';
  $studReg  = $p.'student_register';
  $subjects = $p.'subjects';

  // Validate teacher
  $tid_ok = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$tmTable} WHERE teacher_id=%d", $teacher_id)) > 0;
  if (!$tid_ok) wp_send_json_error(['message'=>'Invalid teacher.']);

  // Validate slot (future, available)
  $slot = $wpdb->get_row($wpdb->prepare(
    "SELECT slot_id, day_of_week, session_date, start_time, end_time, status, is_active, student_id
     FROM {$tsTable}
     WHERE slot_id=%d AND teacher_id=%d AND is_active=1
       AND (student_id IS NULL OR student_id=0)
       AND (status IS NULL OR status IN ('available','open'))
     LIMIT 1",
    $slot_id, $teacher_id
  ));
  if (!$slot) wp_send_json_error(['message'=>'This slot is no longer available.']);

  // Resolve actual date for 0000-00-00
  $book_date = ($slot->session_date && $slot->session_date !== '0000-00-00')
    ? $slot->session_date
    : tp_bl_next_occurrence($slot->day_of_week, $slot->start_time);

  /* ---------- Authentication / Registration ---------- */
  $wp_user_id = get_current_user_id();
  $current_email = '';
  $current_name  = '';

  if (!$wp_user_id) {
    $name  = isset($_POST['name'])  ? sanitize_text_field($_POST['name']) : '';
    $email = isset($_POST['email']) ? sanitize_email($_POST['email'])     : '';
    $pass  = isset($_POST['password']) ? (string)$_POST['password'] : '';
    if (!$name || !$email || !is_email($email) || strlen($pass) < 8) {
      wp_send_json_error(['message'=>'Please provide name, a valid email and a password (min 8 chars).']);
    }

    $existing = get_user_by('email', $email);
    if ($existing){
      // Try to sign them in with the provided password
      $creds = ['user_login' => $existing->user_login, 'user_password' => $pass, 'remember' => true];
      $user  = wp_signon($creds, false);
      if (is_wp_error($user)) {
        wp_send_json_error(['message'=>'This email already exists. Please enter the correct password or log in first.']);
      }
      $wp_user_id = (int)$user->ID;
    } else {
      // Create account with provided password
      $wp_user_id = wp_create_user($email, $pass, $email);
      if (is_wp_error($wp_user_id)) {
        wp_send_json_error(['message'=>'Could not create user.']);
      }
      wp_update_user(['ID'=>$wp_user_id,'display_name'=>$name]);
      $u = new WP_User($wp_user_id); $u->set_role('student');
    }
    wp_set_current_user($wp_user_id);
    wp_set_auth_cookie($wp_user_id, true);
    $current_email = $email;
    $current_name  = $name ?: $email;
  } else {
    $u = get_userdata($wp_user_id);
    $current_email = $u ? (string)$u->user_email : '';
    $current_name  = $u ? (string)($u->display_name ?: $u->user_email) : '';
  }

  if (!$wp_user_id || !$current_email) {
    wp_send_json_error(['message'=>'Login failed. Please try again.']);
  }

  // Ensure student_register row exists and get PK
  $sr = $wpdb->get_row($wpdb->prepare("SELECT student_id FROM {$studReg} WHERE email=%s LIMIT 1", $current_email));
  if (!$sr){
    $wpdb->insert($studReg, [
      'full_name'  => $current_name,
      'email'      => $current_email,
      'password'   => '', // never store plain pw
      'status'     => 'active',
      'created_at' => current_time('mysql'),
    ], ['%s','%s','%s','%s','%s']);
    if (!$wpdb->insert_id) wp_send_json_error(['message'=>'Could not create student profile.']);
    $student_id = (int)$wpdb->insert_id;
  } else {
    $student_id = (int)$sr->student_id;
  }

  /* ---------- Booking ---------- */
  $subj_name = (string)$wpdb->get_var($wpdb->prepare(
    "SELECT SubjectName FROM {$subjects} WHERE subject_id=%d LIMIT 1", $subject_id
  ));
  $topic = (trim($topic_in) !== '') ? $topic_in : ('Intro Lecture + ' . ($subj_name ?: 'Subject'));

  // Pricing rules - use level-based rate
  $level_rate_table = $p.'level_hourly_rates';
  $hourly_rate = (float)$wpdb->get_var($wpdb->prepare(
    "SELECT r.hourly_rate FROM {$level_rate_table} r
     WHERE r.level_id = %d
       AND r.status = 1
       AND (r.effective_from IS NULL OR r.effective_from <= CURDATE())
       AND (r.effective_to IS NULL OR r.effective_to >= CURDATE())
     ORDER BY r.effective_from DESC LIMIT 1", $level_id
  ));

  // Fallback without date filters
  if (!$hourly_rate) {
    $hourly_rate = (float)$wpdb->get_var($wpdb->prepare(
      "SELECT r.hourly_rate FROM {$level_rate_table} r
       WHERE r.level_id = %d AND r.status = 1
       ORDER BY r.rate_id DESC LIMIT 1", $level_id
    ));
  }

  $original_price = max(0.0, $hourly_rate);

  $has_free = (int)$wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$studLect}
     WHERE student_id=%d AND teacher_id=%d AND subject_id=%d AND is_paid='free'",
    $student_id, $teacher_id, $subject_id
  )) > 0;

  $is_paid_flag  = $has_free ? 'paid' : 'free';
  $discount_rate = $has_free ? 0 : 100;
  $final_price   = $has_free ? $original_price : 0.0;

  if ($has_free) {
    $paid_count = (int)$wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM {$studLect}
       WHERE student_id=%d AND teacher_id=%d AND is_paid <> 'free'",
      $student_id, $teacher_id
    ));
    if ($paid_count < 3 && $original_price > 0){
      $discount_rate = 30;
      $final_price   = round($original_price * 0.7, 2);
    }
  }

  // Duration minutes
  $start_ts = strtotime($slot->start_time);
  $end_ts   = strtotime($slot->end_time);
  if ($end_ts <= $start_ts) $end_ts += 3600;
  $duration = (int)round(($end_ts - $start_ts)/60);

  // Sequence per (student, teacher)
  $sequence_no = (int)$wpdb->get_var($wpdb->prepare(
    "SELECT COALESCE(MAX(sequence_no),0)+1 FROM {$studLect}
     WHERE student_id=%d AND teacher_id=%d", $student_id, $teacher_id
  ));

  // Insert only columns that exist (FK safe)
  $cols = array_map('strtolower', (array)$wpdb->get_col("DESC {$studLect}", 0));
  $data = [
    'student_id'        => $student_id,
    'teacher_id'        => $teacher_id,
    'topic'             => $topic,
    'lecture_book_date' => $book_date,
    'lecture_time'      => $slot->start_time,
    'duration'          => $duration,
    'status'            => 'booked',
    'is_taught'         => 0,
    'is_paid'           => $is_paid_flag,
    'seen_by_teacher'   => 0,
    'subject_id'        => $subject_id,
    'sequence_no'       => $sequence_no,
  ];
  if (in_array('original_price', $cols, true)) $data['original_price'] = $original_price;
  if (in_array('discount_rate', $cols, true))  $data['discount_rate']  = $discount_rate;
  if (in_array('final_price', $cols, true))    $data['final_price']    = $final_price;
  if (in_array('created_at', $cols, true))     $data['created_at']     = current_time('mysql');

  $ok = $wpdb->insert($studLect, $data);
  if (!$ok) {
    $err = $wpdb->last_error ?: 'Unknown database error.';
    wp_send_json_error(['message'=>"Could not create booking. {$err}"]);
  }

  // Mark slot booked (store student_register PK)
  $wpdb->query($wpdb->prepare(
    "UPDATE {$tsTable}
     SET status='booked', student_id=%d
     WHERE slot_id=%d
       AND is_active=1
       AND (student_id IS NULL OR student_id=0)
       AND (status IS NULL OR status IN ('available','open'))",
    $student_id, $slot_id
  ));

  wp_send_json_success(['redirect' => site_url('/student-dashboard/?tab=freelecturebook')]);
}
add_action('wp_ajax_tp_book_lecture','tp_book_lecture_ajax');
add_action('wp_ajax_nopriv_tp_book_lecture','tp_book_lecture_ajax');

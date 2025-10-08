<?php
/**
 * Plugin Name: TP LiveClassroom Pings (frontend injector)
 * Description: Injects attendance pings on /liveclassroom without editing theme files.
 */
if (!defined('ABSPATH')) exit;

add_action('wp_enqueue_scripts', function () {
  // Detect the LiveClassroom page
  $is_live = is_page('liveclassroom') || (isset($_SERVER['REQUEST_URI']) && stripos($_SERVER['REQUEST_URI'], '/liveclassroom') !== false);
  if (!$is_live) return;

  global $wpdb;
  $room_id = isset($_GET['roomID']) ? sanitize_text_field($_GET['roomID']) : '';
  $role    = (isset($_GET['role']) && strtolower($_GET['role']) === 'teacher') ? 'teacher' : 'student';
  $lecture_id = 0;

  // Prefix detect
  $p = ($wpdb->get_var("SHOW TABLES LIKE 'wpC_student_lectures'") === 'wpC_student_lectures') ? 'wpC_' : $wpdb->prefix;

  // 1) Prefer explicit lectureId from URL if present
  if (isset($_GET['lectureId']) && ctype_digit($_GET['lectureId'])) {
    $lecture_id = (int) $_GET['lectureId'];
  }

        // 2) Fallback: map by roomID → teacher_generated_slots → student_lectures
        if (!$lecture_id && $room_id) {
        // Find the slot for this roomID (prefer active & latest update)
        $slot = $wpdb->get_row($wpdb->prepare("
        SELECT slot_id, teacher_id, COALESCE(student_id,0) AS student_id,
               subject_id, session_date, start_time
        FROM {$p}teacher_generated_slots
        WHERE room_id = %s
          AND (is_active = 1 OR is_active IS NULL)
        ORDER BY updated_at DESC, slot_id DESC
        LIMIT 1
        ", $room_id));
        
        if ($slot) {
        // Match a booked lecture; tolerate empty student_id in slot
        $lecture_id = (int) $wpdb->get_var($wpdb->prepare("
          SELECT lecture_book_id
          FROM {$p}student_lectures
          WHERE teacher_id = %d
            AND subject_id = %d
            AND lecture_book_date = %s
            AND lecture_time       = %s
            AND (student_id = %d OR %d = 0)
          ORDER BY lecture_book_id DESC
          LIMIT 1
        ", $slot->teacher_id, $slot->subject_id, $slot->session_date, $slot->start_time,
           $slot->student_id, $slot->student_id));
        }
    }


  // Build TP_LIVE object
  $data = [
    'lectureId' => $lecture_id,
    'roomId'    => $room_id,
    'role'      => $role,
    'ajaxUrl'   => admin_url('admin-ajax.php'),
    'nonce'     => wp_create_nonce('tp_live_nonce'),
  ];

  // Register a tiny handle and inject inline script
  $handle = 'tp-live-pings';
  wp_register_script($handle, false, [], null, true);
  wp_enqueue_script($handle);

  // Safer JSON encode
  $json = wp_json_encode($data);

  // Inline: TP_LIVE + pings
  $inline = <<<JS
window.TP_LIVE = <?php echo $json; ?>;
(function(){
  if (!window.TP_LIVE) return;
  var L = window.TP_LIVE;
  if (!L.roomId || !L.ajaxUrl || !L.nonce) { console.warn('TP_LIVE missing data'); return; }

  function post(action){
    var fd = new FormData();
    fd.append('action', action);
    fd.append('_wpnonce', L.nonce);
    fd.append('room_id',  String(L.roomId));
    fd.append('role',     String(L.role || 'student'));
    if (L.lectureId) fd.append('lecture_id', String(L.lectureId));
    return fetch(L.ajaxUrl, { method:'POST', body:fd, credentials:'same-origin' });
  }

  function startPings(){
    // Start only once (with a real lectureId)
    post('tp_att_start').catch(()=>{});
    var _iv = setInterval(function(){ post('tp_att_ping').catch(()=>{}); }, 30000);
    window.addEventListener('beforeunload', function(){
      try { clearInterval(_iv); post('tp_att_ping'); } catch(e){}
    });
  }

  if (!L.lectureId || Number(L.lectureId) <= 0) {
    console.warn('TP_LIVE: lectureId unresolved — NOT starting pings.');
    return; // ← critical: do not spam 400s
  }

  startPings();
})();

JS;

  wp_add_inline_script($handle, $inline);
}, 20);

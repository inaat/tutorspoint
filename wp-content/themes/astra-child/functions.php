<?php


// Load the Teachers shortcode (once)
add_action('init', function () {
    $file = trailingslashit( get_stylesheet_directory() ) . 'General/tp_teachers_home.php';
    if ( file_exists( $file ) ) {
        require_once $file; // include once
    }
});


// Load TP pricing badges shortcode
add_action('init', function () {
    // adjust the path if you put the file elsewhere
    $file = trailingslashit( get_stylesheet_directory() ) . 'General/tp-pricing-badges.php';
    if ( file_exists( $file ) ) {
        require_once $file; // include once
    }
});




// ... existing code
add_action('after_setup_theme', function () {
  $file = get_stylesheet_directory() . '/General/tp-blog-shortcode.php';
  if (file_exists($file)) { require_once $file; }
});



// Admin Dashboard button (header)
require_once get_stylesheet_directory() . '/General/admin-header-dashboard-btn.php';



// Load Admin Dashboard pill
//require_once get_stylesheet_directory() . '/General/admin-fab.php';


// Load Admin Dashboard
require_once get_stylesheet_directory() . '/admin-dashboard/admin-dashboard.php';



// Register the [tp_blog_teasers] shortcode (loaded once)
require_once get_stylesheet_directory() . '/General/blog-teasers.php';


// Load Footer Column 1 Quick Links shortcode only once
require_once get_stylesheet_directory() . '/General/footer-c1-links.php';


// Levels list shortcode
require_once get_stylesheet_directory() . '/General/levels-list.php';


// in functions.php (child‐theme or theme)
function tp_enqueue_jquery() {
    wp_enqueue_script('jquery');
}
add_action('wp_enqueue_scripts', 'tp_enqueue_jquery');



//require_once get_stylesheet_directory() . '/General/HomePage-Levels.php';

//require_once get_stylesheet_directory() . '/General/book-free-session.php';

require_once get_stylesheet_directory() . '/ajax/tutorspoint-subjects.php';

// // Load public teacher profile shortcode file
add_action('after_setup_theme', function () {
    $file = get_stylesheet_directory() . '/General/teacher-profile-public.php';
    if (file_exists($file)) {
        require_once $file;
    }
});


//
//require_once get_stylesheet_directory() . '/teacher-dashboard/partials/students.php';

// Load homepage level/subject shortcodes
require_once get_stylesheet_directory() . '/General/HomePage-Levels.php';

// Load the shortcode fileZEGO 
require_once get_stylesheet_directory() . '/includes/zegocloud-integration.php';


// Hide WP admin bar on the front-end for all logged-in users
add_action('after_setup_theme', function () {
  if ( ! is_admin() ) {
    show_admin_bar(false);           // turn off
    add_filter('show_admin_bar', '__return_false'); // enforce
  }
});




// Shortcodes
require_once get_stylesheet_directory() . '/General/tp-liveclassroom.php';



// Make sure teacher-dashboard AJAX handlers are loaded on admin-ajax.php
add_action('init', function () {
  if ( defined('DOING_AJAX') && DOING_AJAX ) {
    $p = get_stylesheet_directory() . '/General/teacher-dashboard/teacher-dashboard.php';
    if ( file_exists($p) ) { require_once $p; }
  }
});




// functions.php (near the top but after theme setup)
add_action('after_setup_theme', function () {
    $file = trailingslashit( get_stylesheet_directory() ) . 'includes/ajax-home.php';
    if ( ! file_exists( $file ) ) {
        // Maybe the file is in the PARENT theme instead
        $file = trailingslashit( get_template_directory() ) . 'includes/ajax-home.php';
    }
    if ( file_exists( $file ) ) {
        require_once $file;
    } else {
        error_log('ajax-home.php not found in child or parent theme.');
    }
});

require_once get_stylesheet_directory() . '/General/book-free-session.php';

// Force the [list_teachers] shortcode to render on the "ListOfTeachers" page,
// even if Elementor/template content is blocking it.

add_filter('the_content', function ($content) {
    if (is_page() && (is_page('listofteachers') || is_page('ListOfTeachers'))) {
        // Optional: keep existing content above/below by concatenating
        // return $content . do_shortcode('[list_teachers]');
        return do_shortcode('[list_teachers]');
    }
    return $content;

	
}, 1); // run early so it wins *


//require_once get_stylesheet_directory() . '/General/book-lecture.php';


// Load Zego Classroom shortcode
require_once get_stylesheet_directory() . '/includes/zego-classroom.php';

// functions.php (astra-child)
//require_once get_stylesheet_directory() . '/includes/zegocloud-integration.php';

require_once get_stylesheet_directory() . '/includes/tp-zego-ajax.php';


// Dequeue Elementor assets on the LiveClassRoom page to avoid console noise
add_action('wp_enqueue_scripts', function () {
  if (is_page('liveclassroom')) { // adjust if your slug differs
    // Dequeue scripts
    wp_dequeue_script('elementor-frontend');
    wp_dequeue_script('elementor-frontend-modules');
    // Dequeue styles (optional)
    wp_dequeue_style('elementor-frontend');
    wp_dequeue_style('elementor-icons');
  }
}, 100);


// Load the signup/sign-in shortcode
require_once trailingslashit( get_stylesheet_directory() ) . 'General/users-data.php';

// functions.php (child theme)
require_once get_stylesheet_directory() . '/General/passwords.php';

// functions.php (in astra-child)
require_once get_stylesheet_directory() . '/General/terms-modal.php';


//for auto updating
/* ===== old Cron: every 15 minutes ===== 
add_filter('cron_schedules', function ($s) {
  $s['every_15min'] = $s['every_15min'] ?? ['interval' => 15 * 60, 'display' => 'Every 15 Minutes'];
  return $s;
});
add_action('after_setup_theme', function () {
  if (!wp_next_scheduled('tp_release_past_teacher_slots')) {
    wp_schedule_event(time() + 60, 'every_15min', 'tp_release_past_teacher_slots');
  }
});
add_action('tp_release_past_teacher_slots', function () {
  global $wpdb;
  $now = current_time('mysql');
  // Release any dated slot that has ended (regardless of teacher opening the page)
  $wpdb->query($wpdb->prepare("
    UPDATE wpC_teacher_generated_slots
       SET status       = 'available',
           student_id   = NULL,
           room_id      = NULL,
           meeting_link = NULL,
           session_date = '0000-00-00',
           updated_at   = %s
     WHERE (session_date IS NOT NULL AND session_date <> '0000-00-00')
       AND TIMESTAMP(CONCAT(session_date,' ', end_time)) < %s
  ", current_time('mysql'), $now));
});


*/




/* ===== Cron schedule: every 15 minutes ===== */
add_filter('cron_schedules', function ($s) {
  $s['every_15min'] = $s['every_15min'] ?? ['interval' => 15 * 60, 'display' => 'Every 15 Minutes'];
  return $s;
});

add_action('after_setup_theme', function () {
  if (!wp_next_scheduled('tp_close_past_teacher_slots')) {
    wp_schedule_event(time() + 60, 'every_15min', 'tp_close_past_teacher_slots');
  }
});

/**
 * Mark past-dated engaged/booked slots as completed
 * and clear the room/link so nothing stays “Engaged”.
 */
add_action('tp_close_past_teacher_slots', function () {
  global $wpdb;

  // use your custom table prefix if present
  $table = $wpdb->get_var("SHOW TABLES LIKE 'wpC_teacher_generated_slots'")
         ? 'wpC_teacher_generated_slots'
         : $wpdb->prefix . 'teacher_generated_slots';

  // WP timezone “now”
  $now = current_time('mysql');

  /**
   * Parse end_time whether it is TIME(‘HH:MM:SS’) or varchar ‘HH:MM AM/PM’.
   * We normalize to 24h string and build a comparable TIMESTAMP(session_date, parsed_end).
   */
  $sql = "
    UPDATE {$table} AS s
       SET s.status       = 'completed',
           s.is_active    = 0,
           s.room_id      = NULL,
           s.meeting_link = NULL,
           s.updated_at   = %s
     WHERE s.session_date IS NOT NULL
       AND s.session_date <> '0000-00-00'
       AND COALESCE(s.is_active,1) = 1
       AND (s.status IN ('engaged','booked','in_progress') OR s.status IS NULL)
       AND TIMESTAMP(
             s.session_date,
             /* normalize AM/PM times if needed */
             CASE
               WHEN s.end_time REGEXP '^[0-9]{1,2}:[0-9]{2} ?(AM|PM)$'
                 THEN DATE_FORMAT(STR_TO_DATE(s.end_time,'%h:%i %p'), '%H:%i:%s')
               WHEN s.end_time REGEXP '^[0-9]{2}:[0-9]{2}(:[0-9]{2})?$'
                 THEN s.end_time
               ELSE '23:59:59'
             END
           ) < %s
  ";

  $wpdb->query( $wpdb->prepare($sql, current_time('mysql'), $now) );
});


/* -------- auto-create Zego room links 5-minutely -------- */

add_filter('cron_schedules', function($s){
  $s['every_5min'] = $s['every_5min'] ?? ['interval'=>5*60, 'display'=>'Every 5 Minutes'];
  return $s;
});

add_action('after_setup_theme', function(){
  if (!wp_next_scheduled('tp_autocreate_meeting_links')) {
    wp_schedule_event(time()+60, 'every_5min', 'tp_autocreate_meeting_links');
  }
});

add_action('tp_autocreate_meeting_links', function () {
  global $wpdb;

  // choose correct table name (supports your custom wpC_ prefix)
  $table = $wpdb->get_var("SHOW TABLES LIKE 'wpC_teacher_generated_slots'")
         ? 'wpC_teacher_generated_slots'
         : $wpdb->prefix.'teacher_generated_slots';

  $teachers = $wpdb->get_var("SHOW TABLES LIKE 'wpC_teachers_main'")
         ? 'wpC_teachers_main'
         : $wpdb->prefix.'teachers_main';

  $students = $wpdb->get_var("SHOW TABLES LIKE 'wpC_student_register'")
         ? 'wpC_student_register'
         : $wpdb->prefix.'student_register';

  // WP timezone "now"
  $now  = current_time('mysql');
  $plus = date('Y-m-d H:i:s', strtotime($now.' +10 minutes')); // window: next 10 minutes

  // Find engaged/booked slots starting within next 10 minutes, missing link
  $rows = $wpdb->get_results($wpdb->prepare("
    SELECT s.slot_id, s.session_date, s.start_time, s.end_time, s.teacher_id, s.student_id,
           tm.FullName AS teacher_name, tm.Email AS teacher_email,
           st.full_name AS student_name, st.email AS student_email
      FROM {$table} s
      LEFT JOIN {$teachers} tm ON tm.teacher_id = s.teacher_id
      LEFT JOIN {$students} st ON st.student_id = s.student_id
     WHERE s.session_date IS NOT NULL
       AND s.session_date <> '0000-00-00'
       AND (s.status IN ('engaged','booked'))
       AND (s.meeting_link IS NULL OR s.meeting_link = '')
       AND TIMESTAMP(
             s.session_date,
             CASE
               WHEN s.start_time REGEXP '^[0-9]{1,2}:[0-9]{2} ?(AM|PM)$'
                 THEN DATE_FORMAT(STR_TO_DATE(s.start_time,'%h:%i %p'), '%H:%i:%s')
               ELSE s.start_time
             END
           ) BETWEEN %s AND %s
     ORDER BY s.session_date ASC, s.start_time ASC
  ", $now, $plus));

  if (!$rows) return;

  foreach ($rows as $r) {
    $room  = 'slot_' . intval($r->slot_id); // roomID = slot_123
    // One canonical link which decides the role on the join page
    $link  = home_url('/zego-join.php?room=' . rawurlencode($room) . '&slot=' . intval($r->slot_id));

    // Save it
    $wpdb->update(
      $table,
      ['meeting_link' => $link, 'updated_at' => current_time('mysql')],
      ['slot_id' => $r->slot_id],
      ['%s','%s'], ['%d']
    );

    // OPTIONAL: email both parties once the link is created
    // comment out this block if you prefer not to email automatically
    $subject = 'Your class room is ready';
    $timeTxt = esc_html($r->session_date . ' ' . $r->start_time . ' – ' . $r->end_time);
    $body = "Hello,\n\n"
          . "Your class room is ready for:\n"
          . "{$timeTxt}\n\n"
          . "Join link: {$link}\n\n"
          . "Teacher: {$r->teacher_name}\n"
          . "Student: {$r->student_name}\n\n"
          . "Please click the link a minute before time to enter the room.\n\n"
          . "Regards,\nTutorsPoint";
    $headers = ['Content-Type: text/plain; charset=UTF-8'];

    if (!empty($r->teacher_email)) @wp_mail($r->teacher_email, $subject, $body, $headers);
    if (!empty($r->student_email)) @wp_mail($r->student_email, $subject, $body, $headers);
  }
});




// =============== Toggle teacher active/inactive ===============
add_action('wp_ajax_tp_adm_t_toggle', function () {
    if ( ! current_user_can('manage_options') ) {
        wp_send_json_error('Permission denied');
    }
    check_ajax_referer('tp_adm_teacher');

    global $wpdb;

    $teacher_id = isset($_POST['teacher_id']) ? (int) $_POST['teacher_id'] : 0;
    $to         = isset($_POST['to']) ? sanitize_text_field($_POST['to']) : '';
    if (!$teacher_id || !in_array($to, ['active','inactive'], true)) {
        wp_send_json_error('Bad request');
    }

    $t = $wpdb->get_row( $wpdb->prepare(
        "SELECT teacher_id, Email FROM wpC_teachers_main WHERE teacher_id=%d LIMIT 1",
        $teacher_id
    ) );
    if (!$t) wp_send_json_error('Teacher not found');

    $user = get_user_by('email', $t->Email);

    // 1) Update main table (NOTE: column name is Status per your DB)
    $newStatus = ($to === 'active') ? 1 : 0;
    $upd = $wpdb->update(
        'wpC_teachers_main',
        ['Status' => $newStatus],
        ['teacher_id' => $teacher_id],
        ['%d'],
        ['%d']
    );
    if ($upd === false) {
        wp_send_json_error('DB update failed on wpC_teachers_main');
    }

    // 2) Gate WP login via meta
    if ($user) {
        if ($to === 'inactive') {
            update_user_meta($user->ID, 'account_disabled', 1);
        } else {
            delete_user_meta($user->ID, 'account_disabled');
        }
    }

    wp_send_json_success(['status' => $newStatus]);
});

// =============== Create teacher (name, email, password) ===============
add_action('wp_ajax_tp_adm_t_add', function () {
    if ( ! current_user_can('manage_options') ) {
        wp_send_json_error('Permission denied');
    }
    check_ajax_referer('tp_adm_teacher');

    global $wpdb;

    $name = sanitize_text_field($_POST['name'] ?? '');
    $email= sanitize_email($_POST['email'] ?? '');
    $pass = (string) ($_POST['password'] ?? '');

    if (!$name || !$email || !$pass) {
        wp_send_json_error('Name, Email and Password are required.');
    }
    if (email_exists($email)) {
        wp_send_json_error('Email already exists.');
    }

    // Create WP user (teacher role)
    $login = sanitize_user(current(explode('@', $email)));
    if (username_exists($login)) $login .= '_' . wp_generate_password(4, false, false);

    $user_id = wp_create_user($login, $pass, $email);
    if (is_wp_error($user_id)) {
        wp_send_json_error('Could not create WP user: ' . $user_id->get_error_message());
    }
    $wp_user = new WP_User($user_id);
    if (!in_array('teacher', (array)$wp_user->roles, true)) $wp_user->add_role('teacher');
    wp_update_user(['ID'=>$user_id, 'display_name'=>$name]);

    // Insert into wpC_teachers_main if not exists (Status=1 active)
    $exists = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM wpC_teachers_main WHERE Email=%s", $email
    ) );
    if (!$exists) {
        $wpdb->insert('wpC_teachers_main', [
            'FullName' => $name,
            'Email'    => $email,
            'Status'   => 1,
            'created_at' => current_time('mysql'),
        ], ['%s','%s','%d','%s']);
    }

    wp_send_json_success(['user_id'=>$user_id]);
});

// =============== Block login for disabled teachers ===============
add_filter('authenticate', function ($user_or_error, $username, $password) {
    if ($user_or_error instanceof WP_User) {
        $disabled = get_user_meta($user_or_error->ID, 'account_disabled', true);
        if ($disabled) {
            return new WP_Error('account_disabled', __('Your account is deactivated. Please contact support.'));
        }
    }
    return $user_or_error;
}, 30, 3);

// =============== Save hourly rate into wpC_teacher_Hour_Rate ===============
add_action('wp_ajax_tp_adm_t_rate_set', function () {
    if ( ! current_user_can('manage_options') ) {
        wp_send_json_error('Permission denied');
    }
    check_ajax_referer('tp_adm_teacher');

    global $wpdb;
    $teacher_id       = (int) ($_POST['teacher_id'] ?? 0);
    $subject_level_id = (int) ($_POST['subject_level_id'] ?? 0);
    $hourly_rate      = (float) ($_POST['hourly_rate'] ?? 0);

    if (!$teacher_id || !$subject_level_id || $hourly_rate <= 0) {
        wp_send_json_error('All fields are required.');
    }

    // Upsert into wpC_teacher_Hour_Rate (idempotent)
    $existing_id = $wpdb->get_var( $wpdb->prepare(
        "SELECT hour_rate_id FROM wpC_teacher_Hour_Rate WHERE teacher_id=%d AND subject_level_id=%d LIMIT 1",
        $teacher_id, $subject_level_id
    ) );

    if ($existing_id) {
        $ok = $wpdb->update('wpC_teacher_Hour_Rate',
            ['hourly_rate' => $hourly_rate, 'updated_at' => current_time('mysql')],
            ['hour_rate_id' => $existing_id],
            ['%f','%s'], ['%d']
        );
        if ($ok === false) wp_send_json_error('Failed to update rate.');
        wp_send_json_success(['hour_rate_id' => (int)$existing_id]);
    } else {
        $ok = $wpdb->insert('wpC_teacher_Hour_Rate', [
            'teacher_id'       => $teacher_id,
            'subject_level_id' => $subject_level_id,
            'hourly_rate'      => $hourly_rate,
            'created_at'       => current_time('mysql'),
        ], ['%d','%d','%f','%s']);
        if (!$ok) wp_send_json_error('Failed to insert rate.');
        wp_send_json_success(['hour_rate_id' => (int)$wpdb->insert_id]);
    }
});





//add_shortcode('testshort', fn() => 'OK');



/** =========================================================
 *  AJAX: load Levels options  -> used by [levels_dropdown] and [book_free_session_form]
 *  Returns raw <option> HTML (to match your existing JS).
 *  ======================================================= */
/*

add_action('wp_ajax_tp_load_levels', 'tp_load_levels');
add_action('wp_ajax_nopriv_tp_load_levels', 'tp_load_levels');
function tp_load_levels() {
    global $wpdb;
    // Adjust column names if needed (id / level_name / sort_order)
    $rows = $wpdb->get_results("
        SELECT id, level_name
        FROM wpC_class_levels
        ORDER BY COALESCE(sort_order, 9999), level_name
    ");
    $out = '';
    foreach ($rows as $r) {
        $out .= '<option value="'. esc_attr($r->id) .'">'. esc_html($r->level_name) .'</option>';
    }
    echo $out;
    wp_die();
}
*/
/** =========================================================
 *  AJAX: load Subjects by Level -> used by [subjects_dropdown] and [book_free_session_form]
 *  Returns raw <option> HTML (to match your existing JS).
 *  ======================================================= */
/*

add_action('wp_ajax_tp_load_subjects_by_level', 'tp_load_subjects_by_level');
add_action('wp_ajax_nopriv_tp_load_subjects_by_level', 'tp_load_subjects_by_level');
function tp_load_subjects_by_level() {
    if (empty($_POST['level'])) {
        echo ''; wp_die();
    }
    global $wpdb;
    $level_id = intval($_POST['level']);

    // ✅ Use this if your normalized table has columns: level_id, subject_id
    $rows = $wpdb->get_results($wpdb->prepare("
        SELECT s.subject_id, s.SubjectName
        FROM wpC_subjects_level sl
        JOIN wpC_subjects s ON s.subject_id = sl.subject_id
        WHERE sl.level_id = %d
        ORDER BY s.SubjectName
    ", $level_id));

    /* ❗ If your table still uses old column names: subject_Id + level
       replace the query above with this one:

    $rows = $wpdb->get_results($wpdb->prepare("
        SELECT s.subject_id, s.SubjectName
        FROM wpC_subjects_level sl
        JOIN wpC_subjects s ON s.subject_id = sl.subject_Id
        WHERE sl.level = %d
        ORDER BY s.SubjectName
    ", $level_id));
    
    

    $out = '';
    foreach ($rows as $r) {
        $out .= '<option value="'. esc_attr($r->subject_id) .'">'. esc_html($r->SubjectName) .'</option>';
    }
    echo $out;
    wp_die();
}


add_action('wp_enqueue_scripts', function () {
  // Ensure jQuery is present
  wp_enqueue_script('jquery');

  // Enqueue our script in the footer, no minifier clashes
 
  wp_enqueue_script(
    'tp-level-subjects',
    get_stylesheet_directory_uri() . '/js/level-subjects.js',
    array('jquery'),
    '1.0.2',
    true
  );

  // Pass AJAX URL
  wp_localize_script('tp-level-subjects', 'ajaxObj', array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
  ));
}, 20);





add_action('wp_enqueue_scripts', function () {
  wp_enqueue_script(
    'tp-level-subjects',
    get_stylesheet_directory_uri() . '/js/level-subjects.js',
    array('jquery'),
    '1.0.1', // bump version to bust cache
    true
  );

  wp_localize_script('tp-level-subjects', 'ajaxObj', array(
    'ajaxUrl' => admin_url('admin-ajax.php')
  ));
});


*/



//require_once get_stylesheet_directory() . '/inc/zoom-integration.php';


// functions.php (child theme)
//require_once get_stylesheet_directory() . '/student-dashboard/loader.php';



/* ---------- Teacher Account AJAX ---------- */

add_action('wp_ajax_tp_update_teacher_main', 'tp_update_teacher_main');
add_action('wp_ajax_tp_add_qualification',   'tp_add_qualification');
add_action('wp_ajax_tp_update_qualification','tp_update_qualification');
add_action('wp_ajax_tp_delete_qualification','tp_delete_qualification');

function tp_account_requirements_or_die() {
  if (!is_user_logged_in()) wp_send_json_error(['message'=>'Not logged in'], 403);
  if (empty($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tp_teacher_account')) {
    wp_send_json_error(['message'=>'Bad nonce'], 403);
  }
  global $wpdb;
  $user = wp_get_current_user();
  $teacher = $wpdb->get_row($wpdb->prepare(
    "SELECT teacher_id, Email FROM wpC_teachers_main WHERE Email=%s LIMIT 1",
    $user->user_email
  ));
  if (!$teacher) wp_send_json_error(['message'=>'Teacher not found'], 404);
  return [$wpdb, (int)$teacher->teacher_id];
}

/** Update wpC_teachers_main (selected fields only) */
function tp_update_teacher_main() {
  list($wpdb, $teacher_id) = tp_account_requirements_or_die();

  $data = [
    'Phone'             => isset($_POST['Phone']) ? sanitize_text_field($_POST['Phone']) : null,
    'WhatsappNo'        => isset($_POST['WhatsappNo']) ? sanitize_text_field($_POST['WhatsappNo']) : null,
    'Country'           => isset($_POST['Country']) ? sanitize_text_field($_POST['Country']) : null,
    'BankName'          => isset($_POST['BankName']) ? sanitize_text_field($_POST['BankName']) : null,
    'BankAccountNumber' => isset($_POST['BankAccountNumber']) ? sanitize_text_field($_POST['BankAccountNumber']) : null,
    'intro_video_url'   => isset($_POST['intro_video_url']) ? esc_url_raw($_POST['intro_video_url']) : null,
    'object_note'       => isset($_POST['object_note']) ? sanitize_textarea_field($_POST['object_note']) : null,
  ];

  // Remove nulls so we only update submitted keys
  $data = array_filter($data, fn($v) => !is_null($v));

  if (empty($data)) wp_send_json_success(['updated'=>0]); // nothing to do

  $updated = $wpdb->update(
    'wpC_teachers_main',
    $data,
    ['teacher_id' => $teacher_id],
    null,
    ['%d']
  );

  if ($updated === false) {
    wp_send_json_error(['message'=>'DB error: '.$wpdb->last_error], 500);
  }
  wp_send_json_success(['updated'=>(int)$updated]);
}

/** Insert qualification */
function tp_add_qualification() {
  list($wpdb, $teacher_id) = tp_account_requirements_or_die();

  $q = [
    'teacher_id'    => $teacher_id,
    'qualification' => sanitize_text_field($_POST['qualification'] ?? ''),
    'university'    => sanitize_text_field($_POST['university'] ?? ''),
    'year_completed'=> preg_replace('/[^0-9]/','', $_POST['year_completed'] ?? ''),
    'grade_or_cgpa' => sanitize_text_field($_POST['grade_or_cgpa'] ?? ''),
    'country_name'  => sanitize_text_field($_POST['country_name'] ?? ''),
    'created_at'    => current_time('mysql'),
  ];

  $ok = $wpdb->insert('wpC_teacher_qualifications', $q);
  if (!$ok) wp_send_json_error(['message'=>'DB error: '.$wpdb->last_error], 500);

  wp_send_json_success(['qualification_id' => (int)$wpdb->insert_id]);
}

/** Update qualification (verify ownership) */
function tp_update_qualification() {
  list($wpdb, $teacher_id) = tp_account_requirements_or_die();

  $qid = isset($_POST['qualification_id']) ? (int)$_POST['qualification_id'] : 0;
  if ($qid <= 0) wp_send_json_error(['message'=>'Missing id'], 400);

  $owner = $wpdb->get_var($wpdb->prepare(
    "SELECT teacher_id FROM wpC_teacher_qualifications WHERE qualification_id=%d LIMIT 1", $qid
  ));
  if ((int)$owner !== $teacher_id) wp_send_json_error(['message'=>'Not allowed'], 403);

  $data = [
    'qualification' => sanitize_text_field($_POST['qualification'] ?? ''),
    'university'    => sanitize_text_field($_POST['university'] ?? ''),
    'year_completed'=> preg_replace('/[^0-9]/','', $_POST['year_completed'] ?? ''),
    'grade_or_cgpa' => sanitize_text_field($_POST['grade_or_cgpa'] ?? ''),
    'country_name'  => sanitize_text_field($_POST['country_name'] ?? ''),
  ];

  $ok = $wpdb->update('wpC_teacher_qualifications', $data, ['qualification_id'=>$qid], null, ['%d']);
  if ($ok === false) wp_send_json_error(['message'=>'DB error: '.$wpdb->last_error], 500);

  wp_send_json_success(['updated'=>(int)$ok]);
}

/** Delete qualification (verify ownership) */
function tp_delete_qualification() {
  list($wpdb, $teacher_id) = tp_account_requirements_or_die();

  $qid = isset($_POST['qualification_id']) ? (int)$_POST['qualification_id'] : 0;
  if ($qid <= 0) wp_send_json_error(['message'=>'Missing id'], 400);

  $owner = $wpdb->get_var($wpdb->prepare(
    "SELECT teacher_id FROM wpC_teacher_qualifications WHERE qualification_id=%d LIMIT 1", $qid
  ));
  if ((int)$owner !== $teacher_id) wp_send_json_error(['message'=>'Not allowed'], 403);

  $ok = $wpdb->delete('wpC_teacher_qualifications', ['qualification_id'=>$qid], ['%d']);
  if ($ok === false) wp_send_json_error(['message'=>'DB error: '.$wpdb->last_error], 500);

  wp_send_json_success(['deleted'=>(int)$ok]);
}




// Handle AJAX: Load teacher sessions (upcoming, taught, missed)
add_action('wp_ajax_load_teacher_sessions', 'load_teacher_sessions_ajax');

function load_teacher_sessions_ajax() {
    global $wpdb;
    $current_user = wp_get_current_user();
    $teacher = $wpdb->get_row($wpdb->prepare("SELECT teacher_id FROM wpC_teachers_main WHERE Email = %s", $current_user->user_email));
    $teacher_id = $teacher ? (int)$teacher->teacher_id : 0;

    $view = $_POST['view'] ?? 'upcoming';
    $limit = 10;
    $offset = isset($_POST['page']) ? (intval($_POST['page']) - 1) * $limit : 0;

    $where = "WHERE sl.teacher_id = %d AND sl.status = 'booked'";
    if ($view === 'upcoming') {
        $where .= " AND sl.is_taught = 0 AND sl.lecture_book_date >= CURDATE()";
    } elseif ($view === 'taught') {
        $where .= " AND sl.is_taught = 1";
    } elseif ($view === 'missed') {
        $where .= " AND sl.is_taught = 0 AND sl.lecture_book_date < CURDATE()";
    }

    $query = $wpdb->prepare("
        SELECT sl.*, sr.full_name AS student_name, s.SubjectName
        FROM wpC_student_lectures sl
        LEFT JOIN wpC_student_register sr ON sl.student_id = sr.student_id
        LEFT JOIN wpC_subjects s ON sl.subject_id = s.subject_id
        $where
        ORDER BY sl.lecture_book_date ASC, sl.lecture_time ASC
        LIMIT %d OFFSET %d
    ", $teacher_id, $limit, $offset);

    $results = $wpdb->get_results($query);

    if ($results) {
        foreach ($results as $s) {
            echo "<tr>
                <td>" . esc_html($s->SubjectName) . "</td>
                <td>" . esc_html($s->topic) . "</td>
                <td>" . esc_html(date('d M Y', strtotime($s->lecture_book_date))) . "</td>
                <td>" . esc_html(date('g:i A', strtotime($s->lecture_time))) . "</td>
                <td>" . esc_html($s->student_name) . "</td>";

            if ($view === 'upcoming') {
                echo "<td>
                    <button class='mark-taught-btn' data-id='{$s->lecture_book_id}'>✅</button>
                </td>";
            }

            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='6'>No records found.</td></tr>";
    }

    wp_die();
}

//student dashboard 
add_action('wp_ajax_load_student_modal', 'load_student_modal');
function load_student_modal() {
    $type = $_POST['type'];
    $template = get_stylesheet_directory() . "/student-dashboard/partials/modals/modal-$type.php";
    if (file_exists($template)) {
        include $template;
    } else {
        echo "<div id='dynamicModal'><div style='padding:20px;'>Modal file missing.</div></div>";
    }
    wp_die();
}

/*
function tp_enqueue_student_dashboard_scripts() {
    wp_enqueue_script(
        'student-dashboard-modals',
        get_stylesheet_directory_uri() . '/student-dashboard/assets/js/modals.js',
        array('jquery'),
        null,
        true
    );
}
add_action('wp_enqueue_scripts', 'tp_enqueue_student_dashboard_scripts');

*/

//temp


add_action('wp_ajax_mark_as_taught', 'mark_as_taught_ajax');

function mark_as_taught_ajax() {
    global $wpdb;
    $lecture_id = isset($_POST['lecture_id']) ? intval($_POST['lecture_id']) : 0;

    if ($lecture_id > 0) {
        $wpdb->update('wpC_student_lectures', ['is_taught' => 1], ['lecture_book_id' => $lecture_id]);
        echo 'success';
    } else {
        echo 'error';
    }

    wp_die();
}


// Block login for disabled accounts (teachers or any user flagged)
add_filter('authenticate', function($user){
    if (is_wp_error($user) || !$user) return $user;
    if (get_user_meta($user->ID, 'account_disabled', true)) {
        return new WP_Error('disabled', __('Your account is disabled. Contact support.'));
    }
    return $user;
}, 99);



// AJAX: activate / inactivate teacher
add_action('wp_ajax_tp_adm_t_toggle', function () {
    if ( ! current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied.'], 403);
    }
    check_ajax_referer('tp_adm_teacher'); // nonce from your JS

    global $wpdb;
    $teacher_id = isset($_POST['teacher_id']) ? (int) $_POST['teacher_id'] : 0;
    $to         = isset($_POST['to']) ? sanitize_text_field($_POST['to']) : '';
    if (!$teacher_id || ! in_array($to, ['active','inactive'], true)) {
        wp_send_json_error(['message' => 'Bad request.']);
    }

    // Fetch teacher first (we need their email, and to verify it exists)
    $teacher = $wpdb->get_row($wpdb->prepare(
        "SELECT teacher_id, Email, Status FROM wpC_teachers_main WHERE teacher_id = %d LIMIT 1",
        $teacher_id
    ));
    if ( ! $teacher) {
        wp_send_json_error(['message' => 'Teacher not found.']);
    }

    // Map intent to DB + WP meta
    $new_status      = ($to === 'active') ? 1 : 0;      // wpC_teachers_main.Status  (CAPITAL S)
    $account_disable = ($to === 'active') ? 0 : 1;      // user meta account_disabled

    // Update wpC_teachers_main — NOTE the capitalized column name "Status"
    $updated = $wpdb->update(
        'wpC_teachers_main',
        ['Status' => $new_status],
        ['teacher_id' => $teacher_id],
        ['%d'],
        ['%d']
    );

    // If update failed, return details (don’t fake success)
    if ($updated === false) {
        wp_send_json_error([
            'message' => 'DB error: ' . $wpdb->last_error,
            'query'   => $wpdb->last_query,
        ], 500);
    }

    // Update WP user’s login ability based on teacher’s email
    if (!empty($teacher->Email)) {
        $user = get_user_by('email', $teacher->Email);

        // Some sites keep teacher users under role "teacher", adjust if different
        if ($user) {
            if ($account_disable) {
                update_user_meta($user->ID, 'account_disabled', 1);
            } else {
                delete_user_meta($user->ID, 'account_disabled');
            }
        }
    }

    wp_send_json_success([
        'message' => ($to === 'active') ? 'Teacher activated' : 'Teacher inactivated',
        'status'  => $new_status,
    ]);
});



//add hourly rate
add_action('wp_ajax_tp_adm_t_rate_set', function(){
    if ( ! current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied.'], 403);
    }
    check_ajax_referer('tp_adm_teacher');

    global $wpdb;
    $teacher_id       = isset($_POST['teacher_id']) ? (int) $_POST['teacher_id'] : 0;
    $subject_level_id = isset($_POST['subject_level_id']) ? (int) $_POST['subject_level_id'] : 0;
    $hourly_rate      = isset($_POST['hourly_rate']) ? (float) $_POST['hourly_rate'] : 0.0;

    if (!$teacher_id || !$subject_level_id || $hourly_rate <= 0) {
        wp_send_json_error(['message' => 'All fields are required.']);
    }

    // Upsert pattern: if a row exists -> update, else insert
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM wpC_teacher_Hour_Rate WHERE teacher_id=%d AND subject_level_id=%d LIMIT 1",
        $teacher_id, $subject_level_id
    ));

    if ($row) {
        $ok = $wpdb->update(
            'wpC_teacher_Hour_Rate',
            ['hourly_rate' => $hourly_rate, 'updated_at' => current_time('mysql')],
            ['id' => (int)$row->id],
            ['%f','%s'],
            ['%d']
        );
    } else {
        $ok = $wpdb->insert(
            'wpC_teacher_Hour_Rate',
            [
                'teacher_id'       => $teacher_id,
                'subject_level_id' => $subject_level_id,
                'hourly_rate'      => $hourly_rate,
                'created_at'       => current_time('mysql'),
                'updated_at'       => current_time('mysql'),
            ],
            ['%d','%d','%f','%s','%s']
        );
    }

    if ($ok === false) {
        wp_send_json_error(['message' => 'DB error: '.$wpdb->last_error]);
    }
    wp_send_json_success(['message' => 'Hourly rate saved']);
});














//end temp




//
//
/*
add_action('wp_ajax_load_teacher_sessions', function () {
  global $wpdb;
  $teacher_id = get_current_user_id();
  $view = sanitize_text_field($_POST['view'] ?? 'upcoming');
  $page = max(1, intval($_POST['page']));
  $limit = 10;
  $offset = ($page - 1) * $limit;

  $base_query = "
    SELECT sl.*, sr.full_name AS student_name, s.SubjectName
    FROM wpC_student_lectures sl
    LEFT JOIN wpC_student_register sr ON sl.student_id = sr.student_id
    LEFT JOIN wpC_subjects s ON sl.subject_id = s.subject_id
    WHERE sl.teacher_id = %d AND sl.status = 'booked'
  ";

  if ($view === 'upcoming') {
    $base_query .= " AND sl.is_taught = 0 AND sl.lecture_book_date >= CURDATE()";
  } elseif ($view === 'taught') {
    $base_query .= " AND sl.is_taught = 1";
  } elseif ($view === 'missed') {
    $base_query .= " AND sl.is_taught = 0 AND sl.lecture_book_date < CURDATE()";
  }

  $base_query .= " ORDER BY sl.lecture_book_date ASC, sl.lecture_time ASC LIMIT %d OFFSET %d";

  $results = $wpdb->get_results($wpdb->prepare($base_query, $teacher_id, $limit, $offset));

  if ($results) {
    foreach ($results as $s) {
      echo "<tr>
        <td>" . esc_html($s->SubjectName) . "</td>
        <td>" . esc_html($s->topic) . "</td>
        <td>" . esc_html(date('d M Y', strtotime($s->lecture_book_date))) . "</td>
        <td>" . esc_html(date('g:i A', strtotime($s->lecture_time))) . "</td>
        <td>" . esc_html($s->student_name) . "</td>";
      if ($view === 'upcoming') {
        echo "<td><button class='mark-taught-btn' data-id='" . esc_attr($s->lecture_book_id) . "'>✅</button></td>";
      } else {
        echo "<td>-</td>";
      }
      echo "</tr>";
    }
  } else {
    echo "<tr><td colspan='6'>No {$view} sessions found.</td></tr>";
  }

  wp_die();
});


add_action('wp_ajax_mark_as_taught', function () {
  global $wpdb;
  $lecture_id = intval($_POST['lecture_id']);
  $wpdb->update('wpC_student_lectures', ['is_taught' => 1], ['lecture_book_id' => $lecture_id]);
  echo 'success';
  wp_die();
});
*/

//
//


/*
$path = get_stylesheet_directory() . '/General/tutor-registration.php';
if (file_exists($path)) {
    require_once $path;
    add_shortcode('teacher_register_form', 'teacher_registration_form');
}

*/

/*require_once get_stylesheet_directory() . '/General/tutor-registration.php';
/*add_shortcode('teacher_register_form', 'teacher_registration_form');*/





/**
 * Astra Child Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Astra Child
 * @since 1.0.0
 */

/**
 * Define Constants
 */
define( 'CHILD_THEME_ASTRA_CHILD_VERSION', '1.0.0' );

/**
 * Enqueue styles
 * 
 */



function child_enqueue_styles() {

	wp_enqueue_style( 'astra-child-theme-css', get_stylesheet_directory_uri() . '/style.css', array('astra-theme-css'), CHILD_THEME_ASTRA_CHILD_VERSION, 'all' );

	// Enqueue main.css with cache busting
	wp_enqueue_style( 'main-css', get_stylesheet_directory_uri() . '/main.css', array('astra-child-theme-css'), filemtime( get_stylesheet_directory() . '/main.css' ), 'all' );

}

add_action( 'wp_enqueue_scripts', 'child_enqueue_styles', 15 );


/*
 * Custom code starts from here
 * */

/*
 * Teachers Registration Form
 * */

//---------------------------------------------------------------------------------------------

// 🔹 Teacher Registration Shortcode
/*
function teacher_registration_form_shortcode() {
    ob_start();
    ?>

    <div class="teacher-reg-wrapper">
      <div class="teacher-reg-card">
        <form method="post" enctype="multipart/form-data" id="teacherRegForm">

          <!-- Profile Picture Section -->
          <div class="profile-pic-container">
            <label for="profilePic" class="pic-label">
              <img id="previewImage" src="<?php echo get_stylesheet_directory_uri(); ?>/assets/img/default-avatar.png" alt="Profile Picture">
              <span class="camera-icon">📷</span>
            </label>
            <input type="file" id="profilePic" name="profile_pic" accept="image/*" capture="user" hidden>
          </div>

          <!-- Tabs -->
          <div class="tab-buttons">
            <button type="button" class="tab-btn active" data-tab="personal">Personal Info</button>
            <button type="button" class="tab-btn" data-tab="qualifications">Qualifications</button>
          </div>

          <!-- Personal Info -->
          <div class="tab-content active" id="tab-personal">
            <input type="text" name="full_name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="tel" name="phone" placeholder="Phone Number" required>
          </div>

          <!-- Qualifications -->
          <div class="tab-content" id="tab-qualifications">
            <div id="qualification-wrapper">
              <div class="qualification-row">
                <input type="text" name="qualification[]" placeholder="Degree">
                <input type="text" name="university[]" placeholder="University">
                <input type="text" name="country[]" placeholder="Country">
                <input type="text" name="year[]" placeholder="Year">
              </div>
            </div>
            <button type="button" class="add-row">➕</button>
          </div>

          <button type="submit" class="submit-btn">Register</button>
        </form>
      </div>
    </div>

    <style>
    .teacher-reg-wrapper {
      display: flex;
      justify-content: center;
      margin: 2em auto;
    }
    .teacher-reg-card {
      width: 400px;
      background: #fff;
      border-radius: 8px;
      padding: 20px;
      text-align: center;
    }
    .profile-pic-container {
      position: relative;
      width: 100px;
      margin: 0 auto 20px;
    }
    .profile-pic-container img {
      width: 100px; height: 100px;
      border-radius: 50%;
      object-fit: cover;
    }
    .pic-label { cursor: pointer; }
    .camera-icon {
      position: absolute;
      bottom: 0; right: 0;
      background: #0abab5;
      color: #fff;
      border-radius: 50%;
      padding: 6px;
    }
    .tab-buttons {
      display: flex; justify-content: space-around; margin-bottom: 15px;
    }
    .tab-btn {
      padding: 8px 12px;
      border: none;
      background: #eee;
      cursor: pointer;
    }
    .tab-btn.active {
      background: #0abab5;
      color: #fff;
    }
    .tab-content { display: none; }
    .tab-content.active { display: block; }
    .tab-content input {
      width: 100%; margin-bottom: 10px; padding: 8px;
    }
    .qualification-row input {
      width: calc(25% - 5px);
      margin-right: 5px;
      padding: 6px;
    }
    .add-row {
      margin-top: 10px;
      background: #0abab5;
      color: #fff;
      border: none;
      padding: 6px 10px;
    }
    .submit-btn {
      margin-top: 20px;
      width: 100%;
      background: #0abab5;
      color: #fff;
      padding: 10px;
      border: none;
      font-size: 16px;
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
      const tabBtns = document.querySelectorAll('.tab-btn');
      const tabContents = document.querySelectorAll('.tab-content');

      tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
          tabBtns.forEach(b => b.classList.remove('active'));
          btn.classList.add('active');
          const target = btn.getAttribute('data-tab');
          tabContents.forEach(tc => {
            tc.classList.remove('active');
            if (tc.id === 'tab-' + target) tc.classList.add('active');
          });
        });
      });

      document.querySelector('.add-row').addEventListener('click', function() {
        const wrapper = document.getElementById('qualification-wrapper');
        const newRow = document.createElement('div');
        newRow.className = 'qualification-row';
        newRow.innerHTML = `
          <input type="text" name="qualification[]" placeholder="Degree">
          <input type="text" name="university[]" placeholder="University">
          <input type="text" name="country[]" placeholder="Country">
          <input type="text" name="year[]" placeholder="Year">
        `;
        wrapper.appendChild(newRow);
      });

      document.getElementById('profilePic').addEventListener('change', function(e) {
        const reader = new FileReader();
        reader.onload = function(event) {
          document.getElementById('previewImage').src = event.target.result;
        };
        reader.readAsDataURL(e.target.files[0]);
      });
    });
    </script>

    <?php
    return ob_get_clean();
}
add_shortcode('teacher_register_form', 'teacher_registration_form_shortcode');



*/

//---------------------------------------------------------------------------------------------




/*function teacher_registration_form_shortcode() {
    ob_start();
    ?>

    <style>
        .teacher-registration-form {
            max-width: 900px;
            margin: auto;
            background: transparent;
            padding: 10px;
            border-radius: 12px;
			font-family: 'Poppins';
        }

        .teacher-registration-form .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 10px;
        }

        .teacher-registration-form .form-group {
            flex: 1;
            min-width: 200px;
        }

        .teacher-registration-form label {
            color: white;
            font-weight: Normal;
            display: block;
            margin-bottom: 4px;
        }

        .teacher-registration-form input[type="text"],
        .teacher-registration-form input[type="email"],
        .teacher-registration-form input[type="file"] {
            width: 100%;
            padding: 6px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .teacher-registration-form input[type="submit"] {
            background-color: #0073aa;
            color: white;
            padding: 10px 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 10px;
        }

        .teacher-registration-form input[type="submit"]:hover {
            background-color: #005177;
        }
    </style>

    <form class="teacher-registration-form" method="post" enctype="multipart/form-data">
        
        <!-- Row 1 -->
        <div class="form-row">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Upload Photo</label>
                <input type="file" name="photo" accept="image/*" required>
            </div>
        </div>

        <!-- Row 2 -->
        <div class="form-row">
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" required>
            </div>
            <div class="form-group">
                <label>WhatsApp</label>
                <input type="text" name="whatsapp_no" required>
            </div>
        </div>

        <!-- Row 3 -->
        <div class="form-row">
            <div class="form-group">
                <label>Qualification</label>
                <input type="text" name="qualification" required>
            </div>
            <div class="form-group">
                <label>University Name</label>
                <input type="text" name="university_name" required>
            </div>
            <div class="form-group">
                <label>Country</label>
                <input type="text" name="country" required>
            </div>
        </div>

        <!-- Row 4 -->
        <div class="form-row">
            <div class="form-group">
                <label>Bank Name</label>
                <input type="text" name="bank_name" required>
            </div>
            <div class="form-group">
                <label>Bank IBAN Number</label>
                <input type="text" name="bank_account_number" required>
            </div>
        </div>

        <!-- Status checkbox -->
        <div style="margin-top: 10px;">
            <label>
                <input type="checkbox" name="status" value="1" checked> Active
            </label>
        </div>

        <input type="submit" name="teacher_register" value="Register">
    </form>

    <?php
    return ob_get_clean();
}
add_shortcode('teacher_register_form', 'teacher_registration_form_shortcode');

*/
//PHP FOR TEACHERS SHORT CODE SUBMISSION
add_action('init', 'handle_teacher_registration_form');

function handle_teacher_registration_form() {
    if (isset($_POST['teacher_register'])) {
        global $wpdb;

        $table = $wpdb->prefix . 'teachers_main';

        // Sanitize form inputs
        $full_name          = sanitize_text_field($_POST['full_name']);
        $email              = sanitize_email($_POST['email']);
        $qualification      = sanitize_text_field($_POST['qualification']);
        $university_name    = sanitize_text_field($_POST['university_name']);
        $country            = sanitize_text_field($_POST['country']);
        $phone              = sanitize_text_field($_POST['phone']);
        $whatsapp_no        = sanitize_text_field($_POST['whatsapp_no']);
        $bank_account       = sanitize_text_field($_POST['bank_account_number']);
        $bank_name          = sanitize_text_field($_POST['bank_name']);
        $status             = isset($_POST['status']) ? 1 : 0;

        // Handle photo upload
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        $uploadedfile = $_FILES['photo'];
        $upload_overrides = ['test_form' => false];
        $movefile = wp_handle_upload($uploadedfile, $upload_overrides);

        $photo_url = ($movefile && !isset($movefile['error'])) ? esc_url_raw($movefile['url']) : '';

        // Create WordPress user if email doesn't exist
        if (!email_exists($email)) {
            $random_password = wp_generate_password(12, false);
            $user_id = wp_create_user($email, $random_password, $email);

            if (!is_wp_error($user_id)) {
                wp_update_user([
                    'ID' => $user_id,
                    'display_name' => $full_name
                ]);

                // Set role to teacher
                $user = new WP_User($user_id);
                $user->set_role('teacher');

                // Save to custom table
                $wpdb->insert($table, [
                    'FullName'          => $full_name,
                    'Email'             => $email,
                    'Qualification'     => $qualification,
                    'UniversityName'    => $university_name,
                    'Country'           => $country,
                    'Phone'             => $phone,
                    'WhatsappNo'        => $whatsapp_no,
                    'Photo'             => $photo_url,
                    'BankAccountNumber' => $bank_account,
                    'BankName'          => $bank_name,
                    'Status'            => $status
                ]);

                // ✅ Auto-login the user
                wp_set_current_user($user_id);
                wp_set_auth_cookie($user_id);
                do_action('wp_login', $email, get_user_by('id', $user_id));

                // ✅ Redirect to teacher dashboard
                wp_redirect(home_url('/teacher-dashboard'));
                exit;
            } else {
                add_action('wp_footer', function () {
                    echo "<script>alert('Failed to create WordPress user.');</script>";
                });
            }
        } else {
            add_action('wp_footer', function () {
                echo "<script>alert('This email is already registered. Please log in.');</script>";
            });
        }
    }
}


add_action('init', 'create_teacher_role');


//create_teacher_role() 
function create_teacher_role() {
    if (!get_role('teacher')) {
        add_role('teacher', 'Teacher', [
            'read' => true,              // Allow reading
            'edit_posts' => false,       // Cannot edit posts
            'delete_posts' => false,     // Cannot delete posts
        ]);
    }
}


add_action('init', 'handle_teacher_schedule_submission');
function handle_teacher_schedule_submission() {
    if (isset($_POST['add_schedule']) && is_user_logged_in()) {
        global $wpdb;
        $user = wp_get_current_user();
        $email = $user->user_email;

        $teacher = $wpdb->get_row($wpdb->prepare("SELECT * FROM wp_teachers_main WHERE Email = %s", $email));
        if (!$teacher) return;

        $wpdb->insert('wp_teacher_sessions', [
            'TeacherID'     => $teacher->TeacherID,
            'subjectID'    => intval($_POST['subject_id']),
            'session_date'  => sanitize_text_field($_POST['session_date']),
            'start_time'    => sanitize_text_field($_POST['start_time']),
            'end_time'      => sanitize_text_field($_POST['end_time']),
            'status'        => 'Pending'
        ]);

		
		// ✅ Redirect to avoid re-submission on refresh
        wp_redirect($_SERVER['REQUEST_URI']);
        exit;
		
        if ($result === false) {
            echo "<script>alert('❌ Insert failed: " . esc_js($wpdb->last_error) . "');</script>";
        } else {
            echo "<script>alert('✅ Schedule saved successfully.');</script>";
        }
		
        // ✅ Redirect to avoid re-submission on refresh
        wp_redirect($_SERVER['REQUEST_URI']);
        exit;
		 $_SESSION['schedule_submitted'] = true;
    }
}
$_SESSION['schedule_success'] = true;


/*new

add_action('init', 'handle_teacher_schedule_submission');
function handle_teacher_schedule_submission() {
    if (isset($_POST['add_schedule']) && is_user_logged_in()) {
        global $wpdb;
        $user = wp_get_current_user();
        $email = $user->user_email;

        $teacher = $wpdb->get_row($wpdb->prepare("SELECT * FROM wp_teachers_main WHERE Email = %s", $email));
        if (!$teacher) return;

        $result = $wpdb->insert('wp_teacher_sessions', [
            'TeacherID'   => $teacher->TeacherID,
            'subjectID'   => intval($_POST['subject_id']),
            'session_date' => sanitize_text_field($_POST['session_date']),
            'start_time'   => sanitize_text_field($_POST['start_time']),
            'end_time'     => sanitize_text_field($_POST['end_time']),
            'status'       => 'Pending'
        ]);

		// ✅ Redirect to avoid re-submission on refresh
        wp_redirect($_SERVER['REQUEST_URI']);
        exit;
		
        if ($result === false) {
            echo "<script>alert('❌ Insert failed: " . esc_js($wpdb->last_error) . "');</script>";
        } else {
            echo "<script>alert('✅ Schedule saved successfully.');</script>";
        }
    }
}



/*new ends*/
/*
add_action('init', 'handle_teacher_schedule_submission');
function handle_teacher_schedule_submission() {
    if (isset($_POST['add_schedule']) && is_user_logged_in()) {
        global $wpdb;
        $user = wp_get_current_user();
        $email = $user->user_email;

        $teacher = $wpdb->get_row($wpdb->prepare("SELECT * FROM wp_teachers_main WHERE Email = %s", $email));
        if (!$teacher) return;
		
		 // ✅ Show submitted values
				echo "<script>
				alert(
					'Submitting Schedule:\\n' +
					
					'Subject ID: " . $_POST['subject_id'] . "\\n' +
					'Date: " . $_POST['session_date'] . "\\n' +
					'Start: " . $_POST['start_time'] . "\\n' +
					'End: " . $_POST['end_time'] . "'
				);
			</script>";
		
		
		
        $wpdb->insert('wp_teacher_sessions', [
            'teacher_id'   => $teacher->TeacherID,
            'subject_id'   => intval($_POST['subject_id']),
            'session_date' => sanitize_text_field($_POST['session_date']),
            'start_time'   => sanitize_text_field($_POST['start_time']),
            'end_time'     => sanitize_text_field($_POST['end_time']),
            'status'       => 'pending'
        ]);
    }
}
/*-----old ends------------*/
add_action('init', 'handle_schedule_retake_submission');
function handle_schedule_retake_submission() {
    if (isset($_POST['schedule_retake']) && is_user_logged_in()) {
        global $wpdb;
        $retake_id   = intval($_POST['retake_lecture_id']);
        $new_date    = sanitize_text_field($_POST['retake_date']);
        $new_time    = sanitize_text_field($_POST['retake_time']);

        // ✅ Update the retake lecture with new schedule
        $wpdb->update('wp_student_lectures', [
            'lecture_date' => $new_date,
            'lecture_time' => $new_time,
            'status'       => 'retake-scheduled'
        ], [
            'id' => $retake_id
        ]);

        echo "<script>setTimeout(() => alert('✅ Retake lecture has been scheduled.'), 300);</script>";
    }
	
}





add_action('after_setup_theme', function () {
    $base = get_stylesheet_directory() . '/teacher-dashboard/';

    require_once $base . 'teacher-dashboard.php'; // main logic (shortcode)
    require_once $base . 'functions/schedule-handler.php'; // ✅ THIS IS ENOUG
    require_once $base . 'functions/helpers.php';
	
	
	
	require_once get_stylesheet_directory() . '/teacher-dashboard/functions/retake-handler.php';
	

});

add_action('wp_footer', function () {
    if (is_page('tutorsdashboard')) { // change slug if needed
        echo '<script>var ajaxurl = "' . admin_url('admin-ajax.php') . '";</script>';
    }
});




add_action('wp_ajax_load_retake_data', 'load_retake_data');

add_action('wp_ajax_nopriv_load_retake_data', 'load_retake_data');


function student_dashboard_shortcode() {
    ob_start();
    include get_stylesheet_directory() . '/student-dashboard/student-dashboard.php'; // adjust path if needed
    return ob_get_clean();
}
add_shortcode('student_dashboard', 'student_dashboard_shortcode');




//video class room
/*
add_shortcode('zego_video_conference', 'zego_video_conference_shortcode');
function zego_video_conference_shortcode() {
    $appID = YOUR_APP_ID; // Replace with your real App ID
    $serverSecret = 'YOUR_SERVER_SECRET'; // Replace with your server secret
    $userID = get_current_user_id();
    $userName = wp_get_current_user()->display_name;
    $roomID = 'live_classroom';

    // Generate Token
    $token = hash_hmac('sha256', $appID . $userID . $roomID . time(), $serverSecret);

    ob_start(); ?>
    
    <div id="zego-container" style="height: 600px; width: 100%;"></div>

    <!-- ✅ Zego SDK Script (must be first) -->
    <script src="https://zegocloud.github.io/zego-express-web/video/zego-express-video.min.js"></script>

    <script>
    document.addEventListener("DOMContentLoaded", async function () {
        const appID = <?php echo $appID; ?>;
        const server = "wss://webliveroom-test.zegocloud.com/ws";
        const userID = "<?php echo $userID; ?>";
        const userName = "<?php echo $userName; ?>";
        const token = "<?php echo $token; ?>";
        const roomID = "<?php echo $roomID; ?>";

        const zg = new ZegoExpressEngine(appID, server);

        await zg.loginRoom(roomID, token, {
            userID: userID,
            userName: userName
        }, { userUpdate: () => {} });

        zg.startPublishingStream("stream1", {
            camera: {
                video: true,
                audio: true
            }
        });

        zg.createLocalStreamView("stream1", document.getElementById("zego-container"));
    });
    </script>
    
    <?php
    return ob_get_clean();
}
*/
//ds
// Define Zego constants (replace with your actual credentials)
add_shortcode('zegocloud_classroom', 'zegocloud_live_classroom_shortcode');

function zegocloud_live_classroom_shortcode($atts) {
    $atts = shortcode_atts([
        'room_id'   => 'class_' . wp_generate_password(6, false),
        'user_id'   => is_user_logged_in() ? strval(get_current_user_id()) : 'guest_' . rand(1000, 9999),
        'user_name' => is_user_logged_in() ? esc_js(wp_get_current_user()->display_name) : 'Guest',
        'role'      => 'teacher'
    ], $atts);

    ob_start();
    ?>
    <div id="zego-classroom-container" style="width:100%;height:80vh;border:1px solid #ccc;"></div>

    <script src="https://unpkg.com/@zegocloud/zego-uikit-prebuilt@latest/zego-uikit-prebuilt.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', async () => {
        const container = document.getElementById('zego-classroom-container');
        try {
            await navigator.mediaDevices.getUserMedia({ audio: true, video: true });

            const tokenData = await fetch('<?php echo esc_url(rest_url('zego/v1/token')); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    roomID: '<?php echo esc_js($atts["room_id"]); ?>',
                    userID: '<?php echo esc_js($atts["user_id"]); ?>',
                    userName: '<?php echo esc_js($atts["user_name"]); ?>',
                    role: '<?php echo esc_js($atts["role"]); ?>'
                })
            }).then(res => res.json());

            const zp = ZegoUIKitPrebuilt.create(tokenData.token);
            zp.joinRoom({
                container: container,
                scenario: {
                    mode: 'education',
                    config: {
                        role: '<?php echo esc_js($atts["role"]); ?>'
                    }
                },
                showPreJoinView: false
            });
        } catch (error) {
            container.innerHTML = `
                <div style="color:red;padding:20px;text-align:center;">
                    <h4>Error Loading Classroom</h4>
                    <p>${error.message}</p>
                    ${error.message.includes('permission') ? '<p>Please allow camera/microphone access</p>' : ''}
                    <button onclick="window.location.reload()" 
                            style="padding:8px 15px;background:#0073aa;color:white;border:none;">
                        Retry
                    </button>
                </div>`;
            console.error('ZEGO Error:', error);
        }
    });
    </script>
    <?php
    return ob_get_clean();
}
add_action('rest_api_init', function () {
    register_rest_route('zego/v1', '/token', [
        'methods'  => 'POST',
        'callback' => 'generate_zego_token',
        'permission_callback' => '__return_true',
    ]);
});

function generate_zego_token(WP_REST_Request $request) {
    $app_id = 495983226; // your ZEGOCLOUD AppID
    $server_secret = '88c3614282518c89f998690d684e5adb'; // your ServerSecret

    $body = json_decode($request->get_body(), true);
    $room_id = sanitize_text_field($body['roomID'] ?? 'default_room');
    $user_id = sanitize_text_field($body['userID'] ?? 'guest_' . rand(1000, 9999));
    $user_name = sanitize_text_field($body['userName'] ?? 'Guest');
    $role = sanitize_text_field($body['role'] ?? 'student');

    $expire_time = time() + 3600; // valid for 1 hour

    $payload = [
        'app_id' => $app_id,
        'room_id' => $room_id,
        'user_id' => $user_id,
        'privilege' => [
            '1' => 1, // login
            '2' => 1, // publish stream
        ],
        'expire_at' => $expire_time
    ];

    $json = json_encode($payload);
    $signature = hash_hmac('sha256', $json, $server_secret);
    $token = base64_encode($json) . '.' . $signature;

    return rest_ensure_response(['token' => $token]);
	
	// in functions.php (child‐theme or theme)
//require_once get_stylesheet_directory() . '/general/HomePage-Levels.php';
	
	

}

// 1A) Register the shortcode
add_action('init', function() {
    add_shortcode('level_subjects', 'ls_render_level_subjects');
});

// 1B) Enqueue our JS and localize the AJAX URL
/*

add_action('wp_enqueue_scripts', function() {
    wp_enqueue_script(
        'ls-level-subjects',
        get_stylesheet_directory_uri() . '/js/level-subjects.js',
        ['jquery'],
        '1.0',
        true
    );
    wp_localize_script('ls-level-subjects', 'LS_Ajax', [
        'ajax_url' => admin_url('admin-ajax.php')
    ]);
});
*/
// 1C) Shortcode output
function ls_render_level_subjects() {
    ob_start(); ?>
    <div class="ls-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
      <div>
        <label for="ls-level" style="color: white";>Level</label><br>
        <select id="ls-level">
          <option value="">– Select Level –</option>
        </select>
      </div>
      <div>
        <label for="ls-subject">Subject</label><br>
        <select id="ls-subject" disabled>
          <option value="">Select a level first</option>
        </select>
      </div>
    </div>
    <?php
    return ob_get_clean();
}

// 1D) AJAX: return all levels
add_action('wp_ajax_get_levels', 'ls_get_levels');
add_action('wp_ajax_nopriv_get_levels', 'ls_get_levels');
function ls_get_levels() {
    global $wpdb;
    $levels = $wpdb->get_results("SELECT subject_level_id, level FROM wpC_subjects_level ORDER BY level");
    wp_send_json_success($levels);
}

// 1E) AJAX: return subjects for a level
add_action('wp_ajax_get_subjects_by_level', 'ls_get_subjects_by_level');
add_action('wp_ajax_nopriv_get_subjects_by_level', 'ls_get_subjects_by_level');
function ls_get_subjects_by_level() {
    global $wpdb;
    $lvl = intval($_POST['level_id']);
    $subs = $wpdb->get_results($wpdb->prepare("
        SELECT s.subject_id, s.SubjectName
        FROM wpC_subjects s
        JOIN wpC_subjects_level sl
          ON s.subject_id = sl.subject_Id
        WHERE sl.subject_level_id = %d
        ORDER BY s.SubjectName
    ", $lvl));
    wp_send_json_success($subs);
}



/**
 * Lightweight PDF "stamper" endpoint.
 * Usage:  /?tp_terms_pdf=1&file=terms-privacy.pdf
 * Shows the PDF and, on Print, adds header/footer with URL + date.
 */
add_action('template_redirect', function () {

  if (!isset($_GET['tp_terms_pdf'])) return;

  // --- CONFIG: where your PDFs are kept ---
  $subdir   = 'uploads/docs'; // relative to wp-content
  $filename = isset($_GET['file']) ? sanitize_file_name($_GET['file']) : 'terms-privacy.pdf';

  // Absolute path & public URL
  $base_dir = WP_CONTENT_DIR . '/' . $subdir;
  $base_url = content_url($subdir);
  $abs      = trailingslashit($base_dir) . $filename;
  $url      = trailingslashit($base_url) . rawurlencode($filename);

  if (!file_exists($abs)) {
    status_header(404);
    wp_die('<p style="font-family:system-ui,Segoe UI,Arial">PDF not found.</p>');
  }

  // Values for the stamp
  $site_url   = home_url('/');
  $printed_on = date_i18n('Y-m-d H:i');  // site timezone
  $title      = 'Terms & Privacy';

  // Prevent caching so date/time in the stamp is fresh
  nocache_headers();

  ?>
  <!doctype html>
  <html <?php language_attributes(); ?>>
  <head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html($title); ?></title>
    <style>
      :root{ --ink:#0f172a; --muted:#64748b; --bar:#0b1220; --txt:#e5f2ff; }
      html,body{height:100%}
      body{margin:0; background:#0a0f1a; color:#e5f2ff; font:14px/1.4 ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Arial}
      .topbar{
        position:sticky; top:0; z-index:2; display:flex; gap:8px; align-items:center; justify-content:flex-end;
        padding:8px 10px; background:#0b1220; border-bottom:1px solid rgba(255,255,255,.08)
      }
      .topbar .spacer{margin-right:auto; font-weight:600}
      .btn{
        appearance:none; border:1px solid rgba(255,255,255,.16); background:transparent; color:#cfe9ff;
        padding:6px 10px; border-radius:8px; cursor:pointer; font-size:12px
      }
      .btn:hover{border-color:rgba(255,255,255,.28)}
      .frame{
        position:relative; height:calc(100% - 44px); /* minus the topbar */
      }
      .frame embed, .frame object, .frame iframe{
        width:100%; height:100%; border:0; background:#111
      }

      /* PRINT STAMP: fixed header/footer on paper */
      @media print {
        body{background:#fff; color:#000}
        .topbar{display:none}
        @page{ margin:18mm }
        .print-header, .print-footer{
          position:fixed; left:0; right:0; color:#444; font:12px/1.2 ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Arial
        }
        .print-header{ top:0; border-bottom:1px solid #ccc; padding:6px 10mm }
        .print-footer{ bottom:0; border-top:1px solid #ccc; padding:6px 10mm; display:flex; justify-content:space-between }
      }
    </style>
  </head>
  <body>
    <div class="topbar">
      <div class="spacer"><?php echo esc_html($title); ?></div>
      <a class="btn" href="<?php echo esc_url($url); ?>" download>Download</a>
      <button class="btn" onclick="window.print()">Print</button>
      <button class="btn" onclick="window.close()">OK</button>
    </div>

    <!-- The PDF -->
    <div class="frame">
      <!-- Use <embed>; most browsers show their native viewer (with zoom/page controls) -->
      <embed src="<?php echo esc_url($url); ?>" type="application/pdf">
    </div>

    <!-- What actually appears on paper when user prints -->
    <div class="print-header">
      <?php echo esc_html(get_bloginfo('name')); ?> — <?php echo esc_html($title); ?>
    </div>
    <div class="print-footer">
      <div>Printed: <?php echo esc_html($printed_on); ?></div>
      <div>Source: <?php echo esc_html($site_url); ?></div>
    </div>
  </body>
  </html>
  <?php
  exit;
});









//////////////////////////////






/////////////////////////////

/*
add_shortcode('zego_video_conference', 'zego_video_conference_shortcode');
function zego_video_conference_shortcode() {
    $appID = 123456789; // replace with your actual AppID
    $serverSecret = 'c186b809ae926b7d55b0921297ebda88'; // replace with your real server secret
    $userID = get_current_user_id();
    $userName = wp_get_current_user()->display_name ?: 'Anonymous';
    $roomID = 'live_classroom';

    // Generate basic token for demo (not secure)
    $token = hash('sha256', $appID . $userID . $roomID);

    ob_start(); ?>
    
    <div id="zego-container" style="height: 600px; width: 100%; background: #000; color: #fff; display: flex; align-items: center; justify-content: center;">
        <strong>Initializing Video...</strong>
    </div>

 /<!--   <script src="https://zegocloud.github.io/zego-express-web/video/zego-express-video.min.js"></script> -->
<script src="https://cdn.zegocloud.com/sdk/zego-express-video/2.20.0/ZegoExpressVideo.min.js"></script>

    <script>
    function initZegoVideoConference() {
        const zg = new ZegoExpressEngine(<?php echo $appID; ?>, "wss://webliveroom-test.zegocloud.com/ws");

        zg.loginRoom("<?php echo $roomID; ?>", "<?php echo $token; ?>", {
            userID: "<?php echo $userID; ?>",
            userName: "<?php echo $userName; ?>"
        }).then(() => {
            zg.createStream({ camera: { video: true, audio: true } })
              .then(stream => {
                  const videoEl = document.createElement("video");
                  videoEl.srcObject = stream;
                  videoEl.autoplay = true;
                  videoEl.playsInline = true;
                  videoEl.style.width = "100%";
                  videoEl.style.height = "100%";
                  document.getElementById("zego-container").innerHTML = "";
                  document.getElementById("zego-container").appendChild(videoEl);
              });
        });
    }

    // Wait until Zego SDK script is loaded
    if (typeof ZegoExpressEngine === "undefined") {
        window.addEventListener("load", function () {
            setTimeout(() => {
                if (typeof ZegoExpressEngine !== "undefined") {
                    initZegoVideoConference();
                } else {
                    console.error("Zego SDK failed to load.");
                }
            }, 1000);
        });
    } else {
        initZegoVideoConference();
    }
    </script>

    <?php
    return ob_get_clean();
}

function enqueue_zego_sdk_script() {
    if (is_page('liveclassroom')) {
        wp_enqueue_script(
            'zego-sdk',
            'https://cdn.zegocloud.com/sdk/zego-express-video/2.20.0/ZegoExpressVideo.min.js',
            [],
            null,
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_zego_sdk_script');


function enqueue_zego_sdk_script() {
    if (is_page('liveclassroom')) {
        wp_enqueue_script(
            'zego-sdk',
            'https://cdn.zegocloud.com/sdk/zego-express-video/2.20.0/ZegoExpressVideo.min.js',
            array(), null, true
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_zego_sdk_script');



*/

//jitsi
/*
function custom_jitsi_shortcode($atts) {
    $atts = shortcode_atts([
        'room' => 'default-room-' . rand(1000,9999),
        'user' => is_user_logged_in() ? wp_get_current_user()->display_name : 'Guest',
        'width' => '100%',
        'height' => '700px'
    ], $atts);

    return '
    <iframe 
        src="https://meet.jit.si/' . esc_attr($atts['room']) . '?userInfo.displayName=' . urlencode($atts['user']) . '"
        width="' . $atts['width'] . '"
        height="' . $atts['height'] . '"
        frameborder="0"
        allow="camera; microphone"
        style="border: 1px solid #ddd;"
    ></iframe>';
}
add_shortcode('custom_jitsi', 'custom_jitsi_shortcode'); */



//temp short Code

/**
 * Shortcode: [tp_liveclassroom]
 * A minimal ZEGO 1-to-1 classroom that doesn’t rely on other site scripts.
 * DEV ONLY: uses client-side generateKitTokenForTest; switch to a server token later.
 */
// Shortcode: [tp_liveclassroom]
// [tp_liveclassroom]
/*
add_shortcode('tp_liveclassroom', function () {

  if (!defined('TP_ZEGO_APP_ID') || !defined('TP_ZEGO_SERVER_SECRET') || !TP_ZEGO_APP_ID || !TP_ZEGO_SERVER_SECRET) {
    return '<div style="padding:12px;border:1px solid #fee;background:#fff7f7;border-radius:8px;color:#991b1b">
              ZEGO not configured: set TP_ZEGO_APP_ID and TP_ZEGO_SERVER_SECRET in wp-config.php.
            </div>';
  }

  $roomID = isset($_GET['roomID']) ? preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['roomID']) : 'demo_room';
  $roleQ  = isset($_GET['role']) ? strtolower($_GET['role']) : 'student'; // teacher|student
  $user   = wp_get_current_user();

  $userID   = $user && $user->ID ? ('u'.$user->ID) : ('guest_'.wp_generate_uuid4());
  $userName = $user && $user->display_name ? $user->display_name : ($user->user_email ?: 'Guest');

  ob_start(); ?>
    <div id="tp-zego-root" style="height:72vh; width:100%; background:#000;border-radius:12px;overflow:hidden"></div>

    <!-- ZEGO Call Kit -->
    <script src="https://cdn.jsdelivr.net/npm/@zegocloud/zego-uikit-prebuilt/zego-uikit-prebuilt.js"></script>

    <script>
    (function(){
      // NOTE: This uses generateKitTokenForTest (okay for testing).
      // For production, replace with a tokenServer endpoint.
      const appID        = <?php echo (int) TP_ZEGO_APP_ID; ?>;
      const serverSecret = <?php echo json_encode(TP_ZEGO_SERVER_SECRET); ?>;
      const roomID       = <?php echo json_encode($roomID); ?>;
      const userID       = <?php echo json_encode($userID); ?>;
      const userName     = <?php echo json_encode($userName); ?>;
      const isTeacher    = <?php echo $roleQ === 'teacher' ? 'true' : 'false'; ?>;

      const kitToken = ZegoUIKitPrebuilt.generateKitTokenForTest(appID, serverSecret, roomID, userID, userName);
      const zp = ZegoUIKitPrebuilt.create(kitToken);

      zp.joinRoom({
        container: document.getElementById('tp-zego-root'),
        // One-to-one tutoring
        scenario: { mode: ZegoUIKitPrebuilt.OneONoneCall },
        turnOnCameraWhenJoining: true,
        turnOnMicrophoneWhenJoining: true,
        showScreenSharingButton: false,
        showTextChat: true,
        showUserList: false,
        // keep UI simple
        layout: "Auto",
        // teacher can be treated as "host" via role if needed later
        branding: { logoURL: "" }
      });
    })();
    </script>
  <?php
  return ob_get_clean();
});
*/
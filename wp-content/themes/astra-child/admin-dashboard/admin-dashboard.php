<?php
/**
 * Admin Dashboard Shell (+ AJAX + Helpers)
 * Path: /wp-content/themes/astra-child/admin-dashboard/admin-dashboard.php
 * Renders a 10-tab interface via [tp_admin_dashboard]
 */

if (!defined('ABSPATH')) exit;

/* ---------- Helpers ---------- */

/** Return table name with auto-detected prefix (wpC_ wins if exists) */
function tp_adm_tbl($base){
  global $wpdb;
  $custom = $wpdb->get_var( $wpdb->prepare("SHOW TABLES LIKE %s", 'wpC_'.$base) );
  if ($custom === 'wpC_'.$base) return 'wpC_'.$base;
  return $wpdb->prefix . $base;
}

/** Ensure level_hourly_rates table exists (idempotent) */
function tp_adm_ensure_rate_table(){
  global $wpdb;
  $table   = tp_adm_tbl('level_hourly_rates');
  $charset = $wpdb->get_charset_collate();
  require_once ABSPATH . 'wp-admin/includes/upgrade.php';
  $sql = "CREATE TABLE {$table} (
    rate_id        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    level_id       BIGINT UNSIGNED NOT NULL,
    currency       CHAR(3) NOT NULL DEFAULT 'PKR',
    hourly_rate    DECIMAL(10,2) NOT NULL,
    effective_from DATE NOT NULL,
    effective_to   DATE NULL,
    status         TINYINT(1) NOT NULL DEFAULT 1,
    notes          VARCHAR(255) NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (rate_id),
    KEY idx_level (level_id),
    KEY idx_status (status),
    KEY idx_dates (effective_from, effective_to),
    UNIQUE KEY uniq_level_curr_from (level_id, currency, effective_from)
  ) {$charset};";
  dbDelta($sql);
}

/** Attempt to resolve columns for class_levels table */
function tp_adm_levels_cols(){
  global $wpdb;
  $table = tp_adm_tbl('class_levels');
  $cols  = $wpdb->get_col("SHOW COLUMNS FROM {$table}");
  // prefer common names
  $id = in_array('id',$cols,true) ? 'id' : (in_array('level_id',$cols,true) ? 'level_id' : null);
  $nm = in_array('level_name',$cols,true) ? 'level_name' : (in_array('name',$cols,true) ? 'name' : null);
  return [$table, $id, $nm];
}

/* ---------- Shortcode Shell ---------- */
add_shortcode('tp_admin_dashboard', function(){
  if (!current_user_can('edit_posts')) {
    return '<div class="tp-admin-dash">You do not have permission to view this dashboard.</div>';
  }

  tp_adm_ensure_rate_table();

  $tabs = [
    'levels-rates' => 'Levels & Hourly Rates',
    'subjects'     => 'Subjects',
    'teachers'     => 'Teachers',
    'students'     => 'Students',
    'schedules'    => 'Schedules',
    'bookings'     => 'Bookings',
    'retakes'      => 'Retakes',
    'finance'      => 'Finance',
    'suggestions'  => 'Blog Suggestions',
    'settings'     => 'Settings & Logs',
  ];

  $active = isset($_GET['tab']) && isset($tabs[$_GET['tab']]) ? sanitize_key($_GET['tab']) : 'levels-rates';
  $base_url = esc_url(remove_query_arg(['tab']));

  ob_start(); ?>
  <div class="tp-admin-dash">
    <div class="tp-ad-nav" role="tablist">
      <?php foreach($tabs as $slug=>$label): ?>
        <a class="tp-ad-tab <?php echo $slug===$active?'active':''; ?>"
           href="<?php echo esc_url(add_query_arg('tab',$slug,$base_url)); ?>">
          <?php echo esc_html($label); ?>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="tp-ad-body">
      <?php
       // Simpler + safer: map active tab to partial filename
$tab_file_map = [
  'levels-rates' => '1-levels-rates',
  'subjects'     => '2',
  'teachers'     => '3',
  'students'     => '4',
  'schedules'    => '5',
  'bookings'     => '6',
  'retakes'      => '7',
  'finance'      => '8',
  'suggestions'  => '9',
  'settings'     => '10',
];
$slug    = isset($tab_file_map[$active]) ? $tab_file_map[$active] : '1-levels-rates';
$partial = get_stylesheet_directory() . '/admin-dashboard/partials/tab-' . $slug . '.php';

                   '.php';
        if (file_exists($partial)) {
          include $partial;
        } else {
          echo '<div class="tp-ad-empty">Partial not found: '.esc_html(basename($partial)).'</div>';
        }
      ?>
    </div>
  </div>

  <style>
    .tp-admin-dash{font-family:Roboto,Arial,sans-serif}
    .tp-ad-nav{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px}
    .tp-ad-tab{
      text-decoration:none;padding:8px 10px;border:1px solid #ccc;border-radius:6px;
      font-size:13px;color:#222;background:#fff;transition:background .15s ease,opacity .15s ease
    }
    .tp-ad-tab:hover{background:#f6f6f6}
    .tp-ad-tab.active{background:#111;color:#fff;border-color:#111}
    .tp-ad-body{border:1px solid #ddd;border-radius:8px;padding:12px;background:#fff}
    .tp-ad-empty{padding:10px;color:#777}
  </style>
  <?php
  return ob_get_clean();
});

/* ---------- AJAX: Levels & Rates (Tab 1) ---------- */

/** List/search levels */
add_action('wp_ajax_tp_adm_lv_list', function(){
  if (!current_user_can('edit_posts')) wp_send_json_error('No permission');
  check_ajax_referer('tp_adm_lv');

  global $wpdb;
  list($t,$id,$nm) = tp_adm_levels_cols();
  if (!$id || !$nm) wp_send_json_error('Levels table/columns not found');

  $q = isset($_POST['q']) ? trim(wp_unslash($_POST['q'])) : '';
  if ($q!=='') {
    $like = '%'.$wpdb->esc_like($q).'%';
    $rows = $wpdb->get_results( $wpdb->prepare("SELECT {$id} AS id, {$nm} AS name FROM {$t} WHERE {$nm} LIKE %s ORDER BY {$nm} ASC LIMIT 200",$like) );
  } else {
    $rows = $wpdb->get_results("SELECT {$id} AS id, {$nm} AS name FROM {$t} ORDER BY {$nm} ASC LIMIT 200");
  }
  wp_send_json_success(['items'=> array_map(fn($r)=>['id'=>(int)$r->id,'name'=>$r->name], $rows ?: []) ]);
});

/** Add level */
add_action('wp_ajax_tp_adm_lv_add', function(){
  if (!current_user_can('edit_posts')) wp_send_json_error('No permission');
  check_ajax_referer('tp_adm_lv');

  global $wpdb;
  list($t,$id,$nm) = tp_adm_levels_cols();
  if (!$id || !$nm) wp_send_json_error('Levels table/columns not found');

  $name = sanitize_text_field($_POST['name'] ?? '');
  if ($name==='') wp_send_json_error('Level name required');

  // unique-ish check
  $exists = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE {$nm}=%s", $name) );
  if ($exists) wp_send_json_error('Level already exists');

  $ok = $wpdb->insert($t, [$nm=>$name], ['%s']);
  if (!$ok) wp_send_json_error('DB insert failed');
  wp_send_json_success(['id'=>(int)$wpdb->insert_id, 'name'=>$name]);
});

/** Update level (rename) */
add_action('wp_ajax_tp_adm_lv_update', function(){
  if (!current_user_can('edit_posts')) wp_send_json_error('No permission');
  check_ajax_referer('tp_adm_lv');

  global $wpdb;
  list($t,$id,$nm) = tp_adm_levels_cols();
  if (!$id || !$nm) wp_send_json_error('Levels table/columns not found');

  $level_id = intval($_POST['id'] ?? 0);
  $name     = sanitize_text_field($_POST['name'] ?? '');
  if (!$level_id || $name==='') wp_send_json_error('Invalid params');

  $ok = $wpdb->update($t, [$nm=>$name], [$id=>$level_id], ['%s'], ['%d']);
  if ($ok===false) wp_send_json_error('DB update failed');
  wp_send_json_success();
});

/** Get current & history rates for a level */
add_action('wp_ajax_tp_adm_rt_get', function(){
  if (!current_user_can('edit_posts')) wp_send_json_error('No permission');
  check_ajax_referer('tp_adm_rt');

  global $wpdb;
  $table = tp_adm_tbl('level_hourly_rates');
  $level = intval($_POST['level_id'] ?? 0);
  if (!$level) wp_send_json_error('Invalid level');

  $rows = $wpdb->get_results( $wpdb->prepare(
    "SELECT rate_id, currency, hourly_rate, effective_from, effective_to, status, notes
     FROM {$table} WHERE level_id=%d
     ORDER BY effective_from DESC, rate_id DESC LIMIT 200", $level
  ) );
  wp_send_json_success(['items'=>$rows ?: []]);
});

/** Add/version a new rate for a level */
add_action('wp_ajax_tp_adm_rt_add', function(){
  if (!current_user_can('edit_posts')) wp_send_json_error('No permission');
  check_ajax_referer('tp_adm_rt');

  global $wpdb;
  $table = tp_adm_tbl('level_hourly_rates');

  $level_id = intval($_POST['level_id'] ?? 0);
  $currency = strtoupper(substr(sanitize_text_field($_POST['currency'] ?? 'GBP'),0,3));
  $rate     = floatval($_POST['hourly_rate'] ?? 0);
  $from     = sanitize_text_field($_POST['effective_from'] ?? '');
  $notes    = sanitize_text_field($_POST['notes'] ?? '');

  if (!$level_id || !$from || $rate<=0) wp_send_json_error('Required fields missing');

  // Close any open-ended active rate that starts before new from
  $open = $wpdb->get_row( $wpdb->prepare(
    "SELECT rate_id, effective_from FROM {$table}
     WHERE level_id=%d AND currency=%s AND status=1 AND effective_to IS NULL
     ORDER BY effective_from DESC LIMIT 1", $level_id, $currency
  ) );

  if ($open && strtotime($open->effective_from) <= strtotime($from)) {
    // set effective_to to day before new from
    $prev_to = date('Y-m-d', strtotime($from.' -1 day'));
    $wpdb->update($table, ['effective_to'=>$prev_to], ['rate_id'=>$open->rate_id], ['%s'], ['%d']);
  }

  $ok = $wpdb->insert($table, [
    'level_id'       => $level_id,
    'currency'       => $currency,
    'hourly_rate'    => $rate,
    'effective_from' => $from,
    'effective_to'   => null,
    'status'         => 1,
    'notes'          => $notes,
    'created_at'     => current_time('mysql'),
  ], ['%d','%s','%f','%s','%s','%d','%s','%s']);

  if (!$ok) wp_send_json_error('Insert failed (duplicate start date?)');
  wp_send_json_success(['rate_id'=>(int)$wpdb->insert_id]);
});

/** Toggle rate status */
add_action('wp_ajax_tp_adm_rt_toggle', function(){
  if (!current_user_can('edit_posts')) wp_send_json_error('No permission');
  check_ajax_referer('tp_adm_rt');

  global $wpdb;
  $table = tp_adm_tbl('level_hourly_rates');
  $rid   = intval($_POST['rate_id'] ?? 0);
  $to    = intval($_POST['to'] ?? 0);
  if (!$rid) wp_send_json_error('Invalid rate_id');

  $ok = $wpdb->update($table, ['status'=>$to], ['rate_id'=>$rid], ['%d'], ['%d']);
  if ($ok===false) wp_send_json_error('Update failed');
  wp_send_json_success();
});



/* ---------- SUBJECTS (Tab 2) ---------- */

/** Resolve table names/columns for subjects and mapping */
function tp_adm_subject_tables(){
  $subjects = tp_adm_tbl('subjects');           // wpC_subjects or <prefix>subjects
  $map      = tp_adm_tbl('subjects_level');     // wpC_subjects_level or <prefix>subjects_level
  // Fixed column names per your schema:
  return [
    'subjects' => $subjects,        // columns: subject_id, SubjectName
    'map'      => $map,             // columns: subject_level_id, level_id, subject_id
  ];
}

/** (Optional) ensure uniqueness — call once if you like */
// add_action('init', function(){
//   global $wpdb; $t = tp_adm_subject_tables();
//   // Unique subject names (case-insensitive) – safe if not already there
//   @$wpdb->query("ALTER TABLE {$t['subjects']} ADD UNIQUE KEY subj_name_unique (SubjectName(180))");
//   // Unique mapping per (level, subject)
//   @$wpdb->query("ALTER TABLE {$t['map']} ADD UNIQUE KEY uniq_level_subject (level_id, subject_id)");
// });

/** List subjects attached to a level */
add_action('wp_ajax_tp_adm_sub_list_for_level', function(){
  if (!current_user_can('edit_posts')) wp_send_json_error('No permission');
  check_ajax_referer('tp_adm_sub');

  $level_id = isset($_POST['level_id']) ? intval($_POST['level_id']) : 0;
  if (!$level_id) wp_send_json_error('Invalid level');

  global $wpdb; $t = tp_adm_subject_tables();
  $rows = $wpdb->get_results( $wpdb->prepare(
    "SELECT sl.subject_level_id, s.subject_id, s.SubjectName
     FROM {$t['map']} sl
     JOIN {$t['subjects']} s ON s.subject_id = sl.subject_id
     WHERE sl.level_id = %d
     ORDER BY s.SubjectName ASC", $level_id
  ) );
  wp_send_json_success(['items' => $rows ?: []]);
});

/** Search all subjects (library) */
add_action('wp_ajax_tp_adm_sub_list_all', function(){
  if (!current_user_can('edit_posts')) wp_send_json_error('No permission');
  check_ajax_referer('tp_adm_sub');

  $q = isset($_POST['q']) ? trim(wp_unslash($_POST['q'])) : '';
  global $wpdb; $t = tp_adm_subject_tables();
  if ($q !== '') {
    $like = '%'.$wpdb->esc_like($q).'%';
    $rows = $wpdb->get_results( $wpdb->prepare("SELECT subject_id, SubjectName FROM {$t['subjects']} WHERE SubjectName LIKE %s ORDER BY SubjectName ASC LIMIT 300", $like) );
  } else {
    $rows = $wpdb->get_results("SELECT subject_id, SubjectName FROM {$t['subjects']} ORDER BY SubjectName ASC LIMIT 300");
  }
  wp_send_json_success(['items' => $rows ?: []]);
});

/**
 * Attach subject to level.
 * Accepts either subject_id OR subject_name (creates subject if missing).
 */
add_action('wp_ajax_tp_adm_sub_attach', function(){
  if (!current_user_can('edit_posts')) wp_send_json_error('No permission');
  check_ajax_referer('tp_adm_sub');

  global $wpdb; $t = tp_adm_subject_tables();

  $level_id    = isset($_POST['level_id']) ? intval($_POST['level_id']) : 0;
  $subject_id  = isset($_POST['subject_id']) ? intval($_POST['subject_id']) : 0;
  $subject_name= isset($_POST['subject_name']) ? sanitize_text_field($_POST['subject_name']) : '';

  if (!$level_id) wp_send_json_error('Invalid level');

  // If name provided, create-or-get subject_id
  if (!$subject_id) {
    if ($subject_name === '') wp_send_json_error('Subject name required');
    // Try find by name (case-sensitive by default; you can COLLATE to ci if needed)
    $found = $wpdb->get_row( $wpdb->prepare("SELECT subject_id FROM {$t['subjects']} WHERE SubjectName = %s", $subject_name) );
    if ($found) {
      $subject_id = (int)$found->subject_id;
    } else {
      $ok = $wpdb->insert($t['subjects'], ['SubjectName'=>$subject_name], ['%s']);
      if (!$ok) wp_send_json_error('Failed to create subject (maybe duplicate?)');
      $subject_id = (int)$wpdb->insert_id;
    }
  }

  // Create mapping if not exists
  // Protect with unique (level_id, subject_id) if you added the index; otherwise check manually
  $exists = $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM {$t['map']} WHERE level_id=%d AND subject_id=%d", $level_id, $subject_id
  ) );
  if ($exists) wp_send_json_success(['attached'=>false,'message'=>'Already attached']);

  $ok = $wpdb->insert($t['map'], ['level_id'=>$level_id, 'subject_id'=>$subject_id], ['%d','%d']);
  if (!$ok) wp_send_json_error('Attach failed');
  wp_send_json_success(['attached'=>true,'subject_id'=>$subject_id]);
});

/** Detach subject from level (by mapping id) */
add_action('wp_ajax_tp_adm_sub_detach', function(){
  if (!current_user_can('edit_posts')) wp_send_json_error('No permission');
  check_ajax_referer('tp_adm_sub');

  global $wpdb; $t = tp_adm_subject_tables();

  $map_id = isset($_POST['subject_level_id']) ? intval($_POST['subject_level_id']) : 0;
  if (!$map_id) wp_send_json_error('Invalid mapping id');

  $ok = $wpdb->delete($t['map'], ['subject_level_id'=>$map_id], ['%d']);
  if ($ok===false) wp_send_json_error('Delete failed');
  wp_send_json_success();
});



/* ---------- TEACHERS (Tab 3) ---------- */

function tp_adm_teacher_tables(){
  return [
    'teachers'     => tp_adm_tbl('teachers_main'),              // teacher_id, FullName, Email, Status
    'alloc'        => tp_adm_tbl('teacher_allocated_subjects'), // teacher_allocated_subject_id, teacher_id, subject_level_id
    'slots'        => tp_adm_tbl('teacher_generated_slots'),
    't_rate'       => tp_adm_tbl('teacher_Hour_Rate'),
    'p_due'        => tp_adm_tbl('teacher_payments_record'),
    'p_withdrawn'  => tp_adm_tbl('teacher_payment_received'),

    // Lookups
    'subjects'     => tp_adm_tbl('subjects'),
    'sub_level'    => tp_adm_tbl('subjects_level'),
    'levels'       => tp_adm_tbl('class_levels'),
    'lvl_rates'    => tp_adm_tbl('level_hourly_rates'),
  ];
}

/** List/search teachers */
add_action('wp_ajax_tp_adm_t_list', function(){
  if (!current_user_can('edit_posts')) wp_send_json_error('No permission');
  check_ajax_referer('tp_adm_teacher');

  $q = isset($_POST['q']) ? trim(wp_unslash($_POST['q'])) : '';
  $t = tp_adm_teacher_tables(); global $wpdb;
  if ($q!=='') {
    $like = '%'.$wpdb->esc_like($q).'%';
    $rows = $wpdb->get_results( $wpdb->prepare("SELECT teacher_id, FullName, Email, COALESCE(Status,'inactive') AS Status FROM {$t['teachers']} WHERE FullName LIKE %s OR Email LIKE %s ORDER BY FullName ASC LIMIT 300", $like, $like) );
  } else {
    $rows = $wpdb->get_results("SELECT teacher_id, FullName, Email, COALESCE(Status,'inactive') AS Status FROM {$t['teachers']} ORDER BY FullName ASC LIMIT 300");
  }
  wp_send_json_success(['items'=>$rows ?: []]);
});

/** Add teacher (name + email) */
add_action('wp_ajax_tp_adm_t_add', function(){
  if (!current_user_can('edit_posts')) wp_send_json_error('No permission');
  check_ajax_referer('tp_adm_teacher');

  $name = sanitize_text_field($_POST['name'] ?? '');
  $email= sanitize_email($_POST['email'] ?? '');
  if (!$name || !$email) wp_send_json_error('Name & Email required');

  $t = tp_adm_teacher_tables(); global $wpdb;
  // ensure unique email
  $ex = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$t['teachers']} WHERE Email=%s", $email) );
  if ($ex) wp_send_json_error('Email already exists');

  $ok = $wpdb->insert($t['teachers'], [
    'FullName' => $name,
    'Email'    => $email,
    'Status'   => 'active',
    'created_at' => current_time('mysql'),
  ], ['%s','%s','%s','%s']);
  if (!$ok) wp_send_json_error('Insert failed');
  wp_send_json_success(['teacher_id'=>(int)$wpdb->insert_id]);
});

/** Toggle teacher status active/inactive */
add_action('wp_ajax_tp_adm_t_toggle', function(){
  if (!current_user_can('edit_posts')) wp_send_json_error('No permission');
  check_ajax_referer('tp_adm_teacher');

  $id = intval($_POST['teacher_id'] ?? 0);
  $to = sanitize_text_field($_POST['to'] ?? 'inactive');
  if (!$id || !in_array($to,['active','inactive'],true)) wp_send_json_error('Invalid');

  $t = tp_adm_teacher_tables(); global $wpdb;
  $ok = $wpdb->update($t['teachers'], ['Status'=>$to], ['teacher_id'=>$id], ['%s'], ['%d']);
  if ($ok===false) wp_send_json_error('Update failed');
  wp_send_json_success();
});

/** Allocation list for teacher+level (subjects) */
add_action('wp_ajax_tp_adm_t_alloc_list', function(){
  if (!current_user_can('edit_posts')) wp_send_json_error('No permission');
  check_ajax_referer('tp_adm_teacher');

  $teacher_id = intval($_POST['teacher_id'] ?? 0);
  $level_id   = intval($_POST['level_id'] ?? 0);
  if (!$teacher_id || !$level_id) wp_send_json_error('Invalid');

  $t = tp_adm_teacher_tables(); global $wpdb;
  $rows = $wpdb->get_results( $wpdb->prepare(
    "SELECT tas.teacher_allocated_subject_id, sl.subject_level_id, s.subject_id, s.SubjectName
     FROM {$t['alloc']} tas
     JOIN {$t['sub_level']} sl ON sl.subject_level_id = tas.subject_level_id
     JOIN {$t['subjects']}  s  ON s.subject_id = sl.subject_id
     WHERE tas.teacher_id=%d AND sl.level_id=%d
     ORDER BY s.SubjectName ASC", $teacher_id, $level_id
  ) );
  wp_send_json_success(['items'=>$rows ?: []]);
});

/** Attach subject (by id or name) to teacher at a level */
add_action('wp_ajax_tp_adm_t_alloc_attach', function(){
  if (!current_user_can('edit_posts')) wp_send_json_error('No permission');
  check_ajax_referer('tp_adm_teacher');

  $teacher_id = intval($_POST['teacher_id'] ?? 0);
  $level_id   = intval($_POST['level_id'] ?? 0);
  $subject_id = intval($_POST['subject_id'] ?? 0);
  $subject_name = sanitize_text_field($_POST['subject_name'] ?? '');
  if (!$teacher_id || !$level_id) wp_send_json_error('Invalid');

  $t = tp_adm_teacher_tables(); global $wpdb;

  // create subject if name provided
  if (!$subject_id) {
    if ($subject_name==='') wp_send_json_error('Subject required');
    $ex = $wpdb->get_var( $wpdb->prepare("SELECT subject_id FROM {$t['subjects']} WHERE SubjectName=%s", $subject_name) );
    if ($ex) $subject_id = (int)$ex;
    else {
      $ok = $wpdb->insert($t['subjects'], ['SubjectName'=>$subject_name], ['%s']);
      if (!$ok) wp_send_json_error('Failed to create subject'); $subject_id = (int)$wpdb->insert_id;
    }
  }

  // get or create subject_level_id
  $sl = $wpdb->get_var( $wpdb->prepare("SELECT subject_level_id FROM {$t['sub_level']} WHERE level_id=%d AND subject_id=%d", $level_id, $subject_id) );
  if (!$sl) {
    $wpdb->insert($t['sub_level'], ['level_id'=>$level_id, 'subject_id'=>$subject_id], ['%d','%d']);
    $sl = (int)$wpdb->insert_id;
  }

  // attach if not exists
  $ex2 = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$t['alloc']} WHERE teacher_id=%d AND subject_level_id=%d", $teacher_id, $sl) );
  if ($ex2) wp_send_json_success(['attached'=>false,'message'=>'Already attached']);
  $ok = $wpdb->insert($t['alloc'], ['teacher_id'=>$teacher_id, 'subject_level_id'=>$sl], ['%d','%d']);
  if (!$ok) wp_send_json_error('Attach failed');
  wp_send_json_success(['attached'=>true]);
});

/** Detach allocated subject */
add_action('wp_ajax_tp_adm_t_alloc_detach', function(){
  if (!current_user_can('edit_posts')) wp_send_json_error('No permission');
  check_ajax_referer('tp_adm_teacher');

  $id = intval($_POST['teacher_allocated_subject_id'] ?? 0);
  if (!$id) wp_send_json_error('Invalid id');

  $t = tp_adm_teacher_tables(); global $wpdb;
  $ok = $wpdb->delete($t['alloc'], ['teacher_allocated_subject_id'=>$id], ['%d']);
  if ($ok===false) wp_send_json_error('Delete failed');
  wp_send_json_success();
});

/** Save teacher hourly rate (validate <= level standard) */
add_action('wp_ajax_tp_adm_t_rate_set', function(){
  if (!current_user_can('edit_posts')) wp_send_json_error('No permission');
  check_ajax_referer('tp_adm_teacher');

  $teacher_id = intval($_POST['teacher_id'] ?? 0);
  $sl_id      = intval($_POST['subject_level_id'] ?? 0);
  $amount     = floatval($_POST['hourly_rate'] ?? 0);
  if (!$teacher_id || !$sl_id || $amount<=0) wp_send_json_error('Invalid input');

  $t = tp_adm_teacher_tables(); global $wpdb;

  // find level_id for sl_id
  $level_id = $wpdb->get_var( $wpdb->prepare("SELECT level_id FROM {$t['sub_level']} WHERE subject_level_id=%d", $sl_id) );
  if (!$level_id) wp_send_json_error('Mapping not found');

  // get current level standard rate (GBP) as max
  $today = current_time('Y-m-d');
  $max = $wpdb->get_var( $wpdb->prepare(
    "SELECT hourly_rate FROM {$t['lvl_rates']}
     WHERE level_id=%d AND currency='GBP' AND status=1
       AND effective_from<=%s AND (effective_to IS NULL OR effective_to>=%s)
     ORDER BY effective_from DESC LIMIT 1", $level_id, $today, $today
  ) );
  if ($max===null) wp_send_json_error('Level standard rate not set');
  if ($amount > floatval($max)) wp_send_json_error('Hourly rate exceeds level maximum (£'.number_format((float)$max,2).')');

  // insert/update teacher rate (versionless simple upsert)
  // Table wpC_teacher_Hour_Rate: hour_rate_id, teacher_id, subject_level_id, hourly_rate, _date, to_date
  // We'll close any open period and add a new open one.
  $open = $wpdb->get_row( $wpdb->prepare(
    "SELECT hour_rate_id FROM {$t['t_rate']} WHERE teacher_id=%d AND subject_level_id=%d AND to_date IS NULL
     ORDER BY _date DESC LIMIT 1", $teacher_id, $sl_id
  ) );
  if ($open) {
    $wpdb->update($t['t_rate'], ['to_date'=> $today], ['hour_rate_id'=>$open->hour_rate_id], ['%s'], ['%d']);
  }
  $ok = $wpdb->insert($t['t_rate'], [
    'teacher_id'      => $teacher_id,
    'subject_level_id'=> $sl_id,
    'hourly_rate'     => $amount,
    '_date'           => $today,
    'to_date'         => null,
  ], ['%d','%d','%f','%s','%s']);
  if (!$ok) wp_send_json_error('Save failed');
  wp_send_json_success();
});

/** Slots list (range) */
add_action('wp_ajax_tp_adm_t_slots', function(){
  if (!current_user_can('edit_posts')) wp_send_json_error('No permission');
  check_ajax_referer('tp_adm_teacher');

  $teacher_id = intval($_POST['teacher_id'] ?? 0);
  $from = sanitize_text_field($_POST['from'] ?? '');
  $to   = sanitize_text_field($_POST['to'] ?? '');
  if (!$teacher_id) wp_send_json_error('Invalid teacher');

  $t = tp_adm_teacher_tables(); global $wpdb;
  $from = $from ?: current_time('Y-m-d');
  $to   = $to   ?: current_time('Y-m-d', true);

  $rows = $wpdb->get_results( $wpdb->prepare(
    "SELECT s.session_date, s.start_time, s.end_time, s.status,
            l.level_name, sub.SubjectName
     FROM {$t['slots']} s
     LEFT JOIN {$t['levels']} l ON l.id = s.class_level_id
     LEFT JOIN {$t['subjects']} sub ON sub.subject_id = s.subject_id
     WHERE s.teacher_id=%d AND s.session_date BETWEEN %s AND %s
     ORDER BY s.session_date DESC, s.start_time DESC LIMIT 500",
     $teacher_id, $from, $to
  ) );
  wp_send_json_success(['items'=>$rows ?: []]);
});

/** Due payments (records) */
add_action('wp_ajax_tp_adm_t_due', function(){
  if (!current_user_can('edit_posts')) wp_send_json_error('No permission');
  check_ajax_referer('tp_adm_teacher');

  $teacher_id = intval($_POST['teacher_id'] ?? 0);
  if (!$teacher_id) wp_send_json_error('Invalid');

  $t = tp_adm_teacher_tables(); global $wpdb;
  // Column typo "techer_id" handled via COALESCE aliasing if needed
  $rows = $wpdb->get_results( $wpdb->prepare(
    "SELECT payment_record_id, hour_rate_id, lecture_book_id, eligible_payout_amount
     FROM {$t['p_due']}
     WHERE (teacher_id=%d OR techer_id=%d) AND COALESCE(eligible_payout_amount,0) > 0
     ORDER BY payment_record_id DESC LIMIT 300",
     $teacher_id, $teacher_id
  ) );
  wp_send_json_success(['items'=>$rows ?: []]);
});

/** Withdrawn payments */
add_action('wp_ajax_tp_adm_t_withdrawn', function(){
  if (!current_user_can('edit_posts')) wp_send_json_error('No permission');
  check_ajax_referer('tp_adm_teacher');

  $teacher_id = intval($_POST['teacher_id'] ?? 0);
  if (!$teacher_id) wp_send_json_error('Invalid');

  $t = tp_adm_teacher_tables(); global $wpdb;
  $rows = $wpdb->get_results( $wpdb->prepare(
    "SELECT payment_withdrawal_date, amount_withdrawal, status, remarks
     FROM {$t['p_withdrawn']}
     WHERE teacher_id=%d
     ORDER BY payment_received_id DESC LIMIT 300",
     $teacher_id
  ) );
  wp_send_json_success(['items'=>$rows ?: []]);
});


/* ---------- STUDENTS (Tab 4) ---------- */

function tp_adm_student_tables(){
  return [
    'students' => tp_adm_tbl('student_register'),   // wpC_student_register
    'lectures' => tp_adm_tbl('student_lectures'),   // wpC_student_lectures
    'levels'   => tp_adm_tbl('class_levels'),       // id, level_name
    'subjects' => tp_adm_tbl('subjects'),           // subject_id, SubjectName
  ];
}

/** List/search with filters */
add_action('wp_ajax_tp_adm_s_list', function(){
  if (!current_user_can('edit_posts')) wp_send_json_error('No permission');
  check_ajax_referer('tp_adm_student');

  $q = trim(wp_unslash($_POST['q'] ?? ''));
  $level_id  = intval($_POST['level_id'] ?? 0);
  $subject_id= intval($_POST['subject_id'] ?? 0);

  global $wpdb; $t = tp_adm_student_tables();
  $where = ['1=1']; $args = [];
  if ($q !== '') {
    $like = '%'.$wpdb->esc_like($q).'%';
    $where[] = "(full_name LIKE %s OR email LIKE %s OR phone LIKE %s)";
    array_push($args, $like, $like, $like);
  }
  if ($level_id) {
    $where[] = "level_id = %d"; $args[] = $level_id;
  }
  // filter by subject: student_lectures join subjects
  $join = '';
  if ($subject_id) {
    $join = "JOIN {$t['lectures']} l ON l.student_id = s.student_id AND l.subject_id = %d";
    array_unshift($args, $subject_id);
  }
  $sql = "SELECT s.student_id, s.full_name, s.email, s.phone, COALESCE(s.status,'inactive') AS status
          FROM {$t['students']} s
          $join
          WHERE ".implode(' AND ', $where)."
          GROUP BY s.student_id
          ORDER BY s.full_name ASC LIMIT 400";
  $rows = $wpdb->get_results( $wpdb->prepare($sql, $args) );
  wp_send_json_success(['items'=>$rows ?: []]);
});

/** Get single student */
add_action('wp_ajax_tp_adm_s_get', function(){
  if (!current_user_can('edit_posts')) wp_send_json_error('No permission');
  check_ajax_referer('tp_adm_student');

  $id = intval($_POST['student_id'] ?? 0); if(!$id) wp_send_json_error('Invalid');
  global $wpdb; $t = tp_adm_student_tables();
  $row = $wpdb->get_row( $wpdb->prepare(
    "SELECT s.*, l.level_name
     FROM {$t['students']} s
     LEFT JOIN {$t['levels']} l ON l.id = s.level_id
     WHERE s.student_id=%d", $id
  ) );
  if (!$row) wp_send_json_error('Not found');
  wp_send_json_success($row);
});

/** Toggle status active/inactive */
add_action('wp_ajax_tp_adm_s_toggle', function(){
  if (!current_user_can('edit_posts')) wp_send_json_error('No permission');
  check_ajax_referer('tp_adm_student');

  $id = intval($_POST['student_id'] ?? 0);
  $to = sanitize_text_field($_POST['to'] ?? 'inactive');
  if (!$id || !in_array($to, ['active','inactive'], true)) wp_send_json_error('Invalid');

  global $wpdb; $t = tp_adm_student_tables();
  $ok = $wpdb->update($t['students'], ['status'=>$to], ['student_id'=>$id], ['%s'], ['%d']);
  if ($ok===false) wp_send_json_error('Update failed');
  wp_send_json_success();
});

/** Send deactivation email */
add_action('wp_ajax_tp_adm_s_email_deact', function(){
  if (!current_user_can('edit_posts')) wp_send_json_error('No permission');
  check_ajax_referer('tp_adm_student');

  $id = intval($_POST['student_id'] ?? 0);
  if (!$id) wp_send_json_error('Invalid');

  global $wpdb; $t = tp_adm_student_tables();
  $row = $wpdb->get_row( $wpdb->prepare("SELECT full_name, email, status FROM {$t['students']} WHERE student_id=%d", $id) );
  if (!$row || !$row->email) wp_send_json_error('Student/email not found');

  $subject = 'Your TutorsPoint account has been deactivated';
  $body = "Dear {$row->full_name},\n\nYour student account has been deactivated by the administrator. If you believe this is a mistake, please reply to this email.\n\nRegards,\nTutorsPoint";
  $headers = ['Content-Type: text/plain; charset=UTF-8'];

  $sent = wp_mail($row->email, $subject, $body, $headers);
  if (!$sent) wp_send_json_error('Failed to send email');
  wp_send_json_success();
});

/** Set new password (wp_users + custom table hash) */
add_action('wp_ajax_tp_adm_s_set_pass', function(){
  if (!current_user_can('edit_users')) wp_send_json_error('No permission');
  check_ajax_referer('tp_adm_student');

  $id = intval($_POST['student_id'] ?? 0);
  $new = $_POST['new_password'] ?? '';
  if (!$id || strlen($new) < 8) wp_send_json_error('Invalid');

  global $wpdb; $t = tp_adm_student_tables();
  $row = $wpdb->get_row( $wpdb->prepare("SELECT email FROM {$t['students']} WHERE student_id=%d", $id) );
  if (!$row) wp_send_json_error('Student not found');

  // Update WP user if exists
  $user = get_user_by('email', $row->email);
  if ($user) {
    wp_set_password($new, $user->ID); // logs them out everywhere
  }

  // Update custom table with a hash (NEVER store plaintext)
  if (!function_exists('wp_hash_password')) require_once ABSPATH.'wp-includes/pluggable.php';
  $hash = wp_hash_password($new);
  $wpdb->update($t['students'], ['password'=>$hash], ['student_id'=>$id], ['%s'], ['%d']);

  wp_send_json_success();
});

/** Lectures list (range + subject) */
add_action('wp_ajax_tp_adm_s_lectures', function(){
  if (!current_user_can('edit_posts')) wp_send_json_error('No permission');
  check_ajax_referer('tp_adm_student');

  $id = intval($_POST['student_id'] ?? 0);
  $from = sanitize_text_field($_POST['from'] ?? '');
  $to   = sanitize_text_field($_POST['to'] ?? '');
  $subject_id = intval($_POST['subject_id'] ?? 0);
  if(!$id) wp_send_json_error('Invalid');

  global $wpdb; $t = tp_adm_student_tables();

  $where = ["l.student_id=%d"]; $args = [$id];
  if ($from) { $where[]="l.lecture_book_date >= %s"; $args[]=$from; }
  if ($to)   { $where[]="l.lecture_book_date <= %s"; $args[]=$to; }
  if ($subject_id) { $where[]="l.subject_id=%d"; $args[]=$subject_id; }

  $sql = "SELECT l.*, s.SubjectName
          FROM {$t['lectures']} l
          LEFT JOIN {$t['subjects']} s ON s.subject_id = l.subject_id
          WHERE ".implode(' AND ', $where)."
          ORDER BY l.lecture_book_date DESC, l.lecture_time DESC
          LIMIT 1000";
  $rows = $wpdb->get_results( $wpdb->prepare($sql, $args) );
  wp_send_json_success(['items'=>$rows ?: []]);
});

/** Payments (from student_lectures) */
add_action('wp_ajax_tp_adm_s_payments', function(){
  if (!current_user_can('edit_posts')) wp_send_json_error('No permission');
  check_ajax_referer('tp_adm_student');

  $id = intval($_POST['student_id'] ?? 0); if(!$id) wp_send_json_error('Invalid');
  global $wpdb; $t = tp_adm_student_tables();
  $rows = $wpdb->get_results( $wpdb->prepare(
    "SELECT l.lecture_book_id, l.lecture_book_date, l.original_price, l.discount_rate, l.final_price, l.is_paid, s.SubjectName
     FROM {$t['lectures']} l
     LEFT JOIN {$t['subjects']} s ON s.subject_id = l.subject_id
     WHERE l.student_id=%d
     ORDER BY l.lecture_book_id DESC
     LIMIT 1000", $id
  ) );
  wp_send_json_success(['items'=>$rows ?: []]);
});

/** Export CSV (lectures | payments) */
add_action('wp_ajax_tp_adm_s_export', function(){
  if (!current_user_can('edit_posts')) wp_die('No permission');
  check_ajax_referer('tp_adm_student');

  $id = intval($_GET['student_id'] ?? 0);
  $type = sanitize_text_field($_GET['type'] ?? 'lectures');
  $from = sanitize_text_field($_GET['from'] ?? '');
  $to   = sanitize_text_field($_GET['to'] ?? '');
  if(!$id) wp_die('Invalid');

  global $wpdb; $t = tp_adm_student_tables();
  $filename = "student-{$id}-{$type}-".date('Ymd-His').".csv";

  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="'.$filename.'"');
  $out = fopen('php://output', 'w');

  if ($type === 'payments') {
    fputcsv($out, ['lecture_book_id','date','subject','original','discount_rate','final','is_paid']);
    $rows = $wpdb->get_results( $wpdb->prepare(
      "SELECT l.lecture_book_id, l.lecture_book_date, s.SubjectName,
              l.original_price, l.discount_rate, l.final_price, l.is_paid
       FROM {$t['lectures']} l
       LEFT JOIN {$t['subjects']} s ON s.subject_id = l.subject_id
       WHERE l.student_id=%d
       ORDER BY l.lecture_book_id DESC", $id
    ), ARRAY_A );
  } else {
    fputcsv($out, ['lecture_book_id','date','time','topic','subject','duration','status']);
    $where = ["l.student_id=%d"]; $args = [$id];
    if ($from) { $where[]="l.lecture_book_date >= %s"; $args[]=$from; }
    if ($to)   { $where[]="l.lecture_book_date <= %s"; $args[]=$to; }
    $sql = "SELECT l.lecture_book_id, l.lecture_book_date, l.lecture_time, l.topic,
                   s.SubjectName, l.duration, l.status
            FROM {$t['lectures']} l
            LEFT JOIN {$t['subjects']} s ON s.subject_id = l.subject_id
            WHERE ".implode(' AND ', $where)."
            ORDER BY l.lecture_book_date DESC, l.lecture_time DESC";
    $rows = $wpdb->get_results( $wpdb->prepare($sql, $args), ARRAY_A );
  }

  foreach (($rows ?: []) as $r) fputcsv($out, $r);
  fclose($out);
  exit;
});



/* ------------- SCHEDULE (Tab 5) ------------- */
function tp_adm_sched_tbls(){
  return [
    'slots'    => tp_adm_tbl('teacher_generated_slots'),
    'teachers' => tp_adm_tbl('teachers_main'),
    'levels'   => tp_adm_tbl('class_levels'),
    'subjects' => tp_adm_tbl('subjects'),
    'students' => tp_adm_tbl('student_register'),
    'lectures' => tp_adm_tbl('student_lectures'), // for payment flag
  ];
}

/** 1) Teachers with at least one slot for a given day (current week day) */
add_action('wp_ajax_tp_adm_sched_teachers', function(){
  if (!current_user_can('edit_posts')) wp_send_json_error('No permission');
  check_ajax_referer('tp_adm_sched');

  $day = sanitize_text_field($_POST['day'] ?? '');
  if (!$day) wp_send_json_error('Invalid day');

  global $wpdb; $t = tp_adm_sched_tbls();
  $rows = $wpdb->get_results( $wpdb->prepare("
    SELECT s.teacher_id, COUNT(*) AS slots, tm.FullName
    FROM {$t['slots']} s
    LEFT JOIN {$t['teachers']} tm ON tm.teacher_id = s.teacher_id
    WHERE s.session_date = %s AND COALESCE(s.is_active,1) = 1
    GROUP BY s.teacher_id
    ORDER BY tm.FullName ASC
  ", $day) );
  wp_send_json_success(['items'=>$rows ?: []]);
});

/** 2) Slots list for a teacher & day (includes payment/free flags) */
add_action('wp_ajax_tp_adm_sched_slots', function(){
  if (!current_user_can('edit_posts')) wp_send_json_error('No permission');
  check_ajax_referer('tp_adm_sched');

  $day = sanitize_text_field($_POST['day'] ?? '');
  $tid = intval($_POST['teacher_id'] ?? 0);
  if (!$day || !$tid) wp_send_json_error('Invalid');

  global $wpdb; $t = tp_adm_sched_tbls();

  // Join to levels/subjects/students + attempt to join to student_lectures for paid/free info
  $rows = $wpdb->get_results( $wpdb->prepare("
    SELECT s.slot_id, s.teacher_id, s.session_date, s.start_time, s.end_time, s.status, s.is_active,
           s.meeting_link, s.class_level_id, s.subject_id, s.student_id,
           l.level_name, sub.SubjectName,
           st.full_name AS student_name,
           le.final_price, le.is_paid,
           CASE WHEN le.final_price IS NULL OR le.final_price=0 THEN 1 ELSE 0 END AS is_free
    FROM {$t['slots']} s
    LEFT JOIN {$t['levels']}   l   ON l.id = s.class_level_id
    LEFT JOIN {$t['subjects']} sub ON sub.subject_id = s.subject_id
    LEFT JOIN {$t['students']} st  ON st.student_id = s.student_id
    LEFT JOIN {$t['lectures']} le  ON le.student_id = s.student_id
                                   AND le.lecture_book_date = s.session_date
                                   AND le.lecture_time = s.start_time
    WHERE s.session_date=%s AND s.teacher_id=%d
    ORDER BY s.start_time ASC
  ", $day, $tid) );

  wp_send_json_success(['items'=>$rows ?: []]);
});

/** 3) Halt a slot and notify teacher + student */
add_action('wp_ajax_tp_adm_sched_halt', function(){
  if (!current_user_can('edit_posts')) wp_send_json_error('No permission');
  check_ajax_referer('tp_adm_sched');

  $sid    = intval($_POST['slot_id'] ?? 0);
  $reason = sanitize_text_field($_POST['reason'] ?? '');
  if (!$sid || !$reason) wp_send_json_error('Invalid');

  global $wpdb; $t = tp_adm_sched_tbls();
  $row = $wpdb->get_row( $wpdb->prepare("
    SELECT s.*, tm.FullName AS tname, tm.Email AS temail,
           st.full_name AS sname, st.email AS semail,
           l.level_name, sub.SubjectName
    FROM {$t['slots']} s
    LEFT JOIN {$t['teachers']} tm ON tm.teacher_id = s.teacher_id
    LEFT JOIN {$t['students']} st ON st.student_id = s.student_id
    LEFT JOIN {$t['levels']}   l  ON l.id = s.class_level_id
    LEFT JOIN {$t['subjects']} sub ON sub.subject_id = s.subject_id
    WHERE s.slot_id=%d
  ", $sid) );
  if (!$row) wp_send_json_error('Slot not found');

  // mark halted
  $ok = $wpdb->update($t['slots'], ['status'=>'halted','is_active'=>0,'updated_at'=>current_time('mysql')], ['slot_id'=>$sid], ['%s','%d','%s'], ['%d']);
  if ($ok===false) wp_send_json_error('DB update failed');

  // email both parties
  $subject = 'Session halted';
  $body = "This session has been halted by the administrator.\n\n".
          "Teacher: {$row->tname}\n".
          "Student: {$row->sname}\n".
          "Date: {$row->session_date}\nTime: {$row->start_time}–{$row->end_time}\n".
          "Level: {$row->level_name}\nSubject: {$row->SubjectName}\n\n".
          "Reason: {$reason}\n\nRegards,\nTutorsPoint";
  $headers = ['Content-Type: text/plain; charset=UTF-8'];

  if ($row->temail) wp_mail($row->temail, $subject, $body, $headers);
  if ($row->semail) wp_mail($row->semail, $subject, $body, $headers);

  wp_send_json_success();
});

/** 4) Export CSV (day or teacher/day) */
add_action('wp_ajax_tp_adm_sched_export', function(){
  if (!current_user_can('edit_posts')) wp_die('No permission');
  check_ajax_referer('tp_adm_sched');

  $day = sanitize_text_field($_GET['day'] ?? '');
  $tid = intval($_GET['teacher_id'] ?? 0);
  if(!$day) wp_die('Missing day');

  global $wpdb; $t = tp_adm_sched_tbls();

  $where = $tid ? $wpdb->prepare("s.session_date=%s AND s.teacher_id=%d", $day, $tid)
                : $wpdb->prepare("s.session_date=%s", $day);
  $rows = $wpdb->get_results("
    SELECT s.slot_id, s.session_date, s.start_time, s.end_time, tm.FullName AS teacher,
           st.full_name AS student, l.level_name, sub.SubjectName, COALESCE(s.status,'') AS status,
           COALESCE(le.final_price,0) AS final_price, COALESCE(le.is_paid,0) AS is_paid
    FROM {$t['slots']} s
    LEFT JOIN {$t['teachers']} tm ON tm.teacher_id = s.teacher_id
    LEFT JOIN {$t['students']} st ON st.student_id = s.student_id
    LEFT JOIN {$t['levels']}   l  ON l.id = s.class_level_id
    LEFT JOIN {$t['subjects']} sub ON sub.subject_id = s.subject_id
    LEFT JOIN {$t['lectures']} le  ON le.student_id = s.student_id
                                   AND le.lecture_book_date = s.session_date
                                   AND le.lecture_time = s.start_time
    WHERE {$where}
    ORDER BY tm.FullName ASC, s.start_time ASC
  ", ARRAY_A );

  $fn = 'schedules-'.$day.($tid?('-teacher-'.$tid):'').'-'.date('Ymd-His').'.csv';
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="'.$fn.'"');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['slot_id','date','start','end','teacher','student','level','subject','status','final_price','is_paid']);
  foreach(($rows?:[]) as $r) fputcsv($out, $r);
  fclose($out);
  exit;
});

/** 5) Print view (use browser Save as PDF) */
add_action('wp_ajax_tp_adm_sched_print', function(){
  if (!current_user_can('edit_posts')) wp_die('No permission');
  check_ajax_referer('tp_adm_sched');

  $day = sanitize_text_field($_GET['day'] ?? '');
  $tid = intval($_GET['teacher_id'] ?? 0);
  if(!$day) wp_die('Missing day');

  global $wpdb; $t = tp_adm_sched_tbls();
  $where = $tid ? $wpdb->prepare("s.session_date=%s AND s.teacher_id=%d", $day, $tid)
                : $wpdb->prepare("s.session_date=%s", $day);
  $rows = $wpdb->get_results("
    SELECT s.session_date, s.start_time, s.end_time, tm.FullName AS teacher,
           st.full_name AS student, l.level_name, sub.SubjectName, COALESCE(s.status,'') AS status,
           COALESCE(le.final_price,0) AS final_price, COALESCE(le.is_paid,0) AS is_paid
    FROM {$t['slots']} s
    LEFT JOIN {$t['teachers']} tm ON tm.teacher_id = s.teacher_id
    LEFT JOIN {$t['students']} st ON st.student_id = s.student_id
    LEFT JOIN {$t['levels']}   l  ON l.id = s.class_level_id
    LEFT JOIN {$t['subjects']} sub ON sub.subject_id = s.subject_id
    LEFT JOIN {$t['lectures']} le  ON le.student_id = s.student_id
                                   AND le.lecture_book_date = s.session_date
                                   AND le.lecture_time = s.start_time
    WHERE {$where}
    ORDER BY tm.FullName ASC, s.start_time ASC
  ");

  ?><!doctype html><html><head><meta charset="utf-8">
  <title>Schedules <?php echo esc_html($day); ?></title>
  <style>
    body{font-family:Arial,Helvetica,sans-serif;margin:20px}
    h2{margin:0 0 6px}
    .meta{color:#666;margin-bottom:12px}
    table{width:100%;border-collapse:collapse;font-size:13px}
    th,td{border-bottom:1px solid #eee;padding:8px;text-align:left;white-space:nowrap}
    .paid{color:#0a7a4b;font-weight:600}
    .unpaid{color:#555}
    @media print { .noprint{display:none} }
  </style>
  </head><body>
  <div class="noprint"><button onclick="window.print()">Print</button></div>
  <h2>Schedules – <?php echo esc_html($day); ?><?php if($tid) echo ' (Teacher #'.intval($tid).')'; ?></h2>
  <div class="meta">Generated at <?php echo esc_html(date_i18n('Y-m-d H:i')); ?></div>
  <table><thead>
    <tr><th>Time</th><th>Teacher</th><th>Student</th><th>Level</th><th>Subject</th><th>Status</th><th>Payment</th></tr>
  </thead><tbody>
  <?php if($rows){ foreach($rows as $r){ ?>
    <tr>
      <td><?php echo esc_html("{$r->start_time}-{$r->end_time}"); ?></td>
      <td><?php echo esc_html($r->teacher); ?></td>
      <td><?php echo esc_html($r->student); ?></td>
      <td><?php echo esc_html($r->level_name); ?></td>
      <td><?php echo esc_html($r->SubjectName); ?></td>
      <td><?php echo esc_html($r->status); ?></td>
      <td class="<?php echo $r->is_paid?'paid':'unpaid'; ?>">
        <?php echo $r->is_paid ? ('Paid £'.number_format((float)$r->final_price,2)) : ($r->final_price>0?'Unpaid':'Free'); ?>
      </td>
    </tr>
  <?php }} else { ?>
    <tr><td colspan="7">No data.</td></tr>
  <?php } ?>
  </tbody></table>
  </body></html><?php
  exit;
});



/* ------------ BOOKINGS (Tab 6) ------------- */
function tp_adm_book_tbls(){
  return [
    'slots'    => tp_adm_tbl('teacher_generated_slots'),
    'teachers' => tp_adm_tbl('teachers_main'),
    'levels'   => tp_adm_tbl('class_levels'),
    'subjects' => tp_adm_tbl('subjects'),
    'students' => tp_adm_tbl('student_register'),
    'lectures' => tp_adm_tbl('student_lectures'),
  ];
}

/** 1) Distinct time buckets for a day (sorted asc) */
add_action('wp_ajax_tp_adm_book_times', function(){
  if (!current_user_can('edit_posts')) wp_send_json_error('No permission');
  check_ajax_referer('tp_adm_bookings');

  $day = sanitize_text_field($_POST['day'] ?? '');
  if (!$day) wp_send_json_error('Invalid day');

  global $wpdb; $t = tp_adm_book_tbls();
  $rows = $wpdb->get_results( $wpdb->prepare("
    SELECT s.start_time, MIN(s.end_time) AS end_time, COUNT(*) AS ct
    FROM {$t['slots']} s
    WHERE s.session_date = %s AND COALESCE(s.is_active,1)=1
    GROUP BY s.start_time
    ORDER BY s.start_time ASC
  ", $day) );
  wp_send_json_success(['items'=>$rows ?: []]);
});

/** 2) Bookings list for a day + start_time (across teachers) */
add_action('wp_ajax_tp_adm_book_rows', function(){
  if (!current_user_can('edit_posts')) wp_send_json_error('No permission');
  check_ajax_referer('tp_adm_bookings');

  $day  = sanitize_text_field($_POST['day'] ?? '');
  $time = sanitize_text_field($_POST['start_time'] ?? '');
  if (!$day || !$time) wp_send_json_error('Invalid');

  global $wpdb; $t = tp_adm_book_tbls();
  $rows = $wpdb->get_results( $wpdb->prepare("
    SELECT s.slot_id, s.start_time, s.end_time, s.status, s.meeting_link,
           s.teacher_id, tm.FullName AS teacher,
           s.student_id, st.full_name AS student,
           l.level_name, sub.SubjectName,
           COALESCE(le.final_price,0) AS final_price,
           COALESCE(le.is_paid,0)     AS is_paid
    FROM {$t['slots']} s
    LEFT JOIN {$t['teachers']} tm ON tm.teacher_id = s.teacher_id
    LEFT JOIN {$t['students']} st ON st.student_id = s.student_id
    LEFT JOIN {$t['levels']}   l  ON l.id = s.class_level_id
    LEFT JOIN {$t['subjects']} sub ON sub.subject_id = s.subject_id
    LEFT JOIN {$t['lectures']} le  ON le.student_id = s.student_id
                                   AND le.lecture_book_date = s.session_date
                                   AND le.lecture_time = s.start_time
    WHERE s.session_date=%s AND s.start_time=%s
    ORDER BY tm.FullName ASC
  ", $day, $time) );
  wp_send_json_success(['items'=>$rows ?: []]);
});

/** 3) Halt a booking (slot) and email parties */
add_action('wp_ajax_tp_adm_book_halt', function(){
  if (!current_user_can('edit_posts')) wp_send_json_error('No permission');
  check_ajax_referer('tp_adm_bookings');

  $sid    = intval($_POST['slot_id'] ?? 0);
  $reason = sanitize_text_field($_POST['reason'] ?? '');
  if (!$sid || !$reason) wp_send_json_error('Invalid');

  global $wpdb; $t = tp_adm_book_tbls();
  $row = $wpdb->get_row( $wpdb->prepare("
    SELECT s.*, tm.FullName AS tname, tm.Email AS temail,
           st.full_name AS sname, st.email AS semail,
           l.level_name, sub.SubjectName
    FROM {$t['slots']} s
    LEFT JOIN {$t['teachers']} tm ON tm.teacher_id = s.teacher_id
    LEFT JOIN {$t['students']} st ON st.student_id = s.student_id
    LEFT JOIN {$t['levels']}   l  ON l.id = s.class_level_id
    LEFT JOIN {$t['subjects']} sub ON sub.subject_id = s.subject_id
    WHERE s.slot_id=%d
  ", $sid) );
  if (!$row) wp_send_json_error('Slot not found');

  $ok = $wpdb->update($t['slots'], ['status'=>'halted','is_active'=>0,'updated_at'=>current_time('mysql')], ['slot_id'=>$sid], ['%s','%d','%s'], ['%d']);
  if ($ok===false) wp_send_json_error('DB update failed');

  $subject = 'Session halted';
  $body = "This session has been halted by the administrator.\n\n".
          "Teacher: {$row->tname}\n".
          "Student: {$row->sname}\n".
          "Date: {$row->session_date}\nTime: {$row->start_time}–{$row->end_time}\n".
          "Level: {$row->level_name}\nSubject: {$row->SubjectName}\n\n".
          "Reason: {$reason}\n\nRegards,\nTutorsPoint";
  $headers = ['Content-Type: text/plain; charset=UTF-8'];

  if ($row->temail) wp_mail($row->temail, $subject, $body, $headers);
  if ($row->semail) wp_mail($row->semail, $subject, $body, $headers);

  wp_send_json_success();
});

/** 4) Export CSV (whole day OR specific start_time) */
add_action('wp_ajax_tp_adm_book_export', function(){
  if (!current_user_can('edit_posts')) wp_die('No permission');
  check_ajax_referer('tp_adm_bookings');

  $day  = sanitize_text_field($_GET['day'] ?? '');
  $time = sanitize_text_field($_GET['start_time'] ?? '');
  if(!$day) wp_die('Missing day');

  global $wpdb; $t = tp_adm_book_tbls();
  $where = $time
    ? $wpdb->prepare("s.session_date=%s AND s.start_time=%s", $day, $time)
    : $wpdb->prepare("s.session_date=%s", $day);

  $rows = $wpdb->get_results("
    SELECT s.slot_id, s.session_date, s.start_time, s.end_time,
           tm.FullName AS teacher, st.full_name AS student,
           l.level_name, sub.SubjectName, COALESCE(s.status,'') AS status,
           COALESCE(le.final_price,0) AS final_price, COALESCE(le.is_paid,0) AS is_paid
    FROM {$t['slots']} s
    LEFT JOIN {$t['teachers']} tm ON tm.teacher_id = s.teacher_id
    LEFT JOIN {$t['students']} st ON st.student_id = s.student_id
    LEFT JOIN {$t['levels']}   l  ON l.id = s.class_level_id
    LEFT JOIN {$t['subjects']} sub ON sub.subject_id = s.subject_id
    LEFT JOIN {$t['lectures']} le  ON le.student_id = s.student_id
                                   AND le.lecture_book_date = s.session_date
                                   AND le.lecture_time = s.start_time
    WHERE {$where}
    ORDER BY s.start_time ASC, tm.FullName ASC
  ", ARRAY_A );

  $fn = 'bookings-'.$day.($time?('-'.$time):'').'-'.date('Ymd-His').'.csv';
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="'.$fn.'"');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['slot_id','date','start','end','teacher','student','level','subject','status','final_price','is_paid']);
  foreach(($rows?:[]) as $r) fputcsv($out, $r);
  fclose($out);
  exit;
});

/** 5) Print view (use browser Save as PDF) */
add_action('wp_ajax_tp_adm_book_print', function(){
  if (!current_user_can('edit_posts')) wp_die('No permission');
  check_ajax_referer('tp_adm_bookings');

  $day  = sanitize_text_field($_GET['day'] ?? '');
  $time = sanitize_text_field($_GET['start_time'] ?? '');
  if(!$day) wp_die('Missing day');

  global $wpdb; $t = tp_adm_book_tbls();
  $where = $time
    ? $wpdb->prepare("s.session_date=%s AND s.start_time=%s", $day, $time)
    : $wpdb->prepare("s.session_date=%s", $day);

  $rows = $wpdb->get_results("
    SELECT s.start_time, s.end_time, tm.FullName AS teacher, st.full_name AS student,
           l.level_name, sub.SubjectName, COALESCE(s.status,'') AS status,
           COALESCE(le.final_price,0) AS final_price, COALESCE(le.is_paid,0) AS is_paid
    FROM {$t['slots']} s
    LEFT JOIN {$t['teachers']} tm ON tm.teacher_id = s.teacher_id
    LEFT JOIN {$t['students']} st ON st.student_id = s.student_id
    LEFT JOIN {$t['levels']}   l  ON l.id = s.class_level_id
    LEFT JOIN {$t['subjects']} sub ON sub.subject_id = s.subject_id
    LEFT JOIN {$t['lectures']} le  ON le.student_id = s.student_id
                                   AND le.lecture_book_date = s.session_date
                                   AND le.lecture_time = s.start_time
    WHERE {$where}
    ORDER BY s.start_time ASC, tm.FullName ASC
  ");

  ?><!doctype html><html><head><meta charset="utf-8">
  <title>Bookings <?php echo esc_html($day); echo $time?(' '.$time):''; ?></title>
  <style>
    body{font-family:Arial,Helvetica,sans-serif;margin:20px}
    h2{margin:0 0 6px}
    .meta{color:#666;margin-bottom:12px}
    table{width:100%;border-collapse:collapse;font-size:13px}
    th,td{border-bottom:1px solid #eee;padding:8px;text-align:left;white-space:nowrap}
    .paid{color:#0a7a4b;font-weight:600}
    .unpaid{color:#555}
    @media print { .noprint{display:none} }
  </style>
  </head><body>
  <div class="noprint"><button onclick="window.print()">Print</button></div>
  <h2>Bookings – <?php echo esc_html($day); echo $time?(' '.$time):''; ?></h2>
  <div class="meta">Generated at <?php echo esc_html(date_i18n('Y-m-d H:i')); ?></div>
  <table><thead>
    <tr><th>Time</th><th>Teacher</th><th>Student</th><th>Level</th><th>Subject</th><th>Status</th><th>Payment</th></tr>
  </thead><tbody>
  <?php if($rows){ foreach($rows as $r){ ?>
    <tr>
      <td><?php echo esc_html("{$r->start_time}-{$r->end_time}"); ?></td>
      <td><?php echo esc_html($r->teacher); ?></td>
      <td><?php echo esc_html($r->student); ?></td>
      <td><?php echo esc_html($r->level_name); ?></td>
      <td><?php echo esc_html($r->SubjectName); ?></td>
      <td><?php echo esc_html($r->status); ?></td>
      <td class="<?php echo $r->is_paid?'paid':'unpaid'; ?>">
        <?php echo $r->is_paid ? ('Paid £'.number_format((float)$r->final_price,2)) : ($r->final_price>0?'Unpaid':'Free'); ?>
      </td>
    </tr>
  <?php }} else { ?>
    <tr><td colspan="7">No data.</td></tr>
  <?php } ?>
  </tbody></table>
  </body></html><?php
  exit;
});


// === Categories AJAX for Admin/Teacher Dashboard ===
// Register endpoints (logged-in users only, via admin-ajax.php)
add_action('wp_ajax_tp_list_categories',       'tdcats_list_categories');
add_action('wp_ajax_tp_add_category',          'tdcats_add_category');
add_action('wp_ajax_tp_rename_category',       'tdcats_rename_category');
add_action('wp_ajax_tp_delete_category',       'tdcats_delete_category');
add_action('wp_ajax_tp_set_default_category',  'tdcats_set_default_category');

// Optional quick ping for debugging
// add_action('wp_ajax_tp_cat_ping', function(){ wp_send_json_success(['ok'=>true]); });

if (!function_exists('tdcats_guard')) {
  function tdcats_guard(){
    if (!is_user_logged_in() || !current_user_can('manage_categories')) {
      wp_send_json_error('Unauthorized', 403);
    }
    $nonce = isset($_REQUEST['nonce']) ? $_REQUEST['nonce'] : '';
    if (!$nonce || !wp_verify_nonce($nonce, 'tp_cat_manage')) {
      wp_send_json_error('Bad nonce', 400);
    }
  }
}

if (!function_exists('tdcats_list_categories')) {
  function tdcats_list_categories(){
    tdcats_guard();
    $search = isset($_POST['s']) ? sanitize_text_field($_POST['s']) : '';
    $parent = isset($_POST['parent']) && $_POST['parent'] !== '' ? intval($_POST['parent']) : null;

    $args = [
      'taxonomy'   => 'category',
      'hide_empty' => false,
      'number'     => 200,
      'orderby'    => 'name',
      'order'      => 'ASC',
    ];
    if ($search) $args['search'] = $search;
    if ($parent !== null) $args['parent'] = $parent;

    $terms = get_terms($args);
    if (is_wp_error($terms)) wp_send_json_error($terms->get_error_message(), 500);

    $default_id = (int) get_option('default_category');
    $uncat      = get_term_by('slug', 'uncategorized', 'category');
    $uncat_id   = $uncat ? (int)$uncat->term_id : 0;

    $items = [];
    foreach ($terms as $t) {
      $parent_name = '';
      if ($t->parent) {
        $p = get_term($t->parent, 'category');
        if ($p && !is_wp_error($p)) $parent_name = $p->name;
      }
      $items[] = [
        'id'              => (int)$t->term_id,
        'name'            => $t->name,
        'slug'            => $t->slug,
        'parent'          => (int)$t->parent,
        'parent_name'     => $parent_name,
        'count'           => (int)$t->count,
        'is_default'      => ((int)$t->term_id === $default_id),
        'is_uncategorized'=> ((int)$t->term_id === $uncat_id),
      ];
    }
    wp_send_json_success(['items' => $items]);
  }
}

if (!function_exists('tdcats_add_category')) {
  function tdcats_add_category(){
    tdcats_guard();

    $name   = isset($_POST['name'])  ? sanitize_text_field($_POST['name']) : '';
    $slug   = isset($_POST['slug'])  ? sanitize_title($_POST['slug'])      : '';
    $parent = isset($_POST['parent'])? intval($_POST['parent'])            : 0;
    $desc   = isset($_POST['desc'])  ? sanitize_textarea_field($_POST['desc']) : '';

    if ($name === '') wp_send_json_error('Name is required', 400);
    if ($parent < 0)  $parent = 0;

    // Unique slug to avoid "term_exists" errors
    $base = $slug ?: sanitize_title($name);
    $use  = $base ?: 'category';
    $i = 2;
    while (get_term_by('slug', $use, 'category')) {
      $use = $base . '-' . $i++;
    }

    $args = ['description' => $desc, 'slug' => $use];
    if ($parent) $args['parent'] = $parent;

    $res = wp_insert_term($name, 'category', $args);
    if (is_wp_error($res)) wp_send_json_error($res->get_error_message(), 500);

    wp_send_json_success(['id' => (int)$res['term_id'], 'slug' => $use]);
  }
}

if (!function_exists('tdcats_rename_category')) {
  function tdcats_rename_category(){
    tdcats_guard();
    $id   = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
    if ($id <= 0 || $name === '') wp_send_json_error('Invalid data', 400);

    $res = wp_update_term($id, 'category', ['name' => $name]);
    if (is_wp_error($res)) wp_send_json_error($res->get_error_message(), 500);

    wp_send_json_success(['id' => $id]);
  }
}

if (!function_exists('tdcats_delete_category')) {
  function tdcats_delete_category(){
    tdcats_guard();
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if ($id <= 0) wp_send_json_error('Invalid ID', 400);

    $default = (int)get_option('default_category');
    $uncat   = get_term_by('slug', 'uncategorized', 'category');
    $uncat_id= $uncat ? (int)$uncat->term_id : 0;

    if ($id === $default || $id === $uncat_id) {
      wp_send_json_error('Protected category cannot be deleted', 400);
    }

    $res = wp_delete_term($id, 'category');
    if (is_wp_error($res)) wp_send_json_error($res->get_error_message(), 500);

    wp_send_json_success(['id' => $id]);
  }
}

if (!function_exists('tdcats_set_default_category')) {
  function tdcats_set_default_category(){
    tdcats_guard();
    if (!current_user_can('manage_options')) {
      wp_send_json_error('Insufficient permission', 403);
    }
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $term = get_term($id, 'category');
    if (!$term || is_wp_error($term)) wp_send_json_error('Category not found', 404);

    update_option('default_category', (int)$id);
    wp_send_json_success(['id' => (int)$id]);
  }
}



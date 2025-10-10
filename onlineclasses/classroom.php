<?php
$current_user = wp_get_current_user();
global $wpdb;

// 1) Get teacher ID
$teacher = $wpdb->get_row( $wpdb->prepare(
    "SELECT teacher_id FROM wpC_teachers_main WHERE Email = %s",
    $current_user->user_email
) );
$teacher_id = $teacher ? (int) $teacher->teacher_id : 0;

// 2) Handle “Save / Update Schedule”
if ( isset($_POST['save_schedule']) && wp_verify_nonce($_POST['save_schedule_nonce'],'save_schedule') ) {
    $data = [
        'teacher_id'                    => $teacher_id,
        'teacher_allocated_subject_id'  => intval( $_POST['teacher_allocated_subject_id'] ),
        'session_date'                  => sanitize_text_field( $_POST['session_date'] ),
        'start_time'                    => sanitize_text_field( $_POST['start_time'] ),
        'end_time'                      => sanitize_text_field( $_POST['end_time'] ),
        'status'                        => 'pending'
    ];
    if ( ! empty($_POST['session_id']) ) {
        $wpdb->update( 'wpC_teacher_sessions', $data, ['session_id' => intval($_POST['session_id'])] );
    } else {
        $wpdb->insert( 'wpC_teacher_sessions', $data );
    }
    // reload to clear POST
    wp_safe_redirect( remove_query_arg('edit') );
    exit;
}

// 3) Handle “Delete Schedule”
if ( isset($_POST['delete_schedule']) && wp_verify_nonce($_POST['delete_schedule_nonce'],'delete_schedule') ) {
    $sid = intval( $_POST['session_id'] );
    $wpdb->delete( 'wpC_teacher_sessions', ['session_id' => $sid] );
    wp_safe_redirect( remove_query_arg('delete') );
    exit;
}

// 4) Handle “Generate Link & Token”
if ( isset($_POST['generate_link']) && wp_verify_nonce($_POST['generate_link_nonce'],'generate_link') ) {
    $sid = intval( $_POST['session_id'] );
    $session = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM wpC_teacher_sessions WHERE session_id = %d AND teacher_id = %d",
        $sid, $teacher_id
    ) );
    if ( $session ) {
        $app_id        = 2096951377;
        $server_secret = 'c186b809ae926b7d55b0921297ebda88';
        $room_id       = 'class_' . $sid;
        $user_id       = 'teacher_' . $teacher_id;
        $user_name     = 'Teacher';

        $payload = [
          'app_id'      => $app_id,
          'room_id'     => $room_id,
          'user_id'     => $user_id,
          'user_name'   => $user_name,
          'privilege'   => ['login_room'=>1,'publish_stream'=>1],
          'expire_time' => time() + 3600,
          'create_time' => time(),
        ];
        $json = wp_json_encode( $payload );
        $hash = hash_hmac('sha256', $json, $server_secret, true);
        $token= base64_encode( $hash . $json );
        $link = "https://onlineclass.test.tutorspoint.co.uk/classroom.php?token=" . rawurlencode($token) . "&role=teacher";

        $wpdb->update( 'wpC_teacher_sessions', [
            'meeting_link'  => $link,
            'access_token'  => $token
        ], ['session_id' => $sid] );
    }
    wp_safe_redirect();
    exit;
}

// 5) Fetch subjects for dropdown
$subjects = $wpdb->get_results( $wpdb->prepare("
    SELECT sal.teacher_allocated_subject_id, s.SubjectName
    FROM wpC_teacher_allocated_subjects sal
    INNER JOIN wpC_subjects_level sl ON sal.subject_level_id = sl.subject_level_id
    INNER JOIN wpC_subjects s ON sl.subject_Id = s.subject_id
    WHERE sal.teacher_id = %d
", $teacher_id) );

// 6) Fetch all schedules
$schedules = $wpdb->get_results( $wpdb->prepare("
    SELECT ts.*, s.SubjectName
    FROM wpC_teacher_sessions ts
    LEFT JOIN wpC_teacher_allocated_subjects sal ON ts.teacher_allocated_subject_id = sal.teacher_allocated_subject_id
    LEFT JOIN wpC_subjects_level sl ON sal.subject_level_id = sl.subject_level_id
    LEFT JOIN wpC_subjects s ON sl.subject_Id = s.subject_id
    WHERE ts.teacher_id = %d
    ORDER BY ts.session_date DESC, ts.start_time ASC
", $teacher_id) );

// 7) Load session for editing (if any)
$editing = null;
if ( isset($_GET['edit']) ) {
    $editing = $wpdb->get_row( $wpdb->prepare(
      "SELECT * FROM wpC_teacher_sessions WHERE session_id = %d",
      intval($_GET['edit'])
    ) );
}
?>

<style>
  .dashboard-table { width:100%; border-collapse:collapse; margin-top:1rem; }
  .dashboard-table th, .dashboard-table td {
    border:1px solid #ddd; padding:8px;
  }
  .dashboard-table th {
    background:#f9f9f9; text-align:left;
  }
  .action-form { display:inline-block; margin-right:6px; }
  .btn-small {
    padding:4px 8px;
    background:#0ABAB5; color:#fff;
    border:none; border-radius:4px;
    font-size:0.85rem; cursor:pointer;
  }
  .btn-small:hover { background:#089894; }
  .copyable { cursor:pointer; color:#0073aa; }
</style>

<h3>📅 My Teaching Schedule</h3>

<form method="post" style="margin-bottom:20px;">
  <?php wp_nonce_field('save_schedule','save_schedule_nonce'); ?>
  <input type="hidden" name="session_id" value="<?php echo esc_attr($editing->session_id ?? ''); ?>">

  <label>Subject:</label>
  <select name="teacher_allocated_subject_id" required>
    <option value="">— Select Subject —</option>
    <?php foreach ( $subjects as $sub ): ?>
      <option value="<?php echo esc_attr($sub->teacher_allocated_subject_id); ?>"
        <?php selected( $editing->teacher_allocated_subject_id ?? '', $sub->teacher_allocated_subject_id ); ?>>
        <?php echo esc_html($sub->SubjectName); ?>
      </option>
    <?php endforeach; ?>
  </select>

  <label>Date:</label>
  <input type="date" name="session_date" required
    value="<?php echo esc_attr($editing->session_date ?? ''); ?>">

  <label>Start:</label>
  <input type="time" name="start_time" required
    value="<?php echo esc_attr($editing->start_time ?? ''); ?>">

  <label>End:</label>
  <input type="time" name="end_time" required
    value="<?php echo esc_attr($editing->end_time ?? ''); ?>">

  <button type="submit" name="save_schedule" class="btn-small">
    💾 <?php echo $editing ? 'Update' : 'Save'; ?>
  </button>
  <?php if ( $editing ): ?>
    <a href="?tab=tab-schedule" style="margin-left:8px;">Cancel</a>
  <?php endif; ?>
</form>

<table class="dashboard-table">
  <thead>
    <tr>
      <th>Subject</th>
      <th>Date</th>
      <th>Time</th>
      <th>Actions</th>
      <th>Link</th>
      <th>Token</th>
    </tr>
  </thead>
  <tbody>
    <?php if ( $schedules ): foreach ( $schedules as $s ): ?>
      <tr>
        <td><?php echo esc_html($s->SubjectName); ?></td>
        <td><?php echo esc_html($s->session_date); ?></td>
        <td><?php echo esc_html("{$s->start_time} — {$s->end_time}"); ?></td>
        <td>
          <!-- Edit -->
          <a class="btn-small" href="?tab=tab-schedule&edit=<?php echo $s->session_id; ?>">✏️</a>
          <!-- Delete -->
          <form method="post" class="action-form" style="display:inline;">
            <?php wp_nonce_field('delete_schedule','delete_schedule_nonce'); ?>
            <input type="hidden" name="session_id" value="<?php echo $s->session_id; ?>">
            <button type="submit" name="delete_schedule" class="btn-small"
                    onclick="return confirm('Delete this schedule?');">🗑️</button>
          </form>
          <!-- Generate Link -->
          <?php if ( ! $s->meeting_link ): ?>
            <form method="post" class="action-form" style="display:inline;">
              <?php wp_nonce_field('generate_link','generate_link_nonce'); ?>
              <input type="hidden" name="session_id" value="<?php echo $s->session_id; ?>">
              <button type="submit" name="generate_link" class="btn-small">🔗</button>
            </form>
          <?php endif; ?>
        </td>
        <td class="copyable" onclick="copyText(this)">
          <?php echo $s->meeting_link ? esc_url($s->meeting_link) : '—'; ?>
        </td>
        <td class="copyable" onclick="copyText(this)"
            style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
          <?php echo $s->access_token ? esc_html($s->access_token) : '—'; ?>
        </td>
      </tr>
    <?php endforeach; else: ?>
      <tr><td colspan="6" style="text-align:center;">No schedules found.</td></tr>
    <?php endif; ?>
  </tbody>
</table>

<script>
function copyText(el) {
  const txt = el.innerText.trim();
  if (!txt || txt === '—') return;
  navigator.clipboard.writeText(txt);
  const old = el.innerText;
  el.innerText = '✔ Copied';
  setTimeout(()=> el.innerText = old, 1500);
}
</script>

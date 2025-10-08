<?php
add_action('# removed_ajax_get_teacher_schedule', 'get_teacher_schedule');
function get_teacher_schedule() {
    global $wpdb;
    $current_user = wp_get_current_user();
    $teacher = $wpdb->get_row($wpdb->prepare(
        "SELECT teacher_id FROM wpC_teachers_main WHERE Email = %s",
        $current_user->user_email
    ));
    if (!$teacher) {
        wp_send_json_success([]);
    }
    $schedules = $wpdb->get_results($wpdb->prepare(
        "SELECT s.session_id, s.session_date, s.start_time, s.end_time,
                subj.SubjectName
         FROM wpC_teacher_sessions s
         JOIN wpC_teacher_allocated_subjects tas ON s.teacher_allocated_subject_id = tas.teacher_allocated_subject_id
         JOIN wpC_subjects_level sl ON tas.subject_level_id = sl.subject_level_id
         JOIN wpC_subjects subj ON sl.subject_Id = subj.subject_id
         WHERE s.teacher_id = %d
         ORDER BY s.session_date ASC",
        $teacher->teacher_id
    ));
    $results = [];
    foreach ($schedules as $s) {
        $results[] = [
            'session_id' => $s->session_id,
            'subject_name' => $s->SubjectName,
            'session_date' => $s->session_date,
            'start_time' => date('H:i', strtotime($s->start_time)),
            'end_time' => date('H:i', strtotime($s->end_time)),
        ];
    }
    wp_send_json_success($results);
}

add_action('# removed_ajax_generate_class_token', 'generate_class_token');
function generate_class_token() {
    global $wpdb;

    // ✅ Check for required input
    if (!isset($_POST['session_id'])) {
        wp_send_json_error(['message' => 'Missing session_id']);
    }

    $session_id = intval($_POST['session_id']);
    $teacher_id = get_current_user_id();

    // ✅ Make sure session exists
    $session = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM wpC_teacher_sessions WHERE session_id = %d AND teacher_id = %d",
        $session_id, $teacher_id
    ));

    if (!$session) {
        wp_send_json_error(['message' => 'Session not found']);
    }

    // ✅ ZegoCloud App Info
    $app_id = 2096951377;
    $server_secret = 'c186b809ae926b7d55b0921297ebda88';

    // ✅ Generate token manually
    $user_id = "teacher_" . $teacher_id;
    $room_id = "class_" . $session_id;

    $payload = [
        'app_id'      => $app_id,
        'user_id'     => $user_id,
        'room_id'     => $room_id,
        'user_name'   => 'Teacher',
        'privilege'   => ['login_room' => 1, 'publish_stream' => 1],
        'expire_time' => time() + 3600,
        'create_time' => time()
    ];

    $json_str = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $hash = hash_hmac('sha256', $json_str, $server_secret, true);
    $token = base64_encode($hash . $json_str);

    // ✅ Generate link
    $meeting_link = "https://onlineclass.public_html.test/classroom/{$room_id}";

    // ✅ Store in DB (you must have `meeting_link` and `access_token` columns)
    $updated = $wpdb->update(
        'wpC_teacher_sessions',
        [
            'meeting_link' => $meeting_link,
            'access_token' => $token
        ],
        ['session_id' => $session_id],
        ['%s', '%s'],
        ['%d']
    );

    if ($updated === false) {
        wp_send_json_error(['message' => 'Database update failed.']);
    }

    // ✅ Respond
    wp_send_json_success([
        'meeting_link' => $meeting_link,
        'access_token' => $token
    ]);
}




/*
add_action('# removed_ajax_generate_class_token', 'generate_class_token');
function generate_class_token() {
    global $wpdb;
    $session_id = intval($_POST['session_id']);
    $teacher_id = get_current_user_id(); // or get from session if needed

    $session = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM wpC_teacher_sessions WHERE session_id = %d AND teacher_id = %d", 
        $session_id, $teacher_id
    ));

    if (!$session) {
        wp_send_json_error(['message' => 'Session not found']);
    }

    $app_id = 2096951377;
    $server_secret = 'c186b809ae926b7d55b0921297ebda88';
    $user_id = "teacher_" . $teacher_id;
    $room_id = "class_" . $session->session_id;

    $payload = [
        'app_id' => $app_id,
        'room_id' => $room_id,
        'user_id' => $user_id,
        'user_name' => 'Teacher',
        'privilege' => ['login_room' => 1, 'publish_stream' => 1],
        'expire_time' => time() + 3600,
        'create_time' => time()
    ];

    $json = json_encode($payload);
    $hash = hash_hmac('sha256', $json, $server_secret, true);
    $token = base64_encode($hash . $json);

    $link = "https://onlineclass.public_html.test/classroom/{$room_id}";

    $wpdb->update('wpC_teacher_sessions', [
        'meeting_link' => $link,
        'access_token' => $token
    ], ['session_id' => $session_id]);

    wp_send_json_success([
        'meeting_link' => $link,
        'access_token' => $token
    ]);
}

*/



/*add_action('# removed_ajax_get_teacher_schedule', 'get_teacher_schedule');
function get_teacher_schedule() {
    global $wpdb;
    $current_user = wp_get_current_user();

    // Match logged in user with teacher
    $teacher = $wpdb->get_row($wpdb->prepare(
        "SELECT teacher_id FROM wpC_teachers_main WHERE Email = %s",
        $current_user->user_email
    ));

    if (!$teacher) {
        wp_send_json_success([]); // Send empty response
    }

    $schedules = $wpdb->get_results($wpdb->prepare(
        "SELECT s.session_id, s.session_date, s.start_time, s.end_time,
                subj.SubjectName
         FROM wpC_teacher_sessions s
         JOIN wpC_teacher_allocated_subjects tas ON s.teacher_allocated_subject_id = tas.teacher_allocated_subject_id
         JOIN wpC_subjects_level sl ON tas.subject_level_id = sl.subject_level_id
         JOIN wpC_subjects subj ON sl.subject_Id = subj.subject_id
         WHERE s.teacher_id = %d
         ORDER BY s.session_date ASC",
        $teacher->teacher_id
    ));

    $results = [];
    foreach ($schedules as $s) {
        $results[] = [
            'session_id'   => $s->session_id,
            'subject_name' => $s->SubjectName,
            'session_date' => $s->session_date,
            'start_time'   => date('H:i', strtotime($s->start_time)),
            'end_time'     => date('H:i', strtotime($s->end_time)),
        ];
    }

    wp_send_json_success($results);
}




add_action('# removed_ajax_get_teacher_schedule', 'get_teacher_schedule');

function get_teacher_schedule() {
    global $wpdb;
    $current_user = wp_get_current_user();

    // Get teacher ID using email
    $teacher = $wpdb->get_row($wpdb->prepare(
        "SELECT teacher_id FROM wpC_teachers_main WHERE Email = %s",
        $current_user->user_email
    ));

    if (!$teacher) {
        wp_send_json([]);
    }

    $schedules = $wpdb->get_results($wpdb->prepare(
        "SELECT s.session_id, s.session_date, s.start_time, s.end_time,
                subj.SubjectName, tas.teacher_allocated_subject_id
         FROM wpC_teacher_sessions s
         JOIN wpC_teacher_allocated_subjects tas ON s.teacher_allocated_subject_id = tas.teacher_allocated_subject_id
         JOIN wpC_subjects_level sl ON tas.subject_level_id = sl.subject_level_id
         JOIN wpC_subjects subj ON sl.subject_Id = subj.subject_id
         WHERE s.teacher_id = %d
         ORDER BY s.session_date ASC",
        $teacher->teacher_id
    ));

    $results = [];
    foreach ($schedules as $s) {
        $results[] = [
            'session_id'                  => $s->session_id,
            'teacher_allocated_subject_id' => $s->teacher_allocated_subject_id,
            'subject_name'               => $s->SubjectName,
            'session_date'               => $s->session_date,
            'start_time'                 => date('H:i', strtotime($s->start_time)),
            'end_time'                   => date('H:i', strtotime($s->end_time)),
        ];
    }

    wp_send_json($results);
}

*/

?>
<?php
// retake-handler.php

add_action('# removed_ajax_load_retake_data', 'load_retake_data');

function load_retake_data() {
    global $wpdb;
    $current_user = wp_get_current_user();

    // ✅ Step 1: Get the teacher ID
    $teacher = $wpdb->get_row($wpdb->prepare(
        "SELECT teacher_id FROM wpC_teachers_main WHERE Email = %s",
        $current_user->user_email
    ));
    if (!$teacher) {
        wp_send_json_error(['message' => 'Teacher not found']);
    }

    $teacher_id = (int)$teacher->teacher_id;

    // ✅ Step 2: Retake History
    $retake_history = $wpdb->get_results($wpdb->prepare(
        "SELECT 
            sl.lecture_book_date,
            sl.topic,
            subj.SubjectName,
            sr.full_name AS student_name
        FROM wpC_lecture_retake_record r
        JOIN wpC_student_lectures sl ON r.lecture_book_id = sl.lecture_book_id
        JOIN wpC_student_register sr ON sr.student_id = sl.student_id
        JOIN wpC_subjects subj ON subj.subject_id = sl.subject_id
        WHERE r.teacher_id = %d AND r.retake_status = 'completed'
        ORDER BY sl.lecture_book_date DESC",
        $teacher_id
    ));

    // ✅ Step 3: Upcoming Requests
    $upcoming_requests = $wpdb->get_results($wpdb->prepare(
        "SELECT 
            sl.topic,
            sl.lecture_book_date,
            sl.lecture_time,
            subj.SubjectName,
            sr.full_name AS student_name,
            r.lecture_book_id
        FROM wpC_lecture_retake_record r
        JOIN wpC_student_lectures sl ON r.lecture_book_id = sl.lecture_book_id
        JOIN wpC_student_register sr ON sr.student_id = sl.student_id
        JOIN wpC_subjects subj ON subj.subject_id = sl.subject_id
        WHERE r.teacher_id = %d AND r.retake_status = 'pending'",
        $teacher_id
    ));

    // ✅ Step 4: Losses
    $losses = $wpdb->get_row($wpdb->prepare(
        "SELECT 
            COUNT(*) AS total_lectures, 
            COUNT(*) * 1 AS total_loss
        FROM wpC_lecture_retake_record
        WHERE teacher_id = %d AND retake_status = 'completed'",
        $teacher_id
    ));

    // ✅ Send JSON Response
    wp_send_json_success([
        'history'  => $retake_history,
        'requests' => $upcoming_requests,
        'losses'   => $losses
    ]);
}

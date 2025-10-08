<?php
/*
if (! defined('ABSPATH')) exit;

$base = get_stylesheet_directory(); // absolute filesystem path to child theme
$ajax_dir = $base . '/student-dashboard/ajax';

// list of expected handler files
$handlers = [
    'upcoming-lessons.php',
    'total-teachers.php',
    'hours-studied.php',
    'todays-schedule.php',
];

foreach ($handlers as $file) {
    $full_path = $ajax_dir . '/' . $file;
    if (file_exists($full_path)) {
        require_once $full_path;
    } else {
        error_log("student-dashboard loader missing AJAX handler: {$full_path}");
        // optional: fail silently so editor doesn’t break; you can also uncomment for dev:
        // trigger_error("Missing AJAX handler: {$full_path}", E_USER_WARNING);
    }
}

// enqueue and localize script
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_script(
        'tp-student-dashboard',
        get_stylesheet_directory_uri() . '/student-dashboard/js/dashboard.js',
        ['jquery'],
        null,
        true
    );
    wp_localize_script('tp-student-dashboard', 'TPStudentDashboard', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('student_dashboard_nonce'),
    ]);
});
*/
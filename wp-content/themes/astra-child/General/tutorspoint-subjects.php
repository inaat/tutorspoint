<?php
// ✅ AJAX: Load Levels
add_action('wp_ajax_tp_load_levels', 'tp_ajax_load_levels');
add_action('wp_ajax_nopriv_tp_load_levels', 'tp_ajax_load_levels');

function tp_ajax_load_levels() {
    global $wpdb;
    $results = $wpdb->get_col("SELECT DISTINCT level FROM wpC_subjects_level ORDER BY level ASC");

    foreach ($results as $level) {
        echo "<option value='" . esc_attr($level) . "'>$level</option>";
    }
    wp_die();
}

// ✅ AJAX: Load Subjects Based on Level
add_action('wp_ajax_tp_load_subjects_by_level', 'tp_ajax_load_subjects');
add_action('wp_ajax_nopriv_tp_load_subjects_by_level', 'tp_ajax_load_subjects');

function tp_ajax_load_subjects() {
    global $wpdb;
    $level = sanitize_text_field($_POST['level']);

    $query = "
        SELECT s.SubjectName
        FROM wpC_subjects_level sl
        JOIN wpC_subjects s ON sl.subject_Id = s.subject_id
        WHERE sl.level = %s
        ORDER BY s.SubjectName ASC
    ";

    $results = $wpdb->get_col($wpdb->prepare($query, $level));

    foreach ($results as $subject) {
        echo "<option value='" . esc_attr($subject) . "'>$subject</option>";
    }
    wp_die();
}

<?php
/**
 * AJAX for Level/Subject dropdowns (HTML <option> output)
 * Actions:
 *  - tp_load_levels
 *  - tp_load_subjects_by_level
 *
 * Supports both schemas:
 *   A) Normalized:  wpC_class_levels(id, level_name[, sort_order])
 *                   wpC_subjects_level(level_id, subject_id)
 *                   wpC_subjects(subject_id, SubjectName)
 *   B) Legacy:      wpC_subjects_level(level, subject_Id)
 *                   wpC_subjects(subject_id, SubjectName)
 */

/* ----------------------------- Levels ----------------------------- */
/*add_action('wp_ajax_tp_load_levels',        'tp_ajax_load_levels');
add_action('wp_ajax_nopriv_tp_load_levels', 'tp_ajax_load_levels');

function tp_ajax_load_levels() {
    global $wpdb;

    // Does table exist?
    $has_levels_table = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s",
         'wpC_class_levels'
    ));

    $rows = [];

    if ($has_levels_table) {
        // Does sort_order exist?
        $has_sort = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME   = %s
               AND COLUMN_NAME  = %s",
            'wpC_class_levels', 'sort_order'
        ));

        $order_sql = $has_sort ? "ORDER BY IFNULL(sort_order, 9999), level_name"
                               : "ORDER BY level_name";

        $rows = $wpdb->get_results("SELECT id, level_name FROM wpC_class_levels {$order_sql}");
    }

    // Fallback to legacy: distinct levels from subjects_level
    if (empty($rows)) {
        $legacy = $wpdb->get_results("
            SELECT DISTINCT sl.level AS id, sl.level AS level_name
            FROM wpC_subjects_level sl
            WHERE sl.level IS NOT NULL AND sl.level <> ''
            ORDER BY sl.level
        ");
        $rows = $legacy ?: [];
    }

    foreach ($rows as $r) {
        $value = isset($r->id) ? $r->id : $r->level_name;
        $text  = isset($r->level_name) ? $r->level_name : $r->id;
        echo '<option value="' . esc_attr($value) . '">' . esc_html($text) . '</option>';
    }
    wp_die();
}

/* ------------------------- Subjects by Level ---------------------- */
/*add_action('wp_ajax_tp_load_subjects_by_level',        'tp_ajax_load_subjects_by_level');
add_action('wp_ajax_nopriv_tp_load_subjects_by_level', 'tp_ajax_load_subjects_by_level');

function tp_ajax_load_subjects_by_level() {
    global $wpdb;

    if (!isset($_POST['level'])) {
        wp_die(); // nothing to do
    }

    $level_raw = wp_unslash($_POST['level']);  // can be id (numeric) or name (string)
    $level_id  = is_numeric($level_raw) ? intval($level_raw) : null;

    $subjects = [];

    // Try normalized (level_id + subject_id)
    if ($level_id !== null) {
        $subjects = $wpdb->get_results($wpdb->prepare("
            SELECT s.subject_id, s.SubjectName
            FROM wpC_subjects_level sl
            INNER JOIN wpC_subjects s ON s.subject_id = sl.subject_id
            WHERE sl.level_id = %d
            ORDER BY s.SubjectName
        ", $level_id));
    }

    // Fallback to legacy (level text + subject_Id)
    if (empty($subjects)) {
        $subjects = $wpdb->get_results($wpdb->prepare("
            SELECT s.subject_id, s.SubjectName
            FROM wpC_subjects_level sl
            INNER JOIN wpC_subjects s ON s.subject_id = sl.subject_Id
            WHERE sl.level = %s
            ORDER BY s.SubjectName
        ", $level_raw));
    }

    foreach ($subjects as $row) {
        echo '<option value="' . esc_attr($row->subject_id) . '">' . esc_html($row->SubjectName) . '</option>';
    }
    wp_die();
}
*/
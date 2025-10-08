<?php
add_action('wp_ajax_tp_load_levels', 'tp_load_levels');
add_action('wp_ajax_nopriv_tp_load_levels', 'tp_load_levels');
function tp_load_levels() {
  check_ajax_referer('tp_ls_nonce');
  global $wpdb;
  $rows = $wpdb->get_results("SELECT id, level_name FROM wpC_class_levels ORDER BY level_name ASC", ARRAY_A);
  wp_send_json($rows); // or echo <option>… if you prefer
}

add_action('wp_ajax_tp_load_subjects_by_level', 'tp_load_subjects_by_level');
add_action('wp_ajax_nopriv_tp_load_subjects_by_level', 'tp_load_subjects_by_level');
function tp_load_subjects_by_level() {
  check_ajax_referer('tp_ls_nonce');
  global $wpdb;
  $level = isset($_POST['level']) ? intval($_POST['level']) : 0;
  if (!$level) wp_send_json([]);
  $sql = "
    SELECT s.subject_id, s.SubjectName
    FROM wpC_subjects_level sl
    JOIN wpC_subjects s ON s.subject_id = sl.subject_id
    WHERE sl.level_id = %d
    ORDER BY s.SubjectName ASC";
  $rows = $wpdb->get_results($wpdb->prepare($sql, $level), ARRAY_A);
  wp_send_json($rows);
}

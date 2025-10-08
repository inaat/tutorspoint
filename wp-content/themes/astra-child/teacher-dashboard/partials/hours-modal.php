<?php
// 📁 File: teacher-dashboard/partials/hours-modal.php

$current_user = wp_get_current_user();
global $wpdb;

$teacher = $wpdb->get_row($wpdb->prepare("SELECT * FROM wpC_teachers_main WHERE Email = %s", $current_user->user_email));
$teacher_id = $teacher ? (int)$teacher->teacher_id : 0;

$hours_data = $wpdb->get_results($wpdb->prepare("SELECT sl.*, sr.full_name AS student_name, s.SubjectName
  FROM wpC_student_lectures sl
  LEFT JOIN wpC_student_register sr ON sl.student_id = sr.student_id
  LEFT JOIN wpC_subjects s ON sl.subject_id = s.subject_id
  WHERE sl.teacher_id = %d AND sl.status = 'booked' AND sl.is_taught = 1
  ORDER BY sl.lecture_book_date DESC", $teacher_id));
?>

<div id="hoursModal" class="tp-modal" style="display:none">
  <div class="tp-modal-content">
    <span class="tp-close" onclick="document.getElementById('hoursModal').style.display='none'">×</span>
    <h2>⏱️ Hours Taught</h2>
    <?php if (!empty($hours_data)): ?>
      <table class="tp-table">
        <thead>
          <tr>
            <th>Student Name</th>
            <th>Subject</th>
            <th>Topic</th>
            <th>Date of Taught</th>
            <th>Duration</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($hours_data as $h): ?>
            <tr>
              <td><?= esc_html($h->student_name) ?></td>
              <td><?= esc_html($h->SubjectName) ?></td>
              <td><?= esc_html($h->topic) ?></td>
              <td><?= esc_html(date('d M Y', strtotime($h->lecture_book_date))) ?></td>
              <td><?= esc_html($h->duration) ?> hour<?= $h->duration > 1 ? 's' : '' ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>No records found for taught sessions.</p>
    <?php endif; ?>
  </div>
</div>

<style>
.tp-modal {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.tp-modal-content {
  background: #fff;
  padding: 20px;
  max-width: 800px;
  width: 90%;
  border-radius: 8px;
  box-shadow: 0 5px 20px rgba(0,0,0,0.3);
  position: relative;
}
.tp-close {
  position: absolute;
  top: 10px; right: 15px;
  font-size: 24px;
  font-weight: bold;
  cursor: pointer;
}
.tp-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
}
.tp-table th, .tp-table td {
  border: 1px solid #ccc;
  padding: 10px;
  text-align: center;
}
</style>

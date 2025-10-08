<?php
$current_user = wp_get_current_user();
global $wpdb;

// ✅ Get teacher ID
$teacher = $wpdb->get_row($wpdb->prepare(
    "SELECT teacher_id FROM wpC_teachers_main WHERE Email = %s",
    $current_user->user_email
));
$teacher_id = $teacher ? (int)$teacher->teacher_id : 0;

// ✅ Retake History
$retake_history = $wpdb->get_results($wpdb->prepare("
    SELECT 
        sl.lecture_book_date,
        sl.topic,
        subj.SubjectName,
        sr.full_name AS student_name
    FROM wpC_lecture_retake_record r
    JOIN wpC_student_lectures sl ON r.lecture_book_id = sl.lecture_book_id
    JOIN wpC_student_register sr ON sr.student_id = sl.student_id
    JOIN wpC_subjects subj ON subj.subject_id = sl.subject_id
    WHERE r.teacher_id = %d AND r.retake_status = 'completed'
    ORDER BY sl.lecture_book_date DESC
", $teacher_id));

// ✅ Upcoming Requests
$upcoming_requests = $wpdb->get_results($wpdb->prepare("
    SELECT 
        sl.topic,
        sl.lecture_book_date,
        sl.lecture_time,
        subj.SubjectName,
        sr.full_name AS student_name
    FROM wpC_lecture_retake_record r
    JOIN wpC_student_lectures sl ON r.lecture_book_id = sl.lecture_book_id
    JOIN wpC_student_register sr ON sr.student_id = sl.student_id
    JOIN wpC_subjects subj ON subj.subject_id = sl.subject_id
    WHERE r.teacher_id = %d AND r.retake_status = 'pending'
", $teacher_id));

// ✅ Losses
$losses = $wpdb->get_row($wpdb->prepare("
    SELECT 
        COUNT(*) AS total_lectures, 
        COUNT(*) * 1 AS total_loss
    FROM wpC_lecture_retake_record
    WHERE teacher_id = %d AND retake_status = 'completed'
", $teacher_id));
?>

<div id="retake-tab-content" class="tab-inner">
  <h5>📜 Retake History <span style="float:right;">Total Retake 👍: <span id="total-retake-count"><?= count($retake_history); ?></span></span></h5>
  <table class="dashboard-table">
    <thead>
      <tr>
        <th>Date</th>
        <th>Subject</th>
        <th>Topic</th>
        <th>Student</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($retake_history): ?>
        <?php foreach ($retake_history as $row): ?>
          <tr>
            <td><?= esc_html(date("d M Y", strtotime($row->lecture_book_date))); ?></td>
            <td><?= esc_html($row->SubjectName); ?></td>
            <td><?= esc_html($row->topic); ?></td>
            <td><?= esc_html($row->student_name); ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="4">No retake history found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <h5 class="mt-4">📥 Upcoming Retake Requests</h5>
  <table class="dashboard-table">
    <thead>
      <tr>
        <th>Subject</th>
        <th>Topic</th>
        <th>Student</th>
        <th>Requested Time</th>
        <th>Status</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($upcoming_requests): ?>
        <?php foreach ($upcoming_requests as $row): ?>
          <tr>
            <td><?= esc_html($row->SubjectName); ?></td>
            <td><?= esc_html($row->topic); ?></td>
            <td><?= esc_html($row->student_name); ?></td>
            <td><?= esc_html($row->lecture_book_date . ' ' . $row->lecture_time); ?></td>
            <td><span style="color: orange;">Pending</span></td>
            <td><button class="btn btn-sm">📅 Schedule</button></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="6">No upcoming requests.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <h5 class="mt-4">💸 Retake Losses Calculation</h5>
  <table class="dashboard-table">
    <thead>
      <tr>
        <th>Sr. No</th>
        <th>No. of Lectures Retaken</th>
        <th>Total Losses of Company</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>1</td>
        <td><?= esc_html($losses->total_lectures ?? 0); ?></td>
        <td>£<?= esc_html($losses->total_loss ?? 0); ?></td>
      </tr>
    </tbody>
  </table>
</div>

<style>
.dashboard-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 30px;
}
.dashboard-table th,
.dashboard-table td {
  padding: 10px;
  border: 1px solid #ccc;
  text-align: left;
}
.mt-4 {
  margin-top: 40px;
}
@media (max-width: 768px) {
  .dashboard-table th, .dashboard-table td {
    font-size: 14px;
  }
}
</style>

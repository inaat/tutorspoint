<?php
// 📁 book-lecture-tab.php
/*
$current_user = wp_get_current_user();
global $wpdb;

// 1. Get student info
$student = $wpdb->get_row($wpdb->prepare(
  "SELECT id, class_level_id FROM wpC_student_register WHERE email = %s",
  $current_user->user_email
));
if (!$student) {
  echo "<p style='color:red;'>❌ Student not found.</p>";
  return;
}
$student_id = $student->id;
$class_level_id = $student->class_level_id;

// 2. Fetch teachers who teach the student's class level
$teachers = $wpdb->get_results($wpdb->prepare("
  SELECT DISTINCT tm.teacher_id, tm.full_name, tm.photo, s.SubjectName
  FROM wpC_teachers_main tm
  JOIN wpC_teacher_allocated_subjects tas ON tm.teacher_id = tas.teacher_id
  JOIN wpC_subjects_level sl ON tas.subject_level_id = sl.subject_level_id
  JOIN wpC_subjects s ON sl.subject_Id = s.subject_id
  WHERE sl.level_Id = %d
", $class_level_id));
?>

<div class="book-lecture-wrapper">
  <h3>📚 Available Teachers & Sessions</h3>

  <?php if ($teachers): foreach ($teachers as $teacher): ?>
    <div class="teacher-block">
      <div class="teacher-header">
        <img src="<?= esc_url($teacher->photo) ?>" alt="" class="teacher-photo">
        <div>
          <strong><?= esc_html($teacher->full_name) ?></strong><br>
          <small><?= esc_html($teacher->SubjectName) ?></small>
        </div>
      </div>

      <div class="teacher-slots">
        <?php
        $slots = $wpdb->get_results($wpdb->prepare("
          SELECT * FROM wpC_teacher_generated_slots 
          WHERE teacher_id = %d 
            AND status = 'available' 
            AND class_level_id = %d
          ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), start_time
        ", $teacher->teacher_id, $class_level_id));
        ?>

        <?php if ($slots): foreach ($slots as $slot): ?>
          <div class="slot-row">
            <?= esc_html($slot->day_of_week) ?> -
            <?= date('g:i A', strtotime($slot->start_time)) ?> to <?= date('g:i A', strtotime($slot->end_time)) ?>
            <button class="book-btn" data-slot="<?= $slot->slot_id ?>">📅 Book</button>
          </div>
        <?php endforeach; else: ?>
          <p class="no-slots">No available sessions.</p>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; else: ?>
    <p>No teachers found for your level.</p>
  <?php endif; ?>
</div>

<style>
.book-lecture-wrapper { padding: 20px; }
.teacher-block {
  border: 1px solid #ddd;
  margin-bottom: 20px;
  padding: 15px;
  border-radius: 6px;
}
.teacher-header {
  display: flex;
  align-items: center;
  margin-bottom: 10px;
}
.teacher-photo {
  width: 50px; height: 50px;
  border-radius: 50%;
  margin-right: 15px;
}
.slot-row {
  display: flex;
  justify-content: space-between;
  background: #f9f9f9;
  padding: 6px 10px;
  border-radius: 4px;
  margin-bottom: 6px;
}
.book-btn {
  background: #0ABAB5;
  color: white;
  border: none;
  padding: 5px 10px;
  border-radius: 4px;
  cursor: pointer;
}
.no-slots { font-style: italic; color: gray; }
</style>

<script>
jQuery(document).ready(function($){
  $('.book-btn').on('click', function(){
    const btn = $(this);
    const slotId = $(this).data('slot');
    $.post(ajaxurl, {
      action: 'book_teacher_slot',
      slot_id: slotId,
      student_id: <?= $student_id ?>
    }, function(res){
      alert(res.message);
      if (res.success) btn.closest('.slot-row').remove();
    }, 'json');
  });
});
</script>

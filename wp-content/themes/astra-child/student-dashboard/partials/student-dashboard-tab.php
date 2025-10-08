<?php
// File: student-dashboard/partials/student-dashboard-tab.php

$current_user = wp_get_current_user();
global $wpdb;

// Fetch student
$student = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * FROM wpC_student_register WHERE email = %s",
        $current_user->user_email
    )
);
$student_id   = $student ? (int)$student->student_id : 0;
$student_name = $student ? $student->full_name : $current_user->display_name;

$today = date('Y-m-d');

// 1) Upcoming (excl. today)
$upcoming_lessons = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT sl.*, s.SubjectName, t.FullName AS teacher_name
         FROM wpC_student_lectures sl
         LEFT JOIN wpC_subjects s ON sl.subject_id = s.subject_id
         LEFT JOIN wpC_teachers_main t ON sl.teacher_id = t.teacher_id
         WHERE sl.student_id = %d
           AND sl.status = 'booked'
           AND sl.lecture_book_date > %s
         ORDER BY sl.lecture_book_date, sl.lecture_time ASC",
        $student_id, $today
    )
);

// 2) Group by teacher
$teacher_groups = [];
foreach ($upcoming_lessons as $l) {
    $teacher_groups[$l->teacher_id][] = $l;
}

// 3) Studied lectures (taught)
$studied_lectures = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT sl.*, s.SubjectName, t.FullName AS teacher_name
         FROM wpC_student_lectures sl
         LEFT JOIN wpC_subjects s ON sl.subject_id = s.subject_id
         LEFT JOIN wpC_teachers_main t ON sl.teacher_id = t.teacher_id
         WHERE sl.student_id = %d
           AND sl.status = 'booked'
           AND sl.is_taught = 1
         ORDER BY sl.lecture_book_date DESC",
        $student_id
    )
);

// 4) Today’s schedule
$today_sessions = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT sl.*, s.SubjectName, t.FullName AS teacher_name
         FROM wpC_student_lectures sl
         LEFT JOIN wpC_subjects s ON sl.subject_id = s.subject_id
         LEFT JOIN wpC_teachers_main t ON sl.teacher_id = t.teacher_id
         WHERE sl.student_id = %d
           AND sl.status = 'booked'
           AND sl.lecture_book_date = %s
         ORDER BY sl.lecture_time ASC",
        $student_id, $today
    )
);
?>

<h5>👋 Welcome, <?= esc_html($student_name); ?>!</h5>

<div class="student-badge-row">
  <div class="student-badge" id="openUpcoming">
    📘<br>Upcoming Lessons<br><strong><?= count($upcoming_lessons); ?></strong>
  </div>
  <div class="student-badge" id="openTeachers">
    🎓<br>Total Teachers<br><strong><?= count($teacher_groups); ?></strong>
  </div>
  <div class="student-badge" id="openHours">
    ⏱️<br>Hours Studied<br><strong><?= array_sum(array_column($studied_lectures,'duration')); ?></strong>
  </div>
</div>

<div class="tp-today-schedule">
  <button class="tp-toggle-btn" data-target="todayScheduleList">+</button>
  <span>Today's Schedule</span>
  <ul id="todayScheduleList" class="tp-schedule-list" style="display:none;">
    <?php if (!empty($today_sessions)): foreach($today_sessions as $s): ?>
      <li>
        <?= esc_html($s->SubjectName) ?> |
        <?= esc_html($s->topic) ?> |
        <?= date('g:i A',strtotime($s->lecture_time)) ?> |
        <?= esc_html($s->teacher_name) ?>
        <button class="reschedule-btn" data-id="<?= esc_attr($s->lecture_book_id) ?>">Reschedule</button>
        <button class="cancel-btn"     data-id="<?= esc_attr($s->lecture_book_id) ?>">Cancel</button>
      </li>
    <?php endforeach; else: ?>
      <li>No lessons scheduled for today.</li>
    <?php endif; ?>
  </ul>
</div>

<!-- ==== Modals ==== -->
<!-- Upcoming Lessons Modal -->
<div id="upcomingModal" class="tp-modal" style="display:none">
  <div class="tp-modal-header">
    <h2>Upcoming Lessons</h2>
    <button class="close-btn" data-target="upcomingModal">×</button>
  </div>
  <div class="tp-modal-content">
    <?php if($upcoming_lessons): foreach($upcoming_lessons as $l): ?>
      <button class="tp-accordion-header" data-target="up-<?= esc_attr($l->lecture_book_id) ?>">
        + <?= date('M j, Y',strtotime($l->lecture_book_date)) ?>
      </button>
      <div id="up-<?= esc_attr($l->lecture_book_id) ?>" class="tp-accordion-content" style="display:none">
        <table>
          <tr><th>Date</th><th>Subject</th><th>Topic</th><th>Time</th><th>Teacher</th><th>Actions</th></tr>
          <tr>
            <td><?= date('M j, Y',strtotime($l->lecture_book_date)) ?></td>
            <td><?= esc_html($l->SubjectName) ?></td>
            <td><?= esc_html($l->topic) ?></td>
            <td><?= date('g:i A',strtotime($l->lecture_time)) ?></td>
            <td><?= esc_html($l->teacher_name) ?></td>
            <td>
              <button class="reschedule-btn" data-id="<?= esc_attr($l->lecture_book_id) ?>">Reschedule</button>
              <button class="cancel-btn"     data-id="<?= esc_attr($l->lecture_book_id) ?>">Cancel</button>
            </td>
          </tr>
        </table>
      </div>
    <?php endforeach; else: ?>
      <p>No upcoming lessons.</p>
    <?php endif; ?>
  </div>
</div>

<!-- Total Teachers Modal -->
<div id="teachersModal" class="tp-modal" style="display:none">
  <div class="tp-modal-header">
    <h2>Total Teachers</h2>
    <button class="close-btn" data-target="teachersModal">×</button>
  </div>
  <div class="tp-modal-content">
    <?php if($teacher_groups): foreach($teacher_groups as $tid=>$grp): ?>
      <button class="tp-accordion-header" data-target="tg-<?= esc_attr($tid) ?>">
        + <?= esc_html($grp[0]->teacher_name) ?> (<?= count($grp) ?>)
      </button>
      <div id="tg-<?= esc_attr($tid) ?>" class="tp-accordion-content" style="display:none">
        <table>
          <tr><th>Teacher</th><th>Date</th><th>Subject</th><th>Topic</th><th>Time</th><th>Actions</th></tr>
          <?php foreach($grp as $l): ?>
            <tr>
              <td><?= esc_html($l->teacher_name) ?></td>
              <td><?= date('M j, Y',strtotime($l->lecture_book_date)) ?></td>
              <td><?= esc_html($l->SubjectName) ?></td>
              <td><?= esc_html($l->topic) ?></td>
              <td><?= date('g:i A',strtotime($l->lecture_time)) ?></td>
              <td>
                <button class="reschedule-btn" data-id="<?= esc_attr($l->lecture_book_id) ?>">Reschedule</button>
                <button class="cancel-btn"     data-id="<?= esc_attr($l->lecture_book_id) ?>">Cancel</button>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>
    <?php endforeach; else: ?>
      <p>No upcoming lessons by any teacher.</p>
    <?php endif; ?>
  </div>
</div>

<!-- Hours Studied Modal -->
<div id="hoursModal" class="tp-modal" style="display:none">
  <div class="tp-modal-header">
    <h2>Hours Studied</h2>
    <button class="close-btn" data-target="hoursModal">×</button>
  </div>
  <div class="tp-modal-content">
    <?php
      $cut = strtotime('-90 days');
      $found = false;
      foreach($studied_lectures as $l):
        if(strtotime($l->lecture_book_date) < $cut) continue;
        $found = true;
    ?>
      <button class="tp-accordion-header" data-target="hr-<?= esc_attr($l->lecture_book_id) ?>">
        + <?= date('M j, Y',strtotime($l->lecture_book_date)) ?>
      </button>
      <div id="hr-<?= esc_attr($l->lecture_book_id) ?>" class="tp-accordion-content" style="display:none">
        <table>
          <tr><th>Date</th><th>Subject</th><th>Topic</th><th>Time</th><th>Teacher</th><th>Action</th></tr>
          <tr>
            <td><?= date('M j, Y',strtotime($l->lecture_book_date)) ?></td>
            <td><?= esc_html($l->SubjectName) ?></td>
            <td><?= esc_html($l->topic) ?></td>
            <td><?= date('g:i A',strtotime($l->lecture_time)) ?></td>
            <td><?= esc_html($l->teacher_name) ?></td>
            <td>
              <button class="retake-btn" data-id="<?= esc_attr($l->lecture_book_id) ?>">Retake</button>
            </td>
          </tr>
        </table>
      </div>
    <?php endforeach;
      if(!$found): ?>
      <p>No lectures available for retake (last 90 days only).</p>
    <?php endif; ?>
  </div>
</div>

<!-- ==== Styles & Script ==== -->
<style>
.student-badge-row { display:flex; gap:15px; flex-wrap:wrap; justify-content:center; margin-bottom:20px; }
.student-badge { flex:1 1 200px; background:#e6fff6; padding:20px; border-radius:12px; text-align:center; cursor:pointer; font-weight:bold; box-shadow:0 2px 6px rgba(0,0,0,0.1); }
.tp-today-schedule { margin-bottom:30px; }
.tp-toggle-btn { background:none; border:none; font-size:1.2em; cursor:pointer; }
.tp-schedule-list { list-style:none; padding:0; }
.tp-schedule-list li { background:#f8f9fa; margin:8px 0; padding:10px; border-radius:6px; display:flex; gap:10px; align-items:center; }
.tp-modal { position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); background:#fff; padding:20px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.2); display:none; width:90%; max-width:600px; max-height:80%; overflow:auto; z-index:10000; }
.tp-modal-header { display:flex; justify-content:space-between; align-items:center; }
.tp-modal-content table { width:100%; border-collapse:collapse; }
.tp-modal-content th, .tp-modal-content td { border:1px solid #ddd; padding:8px; text-align:left; }
.tp-accordion-header { width:100%; text-align:left; background:none; border:none; font-size:1em; padding:10px; cursor:pointer; }
.tp-accordion-content { display:none; padding:10px 0; }
.close-btn { background:none; border:none; font-size:1.5em; cursor:pointer; }
.tp-modal { z-index:10000 !important; }  /* ensure on top */
</style>

<script>
(function(){
  var badgeMap = {
    openUpcoming: 'upcomingModal',
    openTeachers:  'teachersModal',
    openHours:     'hoursModal'
  };

  // Poll until everything is in the DOM, then bind once
  function tryBind(){
    var ready = true;
    Object.entries(badgeMap).forEach(function([bId,mId]){
      var b = document.getElementById(bId), m = document.getElementById(mId);
      if(!b||!m) { ready=false; return; }
      if(!b.hasAttribute('data-bound')){
        b.setAttribute('data-bound','1');
        b.addEventListener('click',function(){ m.style.display='block'; });
      }
    });
    document.querySelectorAll('.close-btn').forEach(function(btn){
      var tgt = btn.getAttribute('data-target'), m = document.getElementById(tgt);
      if(m && !btn.hasAttribute('data-bound')){
        btn.setAttribute('data-bound','1');
        btn.addEventListener('click',function(){ m.style.display='none'; });
      }
    });
    return ready;
  }

  var iv = setInterval(function(){
    if(tryBind()){
      clearInterval(iv);
      console.log('✅ Student Dashboard modals bound');
    }
  }, 200);
  setTimeout(function(){ clearInterval(iv); console.warn('⚠️ Modal bind timeout'); }, 10000);
})();
</script>

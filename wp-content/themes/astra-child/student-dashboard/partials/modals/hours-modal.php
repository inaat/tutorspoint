<?php
// File: student-dashboard/partials/modals/hours-modal.php
?>
<div id="hoursModal" class="tp-modal" style="display:none">
  <div class="tp-modal-header">
    <h2>Hours Studied</h2>
    <button type="button" class="close-btn" data-target="hoursModal">×</button>
  </div>
  <div class="tp-modal-content">
    <?php
      $cutoff = strtotime('-90 days');
      $has = false;
    ?>
    <?php foreach ($studied_lectures as $lesson): ?>
      <?php if (strtotime($lesson->lecture_book_date) < $cutoff) continue; ?>
      <?php $has = true; ?>
      <button class="tp-accordion-header" data-target="hr-<?= esc_attr($lesson->lecture_book_id) ?>">
        + <?= date('M j, Y', strtotime($lesson->lecture_book_date)) ?>
      </button>
      <div id="hr-<?= esc_attr($lesson->lecture_book_id) ?>" class="tp-accordion-content" style="display:none">
        <table>
          <tr><th>Date</th><th>Subject</th><th>Topic</th><th>Time</th><th>Teacher</th><th>Action</th></tr>
          <tr>
            <td><?= date('M j, Y', strtotime($lesson->lecture_book_date)) ?></td>
            <td><?= esc_html($lesson->SubjectName) ?></td>
            <td><?= esc_html($lesson->topic) ?></td>
            <td><?= date('g:i A', strtotime($lesson->lecture_time)) ?></td>
            <td><?= esc_html($lesson->teacher_name) ?></td>
            <td>
              <button class="retake-btn" data-id="<?= esc_attr($lesson->lecture_book_id) ?>">Retake</button>
            </td>
          </tr>
        </table>
      </div>
    <?php endforeach; ?>
    <?php if (! $has): ?>
      <p>No studied sessions available for retake (last 90 days only).</p>
    <?php endif; ?>
  </div>
</div>

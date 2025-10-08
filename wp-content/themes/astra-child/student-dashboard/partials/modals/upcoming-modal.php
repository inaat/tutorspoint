<?php
// File: student-dashboard/partials/modals/upcoming-modal.php
?>
<div id="upcomingModal" class="tp-modal" style="display:none">
  <div class="tp-modal-header">
    <h2>Upcoming Lessons</h2>
    <button type="button" class="close-btn" data-target="upcomingModal">×</button>
  </div>
  <div class="tp-modal-content">
    <?php if (!empty($upcoming_lessons)): ?>
      <?php foreach ($upcoming_lessons as $lesson): ?>
        <button class="tp-accordion-header" data-target="up-<?= esc_attr($lesson->lecture_book_id) ?>">
          + <?= date('M j, Y', strtotime($lesson->lecture_book_date)) ?>
        </button>
        <div id="up-<?= esc_attr($lesson->lecture_book_id) ?>" class="tp-accordion-content" style="display:none">
          <table>
            <tr><th>Date</th><th>Subject</th><th>Topic</th><th>Time</th><th>Teacher</th><th>Actions</th></tr>
            <tr>
              <td><?= date('M j, Y', strtotime($lesson->lecture_book_date)) ?></td>
              <td><?= esc_html($lesson->SubjectName) ?></td>
              <td><?= esc_html($lesson->topic) ?></td>
              <td><?= date('g:i A', strtotime($lesson->lecture_time)) ?></td>
              <td><?= esc_html($lesson->teacher_name) ?></td>
              <td>
                <button class="reschedule-btn" data-id="<?= esc_attr($lesson->lecture_book_id) ?>">Reschedule</button>
                <button class="cancel-btn"     data-id="<?= esc_attr($lesson->lecture_book_id) ?>">Cancel</button>
              </td>
            </tr>
          </table>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p>No upcoming lessons found.</p>
    <?php endif; ?>
  </div>
</div>

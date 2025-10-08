<?php
// File: student-dashboard/partials/modals/teachers-modal.php
?>
<div id="teachersModal" class="tp-modal" style="display:none">
  <div class="tp-modal-header">
    <h2>Total Teachers</h2>
    <button type="button" class="close-btn" data-target="teachersModal">×</button>
  </div>
  <div class="tp-modal-content">
    <?php if (!empty($teacher_groups)): ?>
      <?php foreach ($teacher_groups as $tid => $group): ?>
        <button class="tp-accordion-header" data-target="tg-<?= esc_attr($tid) ?>">
          + <?= esc_html($group[0]->teacher_name) ?> (<?= count($group) ?>)
        </button>
        <div id="tg-<?= esc_attr($tid) ?>" class="tp-accordion-content" style="display:none">
          <table>
            <tr><th>Teacher</th><th>Date</th><th>Subject</th><th>Topic</th><th>Time</th><th>Actions</th></tr>
            <?php foreach ($group as $lesson): ?>
              <tr>
                <td><?= esc_html($lesson->teacher_name) ?></td>
                <td><?= date('M j, Y', strtotime($lesson->lecture_book_date)) ?></td>
                <td><?= esc_html($lesson->SubjectName) ?></td>
                <td><?= esc_html($lesson->topic) ?></td>
                <td><?= date('g:i A', strtotime($lesson->lecture_time)) ?></td>
                <td>
                  <button class="reschedule-btn" data-id="<?= esc_attr($lesson->lecture_book_id) ?>">Reschedule</button>
                  <button class="cancel-btn"     data-id="<?= esc_attr($lesson->lecture_book_id) ?>">Cancel</button>
                </td>
              </tr>
            <?php endforeach; ?>
          </table>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p>No teachers with upcoming lessons.</p>
    <?php endif; ?>
  </div>
</div>

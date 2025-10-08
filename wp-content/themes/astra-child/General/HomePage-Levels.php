<?php
// astra-child/General/HomePage-Levels.php

wp_enqueue_script('jquery');

// helper: make a per-render nonce + instance id
function tp_levels_subjects_nonce() {
  return wp_create_nonce('tp_ls_nonce');
}
function tp_rand_id() {
  return wp_generate_uuid4();
}

/**
 * [levels_dropdown]
 */
add_shortcode('levels_dropdown', function () {
  $nonce = esc_js(tp_levels_subjects_nonce());
  $inst  = esc_attr(tp_rand_id()); // unique suffix to avoid ID conflicts
  ob_start(); ?>
  <select id="tp_levelDropdown_<?php echo $inst; ?>" class="tp-level-dropdown" aria-label="Select study level">
    <option value="">Select Level</option>
  </select>

  <script>
  jQuery(function ($) {
    const $level = $('#tp_levelDropdown_<?php echo $inst; ?>');
    $level.prop('disabled', true);

    $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
      action: 'tp_load_levels',
      _ajax_nonce: '<?php echo $nonce; ?>'
    })
    .done(function (res) {
      // support either HTML <option> or JSON [{id,name}]
      if (Array.isArray(res)) {
        res.forEach(r => $level.append($('<option/>',{value:r.id,text:r.level_name})));
      } else {
        $level.append(res); // your existing HTML response
      }
      $level.prop('disabled', false);
      // persist last choice
      const saved = sessionStorage.getItem('tp_selected_level');
      if (saved) $level.val(saved).trigger('change');
    })
    .fail(() => { $level.html('<option value="">Failed to load levels</option>'); });

    $level.on('change', function () {
      sessionStorage.setItem('tp_selected_level', $(this).val());
      $(document).trigger('tp:level-changed', [$(this).val(), '<?php echo $inst; ?>']);
    });
  });
  </script>
  <?php
  return ob_get_clean();
});

/**
 * [subjects_dropdown]
 * Dependent on selected Level; listens to the custom event from any levels dropdown.
 */
add_shortcode('subjects_dropdown', function () {
  $nonce = esc_js(tp_levels_subjects_nonce());
  $inst  = esc_attr(tp_rand_id());
  ob_start(); ?>
  <select id="tp_subjectDropdown_<?php echo $inst; ?>" class="tp-subject-dropdown" aria-label="Select subject" disabled>
    <option value="">Select Subject</option>
  </select>

  <script>
  jQuery(function ($) {
    const $subj = $('#tp_subjectDropdown_<?php echo $inst; ?>');

    function loadSubjects(levelId) {
      if (!levelId) { $subj.prop('disabled', true).html('<option value="">Select Subject</option>'); return; }
      $subj.prop('disabled', true).html('<option value="">Loading…</option>');
      $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
        action: 'tp_load_subjects_by_level',
        level: levelId,
        _ajax_nonce: '<?php echo $nonce; ?>'
      })
      .done(function (res) {
        let optionsHtml = '<option value="">Select Subject</option>';
        if (Array.isArray(res)) {
          res.forEach(r => { optionsHtml += `<option value="${r.subject_id}">${r.SubjectName}</option>`; });
        } else {
          optionsHtml += res; // your existing HTML response
        }
        $subj.html(optionsHtml).prop('disabled', false);
      })
      .fail(() => { $subj.html('<option value="">Failed to load</option>'); });
    }

    // initial load if session has a saved level
    const savedLevel = sessionStorage.getItem('tp_selected_level');
    if (savedLevel) loadSubjects(savedLevel);

    // react when any levels dropdown fires the custom event
    $(document).on('tp:level-changed', function (_e, levelId) { loadSubjects(levelId); });
  });
  </script>
  <?php
  return ob_get_clean();
});

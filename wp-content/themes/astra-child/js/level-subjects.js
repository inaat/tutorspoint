/* global ajaxObj, jQuery */
(function ($) {
  'use strict';

  function loadLevels() {
    var $level = $('#level-select');
    if (!$level.length) return;

    $level.html('<option value="">Loading...</option>');
    $.post(ajaxObj.ajaxUrl, { action: 'tp_load_levels' }, function (html) {
      // server returns <option> HTML
      $level.html('<option value="">Select Level</option>' + (html || ''));
    }).fail(function () {
      $level.html('<option value="">Unable to load levels</option>');
    });
  }

  function bindLevelChange() {
    $(document).on('change', '#level-select', function () {
      var lvl = $(this).val();
      var $sub = $('#subject-select');

      if (!$sub.length) return;

      if (!lvl) {
        $sub.prop('disabled', true)
            .html('<option value="">Select Subject</option>');
        return;
      }

      $sub.prop('disabled', true)
          .html('<option value="">Loading...</option>');

      $.post(ajaxObj.ajaxUrl, {
        action: 'tp_load_subjects_by_level',
        level: lvl
      }, function (html) {
        $sub.prop('disabled', false)
            .html('<option value="">Select Subject</option>' + (html || ''));
      }).fail(function () {
        $sub.prop('disabled', true)
            .html('<option value="">Unable to load subjects</option>');
      });
    });
  }

  $(function () {
    // sanity log so you can confirm the correct file is the one loading
    if (window.console && console.log) console.log('[tp] level-subjects.js (ES5) loaded');
    loadLevels();
    bindLevelChange();
  });
})(jQuery);

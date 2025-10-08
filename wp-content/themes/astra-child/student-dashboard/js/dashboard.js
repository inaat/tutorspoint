/*
jQuery(function($){
  // Require the localized object
  if (typeof TPStudentDashboard === 'undefined') {
    console.warn('TPStudentDashboard missing, AJAX will fail.');
    return;
  }

  // Generic AJAX helper
  function ajaxCall(action, extraData, onSuccess, onError) {
    const payload = $.extend({
      action: action,
      nonce: TPStudentDashboard.nonce
    }, extraData || {});
    $.post(TPStudentDashboard.ajax_url, payload, function(res){
      if (res.success) {
        onSuccess && onSuccess(res.data);
      } else {
        console.warn('AJAX error response for', action, res);
        onError && onError(res);
      }
    }, 'json').fail(function(jqXHR){
      console.error('AJAX request failed for', action, jqXHR.responseText);
      onError && onError(jqXHR);
    });
  }

  // Refresh badge counts and today's schedule on load
  function refreshBadges() {
    ajaxCall('load_upcoming_lessons', null, function(data){
      $('#count-upcoming').text(data.count);
    });
    ajaxCall('load_total_teachers', null, function(data){
      $('#count-teachers').text(data.count);
    });
    ajaxCall('load_hours_studied', null, function(data){
      $('#count-hours').text(data.count);
    });
    ajaxCall('load_todays_schedule', null, function(data){
      $('#todays-schedule-container').html(buildTodaysScheduleHTML(data.lessons));
    });
  }
  refreshBadges();

  // Badge / trigger clicks
  $('#openUpcoming, #badge-upcoming-lessons').on('click', function(){
    ajaxCall('load_upcoming_lessons', null, function(data){
      $('#upcoming-content').html(buildUpcomingLessonsHTML(data.lessons));
      $('#upcomingModal').show();
    });
  });

  $('#openTeachers, #badge-total-teachers').on('click', function(){
    ajaxCall('load_total_teachers', null, function(data){
      $('#teachers-content').html(buildTotalTeachersHTML(data.lessons));
      $('#teachersModal').show();
    });
  });

  $('#openHours, #badge-hours-studied').on('click', function(){
    ajaxCall('load_hours_studied', null, function(data){
      $('#hours-content').html(buildHoursStudiedHTML(data.lessons));
      $('#hoursModal').show();
    });
  });

  // Toggle expansion
  $(document).on('click', '.toggle-header', function(){
    const $body = $(this).next('.toggle-body');
    $body.slideToggle(150);
    const $icon = $(this).find('.toggle-icon');
    $icon.text($body.is(':visible') ? '−' : '+');
  });

  // Builders
  function buildUpcomingLessonsHTML(lessons) {
    if (!lessons || !lessons.length) return '<div>No upcoming lessons.</div>';
    const grouped = {};
    lessons.forEach(l => {
      grouped[l.lecture_book_date] = grouped[l.lecture_book_date] || [];
      grouped[l.lecture_book_date].push(l);
    });
    let out = '';
    Object.entries(grouped).forEach(([date, list]) => {
      out += `<div class="toggle-group">
        <div class="toggle-header"><span class="toggle-icon">+</span> <strong>${date}</strong></div>
        <div class="toggle-body" style="display:none;">
          <table class="tp-table">
            <thead><tr>
              <th>Subject</th><th>Topic</th><th>Time</th><th>Teacher</th><th>Actions</th>
            </tr></thead>
            <tbody>`;
      list.forEach(l => {
        out += `<tr data-lecture="${l.lecture_book_id}">
          <td>${l.subject_name || '-'}</td>
          <td>${l.topic || '-'}</td>
          <td>${l.lecture_time || '-'}</td>
          <td>${l.teacher_name || '-'}</td>
          <td>
            <button class="reschedule-btn" data-lecture="${l.lecture_book_id}" data-teacher="${l.teacher_id}">Reschedule</button>
            <button class="cancel-btn" data-lecture="${l.lecture_book_id}">Cancel</button>
          </td>
        </tr>`;
      });
      out += `</tbody></table></div></div>`;
    });
    return out;
  }

  function buildTotalTeachersHTML(lessons) {
    if (!lessons || !lessons.length) return '<div>No upcoming lessons.</div>';
    const grouped = {};
    lessons.forEach(l => {
      grouped[l.teacher_name] = grouped[l.teacher_name] || [];
      grouped[l.teacher_name].push(l);
    });
    let out = '';
    Object.entries(grouped).forEach(([teacher, list]) => {
      out += `<div class="toggle-group">
        <div class="toggle-header"><span class="toggle-icon">+</span> <strong>${teacher}</strong></div>
        <div class="toggle-body" style="display:none;">
          <table class="tp-table">
            <thead><tr>
              <th>Date</th><th>Subject</th><th>Topic</th><th>Time</th><th>Actions</th>
            </tr></thead>
            <tbody>`;
      list.forEach(l => {
        out += `<tr data-lecture="${l.lecture_book_id}">
          <td>${l.lecture_book_date}</td>
          <td>${l.subject_name || '-'}</td>
          <td>${l.topic || '-'}</td>
          <td>${l.lecture_time || '-'}</td>
          <td>
            <button class="reschedule-btn" data-lecture="${l.lecture_book_id}" data-teacher="${l.teacher_id}">Reschedule</button>
            <button class="cancel-btn" data-lecture="${l.lecture_book_id}">Cancel</button>
          </td>
        </tr>`;
      });
      out += `</tbody></table></div></div>`;
    });
    return out;
  }

  function buildHoursStudiedHTML(lessons) {
    if (!lessons || !lessons.length) return '<div>No studied lectures in last 90 days.</div>';
    let out = `<table class="tp-table">
      <thead><tr>
        <th>Date</th><th>Subject</th><th>Topic</th><th>Time</th><th>Teacher</th><th>Retake</th>
      </tr></thead><tbody>`;
    lessons.forEach(l => {
      out += `<tr data-lecture="${l.lecture_book_id}">
        <td>${l.lecture_book_date}</td>
        <td>${l.subject_name || '-'}</td>
        <td>${l.topic || '-'}</td>
        <td>${l.lecture_time || '-'}</td>
        <td>${l.teacher_name || '-'}</td>
        <td><button class="retake-btn" data-lecture="${l.lecture_book_id}">Retake</button></td>
      </tr>`;
    });
    out += `</tbody></table>`;
    return out;
  }

  function buildTodaysScheduleHTML(lessons) {
    if (!lessons || !lessons.length) return '<div>No lessons today.</div>';
    let out = `<div class="toggle-group">
      <div class="toggle-header"><span class="toggle-icon">+</span> <strong>Today’s Schedule</strong></div>
      <div class="toggle-body" style="display:none;">
        <table class="tp-table">
          <thead><tr>
            <th>Subject</th><th>Topic</th><th>Time</th><th>Teacher</th><th>Actions</th>
          </tr></thead>
          <tbody>`;
    lessons.forEach(l => {
      out += `<tr data-lecture="${l.lecture_book_id}">
        <td>${l.subject_name || '-'}</td>
        <td>${l.topic || '-'}</td>
        <td>${l.lecture_time || '-'}</td>
        <td>${l.teacher_name || '-'}</td>
        <td>
          <button class="reschedule-btn" data-lecture="${l.lecture_book_id}" data-teacher="${l.teacher_id}">Reschedule</button>
          <button class="cancel-btn" data-lecture="${l.lecture_book_id}">Cancel</button>
        </td>
      </tr>`;
    });
    out += `</tbody></table></div></div>`;
    return out;
  }
});
*/
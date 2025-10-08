<?php
// 📁 File: student-dashboard/partials/booked-tab.php
$current_user = wp_get_current_user();
global $wpdb;

// Get teacher_id based on login email
$teacher = $wpdb->get_row($wpdb->prepare("SELECT * FROM wpC_teachers_main WHERE Email = %s", $current_user->user_email));
$teacher_id = $teacher ? (int)$teacher->teacher_id : 0;
?>

<h3>📘 Booked Sessions</h3>
<div style="margin-bottom:10px;">
  <button class="btn-tab active" data-view="upcoming">Upcoming</button>
  <button class="btn-tab" data-view="taught">Taught</button>
  <button class="btn-tab" data-view="missed">Missed</button>
</div>

<input type="text" id="searchInput" onkeyup="searchTable()" placeholder="🔍 Search sessions...">
<button onclick="exportTableToExcel('sessionTable')">📥 Export Excel</button>
<div id="session-content"><p>Loading...</p></div>

<style>
.btn-tab {
  display: inline-block;
  background: #ddd;
  padding: 6px 12px;
  margin-right: 5px;
  text-decoration: none;
  border-radius: 4px;
  color: #000;
  cursor: pointer;
}
.btn-tab.active {
  background: #0ABAB5;
  color: white;
}
#searchInput {
  padding: 6px;
  width: 40%;
  margin-bottom: 10px;
}
.booked-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
}
.booked-table th, .booked-table td {
  padding: 10px;
  border: 1px solid #ccc;
  text-align: center;
}
.taught-btn {
  background: #0ABAB5;
  color: white;
  border: none;
  padding: 6px 10px;
  border-radius: 4px;
  cursor: pointer;
}
</style>

<script>
function searchTable() {
  let input = document.getElementById("searchInput").value.toLowerCase();
  let rows = document.querySelectorAll("#sessionTable tbody tr");
  rows.forEach(row => {
    row.style.display = row.innerText.toLowerCase().includes(input) ? "" : "none";
  });
}

function exportTableToExcel(tableID) {
  let table = document.getElementById(tableID);
  if (!table) return;
  let tableHTML = table.outerHTML.replace(/ /g, '%20');
  let filename = 'booked_sessions_' + new Date().toISOString().slice(0, 10) + '.xls';
  let downloadLink = document.createElement("a");
  document.body.appendChild(downloadLink);
  downloadLink.href = 'data:application/vnd.ms-excel,' + tableHTML;
  downloadLink.download = filename;
  downloadLink.click();
  document.body.removeChild(downloadLink);
}

jQuery(document).ready(function($) {
  function loadSessions(view = 'upcoming') {
    $('.btn-tab').removeClass('active');
    $('.btn-tab[data-view="' + view + '"]').addClass('active');
    $('#session-content').html('<p>Loading...</p>');
    $.post(ajaxurl, {
      action: 'load_teacher_sessions',
      view: view
    }, function(response) {
      if (!response || response.trim() === '0') {
        $('#session-content').html('<p>No sessions found.</p>');
      } else {
        $('#session-content').html(response);
      }
    });
  }

  $('.btn-tab').click(function() {
    const view = $(this).data('view');
    loadSessions(view);
  });

  loadSessions('upcoming'); // default
});
</script>

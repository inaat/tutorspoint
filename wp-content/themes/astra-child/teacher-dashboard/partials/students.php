<?php
// Students tab PARTIAL (include from teacher-dashboard.php)
// Safe & scoped. Renders real data on first load and supports AJAX refresh.
if (!defined('ABSPATH')) exit;
if (!is_user_logged_in()) { echo '<p>Please log in.</p>'; return; }

global $wpdb, $current_user;
$current_user = wp_get_current_user();

/** Resolve teacher_id from email */
$teacher_id = (int)$wpdb->get_var($wpdb->prepare(
  "SELECT teacher_id FROM wpC_teachers_main WHERE Email=%s LIMIT 1",
  $current_user->user_email
));

/** Server-side initial load (real rows) */
$rows = [];
if ($teacher_id) {
  $sql = "
    SELECT
      sr.student_id,
      COALESCE(sr.full_name, sr.student_name, CONCAT('Student #', sr.student_id)) AS student_name,
      sr.email,
      slv.level AS level,
      MIN(COALESCE(l.lecture_book_date, l.lecture_date)) AS first_booked,
      MAX(COALESCE(l.lecture_book_date, l.lecture_date)) AS last_booked
    FROM wpC_student_lectures AS l
    JOIN wpC_student_register  AS sr ON sr.student_id = l.student_id
    LEFT JOIN wpC_subjects_level AS slv
      ON slv.subject_level_id = COALESCE(sr.subject_level_id, l.subject_level_id)
    WHERE l.teacher_id = %d
    GROUP BY sr.student_id, sr.full_name, sr.student_name, sr.email, slv.level
    ORDER BY student_name ASC
  ";
  $rows = $wpdb->get_results( $wpdb->prepare($sql, $teacher_id) );
}

/** Nonce + AJAX URL for client-side refresh / schedules modal */
$nonce = wp_create_nonce('tp_teacher_students');
$ajax  = admin_url('admin-ajax.php');
?>
<div class="tps-wrap" data-ajax="<?php echo esc_url($ajax); ?>" data-nonce="<?php echo esc_attr($nonce); ?>">
  <header class="tps-head">
    <h3>Students (by your allocated levels)</h3>
    <div class="tps-actions">
      <button class="tps-btn" id="tps-refresh">Refresh</button>
      <button class="tps-btn tps-primary" id="tps-open-schedules" disabled>Schedules</button>
    </div>
  </header>

  <div class="tps-table-wrap">
    <table class="tps-table" id="tps-students">
      <thead>
        <tr>
          <th style="width:36px;"></th>
          <th>Student</th>
          <th>Email</th>
          <th>Level</th>
          <th>First booked / Last booked</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($rows): ?>
          <?php foreach ($rows as $r): ?>
            <tr data-id="<?php echo (int)$r->student_id; ?>">
              <td><input type="radio" name="tps-pick"></td>
              <td><?php echo esc_html($r->student_name); ?></td>
              <td><?php echo esc_html($r->email); ?></td>
              <td><?php echo esc_html($r->level ?: '—'); ?></td>
              <td>
                <?php
                  $first = $r->first_booked ? date_i18n('Y-m-d', strtotime($r->first_booked)) : '—';
                  $last  = $r->last_booked  ? date_i18n('Y-m-d', strtotime($r->last_booked))  : '—';
                  echo esc_html("$first / $last");
                ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="5" class="tps-empty">No students found for your classes.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Schedules Modal -->
<div class="tps-modal" id="tps-modal" aria-hidden="true" role="dialog" aria-modal="true">
  <div class="tps-overlay"></div>
  <section class="tps-dialog">
    <header class="tps-dialog-head">
      <h4>Schedules</h4>
      <button class="tps-close" aria-label="Close">&times;</button>
    </header>
    <div class="tps-dialog-body">
      <div id="tps-slots">Loading…</div>
    </div>
  </section>
</div>

<style>
  .tps-wrap{display:grid;gap:12px}
  .tps-head{display:flex;align-items:center;justify-content:space-between;gap:10px}
  .tps-actions{display:flex;gap:8px}
  .tps-btn{border:1px solid #d7e0ea;background:#fff;border-radius:10px;padding:8px 12px;cursor:pointer}
  .tps-btn[disabled]{opacity:.6;cursor:not-allowed}
  .tps-primary{background:#0ABAB5;color:#fff;border-color:#0ABAB5}
  .tps-table-wrap{overflow:auto;border:1px solid #edf2f7;border-radius:12px;background:#fff}
  .tps-table{width:100%;border-collapse:collapse}
  .tps-table th,.tps-table td{padding:10px;border-bottom:1px solid #edf2f7;font-size:14px}
  .tps-empty{text-align:center;color:#64748b}
  .tps-modal{position:fixed;inset:0;display:none;z-index:1000}
  .tps-modal.open{display:block}
  .tps-overlay{position:absolute;inset:0;background:rgba(0,0,0,.45)}
  .tps-dialog{position:relative;margin:5vh auto;width:min(780px,92vw);background:#fff;border-radius:12px;overflow:hidden}
  .tps-dialog-head{display:flex;align-items:center;justify-content:space-between;padding:10px 12px;border-bottom:1px solid #edf2f7}
  .tps-close{border:0;background:transparent;font-size:20px;cursor:pointer;line-height:1}
  .tps-dialog-body{max-height:70vh;overflow:auto;padding:12px}
  .tps-slot{display:flex;align-items:center;justify-content:space-between;border:1px solid #edf2f7;border-radius:10px;padding:10px;margin:8px 0}
  .tps-pill{font-size:12px;padding:6px 10px;border-radius:999px;background:#e8fbef;color:#15803d}
  .tps-ghost{border:1px solid #cfd8e3;background:#fff;border-radius:10px;padding:6px 10px;cursor:pointer}
  .tps-engage{background:#0ABAB5;color:#fff;border:0;border-radius:10px;padding:6px 10px;cursor:pointer}
  @media(max-width:560px){.tps-table th:nth-child(5),.tps-table td:nth-child(5){display:none}}
</style>

<script>
(function(){
  const wrap   = document.querySelector('.tps-wrap');
  if (!wrap) return;
  const AJAX   = wrap.dataset.ajax;
  const NONCE  = wrap.dataset.nonce;

  const tbody  = document.querySelector('#tps-students tbody');
  const btnRef = document.getElementById('tps-refresh');
  const btnSch = document.getElementById('tps-open-schedules');

  const modal  = document.getElementById('tps-modal');
  const slotsC = document.getElementById('tps-slots');
  const closeB = modal.querySelector('.tps-close');
  const overlay= modal.querySelector('.tps-overlay');

  let selectedStudentId = null;

  function attachRowPickHandlers(){
    tbody.querySelectorAll('tr[data-id]').forEach(tr => {
      tr.addEventListener('click', () => {
        selectedStudentId = tr.dataset.id || null;
        btnSch.disabled = !selectedStudentId;
        const radio = tr.querySelector('input[type="radio"]');
        if (radio) radio.checked = true;
      });
    });
  }
  attachRowPickHandlers(); // for server-side rows

  async function loadStudents(){
    const prev = tbody.innerHTML;
    tbody.innerHTML = '<tr><td colspan="5" class="tps-empty">Loading…</td></tr>';

    try{
      const fd = new FormData();
      fd.append('action', 'tp_teacher_students_list');
      fd.append('nonce',  NONCE);

      const res = await fetch(AJAX, { method:'POST', credentials:'same-origin', body: fd });
      const json = await res.json();

      if (!json?.success) throw new Error(json?.data?.message || 'Failed');

      if (!json.data || !json.data.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="tps-empty">No students found for your classes.</td></tr>';
      } else {
        tbody.innerHTML = json.data.map(s => `
          <tr data-id="${s.id}">
            <td><input type="radio" name="tps-pick"></td>
            <td>${s.name}</td>
            <td>${s.email}</td>
            <td>${s.level || '—'}</td>
            <td>${(s.first || '—')} / ${(s.last || '—')}</td>
          </tr>
        `).join('');
      }
      attachRowPickHandlers();
    } catch(e){
      console.error(e);
      tbody.innerHTML = prev; // fallback to previous render
      alert('Could not refresh students. Please try again.');
    }
  }

  async function openModal(){
    if (!selectedStudentId) return;
    modal.classList.add('open');
    slotsC.textContent = 'Loading…';

    try {
      const fd = new FormData();
      fd.append('action', 'tp_teacher_slots_for_student');
      fd.append('nonce',  NONCE);
      fd.append('student_id', selectedStudentId);

      const res = await fetch(AJAX, { method:'POST', credentials:'same-origin', body: fd });
      const json = await res.json();

      if (!json?.success || !json.data) throw new Error('Failed');

      if (!json.data.length) {
        slotsC.innerHTML = '<div class="tps-empty">No available slots right now.</div>';
      } else {
        slotsC.innerHTML = json.data.map(x => `
          <div class="tps-slot" data-slot="${x.slot_id}">
            <div>${x.label} ${x.status === 'available' ? '<span class="tps-pill">Available</span>' : ''}</div>
            <button class="tps-engage">Engage</button>
          </div>
        `).join('');
        attachEngageHandlers();
      }
    } catch(e) {
      console.error(e);
      slotsC.innerHTML = '<div class="tps-empty">Could not load slots.</div>';
    }
  }

  function closeModal(){ modal.classList.remove('open'); }

  function attachEngageHandlers(){
    slotsC.querySelectorAll('.tps-engage').forEach(btn => {
      btn.addEventListener('click', async () => {
        const wrap = btn.closest('.tps-slot');
        const sid  = wrap?.dataset.slot;
        if (!sid || !selectedStudentId) return;

        btn.disabled = true; btn.textContent = 'Engaging…';
        try{
          const fd = new FormData();
          fd.append('action',     'tp_teacher_engage');
          fd.append('nonce',      NONCE);
          fd.append('slot_id',    sid);
          fd.append('student_id', selectedStudentId);
          const rr = await fetch(AJAX, { method:'POST', credentials:'same-origin', body: fd });
          const jj = await rr.json().catch(()=>null);

          if (jj?.success) {
            btn.textContent = 'Engaged ✓';
            setTimeout(closeModal, 600);
          } else {
            throw new Error(jj?.data?.message || 'Failed to engage.');
          }
        }catch(e){
          alert(e.message || 'Network error.');
          btn.disabled = false; btn.textContent = 'Engage';
        }
      });
    });
  }

  // bindings
  btnRef.addEventListener('click', loadStudents);
  btnSch.addEventListener('click', openModal);
  closeB.addEventListener('click', closeModal);
  overlay.addEventListener('click', closeModal);
})();
</script>

// File: astra-child/teacher-dashboard/General/teacher-students-tab.php
// Shortcode: [tp_teacher_students]

add_shortcode('tp_teacher_students', function () {
  if (!is_user_logged_in()) return '<p>Please log in.</p>';

  // Nonce for AJAX
  $nonce = wp_create_nonce('tp_teacher_students');
  $ajax  = admin_url('admin-ajax.php');

  ob_start(); ?>
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
          <tr><td colspan="5" class="tps-empty">Loading…</td></tr>
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
    .tps-table-wrap{overflow:auto;border:1px solid #edf2f7;border-radius:12px}
    .tps-table{width:100%;border-collapse:collapse}
    .tps-table th,.tps-table td{padding:10px;border-bottom:1px solid #edf2f7;font-size:14px}
    .tps-empty{text-align:center;color:#64748b}
    /* Modal */
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
    const root  = document.querySelector('.tps-wrap');
    const ajax  = root?.dataset.ajax;
    const nonce = root?.dataset.nonce;

    const tblBody = document.querySelector('#tps-students tbody');
    const refresh = document.getElementById('tps-refresh');
    const openBtn = document.getElementById('tps-open-schedules');

    let selectedStudent = null;

    async function loadStudents(){
      tblBody.innerHTML = '<tr><td colspan="5" class="tps-empty">Loading…</td></tr>';
      const fd = new FormData();
      fd.append('action','tp_teacher_students_list');
      fd.append('nonce', nonce);
      const r  = await fetch(ajax,{method:'POST',credentials:'same-origin',body:fd});
      const j  = await r.json().catch(()=>null);
      if(!j?.success || !Array.isArray(j.data)){ 
        tblBody.innerHTML = '<tr><td colspan="5" class="tps-empty">No students found.</td></tr>'; 
        return;
      }
      const rows = j.data.map(s => `
        <tr>
          <td><input type="radio" name="pick" value="${s.student_id}" data-name="${s.full_name}" data-email="${s.email}" data-level="${s.level_name||''}"></td>
          <td>${s.full_name||'—'}</td>
          <td>${s.email||'—'}</td>
          <td>${s.level_name||'—'}</td>
          <td>${s.first_last||'—'}</td>
        </tr>`).join('');
      tblBody.innerHTML = rows || '<tr><td colspan="5" class="tps-empty">No students match your allocations.</td></tr>';
      tblBody.querySelectorAll('input[type="radio"]').forEach(r=>{
        r.addEventListener('change',()=>{
          selectedStudent = {
            student_id: r.value,
            name: r.dataset.name,
            email: r.dataset.email,
            level: r.dataset.level
          };
          openBtn.disabled = false;
        });
      });
      openBtn.disabled = !selectedStudent;
    }

    refresh?.addEventListener('click', loadStudents);
    loadStudents();

    // Modal
    const modal  = document.getElementById('tps-modal');
    const overlay= modal.querySelector('.tps-overlay');
    const close  = modal.querySelector('.tps-close');
    const slotsC = document.getElementById('tps-slots');

    function openModal(){ modal.classList.add('open'); }
    function closeModal(){ modal.classList.remove('open'); }

    overlay.addEventListener('click', closeModal);
    close.addEventListener('click', closeModal);

    openBtn.addEventListener('click', async ()=>{
      if(!selectedStudent) return;
      openModal();
      slotsC.innerHTML = 'Loading…';
      const fd = new FormData();
      fd.append('action','tp_teacher_slots');
      fd.append('nonce', nonce);
      const r  = await fetch(ajax,{method:'POST',credentials:'same-origin',body:fd});
      const j  = await r.json().catch(()=>null);
      if(!j?.success || !Array.isArray(j.data) || !j.data.length){
        slotsC.innerHTML = '<p>No available/active slots.</p>';
        return;
      }
      slotsC.innerHTML = j.data.map(x=>`
        <div class="tps-slot" data-slot="${x.slot_id}">
          <div><strong>${x.day_label}</strong> &nbsp; ${x.time_label}</div>
          <div>
            <span class="tps-pill">${x.status_label}</span>
            <button class="tps-engage">Engage</button>
          </div>
        </div>
      `).join('');

      slotsC.querySelectorAll('.tps-engage').forEach(btn=>{
        btn.addEventListener('click', async ()=>{
          const wrap = btn.closest('.tps-slot');
          const sid  = wrap?.dataset.slot;
          if(!sid) return;
          btn.disabled = true; btn.textContent='Engaging…';
          const fd2 = new FormData();
          fd2.append('action','tp_teacher_engage');
          fd2.append('nonce', nonce);
          fd2.append('slot_id', sid);
          fd2.append('student_id', selectedStudent.student_id);
          const rr = await fetch(ajax,{method:'POST',credentials:'same-origin',body:fd2});
          const jj = await rr.json().catch(()=>null);
          if(jj?.success){
            btn.textContent = 'Engaged ✓';
            setTimeout(closeModal, 600);
          }else{
            btn.disabled = false; btn.textContent='Engage';
            alert(jj?.data?.message || 'Failed to engage.');
          }
        });
      });
    });
  })();
  </script>
  <?php
  return ob_get_clean();
});

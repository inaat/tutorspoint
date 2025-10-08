<?php
/** Tab 5: Schedule – weekly view by day -> teachers -> slots */
if (!defined('ABSPATH')) exit;

$nonce = wp_create_nonce('tp_adm_sched');

/** Get current week (Sun..Sat) in PHP so JS has defaults */
$today   = current_time('Y-m-d');
$w       = (int)date_i18n('w', strtotime($today)); // 0=Sun
$weekSun = date_i18n('Y-m-d', strtotime($today . ' -'.$w.' day'));
$weekSat = date_i18n('Y-m-d', strtotime($weekSun . ' +6 day'));
?>
<div class="tp-sched-wrap" data-week-sun="<?php echo esc_attr($weekSun); ?>" data-week-sat="<?php echo esc_attr($weekSat); ?>">
  <div class="col left">
    <div class="card">
      <div class="head-row">
        <h3>Schedule</h3>
      </div>

      <div class="week-bar">
        <button class="btn btn-sm ghost wk-prev">‹ Prev</button>
        <div class="wk-range">
          <span class="wk-sun"><?php echo esc_html($weekSun); ?></span>
          <span class="sep">–</span>
          <span class="wk-sat"><?php echo esc_html($weekSat); ?></span>
        </div>
        <button class="btn btn-sm ghost wk-next">Next ›</button>
      </div>

      <ul class="day-list" role="list" aria-live="polite">
        <!-- filled by JS -->
      </ul>

      <div class="export-box">
        <button class="btn btn-sm ghost ex-day">Export Day (CSV)</button>
        <button class="btn btn-sm ghost ex-day-print">Print (PDF)</button>
        <button class="btn btn-sm ghost ex-teacher" disabled>Export Teacher (CSV)</button>
        <button class="btn btn-sm ghost ex-teacher-print" disabled>Print Teacher</button>
      </div>
    </div>
  </div>

  <div class="col right">
    <div class="card">
      <div class="head-row">
        <h3 class="right-title">Day: <span class="sel-day">—</span></h3>
      </div>
      <div class="teacher-list">
        <div class="empty">Select a day.</div>
      </div>
    </div>
  </div>
</div>

<style>
  .tp-sched-wrap{display:grid;grid-template-columns:320px 1fr;gap:16px;font-family:Roboto,Arial,sans-serif}
  @media (max-width:1000px){.tp-sched-wrap{grid-template-columns:1fr}}
  .card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:12px}
  .head-row{display:flex;align-items:center;justify-content:space-between;gap:10px}
  .btn{background:#111827;color:#fff;border:0;border-radius:10px;padding:8px 12px;cursor:pointer;font-size:13px;white-space:nowrap}
  .btn-sm{padding:6px 10px;font-size:12px;border-radius:8px}
  .ghost{background:#fff;color:#111827;border:1px solid #d1d5db}
  .ghost:hover{background:#f9fafb}

  .week-bar{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:10px}
  .wk-range{font-weight:600}
  .wk-range .sep{margin:0 6px;color:#6b7280}

  .day-list{list-style:none;margin:0;padding:0;border-top:1px solid #eee}
  .day-item{display:flex;justify-content:space-between;align-items:center;padding:10px 8px;border-bottom:1px solid #f6f7f9;cursor:pointer}
  .day-item:hover{background:#fafafa}
  .day-item.active{background:#eef2ff}
  .day-item .dm{font-weight:600}
  .day-item .date{color:#6b7280;font-size:12px}

  .teacher-list .empty{color:#6b7280}
  .teacher{border:1px solid #eef2f7;border-radius:10px;margin-bottom:8px}
  .teacher .hdr{display:flex;align-items:center;gap:8px;justify-content:space-between;padding:8px}
  .teacher .tname{display:flex;align-items:center;gap:10px}
  .tgl{width:26px;height:26px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;border:1px solid #d1d5db;background:#fff;cursor:pointer}
  .tgl.on{background:#111827;color:#fff;border-color:#111827}
  .teacher .count{font-size:12px;color:#6b7280}
  .teacher .body{display:none;border-top:1px dashed #eef2f7;padding:8px}
  .teacher.expanded .body{display:block}

  .tbl{width:100%;border-collapse:collapse;font-size:13px}
  .tbl th,.tbl td{border-bottom:1px solid #f1f5f9;padding:8px;text-align:left;white-space:nowrap}
  .badge{display:inline-flex;align-items:center;gap:6px;padding:2px 8px;border-radius:999px;font-size:12px;border:1px solid}
  .badge.ok{background:#ecfdf5;border-color:#34d399;color:#065f46}
  .badge.warn{background:#fff7ed;border-color:#fdba74;color:#9a3412}
  .badge.gray{background:#f3f4f6;border-color:#d1d5db;color:#4b5563}

  .slot-actions{display:flex;gap:8px;align-items:center}
  .slot-actions .halt{color:#b91c1c;text-decoration:underline;cursor:pointer;background:none;border:0;padding:0}
  .export-box{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
  .right-title{font-weight:700}
</style>

<script>
(function(){
  "use strict";
  const AJAX  = "<?php echo esc_url(admin_url('admin-ajax.php')); ?>";
  const NONCE = "<?php echo esc_js($nonce); ?>";

  const wrap = document.querySelector('.tp-sched-wrap');
  const wkSunEl = wrap.querySelector('.wk-sun');
  const wkSatEl = wrap.querySelector('.wk-sat');
  const dayList = wrap.querySelector('.day-list');

  const btnPrev = wrap.querySelector('.wk-prev');
  const btnNext = wrap.querySelector('.wk-next');

  const teacherList = wrap.querySelector('.teacher-list');
  const selDayEl = wrap.querySelector('.sel-day');

  const exDayBtn   = wrap.querySelector('.ex-day');
  const exDayPrBtn = wrap.querySelector('.ex-day-print');
  const exTBtn     = wrap.querySelector('.ex-teacher');
  const exTPrBtn   = wrap.querySelector('.ex-teacher-print');

  let selectedDay = null;
  let selectedTeacher = null; // {id, name}

  function fmtDate(d){ return d.toISOString().slice(0,10); }
  function parseYmd(y){ const [Y,M,D]=y.split('-').map(Number); return new Date(Y, M-1, D); }

  async function post(data){
    const fd = new FormData();
    Object.entries(data).forEach(([k,v])=>fd.append(k,v));
    const res = await fetch(AJAX, {method:'POST', body:fd});
    return res.json();
  }

  function buildWeek(sunStr){
    const sun = parseYmd(sunStr);
    wkSunEl.textContent = fmtDate(sun);
    wkSatEl.textContent = fmtDate(new Date(sun.getFullYear(), sun.getMonth(), sun.getDate()+6));

    dayList.innerHTML = '';
    const names = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    for(let i=0;i<7;i++){
      const d = new Date(sun.getFullYear(), sun.getMonth(), sun.getDate()+i);
      const li = document.createElement('li');
      li.className = 'day-item';
      li.dataset.date = fmtDate(d);
      li.innerHTML = `<div class="dm">${names[i]}</div><div class="date">${fmtDate(d)}</div>`;
      dayList.appendChild(li);
    }
    // auto-select Sunday
    selectDay(dayList.querySelector('.day-item')?.dataset.date || fmtDate(sun));
  }

  function selectDay(dateStr){
    selectedDay = dateStr;
    selDayEl.textContent = dateStr;
    dayList.querySelectorAll('.day-item').forEach(li=>{
      li.classList.toggle('active', li.dataset.date===dateStr);
    });
    selectedTeacher = null;
    exTBtn.disabled = true; exTPrBtn.disabled = true;
    loadTeachersForDay(dateStr);
  }

  async function loadTeachersForDay(dateStr){
    teacherList.innerHTML = `<div class="empty">Loading…</div>`;
    const j = await post({action:'tp_adm_sched_teachers', _ajax_nonce:NONCE, day:dateStr});
    teacherList.innerHTML = '';
    if(!j || !j.success || !j.data.items || !j.data.items.length){
      teacherList.innerHTML = `<div class="empty">No schedules for ${dateStr}.</div>`; return;
    }
    j.data.items.forEach(t=>{
      const box = document.createElement('div');
      box.className = 'teacher';
      box.dataset.tid = t.teacher_id;
      box.innerHTML = `
        <div class="hdr">
          <div class="tname">
            <button class="tgl" aria-label="Toggle">${t.expanded?'–':'+'}</button>
            <strong>${t.FullName || ('Teacher #'+t.teacher_id)}</strong>
          </div>
          <span class="count">${t.slots} ${t.slots==1?'slot':'slots'}</span>
        </div>
        <div class="body">
          <table class="tbl">
            <thead><tr>
              <th>Time</th><th>Level</th><th>Subject</th><th>Student</th><th>Status</th><th>Paid?</th><th>Actions</th>
            </tr></thead>
            <tbody class="rows"><tr><td colspan="7">Loading…</td></tr></tbody>
          </table>
        </div>`;
      teacherList.appendChild(box);
    });
  }

  async function expandTeacher(box, expand=true){
    box.classList.toggle('expanded', expand);
    const tgl = box.querySelector('.tgl'); if(tgl) tgl.textContent = expand?'–':'+';
    if(!expand) return;

    selectedTeacher = { id: parseInt(box.dataset.tid,10), name: box.querySelector('strong').textContent };
    exTBtn.disabled=false; exTPrBtn.disabled=false;

    const tb = box.querySelector('.rows');
    tb.innerHTML = `<tr><td colspan="7">Loading…</td></tr>`;
    const j = await post({action:'tp_adm_sched_slots', _ajax_nonce:NONCE, day:selectedDay, teacher_id:selectedTeacher.id});
    tb.innerHTML = '';
    const list = (j && j.success && j.data.items) ? j.data.items : [];
    if(!list.length){ tb.innerHTML = `<tr><td colspan="7">No slots.</td></tr>`; return; }
    list.forEach(s=>{
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${s.start_time || ''}–${s.end_time || ''}</td>
        <td>${s.level_name || ''}</td>
        <td>${s.SubjectName || ''}</td>
        <td>${s.student_name || ''}</td>
        <td><span class="badge ${s.status==='halted'?'warn':'gray'}">${s.status||'—'}</span></td>
        <td><span class="badge ${s.is_paid? 'ok':'gray'}">${s.is_paid?('Paid £'+Number(s.final_price||0).toFixed(2)):(s.is_free?'Free':'—')}</span></td>
        <td class="slot-actions">
          ${s.meeting_link ? `<a class="btn btn-sm ghost" href="${s.meeting_link}" target="_blank">Join</a>`:''}
          <button class="halt" data-slot="${s.slot_id}">Halt & Notify</button>
        </td>`;
      tb.appendChild(tr);
    });
  }

  // EVENTS
  dayList.addEventListener('click', e=>{
    const li = e.target.closest('.day-item'); if(!li) return;
    selectDay(li.dataset.date);
  });

  teacherList.addEventListener('click', e=>{
    const box = e.target.closest('.teacher'); if(!box) return;
    if(e.target.classList.contains('tgl')){
      const expand = !box.classList.contains('expanded');
      expandTeacher(box, expand);
    }
    if(e.target.classList.contains('halt')){
      const sid = e.target.getAttribute('data-slot');
      const reason = prompt('Reason to halt/cancel this session (will be emailed):','Admin decision');
      if(!reason) return;
      e.target.disabled = true;
      post({action:'tp_adm_sched_halt', _ajax_nonce:NONCE, slot_id:sid, reason})
      .then(j=>{
        if(j && j.success){ expandTeacher(box, true); alert('Halted & emails sent.'); }
        else alert((j&&j.data)? String(j.data) : 'Failed.');
      });
    }
  });

  btnPrev.addEventListener('click', ()=>{
    const sun = parseYmd(wkSunEl.textContent);
    const prev = new Date(sun.getFullYear(), sun.getMonth(), sun.getDate()-7);
    buildWeek(fmtDate(prev));
  });
  btnNext.addEventListener('click', ()=>{
    const sun = parseYmd(wkSunEl.textContent);
    const next = new Date(sun.getFullYear(), sun.getMonth(), sun.getDate()+7);
    buildWeek(fmtDate(next));
  });

  // Export (CSV) + Print
  function exportCSV(params){
    const url = new URL("<?php echo esc_url(admin_url('admin-ajax.php')); ?>");
    url.searchParams.set('action','tp_adm_sched_export');
    url.searchParams.set('_ajax_nonce', "<?php echo esc_js($nonce); ?>");
    Object.entries(params).forEach(([k,v])=> v!=null && url.searchParams.set(k,v));
    window.open(url.toString(), '_blank');
  }
  exDayBtn.addEventListener('click', ()=> selectedDay && exportCSV({day:selectedDay}));
  exTBtn.addEventListener('click',  ()=> (selectedDay&&selectedTeacher) && exportCSV({day:selectedDay, teacher_id:selectedTeacher.id}));

  // Print views (browser “Save as PDF”)
  function exportPrint(params){
    const url = new URL("<?php echo esc_url(admin_url('admin-ajax.php')); ?>");
    url.searchParams.set('action','tp_adm_sched_print');
    url.searchParams.set('_ajax_nonce', "<?php echo esc_js($nonce); ?>");
    Object.entries(params).forEach(([k,v])=> v!=null && url.searchParams.set(k,v));
    window.open(url.toString(), '_blank');
  }
  exDayPrBtn.addEventListener('click', ()=> selectedDay && exportPrint({day:selectedDay}));
  exTPrBtn.addEventListener('click',  ()=> (selectedDay&&selectedTeacher) && exportPrint({day:selectedDay, teacher_id:selectedTeacher.id}));

  // INIT
  buildWeek(wrap.dataset.weekSun);
})();
</script>

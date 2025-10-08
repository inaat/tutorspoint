<?php
/** Tab 6: Bookings – day → time slots → bookings list */
if (!defined('ABSPATH')) exit;

$nonce = wp_create_nonce('tp_adm_bookings');

/** This week (Sun..Sat) using site timezone */
$nowTs   = current_time('timestamp');
$weekSun = date_i18n('Y-m-d', strtotime('sunday this week',   $nowTs));
$weekSat = date_i18n('Y-m-d', strtotime('saturday this week', $nowTs));
?>
<div class="tp-book-wrap" data-week-sun="<?php echo esc_attr($weekSun); ?>" data-week-sat="<?php echo esc_attr($weekSat); ?>">
  <div class="col left">
    <div class="card">
      <div class="head-row">
        <h3>Bookings</h3>
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

      <ul class="day-list" role="list" aria-live="polite"><!-- JS fills --></ul>

      <div class="export-box">
        <button class="btn btn-sm ghost ex-day">Export Day (CSV)</button>
        <button class="btn btn-sm ghost ex-day-print">Print (PDF)</button>
        <button class="btn btn-sm ghost ex-time" disabled>Export Time (CSV)</button>
        <button class="btn btn-sm ghost ex-time-print" disabled>Print Time</button>
      </div>
    </div>
  </div>

  <div class="col right">
    <div class="card">
      <div class="head-row">
        <h3 class="right-title">Day: <span class="sel-day">—</span></h3>
      </div>
      <div class="time-list">
        <div class="empty">Select a day.</div>
      </div>
    </div>
  </div>
</div>

<style>
  .tp-book-wrap{display:grid;grid-template-columns:320px 1fr;gap:16px;font-family:Roboto,Arial,sans-serif}
  @media (max-width:1000px){.tp-book-wrap{grid-template-columns:1fr}}
  .card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:12px}
  .head-row{display:flex;align-items:center;justify-content:space-between;gap:10px}

  .btn{background:#111827;color:#fff;border:0;border-radius:10px;padding:8px 12px;cursor:pointer;font-size:13px;white-space:nowrap}
  .btn-sm{padding:6px 10px;font-size:12px;border-radius:8px}
  .ghost{background:#fff;color:#111827;border:1px solid #d1d5db}
  .ghost:hover{background:#f9fafb}

  .week-bar{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:10px}
  .wk-range{font-weight:200}
  .wk-range .sep{margin:0 2px;color:#6b7280}

  .day-list{list-style:none;margin:0;padding:0;border-top:1px solid #eee}
  .day-item{display:flex;justify-content:space-between;align-items:center;padding:10px 8px;border-bottom:1px solid #f6f7f9;cursor:pointer}
  .day-item:hover{background:#fafafa}
  .day-item.active{background:#eef2ff}
  .day-item .dm{font-weight:600}
  .day-item .date{color:#6b7280;font-size:12px}

  .time-list .empty{color:#6b7280}
  .slot{border:1px solid #eef2f7;border-radius:10px;margin-bottom:8px}
  .slot .hdr{display:flex;align-items:center;gap:8px;justify-content:space-between;padding:8px}
  .slot .title{display:flex;align-items:center;gap:10px}
  .tgl{width:26px;height:26px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;border:1px solid #d1d5db;background:#fff;cursor:pointer}
  .tgl.on{background:#111827;color:#fff;border-color:#111827}
  .slot .count{font-size:12px;color:#6b7280}
  .slot .body{display:none;border-top:1px dashed #eef2f7;padding:8px}
  .slot.expanded .body{display:block}

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

  const wrap = document.querySelector('.tp-book-wrap');
  const wkSunEl = wrap.querySelector('.wk-sun');
  const wkSatEl = wrap.querySelector('.wk-sat');
  const dayList = wrap.querySelector('.day-list');

  const btnPrev = wrap.querySelector('.wk-prev');
  const btnNext = wrap.querySelector('.wk-next');

  const timeList = wrap.querySelector('.time-list');
  const selDayEl = wrap.querySelector('.sel-day');

  const exDayBtn   = wrap.querySelector('.ex-day');
  const exDayPrBtn = wrap.querySelector('.ex-day-print');
  const exTBtn     = wrap.querySelector('.ex-time');
  const exTPrBtn   = wrap.querySelector('.ex-time-print');

  let selectedDay = null;
  let selectedTime = null; // "HH:MM:SS"

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
    // auto-select today if inside the week, else Sunday
    const today = fmtDate(new Date());
    const first = dayList.firstElementChild.dataset.date;
    const last  = dayList.lastElementChild.dataset.date;
    selectDay((today>=first && today<=last) ? today : first);
  }

  function selectDay(dateStr){
    selectedDay = dateStr; selectedTime = null;
    exTBtn.disabled = true; exTPrBtn.disabled = true;
    selDayEl.textContent = dateStr;
    dayList.querySelectorAll('.day-item').forEach(li=> li.classList.toggle('active', li.dataset.date===dateStr));
    loadTimeBuckets(dateStr);
  }

  async function loadTimeBuckets(day){
    timeList.innerHTML = `<div class="empty">Loading…</div>`;
    const j = await post({action:'tp_adm_book_times', _ajax_nonce:NONCE, day});
    timeList.innerHTML = '';
    const times = (j && j.success && j.data.items) ? j.data.items : [];
    if(!times.length){ timeList.innerHTML = `<div class="empty">No bookings for ${day}.</div>`; return; }

    times.forEach(t=>{
      const box = document.createElement('div');
      box.className = 'slot';
      box.dataset.time = t.start_time;
      box.innerHTML = `
        <div class="hdr">
          <div class="title">
            <button class="tgl" aria-label="Toggle">+</button>
            <strong>${t.start_time} – ${t.end_time || ''}</strong>
          </div>
          <span class="count">${t.ct} ${t.ct==1?'booking':'bookings'}</span>
        </div>
        <div class="body">
          <table class="tbl">
            <thead>
              <tr><th>Teacher</th><th>Student</th><th>Level</th><th>Subject</th><th>Status</th><th>Payment</th><th>Actions</th></tr>
            </thead>
            <tbody class="rows"><tr><td colspan="7">Loading…</td></tr></tbody>
          </table>
        </div>`;
      timeList.appendChild(box);
    });
  }

  async function expandTime(box, expand=true){
    box.classList.toggle('expanded', expand);
    const tgl = box.querySelector('.tgl'); if(tgl) tgl.textContent = expand?'–':'+';
    if(!expand) return;

    selectedTime = box.dataset.time;
    exTBtn.disabled=false; exTPrBtn.disabled=false;

    const tb = box.querySelector('.rows');
    tb.innerHTML = `<tr><td colspan="7">Loading…</td></tr>`;
    const j = await post({action:'tp_adm_book_rows', _ajax_nonce:NONCE, day:selectedDay, start_time:selectedTime});
    tb.innerHTML = '';
    const list = (j && j.success && j.data.items) ? j.data.items : [];
    if(!list.length){ tb.innerHTML = `<tr><td colspan="7">No data.</td></tr>`; return; }

    list.forEach(r=>{
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${r.teacher || ('#'+r.teacher_id)}</td>
        <td>${r.student || ''}</td>
        <td>${r.level_name || ''}</td>
        <td>${r.SubjectName || ''}</td>
        <td><span class="badge ${r.status==='halted'?'warn':'gray'}">${r.status||'—'}</span></td>
        <td><span class="badge ${r.is_paid? 'ok':'gray'}">${r.is_paid?('Paid £'+Number(r.final_price||0).toFixed(2)):(r.final_price>0?'Unpaid':'Free')}</span></td>
        <td class="slot-actions">
          ${r.meeting_link ? `<a class="btn btn-sm ghost" href="${r.meeting_link}" target="_blank">Join</a>`:''}
          <button class="halt" data-slot="${r.slot_id}">Halt & Notify</button>
        </td>`;
      tb.appendChild(tr);
    });
  }

  // events
  dayList.addEventListener('click', e=>{
    const li = e.target.closest('.day-item'); if(!li) return;
    selectDay(li.dataset.date);
  });

  timeList.addEventListener('click', e=>{
    const box = e.target.closest('.slot'); if(!box) return;
    if(e.target.classList.contains('tgl')){
      expandTime(box, !box.classList.contains('expanded'));
    }
    if(e.target.classList.contains('halt')){
      const sid = e.target.getAttribute('data-slot');
      const reason = prompt('Reason to halt/cancel this session (will be emailed):','Admin decision');
      if(!reason) return;
      e.target.disabled = true;
      fetch(AJAX,{method:'POST',body:new URLSearchParams({action:'tp_adm_book_halt','_ajax_nonce':NONCE,slot_id:sid,reason})})
      .then(r=>r.json()).then(j=>{
        if(j && j.success){ expandTime(box, true); alert('Halted & emails sent.'); }
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

  // Export
  function exportCSV(params){
    const url = new URL("<?php echo esc_url(admin_url('admin-ajax.php')); ?>");
    url.searchParams.set('action','tp_adm_book_export');
    url.searchParams.set('_ajax_nonce', "<?php echo esc_js($nonce); ?>");
    Object.entries(params).forEach(([k,v])=> v!=null && url.searchParams.set(k,v));
    window.open(url.toString(), '_blank');
  }
  function exportPrint(params){
    const url = new URL("<?php echo esc_url(admin_url('admin-ajax.php')); ?>");
    url.searchParams.set('action','tp_adm_book_print');
    url.searchParams.set('_ajax_nonce', "<?php echo esc_js($nonce); ?>");
    Object.entries(params).forEach(([k,v])=> v!=null && url.searchParams.set(k,v));
    window.open(url.toString(), '_blank');
  }
  exDayBtn.addEventListener('click', ()=> selectedDay && exportCSV({day:selectedDay}));
  exDayPrBtn.addEventListener('click', ()=> selectedDay && exportPrint({day:selectedDay}));
  exTBtn.addEventListener('click', ()=> (selectedDay&&selectedTime) && exportCSV({day:selectedDay,start_time:selectedTime}));
  exTPrBtn.addEventListener('click', ()=> (selectedDay&&selectedTime) && exportPrint({day:selectedDay,start_time:selectedTime}));

  // init
  buildWeek(wrap.dataset.weekSun);
})();
</script>

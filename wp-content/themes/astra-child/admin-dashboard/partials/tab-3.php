<?php
/** Tab 3: Teachers – refined layout + auto-load per tab */
if (!defined('ABSPATH')) exit;

$nonce_t  = wp_create_nonce('tp_adm_teacher');
$nonce_lv = wp_create_nonce('tp_adm_lv');
$nonce_sub= wp_create_nonce('tp_adm_sub');
?>
<div class="tp-teachers-wrap">
  <!-- LEFT COLUMN -->
  <div class="col left">
    <div class="card">
      <div class="head-row">
        <h3>Teachers</h3>
        <div class="inline">
          <input type="search" class="t-search" placeholder="Search by name/email…">
          <button class="btn btn-sm ghost refresh" title="Reload list">Refresh</button>
        </div>
      </div>
      <ul class="t-list" role="list" aria-live="polite"></ul>
    </div>

    <div class="card">
      <h4>Add New Teacher</h4>
      <div class="grid two">
        <label>Name
          <input type="text" class="t-name" placeholder="Full name">
        </label>
        <label>Email
          <input type="email" class="t-email" placeholder="name@example.com">
        </label>
      </div>
      <div class="row-actions">
        <button class="btn add">Create</button>
        <span class="msg add-msg"></span>
      </div>
    </div>
  </div>

  <!-- RIGHT COLUMN -->
  <div class="col right">
    <div class="card">
      <div class="head-row">
        <h3>Teacher Detail</h3>
        <div class="sel-teacher">No teacher selected</div>
      </div>
    </div>

    <div class="card sections">
      <div class="tabs sticky">
        <button class="tab active" data-pane="alloc">Allocations</button>
        <button class="tab" data-pane="rate">Hourly Rate</button>
        <button class="tab" data-pane="slots">Slots</button>
        <button class="tab" data-pane="due">Due Payments</button>
        <button class="tab" data-pane="withdrawn">Withdrawn</button>
      </div>

      <!-- Allocations -->
      <div class="pane show" id="pane-alloc">
        <div class="grid two lg-gap">
          <div class="box">
            <div class="subhead-row">
              <h4>Levels</h4>
              <input type="search" class="lv-search" placeholder="Search levels…">
            </div>
            <ul class="lv-list" role="list"></ul>
          </div>

          <div class="box">
            <h4>Subjects for selected level</h4>
            <div class="chips" aria-live="polite"></div>

            <!-- tag-like autocomplete -->
            <div class="tagbox" role="combobox" aria-haspopup="listbox" aria-expanded="false">
              <input type="text" class="tag-input" placeholder="Type a subject… (Enter / , / Tab to add)" autocomplete="off" aria-autocomplete="list" aria-controls="tag-suggest">
              <ul class="suggest" id="tag-suggest" role="listbox"></ul>
            </div>
            <div class="hint">Use the input to attach subjects to this teacher for the selected level. Click × to remove.</div>
          </div>
        </div>
      </div>

      <!-- Hourly Rate -->
      <div class="pane" id="pane-rate">
        <div class="grid three">
          <label>Level
            <select class="rate-level"></select>
          </label>
          <label>Subject
            <select class="rate-subject"></select>
          </label>
          <label>Hourly Rate (£)
            <input type="number" class="rate-amount" step="0.01" min="0" placeholder="e.g., 20">
          </label>
        </div>
        <div class="row-actions">
          <button class="btn set-rate">Save Rate</button>
          <span class="msg rate-msg"></span>
        </div>
        <div class="hint">Teacher’s hourly rate must be ≤ the standard level rate in your pricing table.</div>
      </div>

      <!-- Slots -->
      <div class="pane" id="pane-slots">
        <div class="inline wrap">
          <label class="mini">From <input type="date" class="slot-from"></label>
          <label class="mini">To <input type="date" class="slot-to"></label>
          <button class="btn btn-sm ghost load-slots">Load</button>
        </div>
        <div class="table-wrap">
          <table class="tbl">
            <thead><tr><th>Date</th><th>Start</th><th>End</th><th>Level</th><th>Subject</th><th>Status</th></tr></thead>
            <tbody class="slots-body"><tr><td colspan="6" class="empty">—</td></tr></tbody>
          </table>
        </div>
      </div>

      <!-- Due -->
      <div class="pane" id="pane-due">
        <div class="table-wrap">
          <table class="tbl">
            <thead><tr><th>#</th><th>Hour Rate</th><th>Lecture</th><th>Eligible (£)</th></tr></thead>
            <tbody class="due-body"><tr><td colspan="4" class="empty">—</td></tr></tbody>
          </table>
        </div>
      </div>

      <!-- Withdrawn -->
      <div class="pane" id="pane-withdrawn">
        <div class="table-wrap">
          <table class="tbl">
            <thead><tr><th>Date</th><th>Amount (£)</th><th>Status</th><th>Remarks</th></tr></thead>
            <tbody class="wd-body"><tr><td colspan="4" class="empty">—</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  /* layout */
  .tp-teachers-wrap{display:grid;grid-template-columns:320px 1fr;gap:16px;font-family:Roboto,Arial,sans-serif}
  @media (max-width:1000px){.tp-teachers-wrap{grid-template-columns:1fr}}
  .card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:12px}
  .head-row,.subhead-row{display:flex;align-items:center;justify-content:space-between;gap:10px}
  .inline{display:flex;gap:8px;align-items:center}
  .inline.wrap{flex-wrap:wrap}
  .grid.two{display:grid;grid-template-columns:1fr 1fr;gap:10px}
  .grid.three{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px}
  .grid.two.lg-gap{gap:14px}
  @media (max-width:900px){.grid.two,.grid.three{grid-template-columns:1fr}}
  .row-actions{display:flex;gap:10px;align-items:center;margin-top:8px}
  input,select{border:1px solid #d1d5db;border-radius:10px;padding:8px 10px;font-size:14px;width:100%;background:#fff}
  input:focus,select:focus{outline:none;box-shadow:0 0 0 3px rgba(79,70,229,.15);border-color:#6366f1}

  /* tabs */
  .tabs{display:flex;gap:8px;margin:-6px -6px 10px -6px;padding:6px;position:relative}
  .tabs.sticky{position:sticky;top:6px;background:#fff;z-index:2;border-bottom:1px dashed #eee}
  .tab{background:#f3f4f6;border:1px solid #e5e7eb;border-radius:999px;padding:6px 12px;cursor:pointer;font-size:13px;line-height:1;white-space:nowrap}
  .tab:hover{background:#e5e7ff;border-color:#c7d2fe}
  .tab.active{background:#111827;color:#fff;border-color:#111827}
  .pane{display:none}.pane.show{display:block}

  /* lists */
  .t-list{list-style:none;margin:10px 0 0;padding:0;max-height:520px;overflow:auto;border-top:1px solid #eee}
  /* teacher row: stacked layout so buttons align LEFT under email */
  .t-row{display:block;padding:10px 6px;border-bottom:1px solid #f5f5f5}
  .t-row:hover{background:#fafafa}
  .t-row .name{font-weight:600;margin-bottom:2px}
  .t-row .t-meta{font-size:12px;color:#6b7280;margin-bottom:6px}
  .t-row .actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap} /* left-aligned row */
  .lv-list{list-style:none;margin:10px 0 0;padding:0;max-height:470px;overflow:auto;border-top:1px solid #eee}
  .lv-list li{display:flex;justify-content:space-between;align-items:center;padding:8px 6px;border-bottom:1px solid #f7f7f7}
  .lv-list li:hover{background:#fafafa}

  /* buttons, pills */
  .btn{background:#111827;color:#fff;border:0;border-radius:10px;padding:8px 12px;cursor:pointer;font-size:13px;white-space:nowrap}
  .btn-sm{padding:6px 10px;font-size:12px;border-radius:9px}
  .ghost{background:#fff;color:#111827;border:1px solid #d1d5db}
  .ghost:hover{background:#f9fafb}
  .status-pill{display:inline-flex;align-items:center;gap:6px;padding:2px 8px;border-radius:999px;font-size:12px;border:1px solid}
  .status-on{background:#ecfdf5;border-color:#34d399;color:#065f46}
  .status-off{background:#f3f4f6;border-color:#d1d5db;color:#4b5563}

  /* chips + tagbox */
  .chips{display:flex;flex-wrap:wrap;gap:8px;min-height:36px}
  .chip{display:inline-flex;align-items:center;gap:6px;background:#eef2ff;border:1px solid #c7d2fe;border-radius:18px;padding:4px 10px;font-size:13px}
  .chip .x{cursor:pointer;border:0;background:transparent;font-size:14px;line-height:1}
  .hint{font-size:12px;color:#6b7280;margin-top:6px}
  .tagbox{position:relative;border:1px dashed #c7d2fe;background:#fbfcff;border-radius:12px;padding:2px;margin-top:8px}
  .tagbox:focus-within{box-shadow:0 0 0 3px #e0e7ff}
  .tag-input{border:0;outline:0;background:transparent;padding:10px 12px;width:100%;font-size:14px}
  .suggest{position:absolute;left:0;right:0;top:100%;z-index:5;background:#fff;border:1px solid #e5e7eb;border-top:0;border-radius:0 0 12px 12px;max-height:220px;overflow:auto;list-style:none;margin:0;padding:6px;display:none}
  .suggest.show{display:block}
  .suggest li{padding:8px;border-radius:8px;cursor:pointer;display:flex;justify-content:space-between;gap:8px}
  .suggest li:hover,.suggest li.active{background:#f5f7ff}

  /* tables */
  .table-wrap{overflow:auto;border:1px solid #f1f5f9;border-radius:10px}
  .tbl{width:100%;border-collapse:collapse;font-size:13px}
  .tbl th,.tbl td{border-bottom:1px solid #f1f5f9;padding:8px;text-align:left;white-space:nowrap}
  .tbl .empty{color:#9ca3af}
  .mini input{width:auto}
  .msg{font-size:12px;color:#111827}
</style>

<script>
(function(){
  const ajax = "<?php echo esc_url(admin_url('admin-ajax.php')); ?>";
  const nonceT  = "<?php echo esc_js($nonce_t); ?>";
  const nonceLV = "<?php echo esc_js($nonce_lv); ?>";
  const nonceSUB= "<?php echo esc_js($nonce_sub); ?>";

  // LEFT: list + add
  const tList  = document.querySelector('.t-list');
  const tSearch= document.querySelector('.t-search');
  const addBtn = document.querySelector('.btn.add');
  const nameIn = document.querySelector('.t-name');
  const emailIn= document.querySelector('.t-email');
  const addMsg = document.querySelector('.add-msg');

  // RIGHT: header + tabs/panes
  const selTeacher = document.querySelector('.sel-teacher');
  const tabs = document.querySelectorAll('.tab');
  const panes= document.querySelectorAll('.pane');

  // Allocations pane
  const lvList   = document.querySelector('.lv-list');
  const lvSearch = document.querySelector('.lv-search');
  const chips    = document.querySelector('.chips');
  const tagBox   = document.querySelector('.tagbox');
  const tagInput = document.querySelector('.tag-input');
  const sug      = document.querySelector('.suggest');

  // Rates pane
  const rateLevel   = document.querySelector('.rate-level');
  const rateSubject = document.querySelector('.rate-subject');
  const rateAmount  = document.querySelector('.rate-amount');
  const rateBtn     = document.querySelector('.set-rate');
  const rateMsg     = document.querySelector('.rate-msg');

  // Slots + payouts
  const slotsBody = document.querySelector('.slots-body');
  const slotFrom  = document.querySelector('.slot-from');
  const slotTo    = document.querySelector('.slot-to');
  const slotsBtn  = document.querySelector('.load-slots');
  const dueBody   = document.querySelector('.due-body');
  const wdBody    = document.querySelector('.wd-body');

  // State
  let currentTeacher = null;
  let currentLevel   = null;
  let suggestions    = [];
  let activeIndex    = -1;
  let debounceT      = null;

  // helpers
  async function post(data){
    const fd = new FormData(); Object.entries(data).forEach(([k,v])=>fd.append(k,v));
    const res = await fetch(ajax,{method:'POST',body:fd});
    return res.json();
  }
  function esc(s){return String(s).replace(/[&<>"']/g,m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]))}
  function setPane(k){
    panes.forEach(p=>p.classList.remove('show'));
    document.getElementById('pane-'+k).classList.add('show');
    tabs.forEach(b=>b.classList.toggle('active',b.dataset.pane===k));
    // Auto-load data for tabs when switched
    if(k==='due') loadDue();
    if(k==='withdrawn') loadWithdrawn();
    if(k==='slots' && currentTeacher) loadSlots(); // convenience
  }
  function showSuggest(show){ tagBox.setAttribute('aria-expanded', show?'true':'false'); sug.classList.toggle('show', !!show); }
  function hideSuggest(){ showSuggest(false); sug.innerHTML=''; activeIndex=-1; }

  /* ---------- TEACHERS LIST ---------- */
  async function loadTeachers(q=''){
    tList.innerHTML = '<li class="t-row"><span class="t-meta">Loading…</span></li>';
    const j = await post({action:'tp_adm_t_list', _ajax_nonce:nonceT, q});
    tList.innerHTML='';
    if(!j || !j.success || !j.data.items.length){ tList.innerHTML='<li class="t-row"><span class="t-meta">No teachers found.</span></li>'; return; }
    j.data.items.forEach(it=>{
      const li=document.createElement('li'); li.className='t-row'; li.dataset.id = it.teacher_id;
      li.innerHTML = `
        <div class="name">${esc(it.FullName || '—')}</div>
        <div class="t-meta">${esc(it.Email || '')}</div>
        <div class="actions">
          <span class="status-pill ${it.Status==='active'?'status-on':'status-off'}">${it.Status==='active'?'Active':'Inactive'}</span>
          <button class="btn btn-sm ghost open">Open</button>
          <button class="btn btn-sm ghost toggle" data-to="${it.Status==='active'?'inactive':'active'}">${it.Status==='active'?'Deactivate':'Activate'}</button>
        </div>`;
      tList.appendChild(li);
    });
  }
  document.querySelector('.btn.refresh').addEventListener('click', ()=> loadTeachers(tSearch.value.trim()));
  tSearch.addEventListener('input', e=> loadTeachers(e.target.value));
  tList.addEventListener('click', async (e)=>{
    const li=e.target.closest('.t-row'); if(!li) return;
    const id=parseInt(li.dataset.id,10);
    if(e.target.classList.contains('open')) openTeacher(id, li.querySelector('.name').textContent);
    if(e.target.classList.contains('toggle')){
      const to=e.target.getAttribute('data-to');
      const j=await post({action:'tp_adm_t_toggle', _ajax_nonce:nonceT, teacher_id:id, to});
      if(j && j.success){ loadTeachers(tSearch.value.trim()); }
      else alert('Failed to toggle');
    }
  });

  // add
  addBtn.addEventListener('click', async ()=>{
    const nm=nameIn.value.trim(), em=emailIn.value.trim();
    if(!nm || !em){ addMsg.textContent='Name and Email are required.'; return; }
    addBtn.disabled=true; addMsg.textContent='Creating…';
    const j=await post({action:'tp_adm_t_add', _ajax_nonce:nonceT, name:nm, email:em});
    addBtn.disabled=false;
    if(j && j.success){ addMsg.textContent='Created.'; nameIn.value=''; emailIn.value=''; loadTeachers(tSearch.value.trim()); }
    else addMsg.textContent=(j && j.data)? String(j.data):'Failed.';
  });

  async function openTeacher(id, name){
    currentTeacher = {id, name};
    selTeacher.textContent = `${name} (ID ${id})`;
    setPane('alloc');

    // allocations: load levels & first level selection
    await loadLevels('');
    // rates drop-downs
    await fillRateLevelOptions();
    rateSubject.innerHTML = '<option value="">Select a level first</option>';

    // slots defaults
    const today=new Date().toISOString().slice(0,10);
    const seven=new Date(Date.now()+6*86400000).toISOString().slice(0,10);
    slotFrom.value=today; slotTo.value=seven;
  }

  /* ---------- ALLOCATIONS ---------- */
  async function loadLevels(q=''){
    const j=await post({action:'tp_adm_lv_list', _ajax_nonce:nonceLV, q});
    lvList.innerHTML='';
    if(!j || !j.success || !j.data.items.length){ lvList.innerHTML='<li><em>No levels.</em></li>'; return; }
    j.data.items.forEach((it,i)=>{
      const li=document.createElement('li'); li.dataset.id=it.id;
      li.innerHTML=`<span>${esc(it.name)}</span><button class="btn btn-sm ghost open">Open</button>`;
      lvList.appendChild(li);
      if(i===0){ openLevel(it.id, it.name); }
    });
  }
  lvSearch.addEventListener('input', e=> loadLevels(e.target.value));
  lvList.addEventListener('click', e=>{
    const li=e.target.closest('li'); if(!li) return;
    if(e.target.classList.contains('open')){
      const id=parseInt(li.dataset.id,10);
      openLevel(id, li.querySelector('span').textContent);
    }
  });

  async function openLevel(id, name){
    currentLevel={id,name};
    chips.innerHTML='<em>Loading…</em>';
    const j=await post({action:'tp_adm_t_alloc_list', _ajax_nonce:nonceT, teacher_id: currentTeacher.id, level_id:id});
    chips.innerHTML='';
    if(!j || !j.success){ chips.innerHTML='<em>Failed.</em>'; return; }
    const items=j.data.items||[];
    if(!items.length) chips.innerHTML='<em>No subjects allocated yet.</em>';
    items.forEach(it=>{
      const chip=document.createElement('div');
      chip.className='chip'; chip.dataset.tasid=it.teacher_allocated_subject_id;
      chip.innerHTML=`<span>${esc(it.SubjectName)}</span><button class="x" title="Remove">×</button>`;
      chips.appendChild(chip);
    });
    await fillRateLevelOptions(id);
    await fillRateSubjectOptions(id);
  }

  chips.addEventListener('click', async (e)=>{
    const x=e.target.closest('.x'); if(!x) return;
    const chip=e.target.closest('.chip'); const id=chip.dataset.tasid;
    const j=await post({action:'tp_adm_t_alloc_detach', _ajax_nonce:nonceT, teacher_allocated_subject_id:id});
    if(j && j.success){ chip.remove(); } else alert('Failed to remove');
  });

  // tag-like autocomplete
  function renderSuggest(list, query){
    sug.innerHTML='';
    if(!list.length){
      const li=document.createElement('li'); li.className='active';
      li.innerHTML=`Create "<strong>${esc(query)}</strong>"`; sug.appendChild(li); activeIndex=0; return;
    }
    list.forEach((it,i)=>{
      const li=document.createElement('li'); li.dataset.sid=it.subject_id;
      li.innerHTML = `${esc(it.SubjectName)} <em>#${it.subject_id}</em>`;
      if(i===0) li.classList.add('active'); sug.appendChild(li);
    });
    activeIndex=0;
  }
  async function fetchSuggest(q){
    const j=await post({action:'tp_adm_sub_list_all', _ajax_nonce:nonceSUB, q});
    suggestions = (j && j.success && j.data.items) ? j.data.items : [];
    renderSuggest(suggestions, q); showSuggest(true);
  }
  tagInput.addEventListener('input', e=>{
    const q=e.target.value.trim();
    if(debounceT) clearTimeout(debounceT);
    if(!q){ hideSuggest(); return; }
    debounceT=setTimeout(()=>fetchSuggest(q), 180);
  });
  tagInput.addEventListener('keydown', (e)=>{
    const q=tagInput.value.trim();
    const commit=(e.key==='Enter'||e.key===','||e.key==='Tab');
    if(sug.classList.contains('show')){
      if(e.key==='ArrowDown'){ e.preventDefault(); move(+1); }
      else if(e.key==='ArrowUp'){ e.preventDefault(); move(-1); }
      else if(commit){ e.preventDefault(); commitSelection(q); }
      else if(e.key==='Escape'){ hideSuggest(); }
    } else if(commit && q){ e.preventDefault(); commitSelection(q); }
  });
  function move(d){
    const items=[...sug.querySelectorAll('li')]; if(!items.length) return;
    activeIndex=(activeIndex+d+items.length)%items.length;
    items.forEach((li,i)=>li.classList.toggle('active', i===activeIndex));
    items[activeIndex].scrollIntoView({block:'nearest'});
  }
  sug.addEventListener('mousedown', e=>{
    const li=e.target.closest('li'); if(!li) return;
    const q=tagInput.value.trim(); selectLI(li, q);
  });
  function selectLI(li, q){
    const sid=li.dataset.sid ? parseInt(li.dataset.sid,10) : null;
    const name=li.dataset.sid ? '' : q;
    attachSubject(sid, name);
  }
  async function commitSelection(q){
    const li=sug.querySelector('li.active'); if(li){ selectLI(li, q); return; }
    if(q) attachSubject(null, q);
  }
  async function attachSubject(subject_id, subject_name){
    if(!currentTeacher||!currentLevel){ alert('Select teacher and level'); return; }
    hideSuggest();
    const j=await post({
      action:'tp_adm_t_alloc_attach', _ajax_nonce:nonceT,
      teacher_id: currentTeacher.id, level_id: currentLevel.id,
      subject_id: subject_id || '', subject_name: subject_name || ''
    });
    if(j && j.success){ tagInput.value=''; openLevel(currentLevel.id, currentLevel.name); }
    else alert((j && j.data)? String(j.data):'Failed.');
  }

  /* ---------- RATES ---------- */
  async function fillRateLevelOptions(preselectId=null){
    const j=await post({action:'tp_adm_lv_list', _ajax_nonce:nonceLV, q:''});
    rateLevel.innerHTML='';
    if(!j || !j.success || !j.data.items.length){ rateLevel.innerHTML='<option value="">No levels</option>'; return; }
    j.data.items.forEach(it=>{
      const op=document.createElement('option'); op.value=it.id; op.textContent=it.name;
      if(preselectId && String(preselectId)===String(it.id)) op.selected=true;
      rateLevel.appendChild(op);
    });
  }
  async function fillRateSubjectOptions(levelId){
    rateSubject.innerHTML='<option value="">Loading…</option>';
    const j=await post({action:'tp_adm_t_alloc_list', _ajax_nonce:nonceT, teacher_id: currentTeacher.id, level_id: levelId});
    rateSubject.innerHTML='';
    const items=(j && j.success && j.data.items) ? j.data.items : [];
    if(!items.length){ rateSubject.innerHTML='<option value="">No subjects allocated for this level</option>'; return; }
    items.forEach(it=>{
      const op=document.createElement('option'); op.value=it.subject_level_id; op.textContent=it.SubjectName;
      rateSubject.appendChild(op);
    });
  }
  rateLevel.addEventListener('change', ()=> fillRateSubjectOptions(rateLevel.value));
  rateBtn.addEventListener('click', async ()=>{
    if(!currentTeacher){ rateMsg.textContent='Select a teacher.'; return; }
    const sl=rateLevel.value, sls=rateSubject.value, amt=parseFloat(rateAmount.value||0);
    if(!sl || !sls || !amt){ rateMsg.textContent='All fields are required.'; return; }
    rateBtn.disabled=true; rateMsg.textContent='Saving…';
    const j=await post({action:'tp_adm_t_rate_set', _ajax_nonce:nonceT, teacher_id: currentTeacher.id, subject_level_id: sls, hourly_rate: amt});
    rateBtn.disabled=false;
    rateMsg.textContent = (j&&j.success) ? 'Saved.' : ((j&&j.data)? String(j.data):'Failed.');
  });

  /* ---------- SLOTS ---------- */
  async function loadSlots(){
    if(!currentTeacher){ return; }
    const from=slotFrom.value, to=slotTo.value;
    const j=await post({action:'tp_adm_t_slots', _ajax_nonce:nonceT, teacher_id: currentTeacher.id, from, to});
    slotsBody.innerHTML='';
    if(!j || !j.success){ slotsBody.innerHTML='<tr><td colspan="6">Failed.</td></tr>'; return; }
    const items=j.data.items||[];
    if(!items.length){ slotsBody.innerHTML='<tr><td colspan="6" class="empty">—</td></tr>'; return; }
    items.forEach(r=>{
      const tr=document.createElement('tr');
      tr.innerHTML=`<td>${r.session_date||''}</td><td>${r.start_time||''}</td><td>${r.end_time||''}</td>
        <td>${esc(r.level_name||'')}</td><td>${esc(r.SubjectName||'')}</td><td>${esc(r.status||'')}</td>`;
      slotsBody.appendChild(tr);
    });
  }
  slotsBtn.addEventListener('click', loadSlots);

  /* ---------- PAYOUTS ---------- */
  async function loadDue(){
    if(!currentTeacher) return;
    const j=await post({action:'tp_adm_t_due', _ajax_nonce:nonceT, teacher_id: currentTeacher.id});
    dueBody.innerHTML='';
    if(!j || !j.success){ dueBody.innerHTML='<tr><td colspan="4">Failed.</td></tr>'; return; }
    const rows=j.data.items||[];
    if(!rows.length){ dueBody.innerHTML='<tr><td colspan="4" class="empty">—</td></tr>'; return; }
    rows.forEach(r=>{
      const tr=document.createElement('tr');
      tr.innerHTML=`<td>${r.payment_record_id}</td><td>${r.hour_rate_id}</td><td>${r.lecture_book_id}</td><td>${Number(r.eligible_payout_amount||0).toFixed(2)}</td>`;
      dueBody.appendChild(tr);
    });
  }
  async function loadWithdrawn(){
    if(!currentTeacher) return;
    const j=await post({action:'tp_adm_t_withdrawn', _ajax_nonce:nonceT, teacher_id: currentTeacher.id});
    wdBody.innerHTML='';
    if(!j || !j.success){ wdBody.innerHTML='<tr><td colspan="4">Failed.</td></tr>'; return; }
    const rows=j.data.items||[];
    if(!rows.length){ wdBody.innerHTML='<tr><td colspan="4" class="empty">—</td></tr>'; return; }
    rows.forEach(r=>{
      const tr=document.createElement('tr');
      tr.innerHTML=`<td>${r.payment_withdrawal_date||''}</td><td>${Number(r.amount_withdrawal||0).toFixed(2)}</td><td>${esc(r.status||'')}</td><td>${esc(r.remarks||'')}</td>`;
      wdBody.appendChild(tr);
    });
  }

  // Tab switching + auto-loads
  document.querySelectorAll('.tab').forEach(tb=>{
    tb.addEventListener('click', ()=> setPane(tb.dataset.pane));
  });

  // init
  loadTeachers('');
})();
</script>

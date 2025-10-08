<?php
/** Tab 4: Students – list, detail, filters, password reset, status, payments, export */
if (!defined('ABSPATH')) exit;

$nonce_s  = wp_create_nonce('tp_adm_student');
$nonce_lv = wp_create_nonce('tp_adm_lv');
$nonce_sub= wp_create_nonce('tp_adm_sub');
?>
<div class="tp-students-wrap">
  <!-- LEFT COLUMN -->
  <div class="col left">
    <div class="card">
      <div class="head-row">
        <h3>Students</h3>
        <div class="inline wrap">
          <input type="search" class="s-search" placeholder="Search by name/email/phone…">
          <select class="s-filter-level"><option value="">All Levels</option></select>
          <select class="s-filter-subject"><option value="">All Subjects</option></select>
          <button class="btn btn-sm ghost refresh">Refresh</button>
        </div>
      </div>
      <ul class="s-list" role="list" aria-live="polite"></ul>
    </div>
  </div>

  <!-- RIGHT COLUMN -->
  <div class="col right">
    <div class="card">
      <div class="head-row">
        <h3>Student Detail</h3>
        <div class="sel-student">No student selected</div>
      </div>
    </div>

    <div class="card sections">
      <div class="tabs sticky">
        <button class="tab active" data-pane="profile">Profile</button>
        <button class="tab" data-pane="lectures">Lectures</button>
        <button class="tab" data-pane="payments">Payments</button>
        <button class="tab" data-pane="export">Export</button>
      </div>

      <!-- Profile -->
      <div class="pane show" id="pane-profile">
        <div class="grid two">
          <div class="box">
            <h4>Basic Info</h4>
            <div class="row"><strong>Name:</strong> <span class="p-name">—</span></div>
            <div class="row"><strong>Email:</strong> <span class="p-email">—</span></div>
            <div class="row"><strong>Phone:</strong> <span class="p-phone">—</span></div>
            <div class="row"><strong>Level:</strong> <span class="p-level">—</span></div>
            <div class="row"><strong>Address:</strong> <span class="p-addr">—</span></div>
            <div class="row"><strong>Guardian:</strong> <span class="p-guard">—</span></div>
            <div class="row"><strong>Status:</strong> <span class="p-status pill">—</span></div>
            <div class="row"><strong>Created:</strong> <span class="p-created">—</span></div>
            <div class="row hint">Passwords are securely hashed; you can set a new one below.</div>
            <div class="row-actions">
              <button class="btn btn-sm ghost toggle-status" data-to="">Toggle Active</button>
              <button class="btn btn-sm ghost email-deact" title="Email the student on deactivation">Send Deactivation Email</button>
            </div>
          </div>

          <div class="box">
            <h4>Set New Password</h4>
            <div class="grid two">
              <label>New password
                <input type="password" class="pw-1" placeholder="At least 8 chars">
              </label>
              <label>Confirm
                <input type="password" class="pw-2" placeholder="Repeat password">
              </label>
            </div>
            <div class="row-actions">
              <button class="btn set-pass">Update Password</button>
              <span class="msg pw-msg"></span>
            </div>
          </div>
        </div>
      </div>

      <!-- Lectures -->
      <div class="pane" id="pane-lectures">
        <div class="inline wrap">
          <input type="date" class="lec-from">
          <input type="date" class="lec-to">
          <select class="lec-subject"><option value="">All Subjects</option></select>
          <button class="btn btn-sm ghost load-lectures">Load</button>
        </div>
        <div class="table-wrap">
          <table class="tbl">
            <thead>
              <tr><th>#</th><th>Date</th><th>Time</th><th>Topic</th><th>Subject</th><th>Duration</th><th>Status</th></tr>
            </thead>
            <tbody class="lec-body"><tr><td colspan="7" class="empty">—</td></tr></tbody>
          </table>
        </div>
      </div>

      <!-- Payments -->
      <div class="pane" id="pane-payments">
        <div class="table-wrap">
          <table class="tbl">
            <thead>
              <tr><th>#</th><th>Date</th><th>Subject</th><th>Orig (£)</th><th>Disc</th><th>Final (£)</th><th>Paid?</th></tr>
            </thead>
            <tbody class="pay-body"><tr><td colspan="7" class="empty">—</td></tr></tbody>
          </table>
        </div>
      </div>

      <!-- Export -->
      <div class="pane" id="pane-export">
        <div class="grid two">
          <div class="box">
            <h4>Export Lectures</h4>
            <div class="inline wrap">
              <input type="date" class="ex-lec-from">
              <input type="date" class="ex-lec-to">
              <button class="btn btn-sm ghost export-lec" title="Download CSV">CSV</button>
            </div>
          </div>
          <div class="box">
            <h4>Export Payments</h4>
            <div class="inline wrap">
              <input type="date" class="ex-pay-from">
              <input type="date" class="ex-pay-to">
              <button class="btn btn-sm ghost export-pay" title="Download CSV">CSV</button>
            </div>
          </div>
        </div>
        <div class="hint">Open CSV in Excel/Google Sheets. (PDF print coming on request.)</div>
      </div>
    </div>
  </div>
</div>

<style>
  .tp-students-wrap{display:grid;grid-template-columns:360px 1fr;gap:16px;font-family:Roboto,Arial,sans-serif}
  @media (max-width:1100px){.tp-students-wrap{grid-template-columns:1fr}}
  .card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:12px}
  .head-row{display:flex;align-items:center;justify-content:space-between;gap:10px}
  .inline{display:flex;gap:8px;align-items:center}
  .inline.wrap{flex-wrap:wrap}
  .grid.two{display:grid;grid-template-columns:1fr 1fr;gap:10px}
  @media (max-width:900px){.grid.two{grid-template-columns:1fr}}
  .row{margin:6px 0}
  .row-actions{display:flex;gap:10px;align-items:center;margin-top:8px}
  .box{border:1px solid #eef2f7;border-radius:10px;padding:10px}

  input,select{border:1px solid #d1d5db;border-radius:10px;padding:8px 10px;font-size:14px;background:#fff}
  input:focus,select:focus{outline:none;box-shadow:0 0 0 3px rgba(79,70,229,.12);border-color:#6366f1}

  .tabs{display:flex;gap:8px;margin:-6px -6px 10px -6px;padding:6px}
  .tabs.sticky{position:sticky;top:6px;background:#fff;z-index:2;border-bottom:1px dashed #eee}
  .tab{background:#f3f4f6;border:1px solid #e5e7eb;border-radius:999px;padding:6px 12px;cursor:pointer;font-size:13px;white-space:nowrap}
  .tab:hover{background:#e5e7ff;border-color:#c7d2fe}
  .tab.active{background:#111827;color:#fff;border-color:#111827}
  .pane{display:none}.pane.show{display:block}

  .btn{background:#111827;color:#fff;border:0;border-radius:10px;padding:8px 12px;cursor:pointer;font-size:13px;white-space:nowrap}
  .btn-sm{padding:6px 10px;font-size:12px;border-radius:8px}
  .ghost{background:#fff;color:#111827;border:1px solid #d1d5db}
  .ghost:hover{background:#f9fafb}
  .pill{display:inline-flex;align-items:center;gap:6px;padding:2px 8px;border-radius:999px;font-size:12px;border:1px solid}
  .pill.on{background:#ecfdf5;border-color:#34d399;color:#065f46}
  .pill.off{background:#f3f4f6;border-color:#d1d5db;color:#4b5563}
  .hint{font-size:12px;color:#6b7280}

  .s-list{list-style:none;margin:10px 0 0;padding:0;max-height:560px;overflow:auto;border-top:1px solid #eee}
  .s-row{padding:10px 6px;border-bottom:1px solid #f5f5f5}
  .s-row:hover{background:#fafafa}
  .s-row .name{font-weight:600;margin-bottom:2px}
  .s-row .meta{font-size:12px;color:#6b7280;margin-bottom:6px}
  .s-row .actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap}

  .table-wrap{overflow:auto;border:1px solid #f1f5f9;border-radius:10px;margin-top:6px}
  .tbl{width:100%;border-collapse:collapse;font-size:13px}
  .tbl th,.tbl td{border-bottom:1px solid #f1f5f9;padding:8px;text-align:left;white-space:nowrap}
  .tbl .empty{color:#9ca3af}
  .msg{font-size:12px;color:#111827}
</style>

<script>
(function () {
  "use strict";

  /* Self-contained config (no window.TP_STUDENTS needed) */
  const CFG = {
    ajax: "<?php echo esc_url(admin_url('admin-ajax.php')); ?>",
    nonceS:  "<?php echo esc_js($nonce_s); ?>",
    nonceLV: "<?php echo esc_js($nonce_lv); ?>",
    nonceSUB:"<?php echo esc_js($nonce_sub); ?>",
  };

  // ---- DOM helpers
  const qs  = (s, r=document)=>r.querySelector(s);
  const qsa = (s, r=document)=>Array.from(r.querySelectorAll(s));
  const on  = (el,ev,cb)=>el && el.addEventListener(ev, cb);

  // ---- Elements
  const sList = qs(".s-list");
  const sSearch = qs(".s-search");
  const fLevel = qs(".s-filter-level");
  const fSub = qs(".s-filter-subject");
  const btnRefresh = qs(".refresh");

  const selBox = qs(".sel-student");
  const panes = qsa(".pane");
  const tabs  = qsa(".tab");

  const pName=qs(".p-name"), pEmail=qs(".p-email"), pPhone=qs(".p-phone"),
        pLevel=qs(".p-level"), pAddr=qs(".p-addr"), pGuard=qs(".p-guard"),
        pStatus=qs(".p-status"), pCreated=qs(".p-created"),
        btnToggle=qs(".toggle-status"), btnDeactMail=qs(".email-deact");

  const pw1=qs(".pw-1"), pw2=qs(".pw-2"), pwBtn=qs(".set-pass"), pwMsg=qs(".pw-msg");

  const lecFrom=qs(".lec-from"), lecTo=qs(".lec-to"), lecSub=qs(".lec-subject"),
        lecBtn=qs(".load-lectures"), lecBody=qs(".lec-body");

  const payBody=qs(".pay-body");

  const exLFrom=qs(".ex-lec-from"), exLTo=qs(".ex-lec-to"), exLPBtn=qs(".export-lec");
  const exPFrom=qs(".ex-pay-from"), exPTo=qs(".ex-pay-to"), exPBtn=qs(".export-pay");

  let currentStudent = null;

  // ---- Utils
  function esc(s){ return String(s).replace(/[&<>"']/g, m=>({ "&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;" }[m])); }

  async function post(data){
    try{
      const fd = new FormData();
      Object.entries(data).forEach(([k,v])=>fd.append(k,v));
      const res = await fetch(CFG.ajax, { method:"POST", body:fd });
      if(!res.ok){ throw new Error("HTTP "+res.status); }
      return await res.json();
    }catch(e){
      console.error("AJAX error:", data.action, e);
      return { success:false, data:String(e) };
    }
  }

  function setPane(key){
    panes.forEach(p=>p.classList.remove("show"));
    qs(`#pane-${key}`)?.classList.add("show");
    tabs.forEach(t=>t.classList.toggle("active", t.dataset.pane===key));
    if(key==="payments") loadPayments();
    if(key==="lectures") loadLectures();
  }

  // ---- Filters (Levels + Subjects)
  async function fillFilters(){
    // Levels
    const jl = await post({ action:"tp_adm_lv_list", _ajax_nonce:CFG.nonceLV, q:"" });
    fLevel.innerHTML = '<option value="">All Levels</option>';
    if(jl && jl.success && jl.data.items){
      jl.data.items.forEach(it=>{
        const op=document.createElement("option");
        op.value=it.id; op.textContent=it.name;
        fLevel.appendChild(op);
      });
    }

    // Subjects
    const js = await post({ action:"tp_adm_sub_list_all", _ajax_nonce:CFG.nonceSUB, q:"" });
    const opts = [['', 'All Subjects']];
    if(js && js.success && js.data.items){
      js.data.items.forEach(it=>opts.push([it.subject_id, it.SubjectName]));
    }
    const html = opts.map(([v,t])=>`<option value="${esc(v)}">${esc(t)}</option>`).join("");
    fSub.innerHTML = html;
    const lecSel = qs(".lec-subject");
    if(lecSel) lecSel.innerHTML = html;
  }

  // ---- List students
  async function loadStudents(){
    sList.innerHTML = '<li class="s-row"><span class="meta">Loading…</span></li>';
    const j = await post({
      action:"tp_adm_s_list",
      _ajax_nonce:CFG.nonceS,
      q:(sSearch?.value||"").trim(),
      level_id:fLevel?.value||"",
      subject_id:fSub?.value||""
    });
    sList.innerHTML = "";
    if(!j || !j.success || !j.data.items || !j.data.items.length){
      sList.innerHTML = '<li class="s-row"><span class="meta">No students found.</span></li>'; return;
    }
    j.data.items.forEach(st=>{
      const li=document.createElement("li");
      li.className="s-row"; li.dataset.id=st.student_id;
      li.innerHTML = `
        <div class="name">${esc(st.full_name||"—")}</div>
        <div class="meta">${esc(st.email||"")} · ${esc(st.phone||"")}</div>
        <div class="actions">
          <span class="pill ${st.status==='active'?'on':'off'}">${st.status==='active'?'Active':'Inactive'}</span>
          <button class="btn btn-sm ghost open">Open</button>
          <button class="btn btn-sm ghost toggle" data-to="${st.status==='active'?'inactive':'active'}">${st.status==='active'?'Deactivate':'Activate'}</button>
        </div>`;
      sList.appendChild(li);
    });
  }

  // ---- Open student
  async function openStudent(id){
    const j = await post({ action:"tp_adm_s_get", _ajax_nonce:CFG.nonceS, student_id:id });
    if(!j || !j.success){ alert("Failed to load student."); return; }
    const s=j.data;
    currentStudent = { id:s.student_id, name:s.full_name, email:s.email };
    selBox.textContent = `${s.full_name} (ID ${s.student_id})`;
    pName.textContent=s.full_name||"—";
    pEmail.textContent=s.email||"—";
    pPhone.textContent=s.phone||"—";
    pLevel.textContent=s.level_name||s.class_level||"—";
    pAddr.textContent=s.address||"—";
    pGuard.textContent=s.guardian_name||"—";
    pCreated.textContent=s.created_at||"—";
    pStatus.textContent=(s.status==='active'?'Active':'Inactive');
    pStatus.className='p-status pill '+(s.status==='active'?'on':'off');
    btnToggle?.setAttribute('data-to', s.status==='active'?'inactive':'active');

    const today=new Date().toISOString().slice(0,10);
    const week =new Date(Date.now()+6*86400000).toISOString().slice(0,10);
    if(lecFrom) lecFrom.value=today;
    if(lecTo)   lecTo.value=week;

    setPane('profile');
  }

  // ---- Actions
  async function toggleStatus(){
    if(!currentStudent) return;
    const to = btnToggle.getAttribute('data-to')||'inactive';
    const j = await post({ action:"tp_adm_s_toggle", _ajax_nonce:CFG.nonceS, student_id:currentStudent.id, to });
    if(j && j.success){ openStudent(currentStudent.id); loadStudents(); }
    else alert((j&&j.data)?String(j.data):'Failed to toggle');
  }

  async function sendDeactEmail(){
    if(!currentStudent) return;
    const j = await post({ action:"tp_adm_s_email_deact", _ajax_nonce:CFG.nonceS, student_id:currentStudent.id });
    alert((j&&j.success)?'Email queued/sent.':((j&&j.data)?String(j.data):'Failed to send email'));
  }

  async function updatePassword(){
    if(!currentStudent) return;
    const a=(pw1?.value||"").trim(), b=(pw2?.value||"").trim();
    if(a.length<8){ pwMsg.textContent='Password must be at least 8 characters.'; return; }
    if(a!==b){ pwMsg.textContent='Passwords do not match.'; return; }
    pwBtn.disabled=true; pwMsg.textContent='Updating…';
    const j = await post({ action:"tp_adm_s_set_pass", _ajax_nonce:CFG.nonceS, student_id:currentStudent.id, new_password:a });
    pwBtn.disabled=false;
    pwMsg.textContent = (j&&j.success)?'Password updated.':((j&&j.data)?String(j.data):'Failed.');
    if(j&&j.success){ pw1.value=''; pw2.value=''; }
  }

  // ---- Lectures
  async function loadLectures(){
    if(!currentStudent) return;
    const j = await post({
      action:"tp_adm_s_lectures", _ajax_nonce:CFG.nonceS, student_id:currentStudent.id,
      from:lecFrom?.value||"", to:lecTo?.value||"", subject_id:lecSub?.value||""
    });
    lecBody.innerHTML='';
    if(!j||!j.success){ lecBody.innerHTML='<tr><td colspan="7">Failed.</td></tr>'; return; }
    const items=j.data.items||[];
    if(!items.length){ lecBody.innerHTML='<tr><td colspan="7" class="empty">—</td></tr>'; return; }
    items.forEach(r=>{
      const tr=document.createElement('tr');
      tr.innerHTML=`<td>${r.lecture_book_id}</td><td>${r.lecture_book_date||''}</td><td>${r.lecture_time||''}</td><td>${esc(r.topic||'')}</td><td>${esc(r.SubjectName||'')}</td><td>${r.duration||''}</td><td>${esc(r.status||'')}</td>`;
      lecBody.appendChild(tr);
    });
  }

  // ---- Payments
  async function loadPayments(){
    if(!currentStudent) return;
    const j = await post({ action:"tp_adm_s_payments", _ajax_nonce:CFG.nonceS, student_id:currentStudent.id });
    payBody.innerHTML='';
    if(!j||!j.success){ payBody.innerHTML='<tr><td colspan="7">Failed.</td></tr>'; return; }
    const rows=j.data.items||[];
    if(!rows.length){ payBody.innerHTML='<tr><td colspan="7" class="empty">—</td></tr>'; return; }
    rows.forEach(r=>{
      const tr=document.createElement('tr');
      tr.innerHTML=`<td>${r.lecture_book_id}</td><td>${r.lecture_book_date||''}</td><td>${esc(r.SubjectName||'')}</td><td>${Number(r.original_price||0).toFixed(2)}</td><td>${Number(r.discount_rate||0)}%</td><td>${Number(r.final_price||0).toFixed(2)}</td><td>${r.is_paid?'Yes':'No'}</td>`;
      payBody.appendChild(tr);
    });
  }

  // ---- Export (CSV)
  function triggerExport(type, from, to){
    if(!currentStudent){ alert('Select a student first'); return; }
    const url = new URL(CFG.ajax);
    url.searchParams.set('action','tp_adm_s_export');
    url.searchParams.set('_ajax_nonce', CFG.nonceS);
    url.searchParams.set('student_id', currentStudent.id);
    url.searchParams.set('type', type);
    if(from) url.searchParams.set('from', from);
    if(to)   url.searchParams.set('to', to);
    window.open(url.toString(), '_blank');
  }

  // ---- Events
  on(btnRefresh, 'click', loadStudents);
  on(sSearch, 'input', loadStudents);
  on(fLevel,  'change', loadStudents);
  on(fSub,    'change', loadStudents);

  on(sList, 'click', e=>{
    const li=e.target.closest('.s-row'); if(!li) return;
    const id=parseInt(li.dataset.id,10);
    if(e.target.classList.contains('open')) openStudent(id);
    if(e.target.classList.contains('toggle')){
      const to=e.target.getAttribute('data-to');
      post({ action:'tp_adm_s_toggle', _ajax_nonce:CFG.nonceS, student_id:id, to }).then(j=>{
        if(j&&j.success){ loadStudents(); if(currentStudent && currentStudent.id===id) openStudent(id); }
        else alert((j&&j.data)?String(j.data):'Failed to toggle');
      });
    }
  });

  on(btnToggle, 'click', toggleStatus);
  on(btnDeactMail, 'click', sendDeactEmail);
  on(pwBtn, 'click', updatePassword);

  on(lecBtn, 'click', loadLectures);
  on(exLPBtn, 'click', ()=>triggerExport('lectures', exLFrom?.value, exLTo?.value));
  on(exPBtn, 'click', ()=>triggerExport('payments', exPFrom?.value, exPTo?.value));

  tabs.forEach(tb=> on(tb, 'click', ()=> setPane(tb.dataset.pane)));

  // ---- Init
  (async function init(){
    await fillFilters();   // populate dropdowns
    await loadStudents();  // populate list
  })();
})();
</script>


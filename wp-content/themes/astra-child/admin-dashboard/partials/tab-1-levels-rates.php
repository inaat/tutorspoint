<?php
/** Tab 1: Levels & Hourly Rates (UI) */
if (!defined('ABSPATH')) exit;
$nonce_lv = wp_create_nonce('tp_adm_lv');
$nonce_rt = wp_create_nonce('tp_adm_rt');
?>
<div class="tp-lr-wrap">
  <div class="tp-lv">
    <div class="hdr">
      <h3>Levels</h3>
      <input type="search" class="lv-search" placeholder="Search levels…">
    </div>
    <div class="lv-add">
      <input type="text" class="lv-name" placeholder="New level name">
      <button class="btn add">Add</button>
    </div>
    <ul class="lv-list" role="list"></ul>
  </div>

  <div class="tp-rt">
    <div class="hdr">
      <h3>Hourly Rates</h3>
      <div class="sel-level"><span>No level selected</span></div>
    </div>

    <div class="rt-current card">
      <div class="empty">Select a level to view current rate.</div>
    </div>

    <div class="rt-add card">
      <h4>Add / Update Rate</h4>
      <div class="grid">
        <label>Currency
          <input type="text" class="rt-currency" value="GBP" maxlength="3">
        </label>
        <label>Hourly Rate
          <input type="number" class="rt-rate" step="0.01" min="0" placeholder="e.g., 2500">
        </label>
        <label>Effective From
          <input type="date" class="rt-from" value="<?php echo esc_attr( date('Y-m-d') ); ?>">
        </label>
        <label>Notes
          <input type="text" class="rt-notes" placeholder="optional note">
        </label>
      </div>
      <button class="btn save" disabled>Save Rate</button>
      <div class="msg"></div>
    </div>

    <div class="rt-history card">
      <h4>History</h4>
      <table class="tbl">
        <thead><tr>
          <th>#</th><th>Currency</th><th>Rate</th><th>From</th><th>To</th><th>Status</th><th>Action</th>
        </tr></thead>
        <tbody class="body"><tr><td colspan="7" class="empty">—</td></tr></tbody>
      </table>
    </div>
  </div>
</div>

<style>
  .tp-lr-wrap{display:grid;grid-template-columns: 280px 1fr;gap:14px}
  @media (max-width:900px){.tp-lr-wrap{grid-template-columns:1fr}}
  .tp-lv,.tp-rt{background:#fff;border:1px solid #ddd;border-radius:8px;padding:12px}
  .tp-lv .hdr,.tp-rt .hdr{display:flex;align-items:center;justify-content:space-between;gap:10px}
  .tp-lv .lv-search{width:50%;min-width:140px}
  .tp-lv .lv-add{display:flex;gap:8px;margin:10px 0}
  .tp-lv input,.tp-rt input{border:1px solid #ccc;border-radius:6px;padding:6px 8px;font-size:13px}
  .tp-lv .btn,.tp-rt .btn{background:#111;color:#fff;border:0;border-radius:6px;padding:6px 10px;cursor:pointer;font-size:13px}
  .tp-lv .btn:disabled,.tp-rt .btn:disabled{opacity:.5;cursor:not-allowed}
  .lv-list{list-style:none;margin:10px 0 0;padding:0;max-height:420px;overflow:auto;border-top:1px solid #eee}
  .lv-list li{display:flex;align-items:center;justify-content:space-between;padding:8px 4px;border-bottom:1px solid #f2f2f2}
  .lv-list .nm{flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-right:8px}
  .lv-list .nm[contenteditable="true"]{outline:2px solid #cde8ff;border-radius:4px;padding:2px 4px}
  .tp-rt .card{border:1px solid #eee;border-radius:8px;padding:10px;margin-top:10px}
  .tp-rt .rt-current .empty{color:#777}
  .tp-rt .grid{display:grid;grid-template-columns:repeat(4,1fr);gap:8px}
  @media (max-width:900px){.tp-rt .grid{grid-template-columns:1fr 1fr}}
  @media (max-width:520px){.tp-rt .grid{grid-template-columns:1fr}}
  .tbl{width:100%;border-collapse:collapse;font-size:13px}
  .tbl th,.tbl td{border-bottom:1px solid #f0f0f0;padding:6px 4px;text-align:left}
  .badge{display:inline-block;padding:2px 6px;border-radius:10px;font-size:12px}
  .badge.on{background:#12a150;color:#fff}
  .badge.off{background:#aaa;color:#fff}
  .action a{cursor:pointer;color:#0a58ca;text-decoration:underline}
  .msg{margin-top:6px;font-size:12px}
  .sel{background:#fafafa}
</style>

<script>
(function(){
  const ajax = "<?php echo esc_url(admin_url('admin-ajax.php')); ?>";
  const nonce_lv = "<?php echo esc_js($nonce_lv); ?>";
  const nonce_rt = "<?php echo esc_js($nonce_rt); ?>";

  const lvList   = document.querySelector('.lv-list');
  const lvSearch = document.querySelector('.lv-search');
  const lvName   = document.querySelector('.lv-name');
  const lvAddBtn = document.querySelector('.lv-add .add');

  const selLevelBox = document.querySelector('.sel-level span');
  const rtCard  = document.querySelector('.rt-current .empty');
  const saveBtn = document.querySelector('.rt-add .save');
  const msgBox  = document.querySelector('.rt-add .msg');
  const tBody   = document.querySelector('.rt-history .body');

  let currentLevel = null;

  function formVal(sel){ return document.querySelector(sel).value.trim(); }

  async function post(data){
    const fd = new FormData();
    Object.entries(data).forEach(([k,v])=>fd.append(k,v));
    const res = await fetch(ajax,{method:'POST',body:fd});
    return res.json();
  }

  async function loadLevels(q=''){
    const j = await post({action:'tp_adm_lv_list', _ajax_nonce:nonce_lv, q});
    lvList.innerHTML = '';
    if(!j || !j.success || !j.data.items.length){
      lvList.innerHTML = '<li><em>No levels found.</em></li>'; return;
    }
    j.data.items.forEach(it=>{
      const li = document.createElement('li');
      li.dataset.id = it.id;
      li.innerHTML = `
        <span class="nm" title="${it.name}">${it.name}</span>
        <div class="ops">
          <button class="btn sm edit">Edit</button>
          <button class="btn sm open">Open</button>
        </div>`;
      lvList.appendChild(li);
    });
  }

  async function openLevel(id, name){
    currentLevel = {id, name};
    selLevelBox.textContent = `${name} (ID ${id})`;
    saveBtn.disabled = false;
    msgBox.textContent = '';
    document.querySelector('.rt-current').innerHTML =
      `<div class="card-body">Loading current rate…</div>`;
    tBody.innerHTML = `<tr><td colspan="7">Loading…</td></tr>`;

    const j = await post({action:'tp_adm_rt_get', _ajax_nonce:nonce_rt, level_id:id});
    if(!j || !j.success){
      document.querySelector('.rt-current').innerHTML =
        `<div class="card-body">Failed to load.</div>`;
      tBody.innerHTML = `<tr><td colspan="7">—</td></tr>`;
      return;
    }
    const rows = j.data.items || [];
    // Current = the first active with NULL effective_to or latest by date
    let current = null;
    for (const r of rows){
      if (r.status==1 && (r.effective_to===null || r.effective_to===undefined)) { current = r; break; }
    }
    if (!current && rows.length) current = rows[0];

    document.querySelector('.rt-current').innerHTML = current ? `
      <div class="card-body">
        <strong>Current:</strong>
        <span>${
        current.currency === 'GBP' ? '£' : current.currency
        } ${Number(current.hourly_rate).toLocaleString()}</span>

        <span>from ${current.effective_from}${current.effective_to?(' to '+current.effective_to):''}</span>
        <span class="badge ${current.status==1?'on':'off'}">${current.status==1?'Active':'Inactive'}</span>
        ${current.notes ? `<div><em>${current.notes}</em></div>`:''}
      </div>` : `<div class="card-body">No rate configured yet.</div>`;

    // History table
    tBody.innerHTML = '';
    if(!rows.length){ tBody.innerHTML = '<tr><td colspan="7" class="empty">—</td></tr>'; return; }
    rows.forEach(r=>{
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${r.rate_id}</td>
        <td>${r.currency === 'GBP' ? '£' : r.currency}</td>
        <td>${Number(r.hourly_rate).toLocaleString()}</td>
        <td>${r.effective_from}</td>
        <td>${r.effective_to ? r.effective_to : '—'}</td>
        <td><span class="badge ${r.status==1?'on':'off'}">${r.status==1?'Active':'Inactive'}</span></td>
        <td class="action">
          <a data-id="${r.rate_id}" data-to="${r.status==1?0:1}" class="toggle">${r.status==1?'Deactivate':'Activate'}</a>
        </td>`;
      tBody.appendChild(tr);
    });
  }

  // Events
  lvSearch.addEventListener('input', (e)=> loadLevels(e.target.value));
  lvAddBtn.addEventListener('click', async ()=>{
    const name = lvName.value.trim();
    if(!name) return;
    lvAddBtn.disabled = true;
    const j = await post({action:'tp_adm_lv_add', _ajax_nonce:nonce_lv, name});
    lvAddBtn.disabled = false;
    if(j && j.success){ lvName.value=''; loadLevels(''); }
    else { alert(j && j.data ? j.data : 'Failed'); }
  });

  lvList.addEventListener('click', async (e)=>{
    const li = e.target.closest('li'); if(!li) return;
    const id = parseInt(li.dataset.id,10);
    const nameSpan = li.querySelector('.nm');
    if(e.target.classList.contains('open')){
      openLevel(id, nameSpan.textContent);
    }
    if(e.target.classList.contains('edit')){
      nameSpan.setAttribute('contenteditable','true'); nameSpan.focus();
      const done = async ()=>{
        nameSpan.removeAttribute('contenteditable');
        const newName = nameSpan.textContent.trim();
        if(!newName) { alert('Name required'); nameSpan.textContent = nameSpan.title; return; }
        if(newName !== nameSpan.title){
          const j = await post({action:'tp_adm_lv_update', _ajax_nonce:nonce_lv, id, name:newName});
          if(!(j && j.success)){ alert('Update failed'); nameSpan.textContent = nameSpan.title; }
          else { nameSpan.title = newName; if(currentLevel && currentLevel.id===id){ selLevelBox.textContent = `${newName} (ID ${id})`; } }
        }
      };
      nameSpan.addEventListener('blur', done, {once:true});
      nameSpan.addEventListener('keydown', (k)=>{ if(k.key==='Enter'){ k.preventDefault(); nameSpan.blur(); }});
    }
  });

  document.querySelector('.rt-add .save').addEventListener('click', async ()=>{
    if(!currentLevel) return;
    const currency = formVal('.rt-currency').toUpperCase().slice(0,3)||'GBP';
    const rate     = formVal('.rt-rate');
    const from     = formVal('.rt-from');
    const notes    = formVal('.rt-notes');
    if(!rate || !from){ msgBox.textContent='Rate and Effective From are required.'; return; }
    saveBtn.disabled = true; msgBox.textContent='Saving…';
    const j = await post({
      action:'tp_adm_rt_add', _ajax_nonce:nonce_rt,
      level_id: currentLevel.id, currency, hourly_rate: rate, effective_from: from, notes
    });
    saveBtn.disabled = false;
    if(j && j.success){ msgBox.textContent='Saved.'; openLevel(currentLevel.id, currentLevel.name); }
    else { msgBox.textContent = (j && j.data)? String(j.data) : 'Failed.'; }
  });

  tBody.addEventListener('click', async (e)=>{
    const a = e.target.closest('a.toggle'); if(!a) return;
    const rid = a.getAttribute('data-id');
    const to  = a.getAttribute('data-to');
    const j = await post({action:'tp_adm_rt_toggle', _ajax_nonce:nonce_rt, rate_id:rid, to});
    if(j && j.success){ openLevel(currentLevel.id, currentLevel.name); }
    else { alert('Toggle failed'); }
  });

  // initial
  loadLevels('');
})();
</script>

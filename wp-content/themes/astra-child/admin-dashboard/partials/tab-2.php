<?php
/** Tab 2: Subjects – map subjects to levels (UI) */
if (!defined('ABSPATH')) exit;

// Nonces (reuse levels list AJAX; new for subjects)
$nonce_lv  = wp_create_nonce('tp_adm_lv');
$nonce_sub = wp_create_nonce('tp_adm_sub');
?>
<div class="tp-subjects-wrap">
  <div class="tp-lv">
    <div class="hdr">
      <h3>Levels</h3>
      <input type="search" class="lv-search" placeholder="Search levels…">
    </div>
    <ul class="lv-list" role="list"></ul>
  </div>

  <div class="tp-sub">
    <div class="hdr">
      <h3>Subjects</h3>
      <div class="sel-level"><span>No level selected</span></div>
    </div>

    <!-- Current mappings -->
    <div class="card mapped">
      <h4>Subjects attached to this level</h4>
      <div class="chips"></div>
      <div class="hint">Click × to remove a subject from this level.</div>
    </div>

    <!-- Add / search -->
    <div class="card add">
      <h4>Add / Attach Subject</h4>
      <div class="grid">
        <label>Subject name
          <input type="text" class="sub-name" placeholder="e.g., Mathematics">
        </label>
        <button class="btn attach" disabled>Attach</button>
      </div>
      <div class="msg"></div>
    </div>

    <!-- Library -->
    <div class="card lib">
      <div class="lib-hdr">
        <h4>All Subjects</h4>
        <input type="search" class="lib-search" placeholder="Search all subjects…">
      </div>
      <table class="tbl">
        <thead><tr><th>ID</th><th>Subject</th><th>Action</th></tr></thead>
        <tbody class="lib-body"><tr><td colspan="3" class="empty">—</td></tr></tbody>
      </table>
    </div>
  </div>
</div>

<style>
  .tp-subjects-wrap{display:grid;grid-template-columns: 300px 1fr;gap:14px;font-family:Roboto,Arial,sans-serif}
  @media (max-width:900px){.tp-subjects-wrap{grid-template-columns:1fr}}
  .tp-lv,.tp-sub{background:#fff;border:1px solid #ddd;border-radius:8px;padding:12px}
  .hdr{display:flex;align-items:center;justify-content:space-between;gap:10px}
  .lv-search,.lib-search{min-width:160px;border:1px solid #ccc;border-radius:6px;padding:6px 8px}
  .lv-list{list-style:none;margin:10px 0 0;padding:0;max-height:460px;overflow:auto;border-top:1px solid #eee}
  .lv-list li{display:flex;align-items:center;justify-content:space-between;padding:8px 4px;border-bottom:1px solid #f2f2f2}
  .lv-list .nm{flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-right:8px}
  .btn{background:#111;color:#fff;border:0;border-radius:6px;padding:6px 10px;cursor:pointer;font-size:13px}
  .btn[disabled]{opacity:.5;cursor:not-allowed}
  .card{border:1px solid #eee;border-radius:8px;padding:10px;margin-top:10px}
  .grid{display:grid;grid-template-columns:1fr auto;gap:8px;align-items:end}
  .sub-name{border:1px solid #ccc;border-radius:6px;padding:6px 8px;width:100%}
  .chips{display:flex;flex-wrap:wrap;gap:8px;min-height:32px}
  .chip{display:inline-flex;align-items:center;gap:6px;background:#f2f5ff;border:1px solid #dbe3ff;border-radius:20px;padding:4px 10px;font-size:13px}
  .chip .x{cursor:pointer;border:0;background:transparent;font-size:14px;line-height:1}
  .hint{color:#6c757d;font-size:12px;margin-top:6px}
  .tbl{width:100%;border-collapse:collapse;font-size:13px;margin-top:6px}
  .tbl th,.tbl td{border-bottom:1px solid #f0f0f0;padding:6px 4px;text-align:left}
  .sel-level{opacity:.75}
</style>

<script>
(function(){
  const ajax = "<?php echo esc_url(admin_url('admin-ajax.php')); ?>";
  const nonce_lv  = "<?php echo esc_js($nonce_lv); ?>";
  const nonce_sub = "<?php echo esc_js($nonce_sub); ?>";

  // Level side
  const lvList   = document.querySelector('.lv-list');
  const lvSearch = document.querySelector('.lv-search');

  // Right side
  const selLevelBox = document.querySelector('.sel-level span');
  const chipsBox    = document.querySelector('.chips');
  const addBtn      = document.querySelector('.attach');
  const nameInput   = document.querySelector('.sub-name');
  const msgBox      = document.querySelector('.add .msg');

  const libSearch = document.querySelector('.lib-search');
  const libBody   = document.querySelector('.lib-body');

  let currentLevel = null;

  // helpers
  async function post(data){
    const fd = new FormData();
    Object.entries(data).forEach(([k,v])=>fd.append(k,v));
    const res = await fetch(ajax,{method:'POST',body:fd});
    return res.json();
  }
  function esc(s){return String(s).replace(/[&<>"']/g,m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]))}

  // Load levels (reuse existing AJAX)
  async function loadLevels(q=''){
    const j = await post({action:'tp_adm_lv_list', _ajax_nonce:nonce_lv, q});
    lvList.innerHTML = '';
    if(!j || !j.success || !j.data.items.length){
      lvList.innerHTML = '<li><em>No levels found.</em></li>'; return;
    }
    j.data.items.forEach(it=>{
      const li = document.createElement('li');
      li.dataset.id = it.id;
      li.innerHTML = `<span class="nm" title="${esc(it.name)}">${esc(it.name)}</span>
                      <button class="btn open">Open</button>`;
      lvList.appendChild(li);
    });
  }

  async function openLevel(id, name){
    currentLevel = {id, name};
    selLevelBox.textContent = `${name} (ID ${id})`;
    addBtn.disabled = false;
    nameInput.value = '';
    msgBox.textContent = '';
    chipsBox.innerHTML = '<em>Loading…</em>';
    loadMappedSubjects(id);
  }

  async function loadMappedSubjects(level_id){
    const j = await post({action:'tp_adm_sub_list_for_level', _ajax_nonce:nonce_sub, level_id});
    chipsBox.innerHTML = '';
    if(!j || !j.success){ chipsBox.innerHTML = '<em>Failed to load.</em>'; return; }
    const items = j.data.items || [];
    if(!items.length){ chipsBox.innerHTML = '<em>No subjects attached yet.</em>'; }
    items.forEach(it=>{
      const div = document.createElement('div');
      div.className = 'chip';
      div.dataset.sid   = it.subject_id;
      div.dataset.mapid = it.subject_level_id;
      div.innerHTML = `<span>${esc(it.SubjectName)}</span><button class="x" title="Remove" aria-label="Remove">×</button>`;
      chipsBox.appendChild(div);
    });
  }

  async function loadLibrary(q=''){
    const j = await post({action:'tp_adm_sub_list_all', _ajax_nonce:nonce_sub, q});
    libBody.innerHTML = '';
    if(!j || !j.success){ libBody.innerHTML = '<tr><td colspan="3">Failed to load.</td></tr>'; return; }
    const items = j.data.items || [];
    if(!items.length){ libBody.innerHTML = '<tr><td colspan="3" class="empty">No subjects found.</td></tr>'; return; }
    items.forEach(it=>{
      const tr = document.createElement('tr');
      tr.innerHTML = `<td>${it.subject_id}</td><td>${esc(it.SubjectName)}</td>
        <td><button class="btn small attach-lib" data-sid="${it.subject_id}">Attach</button></td>`;
      libBody.appendChild(tr);
    });
  }

  // Events
  lvSearch.addEventListener('input', (e)=> loadLevels(e.target.value));
  lvList.addEventListener('click', (e)=>{
    const li = e.target.closest('li'); if(!li) return;
    if(e.target.classList.contains('open')){
      const id = parseInt(li.dataset.id, 10);
      const name = li.querySelector('.nm').textContent;
      openLevel(id, name);
    }
  });

  // Attach by typing a name
  addBtn.addEventListener('click', async ()=>{
    if(!currentLevel) return;
    const name = nameInput.value.trim();
    if(!name){ msgBox.textContent = 'Please enter a subject name.'; return; }
    addBtn.disabled = true; msgBox.textContent = 'Attaching…';
    const j = await post({action:'tp_adm_sub_attach', _ajax_nonce:nonce_sub, level_id: currentLevel.id, subject_name: name});
    addBtn.disabled = false;
    if(j && j.success){ msgBox.textContent = 'Attached.'; nameInput.value=''; loadMappedSubjects(currentLevel.id); loadLibrary(libSearch.value.trim()); }
    else { msgBox.textContent = (j && j.data) ? String(j.data) : 'Failed.'; }
  });

  // Remove mapping (chip ×)
  chipsBox.addEventListener('click', async (e)=>{
    const x = e.target.closest('.x'); if(!x) return;
    const chip = e.target.closest('.chip');
    const mapid = chip.getAttribute('data-mapid');
    if(!mapid) return;
    x.disabled = true;
    const j = await post({action:'tp_adm_sub_detach', _ajax_nonce:nonce_sub, subject_level_id: mapid});
    if(j && j.success){ chip.remove(); }
    else { alert('Failed to remove'); x.disabled = false; }
  });

  // Library search + attach
  libSearch.addEventListener('input', (e)=> loadLibrary(e.target.value));
  libBody.addEventListener('click', async (e)=>{
    const btn = e.target.closest('.attach-lib'); if(!btn || !currentLevel) return;
    btn.disabled = true;
    const sid = parseInt(btn.getAttribute('data-sid'),10);
    const j = await post({action:'tp_adm_sub_attach', _ajax_nonce:nonce_sub, level_id: currentLevel.id, subject_id: sid});
    if(j && j.success){ loadMappedSubjects(currentLevel.id); }
    else { alert('Attach failed'); btn.disabled = false; }
  });

  // initial loads
  loadLevels('');
  loadLibrary('');
})();
</script>


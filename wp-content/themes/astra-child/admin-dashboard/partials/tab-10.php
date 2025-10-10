<?php
/**
 * Admin Dashboard → Settings & Logs → Manage Categories (single bordered section)
 * Path: wp-content/themes/astra-child/admin-dashboard/partials/settings-categories.php
 */
if (!defined('ABSPATH')) exit;

if (!is_user_logged_in() || !current_user_can('manage_categories')) {
  echo '<section class="tp-settings-section"><p>You don’t have permission to manage categories.</p></section>';
  return;
}

$nonce = wp_create_nonce('tp_cat_manage');
$ajax  = esc_url(admin_url('admin-ajax.php'));
$parents = get_categories(['hide_empty' => false, 'parent' => 0]);
?>
<style>
.tp-settings-section{max-width:1100px;margin:0 auto;background:#fff;border:1px solid #e9edf3;border-radius:14px;padding:16px}
.tp-settings-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
.tp-settings-title{margin:0;font-size:18px;font-weight:700}
.tp-settings-sub{margin:4px 0 0;color:#6b7280;font-size:13px}
.tp-setting-block{padding:12px 0}
.tp-divider{height:1px;background:#f2f4f8;margin:12px -16px}
.tpc-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
@media (max-width:900px){.tpc-grid{grid-template-columns:1fr}}
.tpc-label{font-weight:600;margin-bottom:6px;display:block}
.tpc-input,.tpc-select,.tpc-textarea{width:100%;border:1px solid #e5e7eb;border-radius:10px;padding:10px}
.tpc-actions{margin-top:12px;display:flex;gap:10px;align-items:center}
.tpc-btn{background:#111827;color:#fff;border:0;border-radius:10px;padding:10px 14px;cursor:pointer}
.tpc-btn.sec{background:#f3f4f6;color:#111827}
.tpc-note{color:#6b7280;font-size:12px}
.tpc-alert{border-radius:10px;padding:10px 12px;margin:10px 0;display:none}
.tpc-alert.success{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0}
.tpc-alert.error{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
.tpc-toolbar{display:flex;gap:10px;align-items:center;margin-bottom:10px;flex-wrap:wrap}
.tpc-toolbar input,.tpc-toolbar select{border:1px solid #e5e7eb;border-radius:8px;padding:8px 10px}
.tpc-table{width:100%;border-collapse:separate;border-spacing:0 8px}
.tpc-table thead th{padding:6px 10px;color:#6b7280;font-size:13px;text-align:left}
.tpc-row{background:#fff;border:1px solid #e5e7eb;border-radius:10px}
.tpc-row td{padding:10px}
.tpc-chip{display:inline-block;padding:3px 8px;border-radius:999px;font-size:12px;border:1px solid #e5e7eb;background:#f8fafc}
.tpc-actions-col{display:flex;gap:8px;flex-wrap:wrap}

/* -- Actions (table row) : tiny, thin, horizontal, responsive -- */
.tpc-table .tpc-actions-col{
  display:flex;
  gap:6px;
  align-items:center;
  flex-wrap:wrap;            /* wrap on very small screens, still row-aligned */
  min-width:0;
}

.tpc-table .tpc-actions-col .tpc-btn{
  font-size:10px;
  font-weight:100;           /* CSS uses 100..900, not px */
  padding:4px 8px;
  line-height:1.2;
  border-radius:6px;
  flex:0 0 auto;             /* keep them compact, don’t stretch */
}

.tpc-table .tpc-actions-col .tpc-btn.sec{
  background:#f4f6f8;        /* subtle secondary */
  color:#0f172a;
  border:1px solid #e5e7eb;
}

/* the "Default" pill shown beside buttons */
.tpc-table .tpc-actions-col .tpc-chip{
  font-size:10px;
  padding:2px 6px;
  line-height:1.2;
  border-radius:999px;
}

/* tighten row spacing a bit to match the smaller controls */
.tpc-table{ border-spacing:0 6px; }
.tpc-row td{ padding:8px 10px; }

/* Extra safety on very narrow phones */
@media (max-width:480px){
  .tpc-table .tpc-actions-col{ gap:4px; }
  .tpc-table .tpc-actions-col .tpc-btn{ padding:3px 7px; }
}




</style>

<section class="tp-settings-section" id="settings-categories">
  <header class="tp-settings-head">
    <div>
      <h3 class="tp-settings-title">Settings — Categories</h3>
      <p class="tp-settings-sub">Add, rename, delete, and set the default post category.</p>
    </div>
  </header>

  <div id="tpc_alert" class="tpc-alert"></div>

  <div class="tp-setting-block">
    <h4 style="margin:0 0 8px;font-size:15px;">Add New Category</h4>
    <div class="tpc-grid">
      <div>
        <label class="tpc-label" for="tpc_name">Name *</label>
        <input class="tpc-input" id="tpc_name" placeholder="e.g., Chemistry">
      </div>
      <div>
        <label class="tpc-label" for="tpc_slug">Slug (optional)</label>
        <input class="tpc-input" id="tpc_slug" placeholder="e.g., chemistry">
      </div>
      <div>
        <label class="tpc-label" for="tpc_parent">Parent (optional)</label>
        <select class="tpc-select" id="tpc_parent">
          <option value="0">— None —</option>
          <?php foreach ($parents as $p): ?>
            <option value="<?php echo (int)$p->term_id; ?>"><?php echo esc_html($p->name); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="tpc-label" for="tpc_desc">Description (optional)</label>
        <textarea class="tpc-textarea" id="tpc_desc" rows="3" placeholder="Short description (SEO/archives)"></textarea>
      </div>
    </div>
    <div class="tpc-actions">
      <button class="tpc-btn" type="button" onclick="TPCATS.add()">Add Category</button>
      <span id="tpc_status" class="tpc-note"></span>
    </div>
  </div>

  <div class="tp-divider" role="separator"></div>

  <div class="tp-setting-block">
    <h4 style="margin:0 0 8px;font-size:15px;">Manage Categories</h4>
    <div class="tpc-toolbar">
      <input id="tpc_search" placeholder="Search categories…" oninput="TPCATS.debounceFetch()">
      <select id="tpc_parent_filter" onchange="TPCATS.fetch()">
        <option value="">All parents</option>
        <option value="0">No parent</option>
        <?php foreach ($parents as $p): ?>
          <option value="<?php echo (int)$p->term_id; ?>"><?php echo esc_html($p->name); ?></option>
        <?php endforeach; ?>
      </select>
      <button class="tpc-btn sec" type="button" onclick="TPCATS.reset()">Reset</button>
    </div>

    <table class="tpc-table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Slug</th>
          <th>Parent</th>
          <th>Posts</th>
          <th>Flags</th>
          <th style="width:280px">Actions</th>
        </tr>
      </thead>
      <tbody id="tpc_list"><tr><td colspan="6">Loading…</td></tr></tbody>
    </table>
  </div>
</section>

<script>
(() => {
  const AJAX  = "<?php echo $ajax; ?>";
  const NONCE = "<?php echo esc_js($nonce); ?>";

  async function postAjax(params){
    const res = await fetch(AJAX, {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body: new URLSearchParams(params),
      credentials:'same-origin'
    });
    const txt = await res.text();
    try { return JSON.parse(txt); } catch(e){ throw new Error(txt || (res.status + ' ' + res.statusText)); }
  }
  function showAlert(kind, msg){
    const el = document.getElementById('tpc_alert');
    el.className = 'tpc-alert ' + (kind==='error'?'error':'success');
    el.textContent = msg;
    el.style.display = 'block';
    setTimeout(()=>{ el.style.display='none'; }, 3000);
  }

  window.TPCATS = {
    t:null,
    reset(){ document.getElementById('tpc_search').value=''; document.getElementById('tpc_parent_filter').value=''; this.fetch(); },
    debounceFetch(){ clearTimeout(this.t); this.t = setTimeout(()=>this.fetch(), 300); },
    rowHTML(item){
      const actions = [];
      actions.push(`<button class="tpc-btn" type="button" onclick="TPCATS.rename(${item.id}, '${item.name.replace(/'/g, "\\'")}')">Rename</button>`);
      if (!item.is_default && !item.is_uncategorized){
        actions.push(`<button class="tpc-btn sec" type="button" onclick="TPCATS.remove(${item.id})">Delete</button>`);
      } else {
        actions.push(`<button class="tpc-btn sec" type="button" disabled title="Protected">Delete</button>`);
      }
      if (!item.is_default){
        actions.push(`<button class="tpc-btn" type="button" onclick="TPCATS.setDefault(${item.id})">Set Default</button>`);
      } else {
        actions.push(`<span class="tpc-chip">Default</span>`);
      }
      const flags = [
        item.is_default ? 'Default' : null,
        item.is_uncategorized ? 'Uncategorized' : null
      ].filter(Boolean).map(x=>`<span class="tpc-chip">${x}</span>`).join(' ');
      return `
        <tr class="tpc-row">
          <td><strong>${item.name}</strong></td>
          <td>${item.slug}</td>
          <td>${item.parent_name || '—'}</td>
          <td>${item.count}</td>
          <td>${flags || '—'}</td>
          <td class="tpc-actions-col">${actions.join(' ')}</td>
        </tr>
      `;
    },
    render(list){
      const tbody = document.getElementById('tpc_list');
      if (!list || !list.length){ tbody.innerHTML = '<tr><td colspan="6">No categories found.</td></tr>'; return; }
      tbody.innerHTML = list.map(this.rowHTML).join('');
    },
    fetch(){
      postAjax({ action:'tp_list_categories', nonce: NONCE,
        s: document.getElementById('tpc_search')?.value || '',
        parent: document.getElementById('tpc_parent_filter')?.value || '' })
      .then(res=>{ if(!res.success) throw new Error(res.data || 'Load failed'); this.render(res.data.items||[]); })
      .catch(e=>{ document.getElementById('tpc_list').innerHTML = `<tr><td colspan="6">Error: ${e.message}</td></tr>`; });
    },
    add(){
      const name   = document.getElementById('tpc_name').value.trim();
      const slug   = document.getElementById('tpc_slug').value.trim();
      const parent = document.getElementById('tpc_parent').value || '0';
      const desc   = document.getElementById('tpc_desc').value.trim();
      if (!name){ showAlert('error','Please enter a name.'); return; }
      document.getElementById('tpc_status').textContent = 'Adding…';

      postAjax({ action:'tp_add_category', nonce: NONCE, name, slug, parent, desc })
        .then(res=>{ if(!res.success) throw new Error(res.data || 'Add failed');
          showAlert('success','Category added.');
          document.getElementById('tpc_name').value='';
          document.getElementById('tpc_slug').value='';
          document.getElementById('tpc_parent').value='0';
          document.getElementById('tpc_desc').value='';
          this.fetch();
        })
        .catch(e=> showAlert('error', e.message))
        .finally(()=> document.getElementById('tpc_status').textContent='');
    },
    rename(id,current){
      const name = prompt('New category name:', current || '');
      if (!name) return;
      postAjax({ action:'tp_rename_category', nonce: NONCE, id, name })
        .then(res=>{ if(!res.success) throw new Error(res.data || 'Rename failed'); showAlert('success','Renamed.'); this.fetch(); })
        .catch(e=> showAlert('error', e.message));
    },
    remove(id){
      if (!confirm('Delete this category? Posts will be moved to the default category.')) return;
      postAjax({ action:'tp_delete_category', nonce: NONCE, id })
        .then(res=>{ if(!res.success) throw new Error(res.data || 'Delete failed'); showAlert('success','Deleted.'); this.fetch(); })
        .catch(e=> showAlert('error', e.message));
    },
    setDefault(id){
      postAjax({ action:'tp_set_default_category', nonce: NONCE, id })
        .then(res=>{ if(!res.success) throw new Error(res.data || 'Failed'); showAlert('success','Default category updated.'); this.fetch(); })
        .catch(e=> showAlert('error', e.message));
    }
  };

  // Initial load
  TPCATS.fetch();
})();
</script>


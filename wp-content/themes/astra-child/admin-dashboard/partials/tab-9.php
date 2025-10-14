<?php
/**
 * Admin Dashboard → Tab: Blog Suggestions
 * Path: wp-content/themes/astra-child/admin-dashboard/partials/blog-suggestions.php
 *
 * Renders a form to submit a "blog suggestion" (stored as a WP post in Draft with meta _tp_is_suggestion=1),
 * plus a table of existing suggestions with actions (Publish / Edit / Delete).
 */

if (!defined('ABSPATH')) { exit; }

if (!is_user_logged_in() || !current_user_can('edit_posts')) {
  echo '<div class="tpbs-wrap"><div class="tpbs-card"><p>You do not have permission to access Blog Suggestions.</p></div></div>';
  return;
}

$nonce = wp_create_nonce('tp_blog_sugg');

/** Fetch categories for dropdown */
$tpbs_cats = get_categories(['hide_empty' => false]);

/** Inline CSS (keep it contained) */
?>
<style>
.tpbs-wrap{max-width:1100px;margin:0 auto}
.tpbs-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:18px}
.tpbs-h{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
.tpbs-title{margin:0;font-size:20px}
.tpbs-row{display:flex;gap:16px;flex-wrap:wrap}
.tpbs-row > .f{flex:1 1 100%}
.tpbs-row.half > .f{flex:1 1 calc(50% - 8px)}
@media (max-width:720px){.tpbs-row.half > .f{flex-basis:100%}}
.tpbs-label{font-weight:600;margin-bottom:6px;display:block}
.tpbs-input,.tpbs-textarea,.tpbs-select{width:100%;border:1px solid #e5e7eb;border-radius:10px;padding:10px}
.tpbs-note{color:#6b7280;font-size:12px;margin-top:4px}
.tpbs-actions{margin-top:14px;display:flex;gap:10px;align-items:center}
.tpbs-btn{background:#111827;color:#fff;border:0;border-radius:10px;padding:10px 14px;cursor:pointer}
.tpbs-btn.sec{background:#f3f4f6;color:#111827}
.tpbs-btn[disabled]{opacity:.6;cursor:not-allowed}
.tpbs-alert{border-radius:10px;padding:12px 14px;margin-bottom:12px}
.tpbs-alert.success{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0}
.tpbs-alert.error{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
.tpbs-thumb-preview{margin-top:8px;max-height:140px;display:none}

.tpbs-table{width:100%;border-collapse:separate;border-spacing:0 8px;margin-top:18px}
.tpbs-table thead th{font-size:13px;color:#6b7280;text-align:left;padding:6px 10px}
.tpbs-rowcard{background:#fff;border:1px solid #e5e7eb;border-radius:12px}
.tpbs-rowcard td{padding:10px}
.tpbs-chip{display:inline-block;padding:3px 8px;border-radius:999px;font-size:12px;border:1px solid #e5e7eb;background:#f8fafc}
.tpbs-actions-col{display:flex;gap:8px;flex-wrap:wrap}
.tpbs-link{color:#0ea5e9;text-decoration:none}
.tpbs-link:hover{text-decoration:underline}
.tpbs-toolbar{display:flex;gap:10px;align-items:center;margin-bottom:12px}
.tpbs-toolbar input, .tpbs-toolbar select{border:1px solid #e5e7eb;border-radius:8px;padding:8px 10px}
</style>

<div class="tpbs-wrap">
  <div class="tpbs-card">

    <div class="tpbs-h">
      <h2 class="tpbs-title">Blog Suggestions</h2>
    </div>

    <!-- Alerts -->
    <div id="tpbs_alert" style="display:none"></div>

    <!-- Submit form -->
    <form id="tpbs_form" onsubmit="return TPBS.submit(this)">
      <input type="hidden" name="action" value="tp_submit_blog_suggestion">
      <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">
      <input type="hidden" id="tpbs_edit_id" name="edit_id" value="">

      <div class="tpbs-row">
        <div class="f">
          <label class="tpbs-label" for="tpbs_title">Title *</label>
          <input class="tpbs-input" type="text" id="tpbs_title" name="title" required maxlength="160" placeholder="Enter suggestion title">
        </div>
      </div>

      <div class="tpbs-row">
        <div class="f">
          <label class="tpbs-label">Content *</label>
          <?php
            wp_editor('', 'tpbs_content', [
              'textarea_name' => 'content',
              'media_buttons' => false,
              'teeny'         => true,
              'textarea_rows' => 12,
              'quicktags'     => true,
            ]);
          ?>
          <div class="tpbs-note">Draft your idea or full article.</div>
        </div>
      </div>

      <div class="tpbs-row half">
        <div class="f">
          <label class="tpbs-label" for="tpbs_category">Category *</label>
          <select class="tpbs-select" id="tpbs_category" name="category" required>
            <option value="">— Select —</option>
            <?php foreach ($tpbs_cats as $c): ?>
              <option value="<?php echo intval($c->term_id); ?>"><?php echo esc_html($c->name); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="f">
          <label class="tpbs-label" for="tpbs_tags">Tags (comma separated)</label>
          <input class="tpbs-input" type="text" id="tpbs_tags" name="tags" placeholder="e.g., maths, physics, gcse">
        </div>
      </div>

      <div class="tpbs-row half">
        <div class="f">
          <label class="tpbs-label" for="tpbs_featured">Featured Image (optional)</label>
          <input class="tpbs-input" type="file" id="tpbs_featured" name="featured" accept="image/*" onchange="TPBS.preview(this)">
          <img id="tpbs_preview" class="tpbs-thumb-preview" alt="Preview"/>
          <div class="tpbs-note">JPG, PNG, GIF, WEBP. Max 3 MB.</div>
        </div>

        <div class="f">
          <label class="tpbs-label" for="tpbs_excerpt">Excerpt (optional)</label>
          <textarea class="tpbs-textarea" id="tpbs_excerpt" name="excerpt" rows="4" placeholder="A short summary shown in lists."></textarea>
        </div>
      </div>

      <div class="tpbs-actions">
        <button class="tpbs-btn" type="submit" id="tpbs_submit">Save Suggestion</button>
        <button class="tpbs-btn sec" type="button" onclick="TPBS.clearForm()">Clear</button>
        <button class="tpbs-btn sec" type="button" id="tpbs_cancel_edit" onclick="TPBS.cancelEdit()" style="display:none">Cancel Edit</button>
        <span class="tpbs-note" id="tpbs_status"></span>
      </div>
    </form>
  </div>

  <div class="tpbs-card" style="margin-top:16px">
    <div class="tpbs-toolbar">
      <input type="text" id="tpbs_search" placeholder="Search title…" oninput="TPBS.debounceFetch()">
      <select id="tpbs_filter_cat" onchange="TPBS.fetchList()">
        <option value="">All Categories</option>
        <?php foreach ($tpbs_cats as $c): ?>
          <option value="<?php echo intval($c->term_id); ?>"><?php echo esc_html($c->name); ?></option>
        <?php endforeach; ?>
      </select>
      <button class="tpbs-btn sec" type="button" onclick="TPBS.resetFilters()">Reset</button>
    </div>

    <table class="tpbs-table">
      <thead>
        <tr>
          <th>Title</th>
          <th>Category</th>
          <th>Author</th>
          <th>Status</th>
          <th>Created</th>
          <th style="width:260px">Actions</th>
        </tr>
      </thead>
      <tbody id="tpbs_list"><tr><td colspan="6">Loading…</td></tr></tbody>
    </table>
  </div>
</div>

<script>
(function(){
  const AJAX = "<?php echo esc_url(admin_url('admin-ajax.php')); ?>";
  const NONCE = "<?php echo esc_js($nonce); ?>";
  const $ = (sel,ctx)=> (ctx||document).querySelector(sel);
  const $$ = (sel,ctx)=> Array.from((ctx||document).querySelectorAll(sel));

  window.TPBS = {
    debounce: null,
    preview(input){
      const file = input.files && input.files[0];
      const img = document.getElementById("tpbs_preview");
      if(!file || !img) return;
      const reader = new FileReader();
      reader.onload = e => { img.src = e.target.result; img.style.display = "block"; };
      reader.readAsDataURL(file);
    },
    clearForm(){
      $('#tpbs_form').reset();
      $('#tpbs_edit_id').value = '';
      $('#tpbs_submit').textContent = 'Save Suggestion';
      $('#tpbs_cancel_edit').style.display = 'none';
      const img = $('#tpbs_preview'); if(img){img.style.display='none'; img.src='';}
      if (window.tinymce && tinymce.get('tpbs_content')) tinymce.get('tpbs_content').setContent('');
    },
    cancelEdit(){
      TPBS.clearForm();
      TPBS.alert('success', 'Edit cancelled.');
    },
    alert(kind,html){
      const el = $('#tpbs_alert');
      el.className = 'tpbs-alert ' + (kind==='error'?'error':'success');
      el.innerHTML = html;
      el.style.display = 'block';
      setTimeout(()=>{ el.style.display='none'; }, 3500);
    },
    submit(form){
      const btn = $('#tpbs_submit'); const stat = $('#tpbs_status');
      if (btn) btn.disabled = true; if (stat) stat.textContent = 'Saving…';

      // Grab content from TinyMCE if present
      if (window.tinymce && tinymce.get('tpbs_content')) {
        const content = tinymce.get('tpbs_content').getContent();
        const ta = document.getElementById('tpbs_content');
        if (ta) ta.value = content;
      }

      const fd = new FormData(form);
      fetch(AJAX, { method:'POST', body: fd, credentials:'same-origin' })
        .then(r=>r.json())
        .then(res=>{
          if(!res || !res.success){ throw new Error(res && res.data ? res.data : 'Failed'); }
          const isEdit = fd.get('edit_id');
          TPBS.alert('success', isEdit ? 'Suggestion updated.' : 'Suggestion saved.');
          TPBS.clearForm();
          TPBS.fetchList();
        })
        .catch(err=>{ TPBS.alert('error', err.message || 'Error saving.'); })
        .finally(()=>{ if(btn) btn.disabled=false; if(stat) stat.textContent=''; });

      return false;
    },
    edit(id){
      TPBS._post('tp_get_blog_suggestion', {id})
        .then(data=>{
          $('#tpbs_edit_id').value = id;
          $('#tpbs_title').value = data.title || '';
          $('#tpbs_category').value = data.category || '';
          $('#tpbs_tags').value = data.tags || '';
          $('#tpbs_excerpt').value = data.excerpt || '';

          if (window.tinymce && tinymce.get('tpbs_content')) {
            tinymce.get('tpbs_content').setContent(data.content || '');
          } else {
            const ta = $('#tpbs_content');
            if (ta) ta.value = data.content || '';
          }

          if (data.featured_image) {
            const img = $('#tpbs_preview');
            if (img) {
              img.src = data.featured_image;
              img.style.display = 'block';
            }
          }

          $('#tpbs_submit').textContent = 'Update Suggestion';
          $('#tpbs_cancel_edit').style.display = 'inline-block';
          TPBS.alert('success', 'Editing suggestion. Update the form and click "Update Suggestion".');
          window.scrollTo({top: 0, behavior: 'smooth'});
        })
        .catch(e=> TPBS.alert('error', e));
    },
    publish(id){
      if(!confirm('Publish this suggestion?')) return;
      TPBS._post('tp_publish_blog_suggestion', {id})
        .then(()=>{ TPBS.alert('success','Published.'); TPBS.fetchList(); })
        .catch(e=> TPBS.alert('error', e));
    },
    remove(id){
      if(!confirm('Delete this suggestion?')) return;
      TPBS._post('tp_delete_blog_suggestion', {id})
        .then(()=>{ TPBS.alert('success','Deleted.'); TPBS.fetchList(); })
        .catch(e=> TPBS.alert('error', e));
    },
    fetchList(){
      const params = {
        action: 'tp_fetch_blog_suggestions',
        nonce: NONCE,
        s: $('#tpbs_search')?.value || '',
        cat: $('#tpbs_filter_cat')?.value || ''
      };
      fetch(AJAX, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams(params), credentials:'same-origin' })
        .then(r=>r.json())
        .then(res=>{
          const tbody = $('#tpbs_list');
          if(!res || !res.success){ tbody.innerHTML = '<tr><td colspan="6">Failed to load.</td></tr>'; return; }
          tbody.innerHTML = res.data && res.data.html ? res.data.html : '<tr><td colspan="6">No suggestions found.</td></tr>';
        })
        .catch(()=>{ $('#tpbs_list').innerHTML = '<tr><td colspan="6">Error.</td></tr>'; });
    },
    debounceFetch(){
      clearTimeout(TPBS.debounce);
      TPBS.debounce = setTimeout(TPBS.fetchList, 300);
    },
    resetFilters(){
      $('#tpbs_search').value=''; $('#tpbs_filter_cat').value='';
      TPBS.fetchList();
    },
    _post(action, data){
      const payload = new URLSearchParams({ action, nonce: NONCE, ...data });
      return fetch(AJAX, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: payload, credentials:'same-origin' })
        .then(r=>r.json())
        .then(res=>{
          if(!res || !res.success) throw new Error(res && res.data ? res.data : 'Request failed');
          return res.data;
        });
    }
  };

  // Initial load
  TPBS.fetchList();
})();
</script>

<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$current_user = wp_get_current_user();

/** Resolve this teacher from WP user email */
$teacher = $wpdb->get_row($wpdb->prepare(
  "SELECT teacher_id, FullName, Email, Country, Phone, WhatsappNo, intro_video_url, BankAccountNumber, BankName, object_note
   FROM wpC_teachers_main
   WHERE Email = %s
   LIMIT 1",
  $current_user->user_email
));
if (!$teacher) { echo '<p>No teacher record found for your account.</p>'; return; }

$teacher_id = (int) $teacher->teacher_id;

/** Preload qualifications for first paint */
$quals = $wpdb->get_results($wpdb->prepare(
  "SELECT qualification_id, qualification, university, year_completed, grade_or_cgpa, country_name
   FROM wpC_teacher_qualifications
   WHERE teacher_id = %d
   ORDER BY (year_completed+0) DESC, qualification_id DESC",
  $teacher_id
));

$AJAX  = admin_url('admin-ajax.php');
$NONCE = wp_create_nonce('tp_teacher_account');
?>
<div id="tp-account" class="tpacc-wrap" data-ajax="<?php echo esc_url($AJAX); ?>" data-nonce="<?php echo esc_attr($NONCE); ?>">

  <!-- PERSONAL -->
  <section class="tpacc-card">
    <header class="tpacc-card-head">
      <h3>Personal</h3>
      <button form="tpacc-personal" type="submit" class="tpacc-btn save">Save</button>
    </header>

    <form id="tpacc-personal" class="tpacc-grid" autocomplete="off">
      <input type="hidden" name="action" value="tp_update_teacher_main">
      <input type="hidden" name="nonce"  value="<?php echo esc_attr($NONCE); ?>">

      <label class="tpacc-field">
        <span>Full Name</span>
        <input type="text" value="<?php echo esc_attr($teacher->FullName); ?>" disabled>
      </label>

      <label class="tpacc-field">
        <span>Email</span>
        <input type="email" value="<?php echo esc_attr($teacher->Email); ?>" disabled>
      </label>

      <label class="tpacc-field">
        <span>Phone</span>
        <input name="Phone" type="text" value="<?php echo esc_attr($teacher->Phone); ?>">
      </label>

      <label class="tpacc-field">
        <span>WhatsApp No</span>
        <input name="WhatsappNo" type="text" value="<?php echo esc_attr($teacher->WhatsappNo); ?>">
      </label>

      <label class="tpacc-field">
        <span>Country</span>
        <input name="Country" type="text" value="<?php echo esc_attr($teacher->Country); ?>">
      </label>

      <label class="tpacc-field">
        <span>Bank Name</span>
        <input name="BankName" type="text" value="<?php echo esc_attr($teacher->BankName); ?>">
      </label>

      <label class="tpacc-field">
        <span>Bank Account Number</span>
        <input name="BankAccountNumber" type="text" value="<?php echo esc_attr($teacher->BankAccountNumber); ?>">
      </label>

      <label class="tpacc-field">
        <span>Intro Video URL</span>
        <input name="intro_video_url" type="url" value="<?php echo esc_attr($teacher->intro_video_url); ?>">
      </label>

      <label class="tpacc-field tpacc-colspan">
        <span>Notes</span>
        <textarea name="object_note" rows="3"><?php echo esc_textarea($teacher->object_note); ?></textarea>
      </label>
    </form>
  </section>

  <!-- QUALIFICATIONS -->
  <section class="tpacc-card">
    <header class="tpacc-card-head">
      <h3>Qualifications</h3>
      <button id="tpacc-add" class="tpacc-btn">+ Add qualification</button>
    </header>

    <div class="tpacc-table-wrap">
      <table class="tpacc-table" id="tpacc-quals">
        <thead>
          <tr>
            <th>Qualification</th>
            <th>University</th>
            <th>Year</th>
            <th>Grade / CGPA</th>
            <th>Country</th>
            <th style="width:140px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($quals): foreach ($quals as $q): ?>
            <tr data-id="<?php echo (int)$q->qualification_id; ?>">
              <td><input type="text"  value="<?php echo esc_attr($q->qualification); ?>"></td>
              <td><input type="text"  value="<?php echo esc_attr($q->university); ?>"></td>
              <td><input type="number" inputmode="numeric" min="1900" max="2100" value="<?php echo esc_attr($q->year_completed); ?>"></td>
              <td><input type="text"  value="<?php echo esc_attr($q->grade_or_cgpa); ?>"></td>
              <td><input type="text"  value="<?php echo esc_attr($q->country_name); ?>"></td>
              <td class="tpacc-actions">
                <button class="tpacc-btn save-row">Save</button>
                <button class="tpacc-btn ghost del-row">Delete</button>
              </td>
            </tr>
          <?php endforeach; else: ?>
            <tr class="tpacc-empty"><td colspan="6">No qualifications yet. Click “Add qualification”.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

</div>

<!-- Toast host -->
<div id="tpacc-toast" aria-live="polite" aria-atomic="true"></div>

<style>
.tpacc-wrap{display:grid;gap:16px}
.tpacc-card{background:#fff;border:1px solid #e6f2f1;border-radius:14px;box-shadow:0 6px 20px rgba(2,28,25,.06)}
.tpacc-card-head{display:flex;align-items:center;justify-content:space-between;padding:12px 14px;border-bottom:1px solid #eef5f4}
.tpacc-card-head h3{margin:0;font-size:18px;font-weight:700}
.tpacc-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;padding:12px}
.tpacc-field{display:flex;flex-direction:column;gap:6px}
.tpacc-field span{font-size:12px;color:#334155}
.tpacc-field input,.tpacc-field textarea{border:1px solid #d8e2ee;border-radius:10px;padding:8px 10px;font-size:14px}
.tpacc-colspan{grid-column:1/-1}
.tpacc-btn{border:1px solid #cfe7e4;background:#f5fbfa;color:#113d3a;padding:8px 12px;border-radius:10px;cursor:pointer}
.tpacc-btn.save{background:#0ABAB5;border-color:#0ABAB5;color:#fff}
.tpacc-btn.ghost{background:#fff}
.tpacc-btn[disabled]{opacity:.6;cursor:not-allowed}
.tpacc-table-wrap{padding:10px 12px}
.tpacc-table{width:100%;border-collapse:collapse}
.tpacc-table th,.tpacc-table td{border-bottom:1px solid #eef2f7;padding:8px}
.tpacc-table input{width:100%;border:1px solid #d8e2ee;border-radius:8px;padding:6px 8px}
.tpacc-actions{display:flex;gap:8px}
#tpacc-toast{position:fixed;right:18px;bottom:18px;display:grid;gap:8px;z-index:99999}
.tpacc-toast{background:#10b981;color:#fff;border-radius:12px;padding:10px 12px;box-shadow:0 10px 22px rgba(2,8,23,.18)}
.tpacc-toast.err{background:#ef4444}
@media(max-width:720px){.tpacc-grid{grid-template-columns:1fr}}
</style>

<script>
(() => {
  const root  = document.getElementById('tp-account');
  if (!root) return;
  const AJAX  = root.dataset.ajax;
  const NONCE = root.dataset.nonce;

  /* ---------- toasts ---------- */
  const host = document.getElementById('tpacc-toast');
  function toast(msg, err=false){ const d=document.createElement('div'); d.className='tpacc-toast'+(err?' err':''); d.textContent=msg; host.appendChild(d); setTimeout(()=>{d.style.opacity='0'; setTimeout(()=>d.remove(),220)},3500); }

  /* ---------- Personal save (AJAX) ---------- */
  const form = document.getElementById('tpacc-personal');
  form.addEventListener('submit', async (e)=>{
    e.preventDefault();
    const btn = root.querySelector('.tpacc-card-head .tpacc-btn.save');
    btn.disabled = true;

    try{
      const fd = new FormData(form);
      const res = await fetch(AJAX, { method:'POST', credentials:'same-origin', body: fd });
      const j = await res.json();
      if(!j?.success) throw new Error(j?.data?.message || 'Save failed');
      toast('Personal info saved');
    }catch(err){ toast(err.message || 'Network error', true); }
    finally{ btn.disabled = false; }
  });

  /* ---------- Qualifications table helpers ---------- */
  const table = document.getElementById('tpacc-quals').querySelector('tbody');

  document.getElementById('tpacc-add').addEventListener('click', (e)=>{
    e.preventDefault();
    table.querySelector('.tpacc-empty')?.remove();
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><input type="text"  placeholder="e.g. BSc Mathematics"></td>
      <td><input type="text"  placeholder="University"></td>
      <td><input type="number" inputmode="numeric" min="1900" max="2100" placeholder="Year"></td>
      <td><input type="text"  placeholder="Grade / CGPA"></td>
      <td><input type="text"  placeholder="Country"></td>
      <td class="tpacc-actions">
        <button class="tpacc-btn save-row">Save</button>
        <button class="tpacc-btn ghost del-row">Delete</button>
      </td>`;
    table.prepend(tr);
  });

  table.addEventListener('click', async (e)=>{
    const btn = e.target.closest('button');
    if(!btn) return;
    const tr = btn.closest('tr');

    // Collect cell values
    const cells = tr.querySelectorAll('td input');
    const payload = {
      qualification:  cells[0]?.value?.trim() || '',
      university:     cells[1]?.value?.trim() || '',
      year_completed: (cells[2]?.value || '').trim(),
      grade_or_cgpa:  cells[3]?.value?.trim() || '',
      country_name:   cells[4]?.value?.trim() || '',
    };

    if (btn.classList.contains('save-row')) {
      // create vs update
      const id = tr.dataset.id ? parseInt(tr.dataset.id,10) : 0;
      const fd = new FormData();
      fd.append('nonce', NONCE);

      if (id) {
        fd.append('action','tp_update_qualification');
        fd.append('qualification_id', id);
      } else {
        fd.append('action','tp_add_qualification');
      }

      Object.entries(payload).forEach(([k,v])=>fd.append(k,v));

      btn.disabled = true;
      try{
        const res = await fetch(AJAX, {method:'POST', credentials:'same-origin', body:fd});
        const j   = await res.json();
        if(!j?.success) throw new Error(j?.data?.message || 'Save failed');
        if (!id) tr.dataset.id = j.data.qualification_id; // set new id after insert
        toast('Qualification saved');
      }catch(err){ toast(err.message || 'Network error', true); }
      finally{ btn.disabled=false; }
    }

    if (btn.classList.contains('del-row')) {
      const id = tr.dataset.id ? parseInt(tr.dataset.id,10) : 0;
      if (!id) { tr.remove(); if(!table.children.length) table.innerHTML = '<tr class="tpacc-empty"><td colspan="6">No qualifications yet. Click “Add qualification”.</td></tr>'; return; }
      if (!confirm('Delete this qualification?')) return;

      const fd = new FormData();
      fd.append('action','tp_delete_qualification');
      fd.append('nonce', NONCE);
      fd.append('qualification_id', id);

      btn.disabled = true;
      try{
        const res = await fetch(AJAX, {method:'POST', credentials:'same-origin', body:fd});
        const j   = await res.json();
        if(!j?.success) throw new Error(j?.data?.message || 'Delete failed');
        tr.remove();
        if(!table.children.length) table.innerHTML = '<tr class="tpacc-empty"><td colspan="6">No qualifications yet. Click “Add qualification”.</td></tr>';
        toast('Qualification deleted');
      }catch(err){ toast(err.message || 'Network error', true); }
    }
  });
})();
</script>

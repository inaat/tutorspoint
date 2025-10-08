<?php
/**
 * Teacher Dashboard → Schedule tab
 * Path: wp-content/themes/astra-child/teacher-dashboard/partials/schedule-tab.php
 *
 * Relies on these AJAX actions (must exist in your includes/tp-zego-ajax.php):
 *  - tp_add_slot            (POST: day_of_week, start_time, end_time) → {success:true, data:{slot:{...}}}
 *  - tp_delete_session      (POST: slot_id)
 *  - tp_create_room         (POST: slot_id)
 *  - tp_delete_room         (POST: slot_id)
 *  - tp_set_slot_active     (POST: slot_id, active=0/1)
 */

if (!defined('ABSPATH')) { exit; }

$current_user = wp_get_current_user();
global $wpdb;

/** Resolve teacher_id from email */
$teacher = $wpdb->get_row($wpdb->prepare(
  "SELECT teacher_id, FullName FROM wpC_teachers_main WHERE Email = %s LIMIT 1",
  $current_user->user_email
));
$teacher_id = $teacher ? (int)$teacher->teacher_id : 0;

/** AUTO-RELEASE past slots (make them Available + clear room) — run BEFORE SELECT */
if ($teacher_id) {
  $now = current_time('mysql');

  // Release only materialized (dated) occurrences that have ended
  // Adjust the IN(...) set if your statuses differ, or remove it to release regardless of status.
  $wpdb->query($wpdb->prepare("
    UPDATE wpC_teacher_generated_slots
       SET status       = 'available',
           student_id   = NULL,
           room_id      = NULL,
           meeting_link = NULL,
           updated_at   = %s
     WHERE teacher_id = %d
       AND (session_date IS NOT NULL AND session_date <> '0000-00-00')
       AND TIMESTAMP(CONCAT(session_date,' ', end_time)) < %s
       AND status IN ('booked','engaged','taught','missed')
  ", current_time('mysql'), $teacher_id, $now));
}

/** Fetch slots for this teacher (grouped by day) */
$rows = $wpdb->get_results($wpdb->prepare("
  SELECT slot_id, day_of_week, session_date, start_time, end_time, status, is_active, room_id, meeting_link
  FROM wpC_teacher_generated_slots
  WHERE teacher_id = %d
  ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), start_time
", $teacher_id));

$grouped = [];
foreach ($rows as $r) {
  $grouped[$r->day_of_week][] = $r;
}

/** JS context */
$tp_nonce = wp_create_nonce('tp_zego_room');
$ajax_url = admin_url('admin-ajax.php');
?>

<div class="tp-sched">
  <h5 class="tp-h">Define Weekly Availability</h5>

  <div class="tp-form">
    <div class="tp-fcol">
      <label class="tp-lab">Day of Week</label>
      <select id="tp-day" class="tp-inp tp-sel" aria-label="Day of Week">
        <option value="">-- Select Day --</option>
        <?php foreach (['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $d): ?>
          <option value="<?= esc_attr($d) ?>"><?= esc_html($d) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="tp-fcol">
      <label class="tp-lab">Start Time</label>
      <input id="tp-start" class="tp-inp tp-time" type="time" step="60" value="12:00" aria-label="Start Time">
    </div>

    <div class="tp-fcol">
      <label class="tp-lab">End Time</label>
      <input id="tp-end" class="tp-inp tp-time" type="time" step="60" value="13:00" aria-label="End Time">
    </div>

    <div class="tp-fcol tp-fcol-btn">
      <button id="tp-save" class="tp-btn tp-btn-add" type="button" title="Save session" aria-label="Save">
        <span class="tp-plus">+</span> <span class="tp-save-text">Save</span>
      </button>
    </div>
  </div>

  <h5 class="tp-h tp-h-space">Weekly Schedule</h5>

  <?php if (!$grouped): ?>
    <p class="tp-empty">No sessions yet. Add your first slot above.</p>
  <?php else: ?>
    <div id="tp-days">
      <?php foreach ($grouped as $day => $slots): ?>
        <div class="tp-day">
          <div class="tp-day-hd">
            <button class="tp-exp" type="button" aria-expanded="true">−</button>
            <div class="tp-day-title"><?= esc_html($day) ?> <span class="tp-count">(<?= count($slots) ?> sessions)</span></div>
          </div>
          <div class="tp-list">
            <?php foreach ($slots as $s):
              $sid   = (int)$s->slot_id;
              $st    = date_i18n('g:i A', strtotime($s->start_time));
              $et    = date_i18n('g:i A', strtotime($s->end_time));
              $lab   = $st.' – '.$et;
              $on    = (int)$s->is_active === 1;

              // ---- Build vClassroom link from room_id (ignore meeting_link unless we can extract roomID) ----
              $room_id_from_db = '';
              if (!empty($s->room_id)) {
                $room_id_from_db = (string)$s->room_id;
              } elseif (!empty($s->meeting_link)) {
                // Fallback: try to extract roomID from legacy meeting_link
                $parts = wp_parse_url($s->meeting_link);
                if (!empty($parts['query'])) {
                  parse_str($parts['query'], $q);
                  if (!empty($q['roomID'])) $room_id_from_db = (string)$q['roomID'];
                }
              }
              $meet = $room_id_from_db
                ? site_url('/vclassroom/?roomID=' . rawurlencode($room_id_from_db) . '&role=teacher')
                : '';
              $hasRm = !empty($room_id_from_db);
            ?>
              <div class="tp-row" id="row-<?= $sid ?>" data-slot="<?= $sid ?>">
                <div class="tp-time"><?= esc_html($lab) ?></div>

                <div class="tp-actions" id="act-<?= $sid ?>">
                  <?php if ($on): ?>
                    <?php if ($hasRm && $meet): ?>
                      <span class="pill ok" id="pill-<?= $sid ?>">✔ Room Created</span>
                      <!-- Anchor link so Join works even if JS elsewhere fails -->
                      <a class="mini blue" href="<?= esc_url($meet) ?>">Join Room</a>
                      <button class="mini ghost" type="button" onclick="TPZ.deleteRoom(<?= $sid ?>)">Delete Room</button>
                    <?php else: ?>
                      <button class="mini green" type="button" onclick="TPZ.createRoom(<?= $sid ?>, this)">Create Room</button>
                      <button class="mini ghost" type="button" onclick="TPZ.deleteSession(<?= $sid ?>)">Delete Session</button>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="pill stop">⏸ Paused</span>
                    <button class="mini ghost" type="button" onclick="TPZ.deleteSession(<?= $sid ?>)">Delete Session</button>
                  <?php endif; ?>
                </div>

                <div class="tp-state">
                  <span class="state <?= ($s->status==='booked'?'red':'green') ?>">
                    <?= $s->status==='booked' ? 'Engaged' : 'Available' ?>
                  </span>
                  <label class="sw">
                    <input type="checkbox" <?= $on?'checked':'' ?> onchange="TPZ.toggle(<?= $sid ?>, this.checked)">
                    <i></i><span><?= $on?'Active':'Paused' ?></span>
                  </label>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Toast host -->
<div id="tp-toast-host" aria-live="polite" aria-atomic="true"></div>

<style>
/* Typography */
.tp-sched { font-family:"Saira", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; }
.tp-h { margin:14px 0 10px; font-weight:600; font-size:18px; color:#0f172a; }
.tp-h-space { margin-top:24px; }

/* Form row */
.tp-form { display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap; background:#fff; border:1px solid #e8edf3; border-radius:12px; padding:12px; }
.tp-fcol { display:flex; flex-direction:column; gap:6px; }
.tp-fcol-btn { align-self:flex-end; }
.tp-lab { font-size:12px; font-weight:400; color:#334155; }
.tp-inp { height:38px; border:1px solid #d8e2ee; background:#fff; border-radius:10px; padding:0 10px; font-size:12px; color:#0f172a; min-width:120px; }
.tp-sel { min-width:160px; }
.tp-time { width:130px; }
.tp-btn { height:38px; border-radius:12px; border:none; cursor:pointer; display:inline-flex; align-items:center; gap:8px; padding:0 14px; font-size:12px; }
.tp-btn-add { background:#0ABAB5; color:#fff; }
.tp-plus { font-weight:700; }

/* Day groups */
.tp-day { background:#fff; border:1px solid #e8edf3; border-radius:12px; margin-top:12px; }
.tp-day-hd { display:flex; align-items:center; gap:12px; padding:10px 12px; background:#f8fafc; border-bottom:1px solid #edf2f7; }
.tp-exp { width:50px; height:50px; border:1px solid #cfd8e3; background:#fff; border-radius:50%; line-height:1px; text-align:center; cursor:pointer; font-size:12px; font-weight:800; }
.tp-day-title { font-size:12px; color:#0f172a; }
.tp-count { color:#64748b; margin-left:6px; }

.tp-list { padding:8px 10px; }
.tp-row { display:flex; align-items:center; justify-content:space-between; gap:10px; border:1px solid #edf2f7; border-radius:10px; padding:10px 12px; margin:8px 0; }
.tp-time { min-width:180px; font-size:12px; color:#111827; }
.tp-actions { display:flex; gap:8px; align-items:center; }
.mini { font-size:12px; height:30px; padding:0 10px; border-radius:10px; border:1px solid transparent; cursor:pointer; }
.mini.green { background:#e9fbf7; color:#0a9d7a; border-color:#bdf3e7; }
.mini.blue { background:#e8f0ff; color:#1e4dd8; border-color:#cddcff; }
.mini.ghost { background:#fff; color:#334155; border-color:#d8e2ee; }
.mini[disabled]{ opacity:.55; cursor:not-allowed; }

.pill { font-size:12px; padding:6px 10px; border-radius:999px; }
.pill.ok   { background:#e9fbf7; color:#0a9d7a; }
.pill.stop { background:#fde1e1; color:#a41b1b; }

.tp-state { display:flex; align-items:center; gap:12px; }
.state { font-size:12px; padding:6px 10px; border-radius:999px; background:#eef2f7; color:#334155; }
.state.green{ background:#e8fbef; color:#15803d; }
.state.red  { background:#fde1e1; color:#a41b1b; }

/* Switch */
.sw { display:inline-flex; align-items:center; gap:6px; }
.sw input { display:none; }
.sw i{ width:46px; height:26px; border-radius:999px; background:#cfd6dd; position:relative; transition:.2s; border:1px solid #cbd5e1; display:inline-block; vertical-align:middle; }
.sw i:before{ content:""; position:absolute; top:2px; left:2px; width:21px; height:21px; background:#fff; border-radius:50%; transition:.2s; box-shadow:0 1px 3px rgba(0,0,0,.12); }
.sw input:checked + i{ background:#0ABAB5; }
.sw input:checked + i:before{ transform:translateX(20px); }
.sw span{ font-size:12px; color:#334155; }

/* Toast */
#tp-toast-host{ position:fixed; right:18px; bottom:18px; z-index:99999; display:grid; gap:8px; pointer-events:none; }
.tp-toast{ pointer-events:auto; background:#10b981; color:#fff; border-radius:12px; padding:10px 12px; box-shadow:0 10px 22px rgba(2,8,23,.18); font-weight:400; font-size:13px; display:flex; align-items:center; gap:10px; transition:opacity .25s, transform .25s; }
.tp-toast.error{ background:#ef4444; }
.tp-toast .t-close{ margin-left:6px; opacity:.85; cursor:pointer; font-weight:700; }
</style>

<script>
(function(){
  const AJAX  = <?= json_encode($ajax_url) ?>;
  const NONCE = <?= json_encode($tp_nonce) ?>;

  /* ---------- tiny UI helpers ---------- */
  const host = document.getElementById('tp-toast-host');
  function toast(msg, kind='success', ms=3500){
    if(!host) return alert(msg);
    const el = document.createElement('div');
    el.className = 'tp-toast' + (kind==='error' ? ' error' : '');
    el.innerHTML = `<span>${msg}</span><span class="t-close" aria-label="Close">×</span>`;
    host.appendChild(el);
    const close = () => { el.style.opacity='0'; el.style.transform='translateY(6px)'; setTimeout(()=>el.remove(), 220); };
    el.querySelector('.t-close').addEventListener('click', close);
    setTimeout(close, ms);
  }
  function H(hhmm){ // "HH:MM" or "HH:MM:SS" -> label "h:mm AM/PM"
    if(!hhmm) return '';
    const t = hhmm.split(':');
    let H = parseInt(t[0],10), M = parseInt(t[1]||'0',10);
    const am = H<12 ? 'AM' : 'PM';
    H = ((H+11)%12)+1;
    return `${H}:${String(M).padStart(2,'0')} ${am}`;
  }

  /* ---------- form behaviour ---------- */
  const daySel = document.getElementById('tp-day');
  const start  = document.getElementById('tp-start');
  const end    = document.getElementById('tp-end');
  const save   = document.getElementById('tp-save');

  function addHour(hhmm){
    const p = hhmm.split(':'); const d = new Date(2000,0,1, parseInt(p[0]||'0',10), parseInt(p[1]||'0',10));
    d.setHours(d.getHours()+1);
    return `${String(d.getHours()).padStart(2,'0')}:${String(d.getMinutes()).padStart(2,'0')}`;
  }
  start?.addEventListener('change', ()=>{ if(end) end.value = addHour(start.value||'12:00'); });

  save?.addEventListener('click', async ()=>{
    const day = (daySel?.value||'').trim();
    const st  = (start?.value||'').trim();
    const et  = (end?.value||'').trim();
    if(!day || !st || !et){ toast('Please select day and times.','error'); return; }

    const fd = new FormData();
    fd.append('action','tp_add_slot');
    fd.append('nonce', NONCE);
    fd.append('day_of_week', day);
    fd.append('start_time',  st);
    fd.append('end_time',    et);

    try{
      save.disabled = true;
      const r = await fetch(AJAX, {method:'POST', credentials:'same-origin', body:fd});
      const j = await r.json().catch(()=>null);
      if(!j || !j.success){
        toast((j && j.data && j.data.message) ? j.data.message : 'Network error while adding session','error');
        save.disabled = false; return;
      }
      const slot = j.data.slot || {};
      insertRow({
        slot_id:     slot.slot_id,
        day_of_week: slot.day_of_week || day,
        start_time:  slot.start_time  || (st+':00'),
        end_time:    slot.end_time    || (et+':00'),
        status:      slot.status      || 'available',
        is_active:   (typeof slot.is_active!=='undefined' ? slot.is_active : 1),
        time_label:  slot.time_label  || `${H(st)} – ${H(et)}`
      });
      toast(`Session created: ${slot.time_label || (H(st)+' – '+H(et))}`);
      // auto create room for convenience (ignored if it fails)
      try { await TPZ.createRoom(slot.slot_id); } catch(_){}
      save.disabled = false;
    }catch(e){
      toast('Network error while adding session','error');
      save.disabled = false;
    }
  });

  /* ---------- dynamic row builder ---------- */
  function insertRow(slot){
    let daysHost = document.getElementById('tp-days');
    if(!daysHost){
      daysHost = document.createElement('div'); daysHost.id='tp-days';
      document.querySelector('.tp-sched').appendChild(daysHost);
    }
    let group = Array.from(daysHost.querySelectorAll('.tp-day')).find(d =>
      (d.querySelector('.tp-day-title')?.textContent||'').trim().startsWith(slot.day_of_week)
    );
    if(!group){
      const wrap = document.createElement('div');
      wrap.className = 'tp-day';
      wrap.innerHTML = `
        <div class="tp-day-hd">
          <button class="tp-exp" type="button" aria-expanded="true">−</button>
          <div class="tp-day-title">${slot.day_of_week} <span class="tp-count">(1 sessions)</span></div>
        </div>
        <div class="tp-list"></div>`;
      daysHost.appendChild(wrap);
      group = wrap;
      wrap.querySelector('.tp-exp').addEventListener('click', toggleGroup);
    } else {
      const c = group.querySelector('.tp-count');
      if(c){
        const n = Number((c.textContent||'').match(/\d+/)?.[0]||0)+1;
        c.textContent = `(${n} sessions)`;
      }
    }

    const row = document.createElement('div');
    row.className = 'tp-row';
    row.id = 'row-'+slot.slot_id;
    row.dataset.slot = slot.slot_id;

    const label = slot.time_label || `${H(slot.start_time)} – ${H(slot.end_time)}`;

    row.innerHTML = `
      <div class="tp-time">${label}</div>
      <div class="tp-actions" id="act-${slot.slot_id}">
        <button class="mini green" type="button" onclick="TPZ.createRoom(${slot.slot_id}, this)">Create Room</button>
        <button class="mini ghost" type="button" onclick="TPZ.deleteSession(${slot.slot_id})">Delete Session</button>
      </div>
      <div class="tp-state">
        <span class="state green">Available</span>
        <label class="sw">
          <input type="checkbox" checked onchange="TPZ.toggle(${slot.slot_id}, this.checked)">
          <i></i><span>Active</span>
        </label>
      </div>`;
    group.querySelector('.tp-list').appendChild(row);
  }

  function toggleGroup(ev){
    const btn = ev.currentTarget, day = btn.closest('.tp-day'), box = day.querySelector('.tp-list');
    const exp = btn.getAttribute('aria-expanded')==='true';
    btn.textContent = exp ? '+' : '−';
    btn.setAttribute('aria-expanded', exp ? 'false' : 'true');
    box.style.display = exp ? 'none' : 'block';
  }
  document.querySelectorAll('.tp-exp').forEach(b=>b.addEventListener('click',toggleGroup));

  /* ---------- TPZ controller ---------- */
  window.TPZ = {
    async createRoom(slotId, btn){
      try{
        if(btn){ btn.disabled=true; btn.textContent='Creating…'; }
        const fd = new FormData();
        fd.append('action','tp_create_room'); fd.append('nonce', NONCE); fd.append('slot_id', slotId);
        const r = await fetch(AJAX, {method:'POST', credentials:'same-origin', body:fd});
        const j = await r.json();
        if(!j?.success) throw new Error(j?.data?.message || 'Create failed');

        // Build vClassroom Join link strictly from room_id (ignore meeting_link to avoid stale URLs)
        const vBase = '<?= esc_js( site_url('/vclassroom/?roomID=') ) ?>';
        const link  = (j?.data?.room_id) ? (vBase + encodeURIComponent(j.data.room_id) + '&role=teacher') : '';

        const act = document.getElementById('act-'+slotId);
        if(act){
          act.innerHTML = `
            <span class="pill ok" id="pill-${slotId}">✔ Room Created</span>
            <a class="mini blue" href="${String(link).replace(/"/g,'&quot;')}">Join Room</a>
            <button class="mini ghost" type="button" onclick="TPZ.deleteRoom(${slotId})">Delete Room</button>`;
        }
        toast('Room created for next occurrence.');
      }catch(e){
        toast(e.message || 'Could not create room','error');
        if(btn){ btn.disabled=false; btn.textContent='Create Room'; }
      }
    },
    async deleteRoom(slotId){
      if(!confirm('Delete the room for this slot?')) return;
      try{
        const fd = new FormData();
        fd.append('action','tp_delete_room'); fd.append('nonce', NONCE); fd.append('slot_id', slotId);
        const r = await fetch(AJAX, {method:'POST', credentials:'same-origin', body:fd});
        const j = await r.json();
        if(!j?.success) throw new Error(j?.data?.message || 'Delete failed');
        const act = document.getElementById('act-'+slotId);
        if(act){
          act.innerHTML = `
            <button class="mini green" type="button" onclick="TPZ.createRoom(${slotId}, this)">Create Room</button>
            <button class="mini ghost" type="button" onclick="TPZ.deleteSession(${slotId})">Delete Session</button>`;
        }
        toast('Room deleted.');
      }catch(e){ toast(e.message || 'Could not delete room','error'); }
    },
    async deleteSession(slotId){
      if(!confirm('Delete this session slot?')) return;
      try{
        const fd = new FormData();
        fd.append('action','tp_delete_session'); fd.append('nonce', NONCE); fd.append('slot_id', slotId);
        const r = await fetch(AJAX, {method:'POST', credentials:'same-origin', body:fd});
        const j = await r.json();
        if(!j?.success) throw new Error(j?.data?.message || 'Delete failed');
        document.getElementById('row-'+slotId)?.remove();
        toast('Session deleted.');
      }catch(e){ toast(e.message || 'Could not delete session','error'); }
    },
    async toggle(slotId, on){
      try{
        const fd = new FormData();
        fd.append('action','tp_set_slot_active'); fd.append('nonce', NONCE);
        fd.append('slot_id', slotId); fd.append('active', on?1:0);
        const r = await fetch(AJAX, {method:'POST', credentials:'same-origin', body:fd});
        const j = await r.json();
        if(!j?.success) throw new Error(j?.data?.message || 'Toggle failed');

        const act = document.getElementById('act-'+slotId);
        const sw  = document.querySelector(`#row-${slotId} .sw span`);
        if(on){
          if(act){
            act.innerHTML = `
              <button class="mini green" type="button" onclick="TPZ.createRoom(${slotId}, this)">Create Room</button>
              <button class="mini ghost" type="button" onclick="TPZ.deleteSession(${slotId})">Delete Session</button>`;
          }
          if(sw) sw.textContent = 'Active';
          toast('Slot activated.');
          // NEW: auto-create room if this active slot has none yet
          const row = document.getElementById('row-' + slotId);
          const hasRoom = !!row?.querySelector('.pill.ok') || !!row?.querySelector('.mini.blue');
          if (!hasRoom) {
            const btn = row?.querySelector('.mini.green');
            TPZ.createRoom(slotId, btn || undefined).catch(()=>{});
          }
        }else{
          if(act){
            act.innerHTML = `
              <span class="pill stop">⏸ Paused</span>
              <button class="mini ghost" type="button" onclick="TPZ.deleteSession(${slotId})">Delete Session</button>`;
          }
          if(sw) sw.textContent = 'Paused';
          toast('Slot paused.');
        }
      }catch(e){
        toast(e.message || 'Toggle failed','error');
        const box = document.querySelector(`#row-${slotId} .sw input`);
        if(box) box.checked = !on;
      }
    }
    // NOTE: no join() method needed any more; anchors handle navigation.
  };

  /* ---------- Auto-create room for active slots with no room (on page load) ---------- */
  try {
    setTimeout(() => {
      document.querySelectorAll('.tp-row').forEach(row => {
        const sid = parseInt(row.dataset.slot || '0', 10);
        if (!sid) return;
        const isActive = !!row.querySelector('.sw input:checked');
        const hasRoom  = !!row.querySelector('.pill.ok') || !!row.querySelector('.mini.blue');
        const canCreateBtn = row.querySelector('.mini.green'); // "Create Room" button if present
        if (isActive && !hasRoom) {
          TPZ.createRoom(sid, canCreateBtn || undefined).catch(()=>{});
        }
      });
    }, 200);
  } catch(_) {}
})();
</script>

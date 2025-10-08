<?php
/**
 * Template Name: LiveClassRoom (Zego)
 */
if (!defined('ABSPATH')) { exit; }
if (!is_user_logged_in()) { auth_redirect(); }

global $wpdb;
$current_user = wp_get_current_user();

$room_id = isset($_GET['roomID']) ? sanitize_text_field($_GET['roomID']) : '';
$role_in = isset($_GET['role']) ? sanitize_text_field($_GET['role']) : 'student';
$role    = (strtolower($role_in) === 'teacher') ? 'teacher' : 'student';
$tpDev   = isset($_GET['tpDev']) ? (int)$_GET['tpDev'] : 0;

$app_id  = defined('ZEGO_APP_ID') ? (int) ZEGO_APP_ID : 0;

/* --- Pretty display label --- */
$user_name    = $current_user->display_name ?: $current_user->user_login;
$display_name = $user_name;
$level = $subject = $topic = '';

$teacher_row = $wpdb->get_row($wpdb->prepare(
  "SELECT * FROM wpC_teachers_main WHERE Email = %s LIMIT 1",
  $current_user->user_email
));
if ($teacher_row) {
  if (!empty($teacher_row->Name))              $display_name = $teacher_row->Name;
  elseif (!empty($teacher_row->FullName))      $display_name = $teacher_row->FullName;
  elseif (!empty($teacher_row->FirstName) || !empty($teacher_row->LastName)) {
    $display_name = trim(($teacher_row->FirstName ?? '').' '.($teacher_row->LastName ?? ''));
  }
}

/* --- Extract slotId and occurrence date from roomID --- */
$tz = wp_timezone();
$slot_id   = 0;
$occ_date  = null; // DateTime

if (preg_match('/^slot_(\d+)_([0-9]{8})/i', $room_id, $m)) {
  $slot_id  = (int)$m[1];
  $occ_date = DateTime::createFromFormat('Ymd', $m[2], $tz) ?: null;
} elseif (preg_match('/^slot_(\d+)/i', $room_id, $m)) {
  $slot_id = (int)$m[1];
}

/* --- Load slot times (for time gating) --- */
$slot_meta = null;
if ($slot_id) {
  $slot_meta = $wpdb->get_row($wpdb->prepare(
    "SELECT teacher_id, session_date, start_time, end_time FROM wpC_teacher_generated_slots WHERE slot_id = %d LIMIT 1",
    $slot_id
  ));
}

/* --- Time guards (seconds) --- */
$EARLY_TEACHER = 10 * 60;  // 10 min
$EARLY_STUDENT =  2 * 60;  // 2 min
$LATE_GRACE    = 10 * 60;  // 10 min

$allow_join = true;
$guard_note = '';

$now = new DateTime('now', $tz);

/* --- Always allow teacher to proceed; show note, but don't block --- */
if ($role === 'teacher') {
  $allow_join = true;
  // $guard_note may show "You're early…" but never blocks teachers
}



/* --- Final display label --- */
$parts = array_filter([$display_name, $level, $subject, $topic], fn($v)=>$v!=='');
$composed_label = implode(' • ', $parts);

/* --- Unique / stable user IDs --- */
$base_id   = 'wp_' . (int)$current_user->ID;
$suffix    = substr( wp_hash( uniqid('', true) ), 0, 6 );
$unique_id = $base_id . '-' . $suffix;

/* --- Back URL by role --- */
$back_url = ($role === 'teacher')
  ? home_url('/tutorsdashboard/?tab=schedule')
  : home_url('/student-dashboard/?tab=free-lecture');

get_header(); ?>

<style>
  #zego-wrap{width:100vw;background:#0b1020;overflow:hidden;position:relative}
  .liveclass-topbar{position:absolute;bottom:12px;left:12px;z-index:10}
  .liveclass-back{background:#2b6dec;color:#fff;border:none;padding:8px 12px;border-radius:8px;text-decoration:none}
  .liveclass-error{color:#fff;text-align:center;padding-top:18vh;font-size:18px}
  .liveclass-msg{color:#fff;text-align:center;padding-top:12vh;font-size:16px}
  .liveclass-cta{margin-top:18px;display:flex;gap:10px;justify-content:center}
  .btn{background:#2b6dec;color:#fff;border:none;padding:8px 12px;border-radius:8px;text-decoration:none;cursor:pointer}
  .btn.gray{background:#555e6e}
  .dbg{position:absolute;top:10px;right:10px;background:#111827;color:#e5e7eb;border:1px solid #374151;border-radius:8px;padding:8px 10px;font:12px/1.4 system-ui,Arial;max-width:min(46ch,80vw);display:<?= $tpDev? 'block':'none' ?>}
  .dbg h4{margin:0 0 6px;font-size:12px}
  .dbg pre{margin:0;white-space:pre-wrap;word-break:break-word}
</style>

<div id="zego-wrap">
  <div class="liveclass-topbar">
    <a class="liveclass-back" href="<?= esc_url($back_url) ?>">← Back to Dashboard</a>
  </div>

  <?php if ($tpDev): ?>
    <div class="dbg" id="tp-debug">
      <h4>tpDev debug</h4>
      <pre><?php
        $dbg = [
          'room_id' => $room_id,
          'role'    => $role,
          'now'     => $now->format('c'),
          'slot'    => $slot_meta ? [
            'session_date' => $slot_meta->session_date,
            'start_time'   => $slot_meta->start_time,
            'end_time'     => $slot_meta->end_time,
            'teacher_id'   => $slot_meta->teacher_id
          ] : null,
          'occ_date_from_room' => $occ_date ? $occ_date->format('Y-m-d') : null,
          'allow_join' => $allow_join,
          'note'       => $guard_note
        ];
        echo esc_html(json_encode($dbg, JSON_PRETTY_PRINT));
      ?></pre>
    </div>
  <?php endif; ?>

  <?php if (empty($room_id)): ?>
    <div class="liveclass-error">Missing room ID. Please open your class from the dashboard.</div>
  <?php else: ?>
    <?php if ($allow_join): ?>
      <div id="zego-container" style="width:100%; height:100%;"></div>
      <?php if ($guard_note): ?>
        <div class="liveclass-msg"><?= esc_html($guard_note) ?></div>
      <?php endif; ?>
    <?php else: ?>
      <div class="liveclass-msg">
        <?= esc_html($guard_note ?: 'Joining is blocked for this session.') ?><br>
        <div class="liveclass-cta">
          <a class="btn" href="<?= esc_url($back_url) ?>">Back to Dashboard</a>
        </div>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php if (!empty($room_id) && $allow_join): ?>
<script>
(function () {
  const TOKEN_URL = <?= json_encode( site_url('/api/zego_token.php') ) ?>;

  const CFG = {
    appId:   <?= (int)$app_id ?>,
    roomID:  <?= json_encode($room_id) ?>,
    role:    <?= json_encode($role) ?>,
    name:    <?= json_encode($composed_label) ?>,
    baseUID: <?= json_encode('wp_' . (int)$current_user->ID) ?>,
    uniqUID: <?= json_encode('wp_' . (int)$current_user->ID . '-' . substr( wp_hash( uniqid('', true) ), 0, 6 )) ?>,
    backUrl: <?= json_encode($back_url) ?>,
    tpDev:   <?= (int)$tpDev ?>
  };

  /* ---------- layout below header ---------- */
  function sizeLiveArea() {
    const adminBar  = document.getElementById('wpadminbar');
    const header    = document.querySelector('#masthead, header.site-header, header.wp-block-template-part, header');
    const wrap      = document.getElementById('zego-wrap');
    const adminH = adminBar ? adminBar.offsetHeight : 0;
    const headH  = header   ? header.offsetHeight   : 0;
    wrap.style.marginTop = headH + 'px';
    wrap.style.height    = `calc(100vh - ${headH + adminH}px)`;
  }
  addEventListener('load', sizeLiveArea);
  addEventListener('resize', sizeLiveArea);

  /* ---------- tiny debug helper (visible in tpDev=1) ---------- */
  function dbg(label, data){
    if (!CFG.tpDev) return;
    const box = document.getElementById('tp-debug');
    if (!box) return;
    const pre = document.createElement('pre');
    pre.textContent = `[${new Date().toISOString()}] ${label}: ` + (data ? JSON.stringify(data, null, 2) : '');
    box.appendChild(pre);
  }
  
  function loadZego() {
  return new Promise((resolve, reject) => {
    // 1) Remove any existing <script> that loaded a Zego file
    document.querySelectorAll('script[src*="zego-uikit-prebuilt"]').forEach(s => {
      try { s.parentNode && s.parentNode.removeChild(s); } catch(e){}
    });

    // 2) If an old global leaked in, delete it so we can replace cleanly
    if (window.ZegoUIKitPrebuilt && ZegoUIKitPrebuilt.create && ZegoUIKitPrebuilt.create.length !== 5) {
      try { delete window.ZegoUIKitPrebuilt; } catch(e){ window.ZegoUIKitPrebuilt = undefined; }
    }

    // 3) Load a known recent version that supports create(appId, token, roomId, userId, userName)
    const s = document.createElement('script');
    // lock to a specific version; bump if needed
    s.src = 'https://unpkg.com/@zegocloud/zego-uikit-prebuilt@2.22.0/zego-uikit-prebuilt.js';
    s.onload = () => {
      try {
        if (!window.ZegoUIKitPrebuilt) return reject(new Error('Zego SDK not exposed'));
        // quick sanity log (visible if tpDev=1)
        console.log('[Zego] create.length =', ZegoUIKitPrebuilt.create.length);
        if (ZegoUIKitPrebuilt.create.length !== 5) {
          return reject(new Error('Wrong Zego build loaded (create.length='+ZegoUIKitPrebuilt.create.length+')'));
        }
        resolve();
      } catch (e) { reject(e); }
    };
    s.onerror = () => reject(new Error('Failed to load Zego CDN script'));
    document.head.appendChild(s);
  });
}

  
  
/*
  function loadZego() {
    return new Promise((resolve, reject) => {
      if (window.ZegoUIKitPrebuilt) return resolve();
      const s = document.createElement('script');
      s.src = 'https://unpkg.com/@zegocloud/zego-uikit-prebuilt/zego-uikit-prebuilt.js';
      s.onload = () => resolve();
      s.onerror = () => reject(new Error('Failed to load Zego script'));
      document.head.appendChild(s);
    });
  }
*/
  async function requestToken(uid){
    const form = new FormData();
    form.append('user_id', uid);
    form.append('room_id', CFG.roomID);
    form.append('role',    CFG.role);
    if (CFG.tpDev) form.append('tpDev', '1');

    const resp = await fetch(TOKEN_URL, { method:'POST', body:form, credentials:'include' });
    const txt  = await resp.text();
    dbg('token_raw', {status: resp.status, body: txt.slice(0, 300)});

    let data = null;
    try { data = JSON.parse(txt); } catch(_){}

    if (!resp.ok || !data || !data.ok || !data.token) {
      const msg = (data && (data.message || data.error)) || 'Token request failed';
      const err = new Error(msg); err._raw = txt; err._status = resp.status; err._uid = uid;
      throw err;
    }
    dbg('token_ok', {uid, timing:data.timing});
    return data;
  }
  
  function leaveOverlay(message){
  location.href = CFG.backUrl + (message ? ('#live-exit:' + encodeURIComponent(message)) : '');
}

  
/*
  function leaveOverlay(message, extra){
    const c = document.getElementById('zego-container');
    if (!c) return;
    c.innerHTML = `
      <div class="liveclass-msg">
        ${message || 'You left the room or were disconnected.'}
        ${extra ? '<div style="margin-top:8px;font-size:12px;opacity:.8">'+extra+'</div>' : ''}
        <div class="liveclass-cta">
          <a class="btn" href="${CFG.backUrl}">Back to Dashboard</a>
          <button class="btn gray" id="tp-rejoin">Rejoin</button>
        </div>
      </div>`;
    const r = document.getElementById('tp-rejoin');
    if (r) r.onclick = () => location.reload(); // fresh token on reload
  }

   /* 
  function buildKitToken(serverToken, uid){
    return ZegoUIKitPrebuilt.generateKitTokenForProduction(
      Number(CFG.appId),
      String(serverToken),
      String(CFG.roomID),
      String(uid),
      String(CFG.name)
    );
  }
*/
            
            
            




  async function joinWith(uid){
    dbg('join_start', {uid});
    const data = await requestToken(uid);
    await loadZego();
/*
    const kitToken = buildKitToken(data.token, uid);
    const zp = ZegoUIKitPrebuilt.create(kitToken);
*/

        // Use Token04 directly with the 5-arg create(...) signature
        const zp = ZegoUIKitPrebuilt.create(
        Number(CFG.appId),     // appID e.g. 2078429700
        String(data.token),    // Token04 from /api/zego_token.php
        String(CFG.roomID),    // roomID "slot_..."
        String(uid),           // SAME uid you posted to get token
        String(CFG.name)       // display name
        );




    window.__liveZego = zp;

    zp.joinRoom({
      container: document.getElementById('zego-container'),
      scenario: { mode: ZegoUIKitPrebuilt.OneONoneCall },
      showPreJoinView: false,
      sharedLinks: [],
      showScreenSharingButton: true,
      turnOnMicrophoneWhenJoining: true,
      turnOnCameraWhenJoining: true,
      onJoinRoom: () => { dbg('onJoinRoom'); },
     // onLeaveRoom: () => { dbg('onLeaveRoom'); leaveOverlay(); }
     
        onLeaveRoom: () => { leaveOverlay('You left the room.'); }
        // ...
        if (zp.on) {
        zp.on('error', (err)=>{ leaveOverlay(err && err.message ? err.message : 'Connection ended.'); });
        zp.on('kickedOut', ()=> { leaveOverlay('You were removed from the room.'); });
        }

     
     
    });

    try {
      // Not all builds expose .on, so guard it:
      if (zp.on) {
        zp.on('error', (err)=>{ dbg('zego_error', err); leaveOverlay('Connection ended.', err && err.message ? err.message : ''); });
        // Some builds expose connection state:
        zp.on('roomStateChanged', (state)=> dbg('roomStateChanged', state));
        zp.on('kickedOut', (reason)=> { dbg('kickedOut', reason); leaveOverlay('You were removed from the room.'); });
      }
    } catch(_) {}

    addEventListener('beforeunload', () => { try{ window.__liveZego?.destroy?.(); }catch(e){} });
  }

  (async () => {
    try {
      // Try with unique UID first (avoids duplicate collisions). If SDK rejects, retry base UID.
      try { await joinWith(CFG.uniqUID); }
      catch(e1){ dbg('join_unique_failed', {msg: e1.message, status:e1._status}); await joinWith(CFG.baseUID); }
    } catch (e) {
      console.error('[ZEGOCLOUD]', e);
      dbg('fatal', {msg:e.message, status:e._status, raw:(e._raw||'').slice(0,200)});
      leaveOverlay(e.message || 'Could not start the class.');
    }
  })();
})();
</script>

<?php endif; ?>

<?php get_footer(); ?>

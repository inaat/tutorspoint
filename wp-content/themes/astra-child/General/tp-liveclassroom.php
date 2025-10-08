<?php
if (!defined('ABSPATH')) exit;

/** Read AppID & ServerSecret from constants or options */
function tp_zego_cfg() {
  $appID = defined('TP_ZEGO_APP_ID') ? (int)TP_ZEGO_APP_ID : (int)get_option('tp_zego_app_id', 0);
  $secret = defined('TP_ZEGO_SERVER_SECRET') ? TP_ZEGO_SERVER_SECRET : (string)get_option('tp_zego_server_secret', '');
  return [$appID, $secret];
}

/** DB prefix helper */
function tp_tbl_prefix($wpdb){
  return ($wpdb->get_var("SHOW TABLES LIKE 'wpC_teacher_generated_slots'") === 'wpC_teacher_generated_slots')
    ? 'wpC_' : $wpdb->prefix;
}

/** Resolve a canonical room id: prefer ?roomID=..., else ?slot_id=... → DB.room_id */
function tp_resolve_room_id(){
  global $wpdb;
  if (!empty($_GET['roomID'])) {
    $rid = (string)$_GET['roomID'];
  } elseif (!empty($_GET['slot_id'])) {
    $slot_id = (int)$_GET['slot_id'];
    $P = tp_tbl_prefix($wpdb);
    $rid = (string)$wpdb->get_var($wpdb->prepare(
      "SELECT room_id FROM {$P}teacher_generated_slots WHERE slot_id=%d LIMIT 1", $slot_id
    ));
  } else {
    $rid = '';
  }
  // Normalize identically for everyone (avoid stray chars/spaces)
  $rid = preg_replace('/[^A-Za-z0-9_\-]/', '-', $rid);
  $rid = trim(preg_replace('/-+/', '-', $rid), '-');
  return $rid;
}

// [tp_liveclassroom]
add_shortcode('tp_liveclassroom', function () {
  list($appID, $serverSecret) = tp_zego_cfg();
  if (!$appID || !$serverSecret) {
    return '<div style="padding:12px;border:1px solid #fee;background:#fff7f7;border-radius:8px;color:#991b1b">
              ZEGO not configured: set TP_ZEGO_APP_ID and TP_ZEGO_SERVER_SECRET in wp-config.php.
            </div>';
  }

  $roomID = tp_resolve_room_id();
  if (!$roomID) {
    return '<div style="padding:12px;border:1px solid #fee;background:#fff7f7;border-radius:8px;color:#991b1b">
              Missing room. Use <code>?roomID=...</code> or <code>?slot_id=...</code> in the URL.
            </div>';
  }

  $roleQ = isset($_GET['role']) ? strtolower(sanitize_text_field($_GET['role'])) : 'student'; // teacher|student
  $user  = wp_get_current_user();
  $userID   = $user && $user->ID ? ('u'.$user->ID) : ('guest_'.wp_generate_uuid4());
  $userName = $user && $user->display_name ? $user->display_name : ($user->user_email ?: 'Guest');

  ob_start(); ?>
<div id="tp-zego-root" style="height:72vh;width:100%;background:#000;border-radius:12px;overflow:hidden"></div>
<script src="https://cdn.jsdelivr.net/npm/@zegocloud/zego-uikit-prebuilt/zego-uikit-prebuilt.js"></script>
<script>
(function(){
  const appID        = <?php echo (int)$appID; ?>;
  const serverSecret = <?php echo json_encode($serverSecret); ?>;
  const roomID       = <?php echo json_encode($roomID); ?>;
  const userID       = <?php echo json_encode($userID); ?>;
  const userName     = <?php echo json_encode($userName); ?>;
  const role         = <?php echo json_encode($roleQ); ?>;

  // For bring-up/testing. Replace with a tokenServer for production.
  //const kitToken = ZegoUIKitPrebuilt.generateKitTokenForTest(appID, serverSecret, roomID, userID, userName);
  //const zp = ZegoUIKitPrebuilt.create(kitToken);

  //const base = window.location.origin + window.location.pathname + '?roomID=' + encodeURIComponent(roomID);
/*
  zp.joinRoom({
    container: document.getElementById('tp-zego-root'),
    scenario: { mode: ZegoUIKitPrebuilt.OneONoneCall },
    turnOnCameraWhenJoining: true,
    turnOnMicrophoneWhenJoining: true,
    showScreenSharingButton: false,
    showUserList: false,
    showTextChat: true,
    sharedLinks: [
      { name: 'Student link', url: base + '&role=student' },
      { name: 'Teacher link', url: base + '&role=teacher' }
    ]
  });*/


const kitToken = ZegoUIKitPrebuilt.generateKitTokenForTest(appID, serverSecret, roomID, userID, userName);
const zp = ZegoUIKitPrebuilt.create(kitToken);

        // ⬇️ Direct join, no pre-join screen
        zp.joinRoom({
        container: document.getElementById('zego-container'),
        scenario: { mode: ZegoUIKitPrebuilt.OneONoneCall },
        
        // IMPORTANT: skip pre-join UI
        showPreJoinView: false,
        
        // Optional quality-of-life defaults (turn on devices as you prefer)
        turnOnCameraWhenJoining: true,
        turnOnMicrophoneWhenJoining: true,
        
        // (optional) keep the UI clean
        showLeavingView: true,
        showJoinRoomTips: false,
        showRoomTimer: false,
        });


})();


</script>
<?php
  return ob_get_clean();
});

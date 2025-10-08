<?php
/**
 * Shortcode: [zego_classroom]
 * Usage:
 *   Place on a WP page (e.g. /join-classroom/)
 *   Pass URL params: ?roomID=slot_123&userID=teacher_1&userName=Aashir&role=host
 *   - role = "host" (teacher) or "participant" (student)
 */
add_shortcode('zego_classroom', function () {
  // Read query params with sensible fallbacks
  $roomID   = isset($_GET['roomID'])   ? sanitize_text_field($_GET['roomID'])   : 'demo_room';
  $userID   = isset($GET['userID'])   ? sanitize_text_field($_GET['userID'])   : 'guest' . wp_generate_uuid4();
  $userName = isset($_GET['userName']) ? sanitize_text_field($_GET['userName']) : 'Guest';
  $role     = isset($_GET['role'])     ? sanitize_text_field($_GET['role'])     : 'participant';

  // AppID comes from wp-config.php constants
  $appId = defined('ZEGO_APP_ID') ? (int) ZEGO_APP_ID : 0;

  ob_start(); ?>
  <div id="zego-container" style="width:100%;height:calc(100vh - 120px);max-width:1200px;margin:20px auto;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;"></div>

  <!-- Zego UIKit Prebuilt -->
  <script src="https://unpkg.com/@zegocloud/zego-uikit-prebuilt/zego-uikit-prebuilt.js"></script>

  <script>
  (function(){
    const roomID   = <?php echo json_encode($roomID); ?>;
    const userID   = <?php echo json_encode($userID); ?>;
    const userName = <?php echo json_encode($userName); ?>;
    const role     = <?php echo json_encode($role); ?>; // "host" or "participant"
    const appID    = <?php echo (int) $appId; ?>;

    if(!appID){
      document.getElementById('zego-container').innerHTML =
        '<div style="padding:20px;color:#b91c1c;background:#fee2e2;border:1px solid #fecaca;border-radius:10px;">ZEGO_APP_ID is not defined. Please set it in wp-config.php.</div>';
      return;
    }

    async function joinClass() {
      try {
        // Ask our server for a scoped token
        const resp = await fetch(
          '/api/zego_token.php?' + new URLSearchParams({
            user_id: userID,
            room_id: roomID,
            role: role
          })
        );
        const data = await resp.json();
        if(!data || !data.token){
          throw new Error('Failed to fetch token');
        }
        const token = data.token;

        // Create UIKit client and join
        const kit = ZegoUIKitPrebuilt.create(appID, token, roomID, userID, userName);

        kit.joinRoom({
          container: document.querySelector('#zego-container'),
          // One-to-one tutoring layout; you can switch to group if ever needed
          scenario: { mode: ZegoUIKitPrebuilt.OneONoneCall },
          showScreenSharingButton: true,
          onJoinRoom: () => { /* you can log "joined" here */ },
          onLeaveRoom: () => { /* cleanup or redirect */ },
        });
      } catch (e) {
        document.getElementById('zego-container').innerHTML =
          '<div style="padding:20px;color:#b91c1c;background:#fee2e2;border:1px solid #fecaca;border-radius:10px;">'+
          'Failed to start class: ' + (e?.message || e) + '</div>';
      }
    }

    joinClass();
  })();
  </script>
  <?php
  return ob_get_clean();
});
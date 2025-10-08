<?php
// File: wp-content/themes/astra-child/includes/zegocloud-integration.php

add_action('wp_enqueue_scripts', function () {
  // Load Zego UI Kit from CDN only on pages that contain our shortcode
  // Using 'wp_print_scripts' trick to keep it simple here:
  if (is_singular() && has_shortcode(get_post_field('post_content', get_the_ID()), 'zego_classroom')) {
    wp_enqueue_script(
      'zego-uikit-prebuilt',
      'https://unpkg.com/@zegocloud/zego-uikit-prebuilt/zego-uikit-prebuilt.js',
      [],
      null,
      true
    );
  }
});

add_shortcode('zego_classroom', function ($atts) {
  /*
    Usage examples:
    /liveclassroom/?roomID=slot_123&role=teacher
    /liveclassroom/?lecture_id=7               (roomID auto-derived)
  */
  ob_start();
  if (!is_user_logged_in()) {
    echo '<p>Please log in to join your class.</p>';
    return ob_get_clean();
  }

  $current_user = wp_get_current_user();
  $user_id   = 'wp_' . $current_user->ID; // safe unique
  $user_name = $current_user->display_name ?: $current_user->user_login;
  $role = isset($_GET['role']) && $_GET['role'] === 'teacher' ? 'teacher' : 'student';

  global $wpdb;

  // Resolve roomID
  $room_id = isset($_GET['roomID']) ? sanitize_text_field($_GET['roomID']) : '';
  $lecture_id = isset($_GET['lecture_id']) ? intval($_GET['lecture_id']) : 0;

  if (!$room_id && $lecture_id > 0) {
    // Derive a stable room from booked lecture (e.g., "lec_{id}")
    // Your table exists with lecture_book_id & (student_id, teacher_id, date/time, etc.)
    // We'll make room deterministic: lec_{lecture_book_id}
    $exists = $wpdb->get_var($wpdb->prepare(
      "SELECT lecture_book_id FROM wpC_student_lectures WHERE lecture_book_id=%d",
      $lecture_id
    ));
    if ($exists) {
      $room_id = 'lec_' . $lecture_id;
    }
  }

  if (!$room_id) {
    echo '<p>Missing roomID/lecture_id.</p>';
    return ob_get_clean();
  }

  // Page HTML
  ?>
  <div id="zego-container" style="width:100%;height:80vh;border-radius:12px;overflow:hidden;"></div>

  <script>
  (async () => {
    // Wait until CDN script is loaded
    function zegoLoaded() { return typeof window.ZegoUIKitPrebuilt !== 'undefined'; }
    for (let i=0;i<40 && !zegoLoaded();i++) { await new Promise(r=>setTimeout(r, 150)); }
    if (!zegoLoaded()) {
      alert('Zego SDK failed to load. Check your network/CDN.');
      return;
    }

    const roomID  = <?php echo json_encode($room_id); ?>;
    const userID  = <?php echo json_encode($user_id); ?>;
    const userName= <?php echo json_encode($user_name); ?>;
    const role    = <?php echo json_encode($role); ?>;

    // get token from secure endpoint
    const tokenUrl = <?php echo json_encode( site_url('/api/zego_token.php') ); ?> 
                     + '?user_id=' + encodeURIComponent(userID)
                     + '&room_id=' + encodeURIComponent(roomID)
                     + '&role=' + encodeURIComponent(role);

    let tokenResp;
    try {
      const resp = await fetch(tokenUrl, { credentials: 'include' });
      tokenResp = await resp.json();
      if (!tokenResp || !tokenResp.token) throw new Error('No token');
    } catch (e) {
      alert('Could not get secure token. ' + e.message);
      return;
    }

    const { app_id, token } = tokenResp;
    const zp = window.ZegoUIKitPrebuilt.create(app_id, token, roomID, userID, userName);

    // 1-to-1 scenario (Web UI Kit)
    // Docs: https://www.zegocloud.com/docs/uikit/callkit-web/quick-start/using-html-script  (related)
    // Auth docs: https://www.zegocloud.com/docs/uikit/callkit-web/authentication-and-kit-token
    zp.joinRoom({
      container: document.querySelector('#zego-container'),
      scenario: { mode: window.ZegoUIKitPrebuilt.OneONoneCall },
      sharedLinks: [
        { name: 'Invite link', url: window.location.href }
      ],
      showScreenSharingButton: true
    });
  })();
  </script>
  <?php
  return ob_get_clean();
});

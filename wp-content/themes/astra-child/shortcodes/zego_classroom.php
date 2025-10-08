<?php
/**
 * Shortcode: [zego_classroom]
 * Usage: Place this shortcode on "LiveClassRoom" page
 */

function zego_classroom_shortcode($atts) {
    ob_start(); ?>
    
    <div id="zego-container" style="width:100%; height:90vh;"></div>

    <script src="https://unpkg.com/@zegocloud/zego-uikit-prebuilt/zego-uikit-prebuilt.js"></script>
    <script>
    async function joinClass() {
      const urlParams = new URLSearchParams(window.location.search);
      const roomID = urlParams.get("roomID") || "testroom"; 
      const userID = urlParams.get("userID") || "user_" + Math.floor(Math.random()*1000);
      const userName = urlParams.get("userName") || "Guest";

      // Fetch token from server
      const resp = await fetch("<?php echo get_stylesheet_directory_uri(); ?>/api/zego_token.php?user_id=" + userID);
      const data = await resp.json();

      const appID = data.appID;
      const token = data.token;

      const zp = ZegoUIKitPrebuilt.create(appID, token, roomID, userID, userName);
      zp.joinRoom({
        container: document.querySelector("#zego-container"),
        scenario: { mode: ZegoUIKitPrebuilt.OneONoneCall }
      });
    }
    joinClass();
    </script>

    <?php
    return ob_get_clean();
}
add_shortcode("zego_classroom", "zego_classroom_shortcode");

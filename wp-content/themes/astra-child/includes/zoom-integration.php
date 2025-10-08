<?php
/**
 * Zoom Web SDK integration
 * Place this in your theme’s inc/zoom-integration.php and then require it from functions.php
 */

// 1) Signature generator
/*

function tp_generate_zoom_signature( $apiKey, $apiSecret, $meetingNumber, $role = 0 ) {
    $time = round( microtime(true) * 1000 ) - 30000;
    $data = base64_encode( $apiKey . $meetingNumber . $time . $role );
    $hash = hash_hmac( 'sha256', $data, $apiSecret, true );
    $sig  = rtrim( strtr( base64_encode( $apiKey . '.' . $meetingNumber . '.' . $time . '.' . $role . '.' . base64_encode($hash) ), '+/', '-_' ), '=' );
    return $sig;
}

// 2) Enqueue Zoom Web SDK assets
function tp_enqueue_zoom_sdk() {
    // React & ReactDOM
    wp_enqueue_script( 'zoom-react',      'https://source.zoom.us/2.14.0/lib/vendor/react.min.js',       [], null, true );
    wp_enqueue_script( 'zoom-react-dom',  'https://source.zoom.us/2.14.0/lib/vendor/react-dom.min.js',   ['zoom-react'], null, true );
    // Zoom Meeting SDK
    wp_enqueue_script( 'zoom-sdk',        'https://source.zoom.us/2.14.0/zoom-meeting-2.14.0.min.js',    ['zoom-react-dom'], null, true );
    // Styles
    wp_enqueue_style ( 'zoom-bootstrap',  'https://source.zoom.us/2.14.0/css/bootstrap.css' );
    wp_enqueue_style ( 'zoom-styles',     'https://source.zoom.us/2.14.0/css/react-select.css' );
}
add_action( 'wp_enqueue_scripts', 'tp_enqueue_zoom_sdk' );

// 3) Shortcode to render & join a meeting
function tp_zoom_meeting_shortcode( $atts ) {
    $atts = shortcode_atts([
        'meeting_id' => '',
        'user_name'  => 'Guest',
        'role'       => 0, // 0: attendee, 1: host
    ], $atts, 'zoom_meeting' );

    // Your Zoom API credentials
    $apiKey    = 'YOUR_ZOOM_API_KEY';
    $apiSecret = 'YOUR_ZOOM_API_SECRET';
    $signature = tp_generate_zoom_signature( $apiKey, $apiSecret, $atts['meeting_id'], $atts['role'] );

    ob_start(); ?>
    <div id="zmmtg-root"></div>
    <button id="joinZoomBtn">Join Zoom Meeting</button>

    <script>
    document.getElementById('joinZoomBtn').addEventListener('click', function(){
      ZoomMtg.preLoadWasm();
      ZoomMtg.prepareJssdk();

      ZoomMtg.init({
        leaveUrl: window.location.href,
        success: function() {
          ZoomMtg.join({
            meetingNumber: '<?= esc_js( $atts['meeting_id'] ); ?>',
            userName:      '<?= esc_js( $atts['user_name'] ); ?>',
            signature:     '<?= $signature; ?>',
            apiKey:        '<?= esc_js( $apiKey ); ?>',
            passWord:      '', // set if required
            success: function()   { console.log('Zoom join success'); },
            error:   function(err){ console.error(err); }
          });
        },
        error: function(err) { console.error(err); }
      });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode( 'zoom_meeting', 'tp_zoom_meeting_shortcode' );

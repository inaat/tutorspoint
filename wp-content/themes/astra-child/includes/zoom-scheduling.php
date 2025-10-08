<?php
/**
 * Zoom Scheduling API helper
 * Requires a JWT token generated from your Zoom API Key/Secret.
 */

// 1) Generate a JWT token (valid for ~60 minutes)
/*

function tp_zoom_get_jwt() {
    $key    = ZOOM_API_KEY;    // defined in wp-config.php or options
    $secret = ZOOM_API_SECRET;
    $payload = [
      'iss' => $key,
      'exp' => time() + 3600
    ];
    return \Firebase\JWT\JWT::encode($payload, $secret, 'HS256');
}

// 2) Schedule (create) a meeting
function tp_schedule_zoom_meeting( $topic, $start_time, $duration = 60 ) {
    $jwt = tp_zoom_get_jwt();

    $body = [
      'topic'      => $topic,
      'type'       => 2,  // scheduled meeting
      'start_time' => gmdate('Y-m-d\TH:i:s\Z', strtotime($start_time)), // ISO8601 UTC
      'duration'   => $duration,
      'timezone'   => get_option('timezone_string'),
      'settings'   => [
         'join_before_host' => false,
         'approval_type'    => 0,   // auto approve
      ]
    ];

    $response = wp_remote_post(
      'https://api.zoom.us/v2/users/me/meetings',
      [
        'headers' => [
          'Authorization' => "Bearer $jwt",
          'Content-Type'  => 'application/json'
        ],
        'body'    => wp_json_encode($body),
        'timeout' => 15
      ]
    );

    if ( is_wp_error($response) ) {
      return $response;
    }

    $data = json_decode( wp_remote_retrieve_body($response), true );
    if ( ! empty($data['id']) ) {
      return $data;  // contains 'id', 'join_url', 'password', etc.
    } else {
      return new WP_Error('zoom_error', 'Could not schedule meeting');
    }
}

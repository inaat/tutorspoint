<?php
add_action('init', function () {
  if (isset($_GET['zegocheck'])) {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'TP_ZEGO_APP_ID: ' . (defined('TP_ZEGO_APP_ID') ? TP_ZEGO_APP_ID : 'NOT DEFINED') . "\n";
    echo 'TP_ZEGO_SERVER_SECRET: ' . (defined('TP_ZEGO_SERVER_SECRET') ? 'DEFINED' : 'NOT DEFINED') . "\n";
    exit;
  }
});

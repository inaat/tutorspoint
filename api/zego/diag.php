<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/auto_loader.php';
$base = __DIR__ . '/src/ZEGO/';
$files = ['ZegoServerAssistant.php','ZegoAssistantToken.php','ZegoErrorCodes.php'];
$out = [];
foreach ($files as $f) {
  $p = $base . $f;
  $out[$f] = ['exists'=>file_exists($p),'size'=>file_exists($p)?filesize($p):0];
}
$out['class_exists'] = [
  'ZEGO\\ZegoServerAssistant' => class_exists('ZEGO\\ZegoServerAssistant'),
  'ZEGO\\ZegoAssistantToken'  => class_exists('ZEGO\\ZegoAssistantToken'),
];
echo json_encode($out, JSON_PRETTY_PRINT);

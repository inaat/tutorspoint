<?php
// Minimal manual load (no composer)
require_once __DIR__ . '/src/ZEGO/ZegoServerAssistant.php';
require_once __DIR__ . '/src/ZEGO/ZegoErrorCodes.php';

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok' => class_exists('\\ZEGO\\ZegoServerAssistant')]);

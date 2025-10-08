<?php
spl_autoload_register(function ($class) {
  if (strpos($class, 'ZEGO\\') !== 0) return;
  $rel  = substr($class, 5); // strip "ZEGO\"
  $file = __DIR__ . '/src/ZEGO/' . str_replace('\\', '/', $rel) . '.php';
  if (is_file($file)) require_once $file;
});

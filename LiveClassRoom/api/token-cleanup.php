<?php
// Runs hourly via cron
$tokens = json_decode(file_get_contents('tokens.json'), true);
$now = time();

foreach ($tokens as $token => $expiry) {
    if ($expiry < $now) {
        unset($tokens[$token]);
    }
}

file_put_contents('tokens.json', json_encode($tokens));
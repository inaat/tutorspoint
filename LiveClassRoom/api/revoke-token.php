<?php
// Store in database or Redis
$blacklist = [];

$token = $_POST['token'] ?? '';
if ($token) {
    $blacklist[$token] = time() + (24 * 3600); // Blacklist for 24h
    file_put_contents('blacklist.json', json_encode($blacklist));
    echo json_encode(['success' => true]);
}
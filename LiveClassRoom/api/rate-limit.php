<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../vendor/autoload.php';

use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\FilesystemStorage;

// 1. Setup Rate Limiter (5 requests per hour per IP)
$storage = new FilesystemStorage(__DIR__.'/../../rate_limits');
$config = [
    'id' => 'student_tokens',
    'policy' => 'token_bucket',
    'limit' => 5,
    'rate' => ['interval' => '1 hour']
];
$factory = new RateLimiterFactory($config, $storage);
$limiter = $factory->create($_SERVER['REMOTE_ADDR']);

// 2. Check Limit
if (!$limiter->consume()->isAccepted()) {
    http_response_code(429);
    die(json_encode(['error' => 'Too many requests. Try again later.']));
}

// Proceed with token generation...
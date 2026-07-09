<?php
// Test what headers Facebook receives
header('Content-Type: text/plain; charset=utf-8');
echo "User-Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'NOT SET') . "\n";
echo "HTTPS: " . ($_SERVER['HTTPS'] ?? 'NOT SET') . "\n";
echo "X-Forwarded-Proto: " . ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'NOT SET') . "\n";
echo "Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'NOT SET') . "\n";
echo "Response Code: 200 OK\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
?>

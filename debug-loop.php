<?php
session_start();
header('Content-Type: text/plain');
echo "DEBUG LOOP - " . date('Y-m-d H:i:s') . "\n";
echo "URL: " . $_SERVER['REQUEST_URI'] . "\n";
echo "User Type: " . ($_SESSION['user_type'] ?? 'NULL') . "\n";
echo "Store ID: " . ($_SESSION['store_id'] ?? 'NULL') . "\n";
echo "Headers: " . print_r(headers_list(), true) . "\n";
?>
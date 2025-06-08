<?php
// test-session.php
session_start();

header('Content-Type: application/json');

echo json_encode([
    'session_active' => session_status() === PHP_SESSION_ACTIVE,
    'session_id' => session_id(),
    'session_data' => $_SESSION ?? [],
    'user_logged' => isset($_SESSION['user_id']),
    'user_type' => $_SESSION['user_type'] ?? 'não definido',
    'user_id' => $_SESSION['user_id'] ?? 'não definido',
    'cookies' => $_COOKIE,
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
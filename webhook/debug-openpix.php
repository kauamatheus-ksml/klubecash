<?php
// webhook/debug-openpix.php
$logFile = __DIR__ . '/../logs/openpix-webhook.log';
$input = file_get_contents('php://input');
$headers = getallheaders();

$logData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'headers' => $headers,
    'body' => $input,
    'decoded' => json_decode($input, true)
];

file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND);

echo json_encode(['status' => 'logged']);
?>
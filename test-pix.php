<?php
require_once 'config/constants.php';

$testData = [
    'value' => 100, // R$ 1,00
    'comment' => 'Teste Klube Cash',
    'correlationID' => 'test_' . time()
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://api.openpix.com.br/api/v1/charge',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: ' . OPENPIX_API_KEY,
        'Content-Type: application/json'
    ],
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($testData)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";
?>
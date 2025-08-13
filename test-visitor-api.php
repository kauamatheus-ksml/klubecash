<?php
// test-visitor-api.php

session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_type'] = 'loja';
$_SESSION['store_id'] = 1;

$testData = [
    'action' => 'create_visitor_client',
    'nome' => 'Cliente Teste',
    'telefone' => '11999887766',
    'store_id' => 1
];

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => 'https://klubecash.com/api/store-client-search.php',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($testData),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Cookie: ' . session_name() . '=' . session_id()
    ]
]);

$response = curl_exec($curl);
curl_close($curl);

echo "<h1>Teste da API</h1>";
echo "<h2>Resposta:</h2>";
echo "<pre>" . print_r(json_decode($response, true), true) . "</pre>";
?>
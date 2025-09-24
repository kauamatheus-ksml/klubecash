<?php
/**
 * Teste direto da API de notificação para identificar o erro HTTP 400
 */

// Simular uma requisição direta para a API
$postData = [
    'secret' => 'klube-cash-2024',
    'transaction_id' => 520 // Transação que existe no banco
];

echo "=== TESTE DIRETO DA API DE NOTIFICAÇÃO ===\n";
echo "Testando com transação ID: 520\n\n";

$url = 'https://klubecash.com/api/cashback-notificacao.php';

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($postData),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'User-Agent: Test-Direct/1.0'
    ],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_VERBOSE => true
]);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$curlError = curl_error($curl);
curl_close($curl);

echo "Código HTTP: {$httpCode}\n";
if ($curlError) {
    echo "Erro cURL: {$curlError}\n";
}
echo "Resposta: {$response}\n\n";

// Tentar decodificar JSON
$data = json_decode($response, true);
if ($data) {
    echo "Dados decodificados:\n";
    print_r($data);
} else {
    echo "Erro ao decodificar JSON: " . json_last_error_msg() . "\n";
}
?>
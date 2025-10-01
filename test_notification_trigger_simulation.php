<?php
/**
 * Simulação exata do que o NotificationTrigger está fazendo
 */

require_once __DIR__ . '/config/constants.php';

echo "=== SIMULAÇÃO NOTIFICATION TRIGGER ===\n";
echo "URL API: " . CASHBACK_NOTIFICATION_API_URL . "\n";
echo "Secret: " . WHATSAPP_BOT_SECRET . "\n\n";

// Dados exatos como NotificationTrigger envia
$postData = [
    'secret' => WHATSAPP_BOT_SECRET,
    'transaction_id' => 520
];

echo "Dados enviados:\n";
print_r($postData);
echo "\n";

// Configuração cURL exata como NotificationTrigger
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => CASHBACK_NOTIFICATION_API_URL,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5, // Timeout baixo para não afetar performance
    CURLOPT_CONNECTTIMEOUT => 3,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($postData),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'User-Agent: KlubeCash-Trigger/1.0'
    ],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_FOLLOWLOCATION => false
]);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$curlError = curl_error($curl);
curl_close($curl);

echo "=== RESULTADO ===\n";
echo "HTTP Code: {$httpCode}\n";
if ($curlError) {
    echo "cURL Error: {$curlError}\n";
}
echo "Response: {$response}\n\n";

if ($httpCode === 400) {
    echo "❌ REPRODUZIDO o erro HTTP 400!\n";

    // Tentar decodificar para ver a mensagem
    $data = json_decode($response, true);
    if ($data && isset($data['message'])) {
        echo "Mensagem de erro: " . $data['message'] . "\n";
    }
} else {
    echo "✅ Requisição funcionou (HTTP {$httpCode})\n";
}
?>
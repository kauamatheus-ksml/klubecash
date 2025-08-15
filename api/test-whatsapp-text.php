<?php
// api/test-whatsapp-text.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/constants.php';

$phoneNumber = '389910452056';

echo "<h1>🧪 Teste de Envio WhatsApp</h1>";

// Teste 1: Enviar mensagem simples
$testData = [
    'phone' => $phoneNumber,
    'secret' => WHATSAPP_BOT_SECRET
];

echo "<h3>📱 Testando consulta de saldo normal...</h3>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://klubecash.com/api/whatsapp-saldo.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> {$httpCode}</p>";
echo "<pre>{$response}</pre>";

if ($httpCode == 200) {
    $data = json_decode($response, true);
    if ($data && $data['success']) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; color: #155724;'>";
        echo "✅ <strong>API funcionando! Verifique se chegou mensagem no WhatsApp.</strong>";
        echo "</div>";
    }
}
?>
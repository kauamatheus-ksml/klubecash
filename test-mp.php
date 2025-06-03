<?php
require_once 'config/constants.php';
require_once 'utils/MercadoPagoClient.php';

echo "<h2>Teste Mercado Pago - Klube Cash</h2>";

// Testar credenciais
echo "<h3>1. Testando Credenciais</h3>";
echo "Access Token: " . substr(MP_ACCESS_TOKEN, 0, 20) . "...<br>";

// Testar conexão básica
$mpClient = new MercadoPagoClient();

echo "<h3>2. Testando Criação de Pagamento PIX</h3>";

$testData = [
    'amount' => 10.50,
    'payer_email' => 'test@example.com',
    'payer_name' => 'Test',
    'payer_lastname' => 'User',
    'description' => 'Teste PIX Klube Cash',
    'external_reference' => 'test_' . time()
];

$result = $mpClient->createPixPayment($testData);

echo "<pre>";
echo "Resultado: " . json_encode($result, JSON_PRETTY_PRINT);
echo "</pre>";

if ($result['status']) {
    echo "<p style='color: green;'>✅ PIX criado com sucesso!</p>";
    
    if (isset($result['data']['point_of_interaction']['transaction_data']['qr_code_base64'])) {
        echo "<h3>QR Code Gerado:</h3>";
        echo "<img src='data:image/png;base64," . $result['data']['point_of_interaction']['transaction_data']['qr_code_base64'] . "' style='max-width: 300px;'>";
    }
} else {
    echo "<p style='color: red;'>❌ Erro: " . $result['message'] . "</p>";
}
?>
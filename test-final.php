<?php
// test-final.php
session_start();
$_SESSION['user_id'] = 55; // ID do usuário da loja 34
$_SESSION['user_type'] = 'loja';
$_SESSION['store_id'] = 34;

echo "<h1>🧪 TESTE FINAL - Cliente Visitante</h1>";

$telefoneTest = '11' . rand(100000000, 999999999);

$testData = [
    'action' => 'create_visitor_client',
    'nome' => 'Cliente Final ' . date('H:i:s'),
    'telefone' => $telefoneTest,
    'store_id' => 34  // Usar a loja que sabemos que existe
];

echo "<div style='background: #e8f4fd; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
echo "📤 <strong>Testando criação:</strong><br>";
echo "Nome: " . $testData['nome'] . "<br>";
echo "Telefone: " . $testData['telefone'] . "<br>";
echo "Store ID: " . $testData['store_id'] . "<br>";
echo "</div>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://klubecash.com/api/store-client-search.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Cookie: ' . session_name() . '=' . session_id()
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h2>📥 Resultado:</h2>";
echo "<strong>HTTP Code:</strong> $httpCode<br>";

if ($response) {
    $result = json_decode($response, true);
    if ($result) {
        if ($result['status'] === true) {
            echo "<div style='color: green; background: #d4edda; padding: 15px; border-radius: 8px;'>";
            echo "<h3>✅ SUCESSO TOTAL!</h3>";
            echo "Cliente ID: " . $result['data']['id'] . "<br>";
            echo "Nome: " . $result['data']['nome'] . "<br>";
            echo "Telefone: " . $result['data']['telefone'] . "<br>";
            echo "Tipo: " . $result['data']['tipo_cliente_label'] . "<br>";
            if (isset($result['data']['store_id_usado'])) {
                echo "Store ID usado: " . $result['data']['store_id_usado'] . "<br>";
            }
            echo "</div>";
            
            echo "<h2>🎯 Teste na Interface:</h2>";
            echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px;'>";
            echo "<p><strong>Agora teste na interface real:</strong></p>";
            echo "<ol>";
            echo "<li>Acesse: <a href='/store/registrar-transacao' target='_blank'>Registrar Nova Venda</a></li>";
            echo "<li>Digite o telefone: <strong style='color: #d63384;'>$telefoneTest</strong></li>";
            echo "<li>Clique em 'Buscar Cliente'</li>";
            echo "<li>Deve encontrar o cliente visitante criado!</li>";
            echo "</ol>";
            echo "</div>";
            
        } else {
            echo "<div style='color: red; background: #f8d7da; padding: 15px; border-radius: 8px;'>";
            echo "❌ <strong>ERRO:</strong> " . $result['message'];
            echo "</div>";
        }
        
        echo "<h3>Resposta completa:</h3>";
        echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 8px; overflow-x: auto;'>";
        print_r($result);
        echo "</pre>";
        
    } else {
        echo "<div style='color: red;'>❌ Resposta não é JSON válido:</div>";
        echo "<pre>$response</pre>";
    }
} else {
    echo "<div style='color: red;'>❌ Nenhuma resposta recebida</div>";
}
?>
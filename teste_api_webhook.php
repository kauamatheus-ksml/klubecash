<?php
/**
 * TESTE DIRETO DA API WEBHOOK
 * Testar se a API está respondendo corretamente
 */

require_once __DIR__ . '/config/constants.php';

echo "🧪 TESTE DIRETO DA API WEBHOOK\n\n";

try {
    // Dados de teste
    $data = [
        'secret' => WHATSAPP_BOT_SECRET,
        'phone' => '34991191534',
        'message' => '🧪 Teste direto da API webhook - ' . date('H:i:s'),
        'immediate_mode' => true,
        'priority' => 'high'
    ];

    echo "1️⃣ Dados de teste:\n";
    echo "Secret: " . $data['secret'] . "\n";
    echo "Phone: " . $data['phone'] . "\n";
    echo "Message: " . substr($data['message'], 0, 50) . "...\n";

    // URL da API
    $apiUrl = defined('SITE_URL') ? SITE_URL . '/api/whatsapp-enviar-notificacao.php' : 'https://klubecash.com/api/whatsapp-enviar-notificacao.php';

    echo "\n2️⃣ Testando API: {$apiUrl}\n";

    // Fazer requisição
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $start = microtime(true);
    $response = curl_exec($ch);
    $end = microtime(true);

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $time = round(($end - $start) * 1000, 2);

    curl_close($ch);

    echo "\n3️⃣ Resultado:\n";
    echo "HTTP Code: {$httpCode}\n";
    echo "Tempo: {$time}ms\n";
    echo "Erro cURL: " . ($error ?: 'Nenhum') . "\n";
    echo "Resposta: {$response}\n";

    if ($httpCode === 200) {
        $result = json_decode($response, true);
        if ($result) {
            echo "\n4️⃣ Resposta decodificada:\n";
            echo "Success: " . ($result['success'] ? 'true' : 'false') . "\n";
            if (isset($result['error'])) {
                echo "Error: " . $result['error'] . "\n";
            }
            if (isset($result['message'])) {
                echo "Message: " . $result['message'] . "\n";
            }
            if (isset($result['data'])) {
                echo "Data: " . json_encode($result['data']) . "\n";
            }

            if ($result['success']) {
                echo "\n✅ API funcionando corretamente!\n";
            } else {
                echo "\n❌ API retornou erro!\n";
            }
        } else {
            echo "\n⚠️ Resposta não é JSON válido\n";
        }
    } else {
        echo "\n❌ Erro HTTP: {$httpCode}\n";
    }

    // Verificar se SITE_URL está definido
    echo "\n5️⃣ Verificação de configuração:\n";
    echo "SITE_URL definido: " . (defined('SITE_URL') ? 'Sim (' . SITE_URL . ')' : 'Não') . "\n";
    echo "WHATSAPP_BOT_SECRET: " . (defined('WHATSAPP_BOT_SECRET') ? WHATSAPP_BOT_SECRET : 'Não definido') . "\n";

} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}
?>
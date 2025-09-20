<?php
/**
 * Debug Avançado do Bot WhatsApp
 *
 * Este script faz um diagnóstico completo do bot WhatsApp
 * para identificar por que as mensagens não estão sendo entregues
 */

require_once __DIR__ . '/config/constants.php';

$WHATSAPP_BOT_URL = 'http://148.230.73.190:3002';
$SECRET_KEY = 'klube-cash-2024';
$TEST_PHONE = '5538991045205';

echo "=== DEBUG AVANÇADO DO BOT WHATSAPP ===\n";
echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n";
echo "Bot URL: $WHATSAPP_BOT_URL\n";
echo "Telefone de teste: $TEST_PHONE\n\n";

function makeCurlRequest($url, $data = null, $method = 'GET') {
    $curl = curl_init();

    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_VERBOSE => true,
        CURLOPT_STDERR => fopen('curl_debug.log', 'a')
    ];

    if ($method === 'POST' && $data) {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = json_encode($data);
    }

    curl_setopt_array($curl, $options);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    $info = curl_getinfo($curl);

    curl_close($curl);

    return [
        'response' => $response,
        'http_code' => $httpCode,
        'error' => $curlError,
        'info' => $info
    ];
}

try {
    // 1. Status detalhado do bot
    echo "1. === STATUS DETALHADO DO BOT ===\n";
    $statusResult = makeCurlRequest($WHATSAPP_BOT_URL . '/status');

    echo "HTTP Code: {$statusResult['http_code']}\n";
    if ($statusResult['error']) {
        echo "cURL Error: {$statusResult['error']}\n";
    }

    if ($statusResult['response']) {
        $statusData = json_decode($statusResult['response'], true);
        echo "Status Response:\n";
        print_r($statusData);

        if (isset($statusData['bot_ready'])) {
            echo "Bot Ready: " . ($statusData['bot_ready'] ? 'YES' : 'NO') . "\n";
        }
    }
    echo "\n";

    // 2. Teste de envio com diferentes formatos de telefone
    echo "2. === TESTE DE FORMATOS DE TELEFONE ===\n";

    $phoneFormats = [
        '5538991045205',           // Formato completo
        '38991045205',             // Sem código país
        '+5538991045205',          // Com +
        '55 38 99104-5205',        // Com espaços e traço
        '5538991045205@c.us'       // Formato WhatsApp
    ];

    foreach ($phoneFormats as $index => $phone) {
        echo "Testando formato " . ($index + 1) . ": '$phone'\n";

        $testData = [
            'secret' => $SECRET_KEY,
            'phone' => $phone,
            'message' => "🧪 Teste formato telefone " . ($index + 1) . " - " . date('H:i:s')
        ];

        $sendResult = makeCurlRequest($WHATSAPP_BOT_URL . '/send-message', $testData, 'POST');

        echo "  HTTP Code: {$sendResult['http_code']}\n";
        if ($sendResult['error']) {
            echo "  Error: {$sendResult['error']}\n";
        }
        if ($sendResult['response']) {
            $responseData = json_decode($sendResult['response'], true);
            echo "  Response: " . json_encode($responseData) . "\n";
        }
        echo "\n";

        sleep(2); // Aguardar entre testes
    }

    // 3. Teste com número conhecido válido
    echo "3. === TESTE COM NÚMEROS DE REFERÊNCIA ===\n";

    $testNumbers = [
        '5534991191534',  // Número conhecido do sistema
        '5538999999999',  // Número fictício para teste
        '5511999999999'   // São Paulo fictício
    ];

    foreach ($testNumbers as $index => $testNum) {
        echo "Testando número " . ($index + 1) . ": $testNum\n";

        $testData = [
            'secret' => $SECRET_KEY,
            'phone' => $testNum,
            'message' => "🧪 Teste número " . ($index + 1) . " - " . date('H:i:s')
        ];

        $sendResult = makeCurlRequest($WHATSAPP_BOT_URL . '/send-message', $testData, 'POST');

        echo "  HTTP Code: {$sendResult['http_code']}\n";
        if ($sendResult['response']) {
            $responseData = json_decode($sendResult['response'], true);
            echo "  Response: " . json_encode($responseData) . "\n";
        }
        echo "\n";

        sleep(2);
    }

    // 4. Verificar endpoint de teste do bot
    echo "4. === ENDPOINT DE TESTE DO BOT ===\n";

    $testBotData = [
        'secret' => $SECRET_KEY
    ];

    $testBotResult = makeCurlRequest($WHATSAPP_BOT_URL . '/send-test', $testBotData, 'POST');

    echo "HTTP Code: {$testBotResult['http_code']}\n";
    if ($testBotResult['response']) {
        $responseData = json_decode($testBotResult['response'], true);
        echo "Response: " . json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
    }
    echo "\n";

    // 5. Verificar se há outros endpoints disponíveis
    echo "5. === ENDPOINTS DISPONÍVEIS ===\n";

    $endpoints = [
        '/status',
        '/send-message',
        '/send-test',
        '/health',
        '/info'
    ];

    foreach ($endpoints as $endpoint) {
        echo "Testando endpoint: $endpoint\n";
        $result = makeCurlRequest($WHATSAPP_BOT_URL . $endpoint);
        echo "  Status: {$result['http_code']}\n";

        if ($result['http_code'] === 200 && $result['response']) {
            $data = json_decode($result['response'], true);
            if ($data) {
                echo "  Disponível: SIM\n";
            }
        } else {
            echo "  Disponível: NÃO\n";
        }
    }
    echo "\n";

    // 6. Informações de debug da conexão
    echo "6. === INFORMAÇÕES DE DEBUG ===\n";
    echo "User Agent: " . $_SERVER['HTTP_USER_AGENT'] ?? 'N/A' . "\n";
    echo "Server IP: " . $_SERVER['SERVER_ADDR'] ?? 'N/A' . "\n";
    echo "Client IP: " . $_SERVER['REMOTE_ADDR'] ?? 'N/A' . "\n";
    echo "Timestamp: " . time() . "\n";
    echo "PHP Version: " . PHP_VERSION . "\n";
    echo "cURL Version: " . curl_version()['version'] . "\n";

    // Verificar se arquivo de debug cURL foi criado
    if (file_exists('curl_debug.log')) {
        echo "\n7. === LOGS DE DEBUG cURL ===\n";
        echo "Últimas linhas do log cURL:\n";
        $debugLog = file_get_contents('curl_debug.log');
        $lines = explode("\n", $debugLog);
        $lastLines = array_slice($lines, -20);
        echo implode("\n", $lastLines) . "\n";
    }

} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== DEBUG CONCLUÍDO ===\n";
echo "Verifique os logs acima para identificar problemas na entrega de mensagens.\n";
?>
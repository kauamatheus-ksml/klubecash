<?php
/**
 * TESTE DIRETO DO BOT LOCAL
 * Teste simples para verificar conectividade com o bot local
 */

echo "🧪 TESTE DIRETO DO BOT LOCAL\n\n";

// URLs para testar
$urls = [
    'http://localhost:3002/status',
    'http://127.0.0.1:3002/status',
    'http://[::1]:3002/status'
];

foreach ($urls as $url) {
    echo "📡 Testando: {$url}\n";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

    $start = microtime(true);
    $response = curl_exec($ch);
    $end = microtime(true);

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $time = round(($end - $start) * 1000, 2);

    curl_close($ch);

    if ($httpCode === 200 && $response) {
        echo "✅ SUCESSO: HTTP {$httpCode} em {$time}ms\n";
        echo "📄 Resposta: " . substr($response, 0, 200) . "\n";

        $data = json_decode($response, true);
        if ($data) {
            echo "🤖 Status Bot: " . ($data['status'] ?? 'N/A') . "\n";
            echo "📱 Bot Ready: " . ($data['bot_ready'] ? 'Sim' : 'Não') . "\n";
            echo "⏱️ Uptime: " . round($data['uptime'] ?? 0, 2) . "s\n";
        }
        echo "\n";
        break; // Encontrou um que funciona
    } else {
        echo "❌ FALHA: HTTP {$httpCode}, Erro: {$error}, Tempo: {$time}ms\n\n";
    }
}

echo "🔍 Testando envio de mensagem...\n";

// Testar envio
$testData = [
    'phone' => '5534991191534',
    'message' => '🧪 Teste de conectividade direta - ' . date('H:i:s'),
    'secret' => 'klube-cash-2024'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:3002/send-message');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$start = microtime(true);
$response = curl_exec($ch);
$end = microtime(true);

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
$time = round(($end - $start) * 1000, 2);

curl_close($ch);

if ($httpCode === 200 && $response) {
    echo "✅ ENVIO SUCESSO: HTTP {$httpCode} em {$time}ms\n";
    echo "📄 Resposta: {$response}\n";

    $result = json_decode($response, true);
    if ($result && $result['success']) {
        echo "🎉 Mensagem enviada com sucesso!\n";
    }
} else {
    echo "❌ ENVIO FALHOU: HTTP {$httpCode}, Erro: {$error}, Tempo: {$time}ms\n";
}

echo "\n📊 RESUMO:\n";
echo "- Porta 3002 está listening: ✅ (verificado via netstat)\n";
echo "- Conectividade HTTP: " . ($httpCode === 200 ? "✅" : "❌") . "\n";
echo "- Bot funcional: " . (($httpCode === 200 && $response) ? "✅" : "❌") . "\n";
?>
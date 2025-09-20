<?php
/**
 * Teste específico da sessão WhatsApp
 *
 * Verifica se o bot está realmente conectado ao WhatsApp
 * e pode enviar mensagens
 */

$WHATSAPP_BOT_URL = 'http://148.230.73.190:3002';
$SECRET_KEY = 'klube-cash-2024';

echo "=== TESTE DE SESSÃO WHATSAPP ===\n";
echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n\n";

function testWhatsAppConnection($url, $secret) {
    // 1. Verificar status detalhado
    echo "1. Verificando status detalhado...\n";

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url . '/status',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        echo "Status: " . $data['status'] . "\n";
        echo "Bot Ready: " . ($data['bot_ready'] ? 'SIM' : 'NÃO') . "\n";
        echo "Uptime: " . round($data['uptime']/60, 2) . " minutos\n";
        echo "Versão: " . $data['version'] . "\n\n";

        if (!$data['bot_ready']) {
            echo "❌ PROBLEMA: Bot não está pronto!\n";
            return false;
        }
    } else {
        echo "❌ PROBLEMA: Não conseguiu obter status!\n";
        return false;
    }

    // 2. Teste de envio para número conhecido
    echo "2. Testando envio para número conhecido...\n";

    $testMessage = "🧪 TESTE DE SESSÃO WHATSAPP\n\n";
    $testMessage .= "Se você recebeu esta mensagem, a sessão WhatsApp está funcionando!\n\n";
    $testMessage .= "Horário: " . date('H:i:s') . "\n";
    $testMessage .= "Teste: #" . rand(1000, 9999);

    $phones = [
        '5538991045205', // Número principal
        '5534991191534'  // Número de backup
    ];

    foreach ($phones as $phone) {
        echo "Testando: $phone\n";

        $postData = [
            'secret' => $secret,
            'phone' => $phone,
            'message' => $testMessage
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url . '/send-message',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: KlubeCash-SessionTest/1.0'
            ]
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        echo "  HTTP: $httpCode\n";
        if ($curlError) {
            echo "  Erro: $curlError\n";
        }
        if ($response) {
            $responseData = json_decode($response, true);
            echo "  Resposta: " . json_encode($responseData) . "\n";

            if (isset($responseData['success']) && $responseData['success']) {
                echo "  ✅ API reportou sucesso\n";
            } else {
                echo "  ❌ API reportou falha\n";
                if (isset($responseData['error'])) {
                    echo "  Erro específico: " . $responseData['error'] . "\n";
                }
            }
        }
        echo "\n";

        sleep(3); // Aguardar entre envios
    }

    // 3. Teste com endpoint de teste específico
    echo "3. Testando endpoint interno de teste...\n";

    $testData = ['secret' => $secret];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url . '/send-test',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($testData),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json']
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    echo "HTTP: $httpCode\n";
    if ($response) {
        $responseData = json_decode($response, true);
        echo "Resposta: " . json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
    }
    echo "\n";

    return true;
}

try {
    if (testWhatsAppConnection($WHATSAPP_BOT_URL, $SECRET_KEY)) {
        echo "=== DIAGNÓSTICO ===\n";
        echo "O bot está respondendo corretamente às requisições.\n";
        echo "Se as mensagens não chegaram, possíveis causas:\n\n";
        echo "1. 📱 SESSÃO WHATSAPP:\n";
        echo "   - O bot pode ter perdido a conexão com WhatsApp\n";
        echo "   - Precisa escanear QR Code novamente\n";
        echo "   - Telefone principal desconectado\n\n";
        echo "2. 📞 NÚMERO DE TELEFONE:\n";
        echo "   - Número pode não existir no WhatsApp\n";
        echo "   - Número pode estar bloqueado\n";
        echo "   - Formato do número incorreto\n\n";
        echo "3. 🤖 CONFIGURAÇÃO DO BOT:\n";
        echo "   - WhatsApp Web pode ter desconectado\n";
        echo "   - Sessão expirou\n";
        echo "   - Precisa reiniciar o serviço\n\n";
        echo "=== AÇÕES RECOMENDADAS ===\n";
        echo "1. Verificar logs do bot no VPS:\n";
        echo "   journalctl -f -u klube-whatsapp-bot.service\n\n";
        echo "2. Reiniciar o serviço do bot:\n";
        echo "   systemctl restart klube-whatsapp-bot.service\n\n";
        echo "3. Verificar se precisa escanear QR Code:\n";
        echo "   Acessar logs para ver se tem QR Code\n\n";
        echo "4. Testar com número diferente\n";
        echo "   Use um número que você tem certeza que existe\n\n";
    }

} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}

echo "=== FIM DO TESTE ===\n";
?>
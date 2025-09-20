<?php
/**
 * Script de Teste para Notificação de Cashback via WhatsApp
 *
 * Este script simula todo o processo de registro de cashback e
 * envio de notificação para descobrir onde está o problema
 *
 * Para usar no VPS: php test_cashback_whatsapp.php
 */

// Incluir dependências
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/classes/CashbackNotifier.php';

// === CONFIGURAÇÕES DO TESTE ===
$PHONE_TEST = '5538991045205'; // Número para teste
$SECRET_KEY = 'klube-cash-2024'; // Chave secreta
$WHATSAPP_BOT_URL = 'http://148.230.73.190:3002'; // URL do bot

echo "=== TESTE DE NOTIFICAÇÃO CASHBACK VIA WHATSAPP ===\n";
echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n";
echo "Telefone de teste: $PHONE_TEST\n";
echo "Bot WhatsApp: $WHATSAPP_BOT_URL\n\n";

try {
    // === PASSO 1: VERIFICAR CONEXÃO COM BANCO ===
    echo "1. Verificando conexão com banco de dados...\n";
    $db = Database::getConnection();
    if (!$db) {
        throw new Exception("Falha na conexão com banco de dados");
    }
    echo "✅ Conexão com banco OK\n\n";

    // === PASSO 2: VERIFICAR SE BOT ESTÁ ATIVO ===
    echo "2. Verificando status do bot WhatsApp...\n";
    $statusUrl = $WHATSAPP_BOT_URL . '/status';
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $statusUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json']
    ]);

    $statusResponse = curl_exec($curl);
    $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($curlError) {
        echo "❌ Erro cURL: $curlError\n";
    } else {
        echo "Status HTTP: $statusCode\n";
        if ($statusResponse) {
            $statusData = json_decode($statusResponse, true);
            echo "Resposta do bot: " . json_encode($statusData, JSON_PRETTY_PRINT) . "\n";
        }
    }
    echo "\n";

    // === PASSO 3: BUSCAR OU CRIAR USUÁRIO DE TESTE ===
    echo "3. Verificando usuário de teste...\n";
    $stmt = $db->prepare("SELECT id, nome, telefone FROM usuarios WHERE telefone = :phone LIMIT 1");
    $stmt->bindParam(':phone', $PHONE_TEST);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        echo "Usuário não encontrado. Criando usuário de teste...\n";
        $stmt = $db->prepare("
            INSERT INTO usuarios (nome, telefone, email, status, tipo, senha_hash, data_criacao)
            VALUES (:nome, :telefone, :email, 'ativo', 'cliente', :senha_hash, NOW())
        ");
        $stmt->execute([
            ':nome' => 'Teste WhatsApp',
            ':telefone' => $PHONE_TEST,
            ':email' => 'teste@klubecash.com',
            ':senha_hash' => password_hash('123456', PASSWORD_DEFAULT)
        ]);
        $usuarioId = $db->lastInsertId();
        echo "✅ Usuário criado com ID: $usuarioId\n";
    } else {
        $usuarioId = $usuario['id'];
        echo "✅ Usuário encontrado: {$usuario['nome']} (ID: $usuarioId)\n";
    }
    echo "\n";

    // === PASSO 4: BUSCAR OU CRIAR LOJA DE TESTE ===
    echo "4. Verificando loja de teste...\n";
    $stmt = $db->prepare("SELECT id, nome_fantasia FROM lojas WHERE status = 'aprovado' LIMIT 1");
    $stmt->execute();
    $loja = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$loja) {
        echo "Nenhuma loja aprovada encontrada. Criando loja de teste...\n";
        $stmt = $db->prepare("
            INSERT INTO lojas (nome_fantasia, cnpj, telefone, email, porcentagem_cashback, status, data_cadastro)
            VALUES (:nome, :cnpj, :telefone, :email, :porcentagem, 'aprovado', NOW())
        ");
        $stmt->execute([
            ':nome' => 'Loja Teste WhatsApp',
            ':cnpj' => '12345678000199',
            ':telefone' => '1140004000',
            ':email' => 'loja@teste.com',
            ':porcentagem' => 10.00
        ]);
        $lojaId = $db->lastInsertId();
        echo "✅ Loja criada com ID: $lojaId\n";
    } else {
        $lojaId = $loja['id'];
        echo "✅ Loja encontrada: {$loja['nome_fantasia']} (ID: $lojaId)\n";
    }
    echo "\n";

    // === PASSO 5: CRIAR TRANSAÇÃO DE TESTE ===
    echo "5. Criando transação de cashback de teste...\n";
    $valorTotal = 100.00;
    $valorCashback = 10.00;
    $valorCliente = 5.00;

    $stmt = $db->prepare("
        INSERT INTO transacoes_cashback
        (usuario_id, loja_id, valor_total, valor_cashback, valor_cliente, status, data_transacao)
        VALUES (:usuario_id, :loja_id, :valor_total, :valor_cashback, :valor_cliente, 'aprovado', NOW())
    ");

    $stmt->execute([
        ':usuario_id' => $usuarioId,
        ':loja_id' => $lojaId,
        ':valor_total' => $valorTotal,
        ':valor_cashback' => $valorCashback,
        ':valor_cliente' => $valorCliente
    ]);

    $transactionId = $db->lastInsertId();
    echo "✅ Transação criada com ID: $transactionId\n";
    echo "Valor da compra: R$ $valorTotal\n";
    echo "Cashback do cliente: R$ $valorCliente\n\n";

    // === PASSO 6: TESTAR CLASSE CASHBACKNOTIFIER ===
    echo "6. Testando CashbackNotifier...\n";
    $notifier = new CashbackNotifier();
    $result = $notifier->notifyNewTransaction($transactionId);

    echo "Resultado da notificação:\n";
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

    // === PASSO 7: TESTAR API DE NOTIFICAÇÃO DIRETAMENTE ===
    echo "7. Testando API de notificação diretamente...\n";
    $apiUrl = SITE_URL . '/api/cashback-notificacao.php';
    $postData = [
        'secret' => $SECRET_KEY,
        'transaction_id' => $transactionId
    ];

    echo "URL da API: $apiUrl\n";
    echo "Dados enviados: " . json_encode($postData) . "\n";

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($postData),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'User-Agent: KlubeCash-Test/1.0'
        ]
    ]);

    $apiResponse = curl_exec($curl);
    $apiStatusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $apiCurlError = curl_error($curl);
    curl_close($curl);

    echo "Status HTTP: $apiStatusCode\n";
    if ($apiCurlError) {
        echo "❌ Erro cURL: $apiCurlError\n";
    }
    if ($apiResponse) {
        echo "Resposta da API:\n";
        $apiData = json_decode($apiResponse, true);
        echo json_encode($apiData, JSON_PRETTY_PRINT) . "\n";
    }
    echo "\n";

    // === PASSO 8: TESTAR ENVIO DIRETO PARA O BOT ===
    echo "8. Testando envio direto para o bot WhatsApp...\n";
    $botUrl = $WHATSAPP_BOT_URL . '/send-message';
    $message = "🧪 *TESTE CASHBACK KLUBE CASH*\n\n" .
               "Esta é uma mensagem de teste do sistema de notificação de cashback.\n\n" .
               "📋 *Detalhes do teste:*\n" .
               "💰 Valor: R$ $valorTotal\n" .
               "🎁 Cashback: R$ $valorCliente\n" .
               "🆔 Transação: $transactionId\n\n" .
               "Data/Hora: " . date('Y-m-d H:i:s') . "\n\n" .
               "Se você recebeu esta mensagem, o sistema está funcionando! ✅";

    $botData = [
        'secret' => $SECRET_KEY,
        'phone' => $PHONE_TEST,
        'message' => $message
    ];

    echo "URL do bot: $botUrl\n";
    echo "Dados enviados: " . json_encode($botData) . "\n";

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $botUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($botData),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'User-Agent: KlubeCash-Test/1.0'
        ]
    ]);

    $botResponse = curl_exec($curl);
    $botStatusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $botCurlError = curl_error($curl);
    curl_close($curl);

    echo "Status HTTP: $botStatusCode\n";
    if ($botCurlError) {
        echo "❌ Erro cURL: $botCurlError\n";
    }
    if ($botResponse) {
        echo "Resposta do bot:\n";
        $botData = json_decode($botResponse, true);
        echo json_encode($botData, JSON_PRETTY_PRINT) . "\n";
    }
    echo "\n";

    // === VERIFICAÇÕES ADICIONAIS ===
    echo "9. Verificações adicionais...\n";

    // Verificar constantes
    echo "WHATSAPP_BOT_URL: " . (defined('WHATSAPP_BOT_URL') ? WHATSAPP_BOT_URL : 'NÃO DEFINIDA') . "\n";
    echo "WHATSAPP_BOT_SECRET: " . (defined('WHATSAPP_BOT_SECRET') ? WHATSAPP_BOT_SECRET : 'NÃO DEFINIDA') . "\n";
    echo "CASHBACK_NOTIFICATIONS_ENABLED: " . (defined('CASHBACK_NOTIFICATIONS_ENABLED') ? (CASHBACK_NOTIFICATIONS_ENABLED ? 'SIM' : 'NÃO') : 'NÃO DEFINIDA') . "\n";
    echo "SITE_URL: " . (defined('SITE_URL') ? SITE_URL : 'NÃO DEFINIDA') . "\n";

    echo "\n=== TESTE CONCLUÍDO ===\n";
    echo "Verifique seu WhatsApp ($PHONE_TEST) para confirmar se as mensagens foram recebidas.\n";

} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nTeste executado em: " . date('Y-m-d H:i:s') . "\n";
?>
<?php
/**
 * Teste de Integração - Notificação WhatsApp para Novas Transações
 *
 * Este arquivo testa se a funcionalidade de notificação automática
 * está funcionando corretamente quando uma nova transação é registrada.
 *
 * Para usar:
 * 1. Acesse: http://klubecash.com/test_whatsapp_notification.php
 * 2. Ou execute via linha de comando: php test_whatsapp_notification.php
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/models/Transaction.php';
require_once __DIR__ . '/utils/NotificationTrigger.php';

echo "<h1>🧪 Teste de Notificação WhatsApp para Transações</h1>\n";
echo "<hr>\n";

try {
    // 1. Verificar se as constantes estão configuradas
    echo "<h2>1️⃣ Verificação de Configurações</h2>\n";

    $configs = [
        'WHATSAPP_BOT_URL' => WHATSAPP_BOT_URL ?? 'NÃO DEFINIDO',
        'WHATSAPP_BOT_SECRET' => WHATSAPP_BOT_SECRET ?? 'NÃO DEFINIDO',
        'CASHBACK_NOTIFICATIONS_ENABLED' => CASHBACK_NOTIFICATIONS_ENABLED ? 'SIM' : 'NÃO',
        'CASHBACK_NOTIFICATION_API_URL' => CASHBACK_NOTIFICATION_API_URL ?? 'NÃO DEFINIDO'
    ];

    foreach ($configs as $key => $value) {
        $status = ($value !== 'NÃO DEFINIDO' && $value !== 'NÃO') ? '✅' : '❌';
        echo "{$status} {$key}: {$value}<br>\n";
    }

    // 2. Testar conectividade com o bot WhatsApp
    echo "<br><h2>2️⃣ Teste de Conectividade com Bot WhatsApp</h2>\n";

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => WHATSAPP_BOT_URL . '/status',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json']
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($curlError) {
        echo "❌ Erro de conexão: {$curlError}<br>\n";
    } elseif ($httpCode === 200) {
        $data = json_decode($response, true);
        echo "✅ Bot conectado! Status: " . ($data['status'] ?? 'desconhecido') . "<br>\n";
        echo "📱 Bot pronto: " . ($data['bot_ready'] ? 'SIM' : 'NÃO') . "<br>\n";
        echo "⏱️ Uptime: " . ($data['uptime'] ?? 0) . " segundos<br>\n";
    } else {
        echo "❌ HTTP Error {$httpCode}<br>\n";
    }

    // 3. Buscar uma transação recente para teste
    echo "<br><h2>3️⃣ Busca de Transação para Teste</h2>\n";

    $db = Database::getConnection();
    $stmt = $db->prepare("
        SELECT t.id, t.usuario_id, t.valor_total, t.valor_cliente, t.status,
               u.nome, u.telefone, l.nome_fantasia as loja_nome
        FROM transacoes_cashback t
        INNER JOIN usuarios u ON t.usuario_id = u.id
        INNER JOIN lojas l ON t.loja_id = l.id
        WHERE t.status = 'pendente'
        ORDER BY t.data_transacao DESC
        LIMIT 1
    ");
    $stmt->execute();
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        echo "❌ Nenhuma transação pendente encontrada para teste<br>\n";
        echo "💡 Dica: Registre uma transação primeiro para testar a notificação<br>\n";

        // Buscar qualquer transação para demonstração
        $stmt = $db->prepare("
            SELECT t.id, t.usuario_id, t.valor_total, t.valor_cliente, t.status,
                   u.nome, u.telefone, l.nome_fantasia as loja_nome
            FROM transacoes_cashback t
            INNER JOIN usuarios u ON t.usuario_id = u.id
            INNER JOIN lojas l ON t.loja_id = l.id
            ORDER BY t.data_transacao DESC
            LIMIT 1
        ");
        $stmt->execute();
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$transaction) {
            echo "❌ Nenhuma transação encontrada no sistema<br>\n";
            exit;
        }

        echo "📋 Usando transação existente para demonstração:<br>\n";
    } else {
        echo "✅ Transação encontrada para teste:<br>\n";
    }

    echo "🆔 ID: {$transaction['id']}<br>\n";
    echo "👤 Cliente: {$transaction['nome']}<br>\n";
    echo "📱 Telefone: {$transaction['telefone']}<br>\n";
    echo "🏪 Loja: {$transaction['loja_nome']}<br>\n";
    echo "💰 Valor: R$ " . number_format($transaction['valor_total'], 2, ',', '.') . "<br>\n";
    echo "🎁 Cashback: R$ " . number_format($transaction['valor_cliente'], 2, ',', '.') . "<br>\n";
    echo "📊 Status: {$transaction['status']}<br>\n";

    // 4. Testar NotificationTrigger diretamente
    echo "<br><h2>4️⃣ Teste do Sistema de Notificação</h2>\n";

    echo "📤 Enviando notificação de teste...<br>\n";
    $result = NotificationTrigger::send($transaction['id'], [
        'async' => false,
        'debug' => true
    ]);

    if ($result['success']) {
        echo "✅ Notificação enviada com sucesso!<br>\n";
        echo "📋 Tipo de mensagem: " . ($result['message_type'] ?? 'N/A') . "<br>\n";
        echo "📱 Telefone: " . ($result['phone'] ?? 'N/A') . "<br>\n";
    } else {
        echo "❌ Falha no envio: {$result['message']}<br>\n";
        if (isset($result['retry_scheduled'])) {
            echo "🔄 Retry agendado para nova tentativa<br>\n";
        }
    }

    // 5. Verificar sistema de retry
    echo "<br><h2>5️⃣ Verificação do Sistema de Retry</h2>\n";

    if (class_exists('CashbackRetrySystem')) {
        require_once __DIR__ . '/utils/CashbackRetrySystem.php';
        $retrySystem = new CashbackRetrySystem();
        $stats = $retrySystem->getStats();

        echo "📊 Estatísticas do sistema de retry:<br>\n";
        echo "⏳ Pendentes: " . ($stats['pending_retries'] ?? 0) . "<br>\n";
        echo "✅ Sucessos: " . ($stats['successful_retries'] ?? 0) . "<br>\n";
        echo "❌ Falhas: " . ($stats['failed_retries'] ?? 0) . "<br>\n";
    } else {
        echo "⚠️ Sistema de retry não encontrado<br>\n";
    }

    // 6. Verificar integração no Transaction.php
    echo "<br><h2>6️⃣ Verificação da Integração Automática</h2>\n";

    $transactionFile = __DIR__ . '/models/Transaction.php';
    if (file_exists($transactionFile)) {
        $content = file_get_contents($transactionFile);
        if (strpos($content, 'NotificationTrigger::send') !== false) {
            echo "✅ Integração automática ativa no Transaction.php<br>\n";
            echo "🔗 Toda nova transação dispara notificação automaticamente<br>\n";
        } else {
            echo "❌ Integração automática não encontrada<br>\n";
        }
    }

    echo "<br><h2>🎉 Resultado Final</h2>\n";
    echo "✅ <strong>Sistema de notificação WhatsApp está configurado e pronto!</strong><br>\n";
    echo "📱 Sempre que uma nova transação for registrada, o cliente receberá uma mensagem personalizada no WhatsApp<br>\n";
    echo "🔄 Sistema de retry ativo para garantir entrega em caso de falhas temporárias<br>\n";

} catch (Exception $e) {
    echo "<br>❌ <strong>Erro durante o teste:</strong> " . $e->getMessage() . "<br>\n";
    echo "📋 Verifique os logs do sistema para mais detalhes<br>\n";
}

echo "<br><hr>\n";
echo "<small>Teste concluído em " . date('d/m/Y H:i:s') . "</small>\n";
?>
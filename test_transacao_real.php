<?php
/**
 * Teste de transação real exatamente como o sistema faz
 * Para verificar se as notificações disparam automaticamente
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/models/Transaction.php';

echo "=== TESTE DE TRANSAÇÃO REAL ===\n\n";

try {
    // Usar os mesmos dados de uma transação real
    echo "1. Criando transação real via Transaction model:\n";

    $transaction = new Transaction();

    // Dados realistas
    $transaction->setUsuarioId(9); // ID do usuário que você usa nos testes
    $transaction->setLojaId(59);   // ID da loja que você usa
    $transaction->setValorTotal(50.00); // Valor diferente para identificar
    $transaction->setDataTransacao(date('Y-m-d H:i:s'));
    $transaction->setStatus(TRANSACTION_PENDING);
    $transaction->setCriadoPor(9); // Simulando criação por funcionário da loja

    // Calcular distribuição automática
    $transaction->calcularDistribuicao();

    echo "   - Usuário: 9\n";
    echo "   - Loja: 59\n";
    echo "   - Valor: R$ 50,00\n";
    echo "   - Status: " . TRANSACTION_PENDING . "\n";
    echo "   - Cashback calculado: R$ " . number_format($transaction->getValorCashback(), 2, ',', '.') . "\n";

    // Salvar (isso deve disparar notificação automaticamente)
    echo "\n2. Salvando transação (deve disparar notificação automaticamente):\n";

    $resultado = $transaction->save();

    if ($resultado) {
        $transactionId = $transaction->getId();
        echo "   ✅ Transação criada com ID: {$transactionId}\n";

        // Aguardar processamento
        echo "\n3. Aguardando processamento da notificação...\n";
        sleep(3);

        // Verificar logs recentes
        echo "\n4. Verificando logs WhatsApp para esta transação:\n";

        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT id, type, phone, success, error_message, created_at
            FROM whatsapp_logs
            WHERE additional_data LIKE CONCAT('%\"transaction_id\":', ?, '%')
            OR created_at >= DATE_SUB(NOW(), INTERVAL 2 MINUTE)
            ORDER BY id DESC
            LIMIT 5
        ");
        $stmt->execute([$transactionId]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($logs)) {
            echo "   ⚠️  Nenhum log encontrado nos últimos 2 minutos\n";
        } else {
            foreach ($logs as $log) {
                echo "   - Log ID {$log['id']}: {$log['type']} para {$log['phone']} - " .
                     ($log['success'] ? 'SUCESSO' : 'FALHA') .
                     " em {$log['created_at']}\n";
                if (!$log['success'] && $log['error_message']) {
                    echo "     Erro: {$log['error_message']}\n";
                }
            }
        }

        // Testar notificação manual também
        echo "\n5. Testando notificação manual para comparação:\n";

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://klubecash.com/api/cashback-notificacao.php',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'secret' => 'klube-cash-2024',
                'transaction_id' => $transactionId
            ]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json']
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        echo "   - HTTP Code: {$httpCode}\n";
        echo "   - Resposta: {$response}\n";

    } else {
        echo "   ❌ Erro ao criar transação\n";
    }

} catch (Exception $e) {
    echo "❌ Erro no teste: " . $e->getMessage() . "\n";
}

echo "\n=== CONCLUSÃO ===\n";
echo "Se você vir logs de sucesso acima, o sistema automático está funcionando.\n";
echo "Se não houver logs, então há um problema no disparo automático.\n";
echo "Compare este teste com suas transações reais para identificar diferenças.\n";
?>
<?php
/**
 * Teste específico para transações aprovadas
 * Agora que modificamos o sistema para notificar transações aprovadas também
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/models/Transaction.php';

echo "=== TESTE DE TRANSAÇÃO APROVADA ===\n\n";

try {
    // Teste 1: Criar transação com status APROVADO
    echo "1. Criando transação com status APROVADO:\n";

    $transaction = new Transaction();
    $transaction->setUsuarioId(9);
    $transaction->setLojaId(59);
    $transaction->setValorTotal(75.00);
    $transaction->setDataTransacao(date('Y-m-d H:i:s'));
    $transaction->setStatus(TRANSACTION_APPROVED); // Status APROVADO
    $transaction->calcularDistribuicao();

    echo "   - Status: " . TRANSACTION_APPROVED . " (APROVADO)\n";
    echo "   - Valor: R$ 75,00\n";
    echo "   - Deve disparar notificação automaticamente agora!\n";

    $resultado = $transaction->save();

    if ($resultado) {
        $transactionId = $transaction->getId();
        echo "   ✅ Transação APROVADA criada com ID: {$transactionId}\n";

        // Aguardar processamento
        sleep(2);

        // Testar notificação manual para comparar
        echo "\n2. Testando notificação manual para transação aprovada:\n";

        $response = file_get_contents('https://klubecash.com/api/cashback-notificacao.php', false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode([
                    'secret' => 'klube-cash-2024',
                    'transaction_id' => $transactionId
                ])
            ]
        ]));

        echo "   - Resposta da API: {$response}\n";

        // Decodificar resposta para verificar se a mensagem é diferente
        $responseData = json_decode($response, true);
        if (isset($responseData['success']) && $responseData['success']) {
            echo "   ✅ Notificação processada com sucesso!\n";
            if (isset($responseData['data']['message_type'])) {
                echo "   - Tipo de mensagem: {$responseData['data']['message_type']}\n";
            }
        }

    } else {
        echo "   ❌ Erro ao criar transação aprovada\n";
    }

    // Teste 2: Verificar se mensagem para transação aprovada é diferente
    echo "\n3. Comparando mensagens pendente vs aprovada:\n";

    // Testar com transação pendente (ID 516)
    echo "\n   A) Testando mensagem para transação PENDENTE (ID 516):\n";
    $response1 = file_get_contents('https://klubecash.com/api/whatsapp-enviar-notificacao.php', false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode([
                'secret' => 'klube-cash-2024',
                'transaction_id' => 516,
                'phone' => '5538991045205'
            ])
        ]
    ]));

    $data1 = json_decode($response1, true);
    if (isset($data1['message'])) {
        echo "   Mensagem PENDENTE: " . substr($data1['message'], 0, 100) . "...\n";
    }

    // Testar com transação aprovada que acabamos de criar
    if (isset($transactionId)) {
        echo "\n   B) Testando mensagem para transação APROVADA (ID {$transactionId}):\n";
        $response2 = file_get_contents('https://klubecash.com/api/whatsapp-enviar-notificacao.php', false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode([
                    'secret' => 'klube-cash-2024',
                    'transaction_id' => $transactionId,
                    'phone' => '5538991045205'
                ])
            ]
        ]));

        $data2 = json_decode($response2, true);
        if (isset($data2['message'])) {
            echo "   Mensagem APROVADA: " . substr($data2['message'], 0, 100) . "...\n";
        }

        // Verificar se as mensagens são diferentes
        if (isset($data1['message']) && isset($data2['message'])) {
            if (strpos($data2['message'], 'APROVADA') !== false && strpos($data2['message'], 'DISPONÍVEL') !== false) {
                echo "\n   ✅ Mensagens são diferentes! Transação aprovada tem mensagem especial.\n";
            } else {
                echo "\n   ⚠️ Mensagens parecem iguais. Verificar se modificação funcionou.\n";
            }
        }
    }

} catch (Exception $e) {
    echo "❌ Erro no teste: " . $e->getMessage() . "\n";
}

echo "\n=== CONCLUSÃO ===\n";
echo "Se você vir 'APROVADA' e 'DISPONÍVEL' na mensagem de teste,\n";
echo "então o sistema agora funciona para ambos os status!\n";
?>
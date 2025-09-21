<?php
/**
 * Teste do modo síncrono de notificações
 * Tenta usar CashbackNotifier diretamente
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/classes/CashbackNotifier.php';
require_once __DIR__ . '/utils/NotificationTrigger.php';

echo "=== TESTE MODO SÍNCRONO ===\n\n";

// Testar NotificationTrigger em modo síncrono
echo "1. Testando NotificationTrigger (modo síncrono):\n";
$result1 = NotificationTrigger::send(506, ['async' => false, 'debug' => true]);

echo "   - Sucesso: " . ($result1['success'] ? 'SIM' : 'NÃO') . "\n";
echo "   - Mensagem: " . $result1['message'] . "\n";
echo "   - Async: " . ($result1['async'] ? 'SIM' : 'NÃO') . "\n\n";

// Testar CashbackNotifier diretamente
echo "2. Testando CashbackNotifier diretamente:\n";
try {
    $notifier = new CashbackNotifier();
    $result2 = $notifier->notifyNewTransaction(506);

    echo "   - Sucesso: " . ($result2['success'] ? 'SIM' : 'NÃO') . "\n";
    echo "   - Mensagem: " . $result2['message'] . "\n";
    echo "   - Telefone: " . ($result2['phone'] ?? 'N/A') . "\n";
    echo "   - Tipo mensagem: " . ($result2['message_type'] ?? 'N/A') . "\n";

} catch (Exception $e) {
    echo "   ❌ Erro: " . $e->getMessage() . "\n";
}

echo "\n=== RESULTADO ===\n";
echo "Se o modo síncrono funcionar, podemos alterar a configuração padrão.\n";
?>
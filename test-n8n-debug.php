<?php
require_once 'config/constants.php';
require_once 'api/n8n-webhook.php';

// Testar com uma transação específica
$transactionId = 672; // Use um ID real do seu sistema
$result = N8nWebhook::sendTransactionData($transactionId, 'nova_transacao');

echo "Resultado do teste: " . ($result ? "Sucesso" : "Falha") . "\n";
echo "Verifique os logs para detalhes completos.\n";
?>
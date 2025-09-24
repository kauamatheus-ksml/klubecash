<?php
/**
 * Script para executar notificação única em background
 */

$transactionId = $argv[1] ?? null;
$action = $argv[2] ?? 'created';

if (!$transactionId) {
    echo "Erro: ID da transação não fornecido\n";
    exit(1);
}

// Incluir sistema de notificação
require_once __DIR__ . '/classes/FixedBrutalNotificationSystem.php';

try {
    $system = new FixedBrutalNotificationSystem();
    $result = $system->forceNotifyTransaction($transactionId);

    $status = $result['success'] ? 'SUCESSO' : 'ERRO';
    echo "[" . date('Y-m-d H:i:s') . "] {$status}: {$result['message']}\n";

} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ERRO CRÍTICO: " . $e->getMessage() . "\n";
}
?>
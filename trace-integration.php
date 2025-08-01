<?php
/**
 * RASTREADOR DE INTEGRAÇÃO - Sistema de Notificações
 * 
 * Este arquivo nos permite rastrear exatamente onde as integrações
 * foram (ou não foram) ativadas durante uma transação real.
 * 
 * É como um "GPS" que nos mostra o caminho que os dados percorrem.
 */

// Configurar para capturar TODOS os erros e avisos
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Função para registrar eventos do trace
function logTraceEvent($location, $transactionId, $message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$location}: {$message}";
    
    if ($transactionId) {
        $logMessage .= " (Transaction ID: {$transactionId})";
    }
    
    if ($data) {
        $logMessage .= " - Data: " . json_encode($data);
    }
    
    // Log em arquivo específico
    error_log($logMessage, 3, 'integration_trace.log');
    
    // Também exibir na tela para debug imediato
    echo "<div style='margin: 5px 0; padding: 10px; background: #f8f9fa; border-left: 3px solid #007bff;'>";
    echo "<strong>[{$timestamp}]</strong> {$location}: {$message}";
    if ($transactionId) echo " <em>(ID: {$transactionId})</em>";
    echo "</div>";
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>🔍 Rastreador de Integração - Klube Cash</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #FF7A00; padding-bottom: 10px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Rastreador de Integração de Notificações</h1>
    
    <div class="section">
        <h2>📝 Como Usar Este Rastreador</h2>
        <p><strong>Objetivo:</strong> Descobrir exatamente onde nossa integração deveria funcionar mas não está funcionando.</p>
        <p><strong>Método:</strong> Vamos adicionar "marcadores" temporários no código para ver o caminho que os dados percorrem.</p>
    </div>

<?php

logTraceEvent("TRACE_START", null, "Iniciando rastreamento de integração");

// Verificar se há logs de integração recentes
echo "<div class='section'>";
echo "<h2>📊 Verificação de Logs Recentes</h2>";

if (file_exists('integration_trace.log')) {
    $logs = file('integration_trace.log');
    $recentLogs = array_slice($logs, -10); // Últimas 10 linhas
    
    echo "<h3>Últimos 10 eventos registrados:</h3>";
    foreach ($recentLogs as $log) {
        echo "<div style='font-family: monospace; font-size: 12px; margin: 2px 0; padding: 5px; background: #f1f1f1;'>";
        echo htmlspecialchars(trim($log));
        echo "</div>";
    }
} else {
    echo "<p>Arquivo de trace ainda não foi criado. Registre uma transação para começar o rastreamento.</p>";
}

echo "</div>";

// Verificar última transação registrada
echo "<div class='section'>";
echo "<h2>🔄 Última Transação Registrada</h2>";

try {
    require_once 'config/database.php';
    $db = Database::getConnection();
    
    $stmt = $db->prepare("
        SELECT t.*, u.nome, u.telefone, l.nome_fantasia 
        FROM transacoes_cashback t
        JOIN usuarios u ON t.usuario_id = u.id
        JOIN lojas l ON t.loja_id = l.id
        ORDER BY t.data_transacao DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $lastTransaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($lastTransaction) {
        echo "<h3>Dados da última transação:</h3>";
        echo "<div style='background: #e9ecef; padding: 15px; border-radius: 5px;'>";
        echo "<strong>ID:</strong> {$lastTransaction['id']}<br>";
        echo "<strong>Cliente:</strong> {$lastTransaction['nome']}<br>";
        echo "<strong>Telefone:</strong> {$lastTransaction['telefone']}<br>";
        echo "<strong>Loja:</strong> {$lastTransaction['nome_fantasia']}<br>";
        echo "<strong>Valor:</strong> R$ " . number_format($lastTransaction['valor_total'], 2, ',', '.') . "<br>";
        echo "<strong>Status:</strong> {$lastTransaction['status']}<br>";
        echo "<strong>Data:</strong> {$lastTransaction['data_transacao']}<br>";
        echo "</div>";
        
        logTraceEvent("LAST_TRANSACTION", $lastTransaction['id'], "Última transação encontrada", [
            'cliente' => $lastTransaction['nome'],
            'telefone' => $lastTransaction['telefone'],
            'status' => $lastTransaction['status']
        ]);
    } else {
        echo "<p>Nenhuma transação encontrada no banco de dados.</p>";
        logTraceEvent("LAST_TRANSACTION", null, "Nenhuma transação encontrada");
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro ao buscar última transação: " . $e->getMessage() . "</p>";
    logTraceEvent("LAST_TRANSACTION", null, "Erro ao buscar transação: " . $e->getMessage());
}

echo "</div>";

?>

    <div class="section">
        <h2>🎯 Próximos Passos para Diagnóstico</h2>
        <p><strong>Passo 1:</strong> Adicione marcadores temporários nos pontos de integração</p>
        <p><strong>Passo 2:</strong> Registre uma nova transação</p>
        <p><strong>Passo 3:</strong> Recarregue esta página para ver o trace</p>
        <p><strong>Passo 4:</strong> Analise onde a integração foi (ou não foi) ativada</p>
    </div>

</div>
</body>
</html>
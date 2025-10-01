<?php
// test-whatsapp-automation.php
require_once 'config/database.php';
require_once 'classes/WhatsAppEvolutionAutomation.php';

$automation = new WhatsAppEvolutionAutomation();

// Verificar status da instância
echo "<h2>Status da Instância Evolution</h2>";
$status = $automation->verificarStatusInstancia();
echo "<pre>";
print_r($status);
echo "</pre>";

// Testar envio para uma transação específica
if (isset($_GET['test_transaction'])) {
    $transactionId = $_GET['test_transaction'];
    echo "<h2>Testando envio para transação #{$transactionId}</h2>";
    $result = $automation->notificarCashback($transactionId);
    echo "<pre>";
    print_r($result);
    echo "</pre>";
}

// Listar últimas notificações enviadas
echo "<h2>Últimas Notificações Enviadas</h2>";
$db = Database::getConnection();
$stmt = $db->query("
    SELECT * FROM whatsapp_evolution_logs 
    ORDER BY created_at DESC 
    LIMIT 10
");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Transação</th><th>Telefone</th><th>Sucesso</th><th>Data</th></tr>";
foreach ($logs as $log) {
    echo "<tr>";
    echo "<td>{$log['id']}</td>";
    echo "<td>{$log['transaction_id']}</td>";
    echo "<td>{$log['phone']}</td>";
    echo "<td>" . ($log['success'] ? '✅' : '❌') . "</td>";
    echo "<td>{$log['created_at']}</td>";
    echo "</tr>";
}
echo "</table>";
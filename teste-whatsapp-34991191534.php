<?php
// teste-whatsapp-34991191534.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'classes/SaldoConsulta.php';

$telefone = '34991191534';

echo "<h1>🧪 Teste WhatsApp - {$telefone}</h1>";

try {
    // 1. Verificar usuário no banco
    echo "<h3>📊 Verificação no Banco:</h3>";
    $db = Database::getConnection();
    
    $stmt = $db->prepare("
        SELECT id, nome, email, telefone, tipo_cliente, status, cpf 
        FROM usuarios 
        WHERE telefone = ? OR telefone = ? OR telefone = ?
    ");
    $stmt->execute([$telefone, "55{$telefone}", "0{$telefone}"]);
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
        echo "✅ <strong>USUÁRIO ENCONTRADO</strong><br>";
        echo "ID: {$user['id']}<br>";
        echo "Nome: {$user['nome']}<br>";
        echo "Email: " . ($user['email'] ?: 'Não informado') . "<br>";
        echo "Telefone: {$user['telefone']}<br>";
        echo "Tipo: {$user['tipo_cliente']}<br>";
        echo "Status: {$user['status']}<br>";
        echo "CPF: " . ($user['cpf'] ?: 'Não informado') . "<br>";
        echo "</div>";
        
        $tipoCliente = $user['tipo_cliente'];
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
        echo "❌ <strong>USUÁRIO NÃO ENCONTRADO</strong>";
        echo "</div>";
        $tipoCliente = 'inexistente';
    }
    
    // 2. Testar consulta de saldo
    echo "<h3>💰 Teste Consulta Saldo:</h3>";
    $saldoConsulta = new SaldoConsulta();
    $resultado = $saldoConsulta->consultarSaldoPorTelefone($telefone);
    
    echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
    echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo "</pre>";
    
    // 3. Determinar menu a ser exibido
    echo "<h3>📋 Menu a ser Exibido:</h3>";
    if ($tipoCliente === 'visitante') {
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
        echo "<strong>🏪 Klube Cash - Bem-vindo!</strong><br><br>";
        echo "Digite o número da opção desejada:<br><br>";
        echo "1️⃣ Consultar Saldo<br>";
        echo "2️⃣ Completar Cadastro";
        echo "</div>";
    } elseif ($tipoCliente === 'completo') {
        echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px;'>";
        echo "<strong>🏪 Klube Cash - Bem-vindo!</strong><br><br>";
        echo "Digite o número da opção desejada:<br><br>";
        echo "1️⃣ Consultar Saldo<br>";
        echo "2️⃣ Atualizar Cadastro";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
        echo "<strong>🏪 Klube Cash - Bem-vindo!</strong><br><br>";
        echo "1️⃣ Consultar Saldo<br>";
        echo "<small>Usuário não encontrado - menu básico</small>";
        echo "</div>";
    }
    
    // 4. Testar API de cadastro se for visitante
    if ($tipoCliente === 'visitante') {
        echo "<h3>📝 Teste API Completar Cadastro:</h3>";
        
        $testData = [
            'phone' => $telefone,
            'action' => 'iniciar',
            'secret' => WHATSAPP_BOT_SECRET
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, SITE_URL . '/api/whatsapp-completar-cadastro.php');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "<p><strong>HTTP Code:</strong> {$httpCode}</p>";
        echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
        echo $response;
        echo "</pre>";
    }
    
    // 5. Simular envio via WhatsApp Bot
    echo "<h3>📱 Teste Simulação WhatsApp Bot:</h3>";
    
    $botTestData = [
        'phone' => $telefone,
        'message' => '🧪 Teste do sistema de menu dinâmico',
        'secret' => WHATSAPP_BOT_SECRET
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, WHATSAPP_BOT_URL . '/send-message');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($botTestData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    $botResponse = curl_exec($ch);
    $botHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p><strong>Bot HTTP Code:</strong> {$botHttpCode}</p>";
    echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
    echo $botResponse ?: 'Erro na conexão com o bot';
    echo "</pre>";
    
    // 6. Resumo final
    echo "<h3>📊 Resumo do Teste:</h3>";
    echo "<div style='background: #e2e3e5; padding: 15px; border-radius: 5px;'>";
    echo "<strong>Número testado:</strong> {$telefone}<br>";
    echo "<strong>Status no banco:</strong> " . ($tipoCliente !== 'inexistente' ? 'Encontrado' : 'Não encontrado') . "<br>";
    echo "<strong>Tipo de cliente:</strong> {$tipoCliente}<br>";
    echo "<strong>Menu apropriado:</strong> " . ($tipoCliente === 'visitante' ? 'Com opção Completar Cadastro' : ($tipoCliente === 'completo' ? 'Com opção Atualizar Cadastro' : 'Menu básico')) . "<br>";
    echo "<strong>API funcionando:</strong> " . ($httpCode == 200 ? 'Sim' : 'Não') . "<br>";
    echo "<strong>Bot conectado:</strong> " . ($botHttpCode == 200 ? 'Sim' : 'Não') . "<br>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "❌ <strong>ERRO:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<br><p><a href='/' style='color: #007bff;'>← Voltar ao início</a></p>";
?>
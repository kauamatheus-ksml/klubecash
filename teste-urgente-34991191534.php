<?php
// debug/teste-urgente-34991191534.php
// TESTE URGENTE ESPECÍFICO

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🚨 DEBUG URGENTE - 34991191534</h1>";
echo "<p>Executado em: " . date('Y-m-d H:i:s') . "</p>";

require_once __DIR__ . '/../classes/SaldoConsulta.php';
require_once __DIR__ . '/../config/constants.php';

$telefone = '34991191534';

try {
    echo "<h2>1. TESTE DIRETO NO BANCO:</h2>";
    
    $db = Database::getConnection();
    
    // Testar várias variações do telefone
    $variantes = [
        '34991191534',
        '5534991191534',
        '+5534991191534',
        '(34) 99119-1534',
        '34 99119-1534'
    ];
    
    foreach ($variantes as $var) {
        $cleanVar = preg_replace('/[^0-9]/', '', $var);
        
        $stmt = $db->prepare("SELECT id, nome, email, senha_hash, telefone, tipo_cliente FROM usuarios WHERE REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(telefone, '(', ''), ')', ''), '-', ''), ' ', ''), '+', '') = :telefone AND tipo = 'cliente' AND status = 'ativo'");
        $stmt->execute([':telefone' => $cleanVar]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "<div style='background: #d4edda; padding: 10px; margin: 5px; border-radius: 5px;'>";
            echo "✅ <strong>ENCONTRADO com variante: {$var}</strong><br>";
            echo "ID: {$user['id']}<br>";
            echo "Nome: {$user['nome']}<br>";
            echo "Email: " . ($user['email'] ?: '<span style=\"color:red\">VAZIO</span>') . "<br>";
            echo "Senha: " . (empty($user['senha_hash']) ? '<span style="color:red">VAZIO</span>' : '<span style="color:green">PREENCHIDO</span>') . "<br>";
            echo "Telefone BD: {$user['telefone']}<br>";
            echo "Tipo Cliente: " . ($user['tipo_cliente'] ?: 'NULL') . "<br>";
            echo "</div>";
            break;
        }
    }
    
    echo "<h2>2. TESTE COM SaldoConsulta:</h2>";
    
    $saldoConsulta = new SaldoConsulta();
    
    echo "<h3>A) buscarUsuarioPorTelefone:</h3>";
    $usuario = $saldoConsulta->buscarUsuarioPorTelefone($telefone);
    
    if ($usuario) {
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px;'>";
        echo "✅ Usuário encontrado pela classe<br>";
        echo "Nome: {$usuario['nome']}<br>";
        echo "Email: " . ($usuario['email'] ?: '<span style="color:red">VAZIO</span>') . "<br>";
        echo "Senha: " . (empty($usuario['senha_hash']) ? '<span style="color:red">VAZIO</span>' : '<span style="color:green">PREENCHIDO</span>') . "<br>";
        echo "</div>";
        
        echo "<h3>B) determinarTipoCliente:</h3>";
        if (method_exists($saldoConsulta, 'determinarTipoCliente')) {
            $tipo = $saldoConsulta->determinarTipoCliente($usuario);
            echo "<div style='background: #d1ecf1; padding: 10px; border-radius: 5px;'>";
            echo "📊 Tipo determinado: <strong>{$tipo}</strong>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
            echo "❌ Método determinarTipoCliente NÃO EXISTE na classe";
            echo "</div>";
        }
        
    } else {
        echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
        echo "❌ Usuário NÃO encontrado pela classe SaldoConsulta";
        echo "</div>";
    }
    
    echo "<h2>3. SIMULAÇÃO DA API:</h2>";
    
    if (method_exists($saldoConsulta, 'buscarDadosParaAPI')) {
        $resultadoAPI = $saldoConsulta->buscarDadosParaAPI($telefone);
        
        echo "<h3>Resultado da API:</h3>";
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
        echo json_encode($resultadoAPI, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        echo "</pre>";
        
        if ($resultadoAPI['user_found'] && $resultadoAPI['user_data']) {
            $tipo = $saldoConsulta->determinarTipoCliente($resultadoAPI['user_data']);
            
            echo "<h3>Menu que deveria aparecer:</h3>";
            echo "<div style='background: #e2e3e5; padding: 15px; border-radius: 5px; font-family: monospace;'>";
            
            if ($tipo === 'visitante') {
                echo "🏪 <strong>Klube Cash</strong> - Bem-vindo!<br><br>";
                echo "Digite o número da opção desejada:<br><br>";
                echo "1️⃣ Consultar Saldo<br>";
                echo "2️⃣ <strong style='color: blue;'>Completar Cadastro</strong>";
            } elseif ($tipo === 'completo') {
                echo "🏪 <strong>Klube Cash</strong> - Bem-vindo!<br><br>";
                echo "Digite o número da opção desejada:<br><br>";
                echo "1️⃣ Consultar Saldo<br>";
                echo "2️⃣ <strong style='color: green;'>Atualizar Cadastro</strong>";
            } else {
                echo "🏪 <strong>Klube Cash</strong> - Bem-vindo!<br><br>";
                echo "Digite o número da opção desejada:<br><br>";
                echo "1️⃣ Consultar Saldo";
            }
            
            echo "</div>";
        }
        
    } else {
        echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
        echo "❌ Método buscarDadosParaAPI NÃO EXISTE na classe";
        echo "</div>";
    }
    
    echo "<h2>4. TESTE REAL DA API:</h2>";
    
    $postData = json_encode([
        'phone' => $telefone,
        'secret' => WHATSAPP_BOT_SECRET
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, SITE_URL . '/api/whatsapp-saldo.php');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<h3>Resposta da API (HTTP {$httpCode}):</h3>";
    echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
    
    if ($response) {
        $jsonResponse = json_decode($response, true);
        if ($jsonResponse) {
            echo json_encode($jsonResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
            // Mostrar qual menu o bot deveria exibir
            if (isset($jsonResponse['client_type'])) {
                echo "\n\n=== MENU QUE O BOT DEVERIA EXIBIR ===\n";
                echo "Tipo detectado: " . $jsonResponse['client_type'] . "\n";
            }
        } else {
            echo "ERRO: Resposta não é JSON válido\n";
            echo $response;
        }
    } else {
        echo "ERRO: Sem resposta da API";
    }
    
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
    echo "❌ <strong>ERRO CRÍTICO:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<br><p><strong>EXECUTE ESTE ARQUIVO AGORA:</strong> " . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "</p>";
?>
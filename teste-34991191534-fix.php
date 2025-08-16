<?php
// teste-34991191534-fix.php - VERSÃO CORRIGIDA
// DEBUG URGENTE CORRIGIDO - 34991191534

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🚨 DEBUG URGENTE CORRIGIDO - 34991191534</h1>";
echo "<p>Executado em: " . date('Y-m-d H:i:s') . "</p>";

$telefone = '34991191534';

// CARREGAR AS CREDENCIAIS CORRETAS DO DATABASE.PHP
require_once __DIR__ . '/config/database.php';

echo "<h2>3. TESTE DIRETO NO BANCO:</h2>";

try {
    // USAR A CLASSE DATABASE DO SISTEMA
    $pdo = Database::getConnection();
    
    echo "✅ Conexão com banco estabelecida usando Database::getConnection()!<br><br>";
    
    // BUSCAR O USUÁRIO COM DIFERENTES VARIAÇÕES
    echo "<h3>A) Busca por telefone exato:</h3>";
    
    $variantes = [
        '34991191534',
        '5534991191534', 
        '+5534991191534',
        '(34) 99119-1534',
        '34 99119-1534'
    ];
    
    $usuarioEncontrado = null;
    
    foreach ($variantes as $var) {
        $cleanVar = preg_replace('/[^0-9]/', '', $var);
        
        $stmt = $pdo->prepare("
            SELECT id, nome, email, senha_hash, telefone, tipo, status, tipo_cliente 
            FROM usuarios 
            WHERE REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(telefone, '(', ''), ')', ''), '-', ''), ' ', ''), '+', '') = :telefone 
            AND tipo = 'cliente' 
            AND status = 'ativo'
        ");
        $stmt->execute([':telefone' => $cleanVar]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "<div style='background: #d4edda; padding: 10px; margin: 5px; border-radius: 5px;'>";
            echo "✅ <strong>ENCONTRADO com variante: {$var} (clean: {$cleanVar})</strong><br>";
            echo "ID: {$user['id']}<br>";
            echo "Nome: {$user['nome']}<br>";
            echo "Email: " . ($user['email'] ?: '<span style="color:red">VAZIO</span>') . "<br>";
            echo "Senha: " . (empty($user['senha_hash']) ? '<span style="color:red">VAZIO</span>' : '<span style="color:green">PREENCHIDO</span>') . "<br>";
            echo "Telefone BD: {$user['telefone']}<br>";
            echo "Tipo: {$user['tipo']}<br>";
            echo "Status: {$user['status']}<br>";
            echo "Tipo Cliente: " . ($user['tipo_cliente'] ?: 'NULL') . "<br>";
            echo "</div>";
            
            $usuarioEncontrado = $user;
            break;
        } else {
            echo "❌ Não encontrado com: {$var} (clean: {$cleanVar})<br>";
        }
    }
    
    if (!$usuarioEncontrado) {
        echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
        echo "❌ <strong>USUÁRIO NÃO ENCONTRADO EM NENHUMA VARIAÇÃO!</strong>";
        echo "</div>";
        
        // BUSCAR QUALQUER USUÁRIO COM TELEFONE PARECIDO
        echo "<h3>B) Busca por telefones similares:</h3>";
        
        $stmt = $pdo->prepare("
            SELECT id, nome, telefone 
            FROM usuarios 
            WHERE (telefone LIKE :telefone1 
            OR telefone LIKE :telefone2 
            OR telefone LIKE :telefone3)
            AND tipo = 'cliente'
            LIMIT 10
        ");
        $stmt->execute([
            ':telefone1' => '%99119%',
            ':telefone2' => '%1534%', 
            ':telefone3' => '%34991%'
        ]);
        $similares = $stmt->fetchAll();
        
        if ($similares) {
            echo "📞 Telefones similares encontrados:<br>";
            foreach ($similares as $sim) {
                echo "- ID {$sim['id']}: {$sim['nome']} - {$sim['telefone']}<br>";
            }
        } else {
            echo "❌ Nenhum telefone similar encontrado<br>";
        }
        
    } else {
        echo "<h3>C) Determinando tipo de cliente:</h3>";
        
        $temEmail = !empty($usuarioEncontrado['email']);
        $temSenha = !empty($usuarioEncontrado['senha_hash']);
        
        if ($temEmail && $temSenha) {
            $tipoCliente = 'completo';
        } else {
            $tipoCliente = 'visitante';
        }
        
        echo "<div style='background: #d1ecf1; padding: 10px; border-radius: 5px;'>";
        echo "📊 <strong>Análise do cadastro:</strong><br>";
        echo "- Tem email: " . ($temEmail ? 'SIM' : 'NÃO') . "<br>";
        echo "- Tem senha: " . ($temSenha ? 'SIM' : 'NÃO') . "<br>";
        echo "- <strong>Tipo determinado: {$tipoCliente}</strong><br>";
        echo "</div>";
        
        echo "<h3>D) Menu que deveria aparecer:</h3>";
        echo "<div style='background: #e2e3e5; padding: 15px; border-radius: 5px; font-family: monospace;'>";
        
        if ($tipoCliente === 'visitante') {
            echo "🏪 <strong>Klube Cash</strong> - Bem-vindo!<br><br>";
            echo "Digite o número da opção desejada:<br><br>";
            echo "1️⃣ Consultar Saldo<br>";
            echo "<strong style='color: blue;'>2️⃣ Completar Cadastro</strong>";
        } else {
            echo "🏪 <strong>Klube Cash</strong> - Bem-vindo!<br><br>";
            echo "Digite o número da opção desejada:<br><br>";
            echo "1️⃣ Consultar Saldo<br>";
            echo "<strong style='color: green;'>2️⃣ Atualizar Cadastro</strong>";
        }
        
        echo "</div>";
        
        // BUSCAR SALDO DO USUÁRIO
        echo "<h3>E) Saldo do usuário:</h3>";
        
        $stmt = $pdo->prepare("
            SELECT l.nome_fantasia, 
                   SUM(CASE WHEN t.status = 'aprovado' THEN t.valor_cliente ELSE 0 END) as saldo_disponivel,
                   SUM(CASE WHEN t.status IN ('pendente', 'pagamento_pendente') THEN t.valor_cliente ELSE 0 END) as saldo_pendente
            FROM transacoes_cashback t
            INNER JOIN lojas l ON t.loja_id = l.id  
            WHERE t.usuario_id = :user_id
            GROUP BY t.loja_id, l.nome_fantasia
            HAVING saldo_disponivel > 0 OR saldo_pendente > 0
            ORDER BY saldo_disponivel DESC
        ");
        $stmt->execute([':user_id' => $usuarioEncontrado['id']]);
        $saldos = $stmt->fetchAll();
        
        if ($saldos) {
            echo "💰 Saldos por loja:<br>";
            $totalDisponivel = 0;
            $totalPendente = 0;
            foreach ($saldos as $saldo) {
                echo "- {$saldo['nome_fantasia']}: R$ " . number_format($saldo['saldo_disponivel'], 2, ',', '.') . " (disponível)<br>";
                if ($saldo['saldo_pendente'] > 0) {
                    echo "  + R$ " . number_format($saldo['saldo_pendente'], 2, ',', '.') . " (pendente)<br>";
                }
                $totalDisponivel += $saldo['saldo_disponivel'];
                $totalPendente += $saldo['saldo_pendente'];
            }
            echo "<br><strong>TOTAL: R$ " . number_format($totalDisponivel, 2, ',', '.') . " disponível</strong><br>";
            if ($totalPendente > 0) {
                echo "<strong>PENDENTE: R$ " . number_format($totalPendente, 2, ',', '.') . "</strong><br>";
            }
        } else {
            echo "❌ Usuário sem saldo em nenhuma loja<br>";
        }
        
        // TESTE DA API WHATSAPP-SALDO
        echo "<h3>F) Teste da API WhatsApp-Saldo:</h3>";
        
        // Incluir constants para pegar o secret
        require_once __DIR__ . '/config/constants.php';
        
        $postData = json_encode([
            'phone' => $telefone,
            'secret' => WHATSAPP_BOT_SECRET
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://klubecash.com/api/whatsapp-saldo.php');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "<strong>Resposta da API (HTTP {$httpCode}):</strong><br>";
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px; font-size: 12px;'>";
        
        if ($response) {
            $jsonResponse = json_decode($response, true);
            if ($jsonResponse) {
                echo json_encode($jsonResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                
                // ANALISAR A RESPOSTA
                echo "\n\n=== ANÁLISE DA RESPOSTA ===\n";
                echo "User Found: " . ($jsonResponse['user_found'] ? 'SIM' : 'NÃO') . "\n";
                echo "Client Type: " . ($jsonResponse['client_type'] ?? 'NÃO INFORMADO') . "\n";
                echo "Success: " . ($jsonResponse['success'] ? 'SIM' : 'NÃO') . "\n";
                
                if (isset($jsonResponse['client_type'])) {
                    echo "\n=== MENU QUE O BOT DEVERIA EXIBIR ===\n";
                    if ($jsonResponse['client_type'] === 'visitante') {
                        echo "MENU VISITANTE (2️⃣ Completar Cadastro)\n";
                    } elseif ($jsonResponse['client_type'] === 'completo') {
                        echo "MENU COMPLETO (2️⃣ Atualizar Cadastro)\n";
                    } else {
                        echo "MENU BÁSICO (apenas 1️⃣ Consultar Saldo)\n";
                    }
                }
                
            } else {
                echo "ERRO: Resposta não é JSON válido\n";
                echo $response;
            }
        } else {
            echo "ERRO: Sem resposta da API";
        }
        
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
    echo "❌ <strong>ERRO CRÍTICO:</strong> " . $e->getMessage();
    echo "<br>Stack trace: " . $e->getTraceAsString();
    echo "</div>";
}

echo "<br><p><strong>🚨 DIAGNÓSTICO COMPLETO EXECUTADO!</strong></p>";
?>
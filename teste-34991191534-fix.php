<?php
// teste-34991191534-fix.php
// DEBUG URGENTE CORRIGIDO

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🚨 DEBUG URGENTE CORRIGIDO - 34991191534</h1>";
echo "<p>Executado em: " . date('Y-m-d H:i:s') . "</p>";

$telefone = '34991191534';

// PRIMEIRO: Descobrir a estrutura de arquivos
echo "<h2>1. ESTRUTURA DE ARQUIVOS:</h2>";
echo "Diretório atual: " . __DIR__ . "<br>";
echo "Arquivos no diretório atual:<br>";

$files = scandir(__DIR__);
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        if (is_dir(__DIR__ . '/' . $file)) {
            echo "📁 {$file}/<br>";
        } else {
            echo "📄 {$file}<br>";
        }
    }
}

// TENTAR DIFERENTES CAMINHOS
$possiveisCaminhos = [
    __DIR__ . '/classes/SaldoConsulta.php',
    __DIR__ . '/../classes/SaldoConsulta.php',
    __DIR__ . '/../../classes/SaldoConsulta.php',
    __DIR__ . '/config/database.php',
    __DIR__ . '/config/constants.php'
];

echo "<h2>2. TESTANDO CAMINHOS:</h2>";
foreach ($possiveisCaminhos as $caminho) {
    if (file_exists($caminho)) {
        echo "✅ EXISTE: {$caminho}<br>";
    } else {
        echo "❌ NÃO EXISTE: {$caminho}<br>";
    }
}

// TESTE DIRETO NO BANCO SEM INCLUDES
echo "<h2>3. TESTE DIRETO NO BANCO:</h2>";

try {
    // Configuração de banco (copie da constants.php)
    $host = 'localhost';
    $dbname = 'u383946504_klube_cash';
    $username = 'u383946504_admin';
    $password = '!#Klube2024@Cash';
    
    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    
    echo "✅ Conexão com banco estabelecida!<br><br>";
    
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
            WHERE telefone LIKE :telefone1 
            OR telefone LIKE :telefone2 
            OR telefone LIKE :telefone3
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
            foreach ($saldos as $saldo) {
                echo "- {$saldo['nome_fantasia']}: R$ " . number_format($saldo['saldo_disponivel'], 2, ',', '.') . " (disponível)<br>";
                if ($saldo['saldo_pendente'] > 0) {
                    echo "  + R$ " . number_format($saldo['saldo_pendente'], 2, ',', '.') . " (pendente)<br>";
                }
            }
        } else {
            echo "❌ Usuário sem saldo em nenhuma loja<br>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
    echo "❌ <strong>ERRO DE BANCO:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<br><p><strong>RESULTADO:</strong> Execute este arquivo agora para ver o que está acontecendo com o número 34991191534!</p>";
?>
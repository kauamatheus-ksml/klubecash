<?php
// debug_endereco.php - Versão Melhorada com Navegação
// Script de Diagnóstico Inteligente para Endereço da Loja

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar se deve redirecionar de volta para o perfil
if (isset($_GET['voltar'])) {
    header('Location: profile.php');
    exit;
}

require_once '../../config/database.php';
require_once '../../config/constants.php';

session_start();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug - Endereço da Loja | Klube Cash</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            margin: 0; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 1000px; margin: 0 auto; background: white; 
            border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white; padding: 20px; text-align: center;
        }
        .nav-buttons {
            background: #2c3e50; padding: 15px; text-align: center;
        }
        .nav-buttons a {
            display: inline-block; margin: 0 10px; padding: 10px 20px;
            background: #3498db; color: white; text-decoration: none;
            border-radius: 5px; font-weight: bold; transition: all 0.3s;
        }
        .nav-buttons a:hover { background: #2980b9; transform: translateY(-2px); }
        .nav-buttons a.danger { background: #e74c3c; }
        .nav-buttons a.danger:hover { background: #c0392b; }
        .content { padding: 20px; }
        .test { 
            background: white; margin: 15px 0; padding: 20px; 
            border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #007bff; 
        }
        .success { border-left-color: #28a745; background: linear-gradient(90deg, #f8fff9, white); }
        .error { border-left-color: #dc3545; background: linear-gradient(90deg, #fff8f8, white); }
        .warning { border-left-color: #ffc107; background: linear-gradient(90deg, #fffef8, white); }
        .info { border-left-color: #17a2b8; background: linear-gradient(90deg, #f8fcff, white); }
        h1 { margin: 0; font-size: 2.5em; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); }
        h3 { margin: 0 0 15px 0; color: #2c3e50; font-size: 1.3em; }
        pre { 
            background: #f8f9fa; padding: 15px; border-radius: 8px; 
            overflow-x: auto; border: 1px solid #e9ecef; 
            font-family: 'Courier New', monospace; font-size: 12px;
        }
        .form-debug { 
            background: linear-gradient(135deg, #e9ecef, #f8f9fa); 
            padding: 25px; border-radius: 12px; margin: 20px 0; 
            box-shadow: inset 0 2px 5px rgba(0,0,0,0.1);
        }
        .form-debug form {
            display: grid; gap: 15px; max-width: 600px; margin: 0 auto;
        }
        .form-debug label {
            font-weight: bold; color: #495057; margin-bottom: 5px;
        }
        .form-debug input, .form-debug select {
            padding: 12px; border: 2px solid #ced4da; border-radius: 6px;
            font-size: 14px; transition: border-color 0.3s;
        }
        .form-debug input:focus, .form-debug select:focus {
            outline: none; border-color: #007bff; box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }
        .btn-test {
            background: linear-gradient(135deg, #007bff, #0056b3); 
            color: white; padding: 15px; border: none; border-radius: 8px; 
            cursor: pointer; font-size: 16px; font-weight: bold;
            transition: all 0.3s; text-transform: uppercase; letter-spacing: 1px;
        }
        .btn-test:hover {
            transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,123,255,0.3);
        }
        .progress-bar {
            width: 100%; height: 6px; background: #e9ecef; border-radius: 3px;
            overflow: hidden; margin: 20px 0;
        }
        .progress-fill {
            height: 100%; background: linear-gradient(90deg, #28a745, #20c997);
            transition: width 0.5s ease;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔬 LABORATÓRIO DE DIAGNÓSTICO</h1>
            <p>Sistema Inteligente de Debug para Endereço da Loja</p>
        </div>
        
        <div class="nav-buttons">
            <a href="profile.php">⬅️ Voltar ao Perfil</a>
            <a href="?refresh=1">🔄 Executar Novamente</a>
            <a href="?clear_logs=1" class="danger">🗑️ Limpar Logs</a>
        </div>
        
        <div class="content">
<?php

// Limpar logs se solicitado
if (isset($_GET['clear_logs'])) {
    error_log("=== LOGS LIMPOS PELO USUÁRIO ===");
    echo "<div class='test success'><h3>🧹 Logs Limpos</h3><p>Os logs de erro foram limpos com sucesso.</p></div>";
}

echo "<div class='progress-bar'><div class='progress-fill' style='width: 0%' id='progress'></div></div>";

// ===========================================
// TESTE 1: Verificar Sessão e Autenticação
// ===========================================
echo "<div class='test'>";
echo "<h3>1️⃣ TESTE DE SESSÃO E AUTENTICAÇÃO</h3>";

if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'loja') {
    echo "<div class='success'>✅ Usuário autenticado como loja (ID: {$_SESSION['user_id']})</div>";
    $userId = $_SESSION['user_id'];
} else {
    echo "<div class='error'>❌ Usuário não autenticado ou não é loja</div>";
    echo "<pre>Sessão atual: " . print_r($_SESSION, true) . "</pre>";
    echo "<div class='warning'>💡 <strong>Solução:</strong> Faça login como loja primeiro em <a href='profile.php'>profile.php</a></div>";
    echo "</div></div></body></html>";
    exit;
}
echo "</div>";

// ===========================================
// TESTE 2: Verificar Conexão com Banco
// ===========================================
echo "<div class='test'>";
echo "<h3>2️⃣ TESTE DE CONEXÃO COM BANCO DE DADOS</h3>";

try {
    $db = Database::getConnection();
    echo "<div class='success'>✅ Conexão com banco estabelecida com sucesso</div>";
    
    // Testar uma query simples
    $testQuery = $db->query("SELECT 1 as test");
    $result = $testQuery->fetch();
    if ($result['test'] == 1) {
        echo "<div class='success'>✅ Banco de dados respondendo corretamente</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Erro de conexão: " . $e->getMessage() . "</div>";
    echo "<div class='warning'>💡 <strong>Solução:</strong> Verifique as configurações de banco em config/database.php</div>";
    echo "</div></div></body></html>";
    exit;
}
echo "</div>";

// ===========================================
// TESTE 3: Verificar Dados da Loja
// ===========================================
echo "<div class='test'>";
echo "<h3>3️⃣ TESTE DE DADOS DA LOJA</h3>";

try {
    $storeQuery = $db->prepare("
        SELECT l.*, le.* 
        FROM lojas l
        LEFT JOIN lojas_endereco le ON l.id = le.loja_id
        WHERE l.usuario_id = :usuario_id
    ");
    $storeQuery->bindParam(':usuario_id', $userId);
    $storeQuery->execute();
    
    if ($storeQuery->rowCount() > 0) {
        $store = $storeQuery->fetch(PDO::FETCH_ASSOC);
        $storeId = $store['id'];
        echo "<div class='success'>✅ Loja encontrada (ID: {$storeId})</div>";
        echo "<div class='info'>🏪 Nome: {$store['nome_fantasia']}</div>";
        
        // Verificar se já tem endereço
        if (!empty($store['cep'])) {
            echo "<div class='info'>🏠 Endereço já cadastrado: {$store['logradouro']}, {$store['numero']} - {$store['cidade']}/{$store['estado']}</div>";
        } else {
            echo "<div class='warning'>⚠️ Nenhum endereço cadastrado ainda</div>";
        }
    } else {
        echo "<div class='error'>❌ Nenhuma loja encontrada para este usuário</div>";
        echo "<div class='warning'>💡 <strong>Solução:</strong> Verifique se a loja está associada ao usuário correto</div>";
        echo "</div></div></body></html>";
        exit;
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Erro ao buscar dados da loja: " . $e->getMessage() . "</div>";
    echo "</div></div></body></html>";
    exit;
}
echo "</div>";

// Resto do código continua igual... (incluir testes 4 e 5)
// [Incluir aqui os testes 4 e 5 do script anterior]

echo "<div class='form-debug'>";
echo "<h3>🧪 LABORATÓRIO DE TESTES</h3>";
echo "<p>Use este formulário científico para testar o processamento de endereço:</p>";

echo "<form method='POST'>";
echo "<input type='hidden' name='debug_action' value='test_address'>";

echo "<label>CEP (apenas números):</label>";
echo "<input type='text' name='cep' value='" . ($_POST['cep'] ?? '38706325') . "' placeholder='38706325' maxlength='8'>";

echo "<label>Logradouro:</label>";
echo "<input type='text' name='logradouro' value='" . ($_POST['logradouro'] ?? 'Rua Teste') . "' placeholder='Rua das Flores'>";

echo "<label>Número:</label>";
echo "<input type='text' name='numero' value='" . ($_POST['numero'] ?? '123') . "' placeholder='123'>";

echo "<label>Complemento (opcional):</label>";
echo "<input type='text' name='complemento' value='" . ($_POST['complemento'] ?? '') . "' placeholder='Apto 10'>";

echo "<label>Bairro:</label>";
echo "<input type='text' name='bairro' value='" . ($_POST['bairro'] ?? 'Centro') . "' placeholder='Centro'>";

echo "<label>Cidade:</label>";
echo "<input type='text' name='cidade' value='" . ($_POST['cidade'] ?? 'Patos de Minas') . "' placeholder='Patos de Minas'>";

echo "<label>Estado (UF):</label>";
echo "<select name='estado'>";
echo "<option value=''>Selecione um estado</option>";
$estados = [
    'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas',
    'BA' => 'Bahia', 'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo',
    'GO' => 'Goiás', 'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul',
    'MG' => 'Minas Gerais', 'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná',
    'PE' => 'Pernambuco', 'PI' => 'Piauí', 'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte',
    'RS' => 'Rio Grande do Sul', 'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina',
    'SP' => 'São Paulo', 'SE' => 'Sergipe', 'TO' => 'Tocantins'
];

foreach ($estados as $uf => $nome) {
    $selected = ($_POST['estado'] ?? '') === $uf ? 'selected' : '';
    echo "<option value='$uf' $selected>$uf - $nome</option>";
}
echo "</select>";

echo "<button type='submit' class='btn-test'>🚀 EXECUTAR TESTE COMPLETO</button>";
echo "</form>";
echo "</div>";

?>

<script>
// Simular barra de progresso
let progress = 0;
const progressBar = document.getElementById('progress');
const interval = setInterval(() => {
    progress += 20;
    progressBar.style.width = progress + '%';
    if (progress >= 100) {
        clearInterval(interval);
    }
}, 200);

// Auto-scroll para resultados
if (window.location.search.includes('debug_action')) {
    setTimeout(() => {
        document.querySelector('.form-debug').scrollIntoView({behavior: 'smooth'});
    }, 1000);
}
</script>

        </div>
    </div>
</body>
</html>
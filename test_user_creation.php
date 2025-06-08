<?php
/**
 * Script de Diagnóstico - Teste de Criação de Usuários
 * 
 * Este script simula completamente o processo de criação de usuários
 * para identificar exatamente onde está ocorrendo o problema.
 */

// Configurar relatório de erros para capturar tudo
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Começar a capturar a saída para não interferir no JSON
ob_start();

echo "=== DIAGNÓSTICO DO SISTEMA DE CRIAÇÃO DE USUÁRIOS ===\n\n";

// Função para exibir resultados de forma organizada
function testeResultado($nome, $sucesso, $detalhes = '') {
    $status = $sucesso ? '✅ SUCESSO' : '❌ FALHOU';
    echo "TESTE: {$nome}\n";
    echo "RESULTADO: {$status}\n";
    if ($detalhes) {
        echo "DETALHES: {$detalhes}\n";
    }
    echo str_repeat('-', 50) . "\n\n";
    return $sucesso;
}

// TESTE 1: Verificar se os arquivos necessários existem
echo "ETAPA 1: Verificando arquivos do sistema...\n\n";

$arquivos = [
    'config/database.php' => __DIR__ . '/config/database.php',
    'config/constants.php' => __DIR__ . '/config/constants.php',
    'controllers/AuthController.php' => __DIR__ . '/controllers/AuthController.php',
    'controllers/AdminController.php' => __DIR__ . '/controllers/AdminController.php'
];

$arquivosOk = true;
foreach ($arquivos as $nome => $caminho) {
    $existe = file_exists($caminho);
    testeResultado("Arquivo {$nome}", $existe, $existe ? 'Arquivo encontrado' : 'Arquivo não encontrado');
    if (!$existe) $arquivosOk = false;
}

if (!$arquivosOk) {
    echo "❌ ERRO CRÍTICO: Arquivos essenciais não encontrados. Verifique a estrutura do projeto.\n";
    exit;
}

// TESTE 2: Incluir arquivos necessários
echo "ETAPA 2: Carregando dependências...\n\n";

try {
    require_once __DIR__ . '/config/database.php';
    testeResultado('Carregar database.php', true);
} catch (Exception $e) {
    testeResultado('Carregar database.php', false, $e->getMessage());
    exit;
}

try {
    require_once __DIR__ . '/config/constants.php';
    testeResultado('Carregar constants.php', true);
} catch (Exception $e) {
    testeResultado('Carregar constants.php', false, $e->getMessage());
    exit;
}

try {
    require_once __DIR__ . '/controllers/AuthController.php';
    testeResultado('Carregar AuthController.php', true);
} catch (Exception $e) {
    testeResultado('Carregar AuthController.php', false, $e->getMessage());
    exit;
}

// TESTE 3: Verificar conexão com banco de dados
echo "ETAPA 3: Testando conexão com banco de dados...\n\n";

try {
    $db = Database::getConnection();
    $testeDb = $db->query("SELECT 1");
    testeResultado('Conexão com banco', true, 'Conexão estabelecida com sucesso');
} catch (Exception $e) {
    testeResultado('Conexão com banco', false, $e->getMessage());
    exit;
}

// TESTE 4: Verificar se a tabela de usuários existe
echo "ETAPA 4: Verificando estrutura do banco...\n\n";

try {
    $tabelas = ['usuarios', 'lojas'];
    foreach ($tabelas as $tabela) {
        $stmt = $db->query("DESCRIBE {$tabela}");
        $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        testeResultado("Tabela {$tabela}", true, "Encontradas " . count($colunas) . " colunas");
    }
} catch (Exception $e) {
    testeResultado('Verificação de tabelas', false, $e->getMessage());
    exit;
}

// TESTE 5: Iniciar sessão como administrador para testes
echo "ETAPA 5: Configurando ambiente de teste...\n\n";

session_start();

// Buscar um usuário admin existente para simular login
try {
    $stmt = $db->query("SELECT id, nome, email FROM usuarios WHERE tipo = 'admin' LIMIT 1");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['user_name'] = $admin['nome'];
        $_SESSION['user_type'] = 'admin';
        $_SESSION['user_email'] = $admin['email'];
        
        testeResultado('Configurar sessão admin', true, "Logado como: {$admin['nome']} (ID: {$admin['id']})");
    } else {
        testeResultado('Configurar sessão admin', false, 'Nenhum usuário admin encontrado no banco');
        exit;
    }
} catch (Exception $e) {
    testeResultado('Configurar sessão admin', false, $e->getMessage());
    exit;
}

// TESTE 6: Verificar se as classes necessárias existem
echo "ETAPA 6: Verificando classes do sistema...\n\n";

$classesNecessarias = ['Database', 'AuthController'];
foreach ($classesNecessarias as $classe) {
    $existe = class_exists($classe);
    testeResultado("Classe {$classe}", $existe, $existe ? 'Classe carregada' : 'Classe não encontrada');
    if (!$existe) exit;
}

// TESTE 7: Testar o método AuthController::register diretamente
echo "ETAPA 7: Testando método AuthController::register...\n\n";

$dadosTeste = [
    'nome' => 'Usuario Teste Debug ' . date('H:i:s'),
    'email' => 'teste_debug_' . time() . '@exemplo.com',
    'telefone' => '(11) 99999-9999',
    'senha' => '12345678',
    'tipo' => 'cliente'
];

echo "Dados de teste que serão enviados:\n";
foreach ($dadosTeste as $campo => $valor) {
    echo "  {$campo}: {$valor}\n";
}
echo "\n";

try {
    $resultado = AuthController::register(
        $dadosTeste['nome'],
        $dadosTeste['email'],
        $dadosTeste['telefone'],
        $dadosTeste['senha'],
        $dadosTeste['tipo']
    );
    
    testeResultado('AuthController::register', $resultado['status'], $resultado['message']);
    
    if ($resultado['status']) {
        echo "✅ ID do usuário criado: " . ($resultado['user_id'] ?? 'não retornado') . "\n\n";
    } else {
        echo "❌ Erro detalhado: " . $resultado['message'] . "\n\n";
    }
    
} catch (Exception $e) {
    testeResultado('AuthController::register', false, "Exceção: " . $e->getMessage());
}

// TESTE 8: Simular requisição AJAX completa ao AdminController
echo "ETAPA 8: Simulando requisição AJAX ao AdminController...\n\n";

// Preparar dados como se fosse uma requisição POST
$_POST = [
    'action' => 'register',
    'nome' => 'Usuario AJAX Test ' . date('H:i:s'),
    'email' => 'ajax_test_' . time() . '@exemplo.com',
    'telefone' => '(11) 88888-8888',
    'senha' => 'senha123456',
    'tipo' => 'cliente',
    'status' => 'ativo'
];

// Simular headers AJAX
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
$_SERVER['REQUEST_METHOD'] = 'POST';

echo "Simulando requisição POST com dados:\n";
foreach ($_POST as $campo => $valor) {
    echo "  {$campo}: {$valor}\n";
}
echo "\n";

// Limpar buffer de saída antes de testar o AdminController
$debugOutput = ob_get_clean();

// Capturar a saída do AdminController
ob_start();

try {
    // Incluir e executar AdminController
    include __DIR__ . '/controllers/AdminController.php';
    
    $adminOutput = ob_get_clean();
    
    echo $debugOutput; // Reexibir nosso debug
    
    echo "RESPOSTA DO ADMINCONTROLLER:\n";
    echo str_repeat('=', 50) . "\n";
    echo $adminOutput;
    echo "\n" . str_repeat('=', 50) . "\n\n";
    
    // Tentar fazer parse da resposta como JSON
    $resposta = json_decode($adminOutput, true);
    if ($resposta !== null) {
        testeResultado('Resposta JSON válida', true, "Status: " . ($resposta['status'] ? 'true' : 'false') . ", Mensagem: " . ($resposta['message'] ?? 'sem mensagem'));
    } else {
        testeResultado('Resposta JSON válida', false, 'Resposta não é JSON válido');
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo $debugOutput; // Reexibir nosso debug
    testeResultado('Execução AdminController', false, "Exceção: " . $e->getMessage());
}

// TESTE 9: Verificar logs de erro recentes
echo "ETAPA 9: Verificando logs de erro do sistema...\n\n";

$logFiles = [
    ini_get('error_log'),
    '/var/log/apache2/error.log',
    '/var/log/nginx/error.log',
    __DIR__ . '/logs/error.log',
    __DIR__ . '/error.log'
];

$logsEncontrados = false;
foreach ($logFiles as $logFile) {
    if ($logFile && file_exists($logFile) && is_readable($logFile)) {
        $logsEncontrados = true;
        echo "📋 Arquivo de log encontrado: {$logFile}\n";
        
        // Ler as últimas 20 linhas do log
        $linhas = file($logFile);
        $ultimasLinhas = array_slice($linhas, -20);
        
        echo "Últimas entradas relevantes:\n";
        foreach ($ultimasLinhas as $linha) {
            if (strpos($linha, 'DEBUG') !== false || strpos($linha, 'AdminController') !== false) {
                echo "  " . trim($linha) . "\n";
            }
        }
        echo "\n";
        break;
    }
}

if (!$logsEncontrados) {
    echo "⚠️  Nenhum arquivo de log acessível encontrado.\n\n";
}

// RESUMO FINAL
echo str_repeat('=', 60) . "\n";
echo "RESUMO DO DIAGNÓSTICO\n";
echo str_repeat('=', 60) . "\n\n";

echo "Este script testou todos os componentes do sistema de criação de usuários.\n";
echo "Se você chegou até aqui, significa que os componentes básicos estão funcionando.\n\n";

echo "PRÓXIMOS PASSOS:\n";
echo "1. Verifique se houve algum teste que falhou nas etapas acima\n";
echo "2. Se todos os testes passaram, o problema pode estar no JavaScript ou no roteamento\n";
echo "3. Verifique se o arquivo 'assets/js/admin/users.js' está carregando corretamente\n";
echo "4. Verifique se não há erros JavaScript no console do navegador\n\n";

echo "Para executar este teste novamente, acesse: " . $_SERVER['HTTP_HOST'] . "/test_user_creation.php\n\n";

?>
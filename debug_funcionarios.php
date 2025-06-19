<?php
/**
 * SCRIPT DE DEBUG COMPLETO - SISTEMA DE FUNCIONÁRIOS
 * 
 * Este script funciona como um "médico" para o sistema, verificando:
 * - Saúde do banco de dados
 * - Existência de arquivos necessários
 * - Funcionamento das classes e métodos
 * - Simulação de operações CRUD
 * - Identificação de problemas específicos
 * 
 * Execute este arquivo na raiz do projeto via navegador ou linha de comando
 * 
 * Arquivo: debug_funcionarios.php (salvar na raiz do projeto)
 */

// Configurar exibição de erros para debug completo
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Iniciar buffer de saída para capturar tudo
ob_start();

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Debug Sistema de Funcionários - Klube Cash</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .test-section { margin: 20px 0; padding: 15px; border-left: 4px solid #007bff; background: #f8f9fa; }
        .success { border-left-color: #28a745; background: #d4edda; }
        .error { border-left-color: #dc3545; background: #f8d7da; }
        .warning { border-left-color: #ffc107; background: #fff3cd; }
        .info { border-left-color: #17a2b8; background: #d1ecf1; }
        h1 { color: #333; text-align: center; }
        h2 { color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
        h3 { color: #6c757d; }
        .code-block { background: #f8f9fa; border: 1px solid #e9ecef; padding: 10px; border-radius: 4px; font-family: monospace; margin: 10px 0; }
        .result { padding: 10px; margin: 5px 0; border-radius: 4px; }
        .result.ok { background: #d4edda; color: #155724; }
        .result.fail { background: #f8d7da; color: #721c24; }
        .result.warn { background: #fff3cd; color: #856404; }
        pre { white-space: pre-wrap; word-wrap: break-word; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
        .btn { padding: 8px 16px; margin: 5px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🔍 DEBUG COMPLETO - SISTEMA DE FUNCIONÁRIOS</h1>";
echo "<p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>";

// Array para armazenar todos os resultados dos testes
$testResults = [];
$overallStatus = true;

/**
 * Função auxiliar para registrar resultados dos testes
 */
function logTest($testName, $status, $message, $details = '') {
    global $testResults, $overallStatus;
    
    $testResults[] = [
        'name' => $testName,
        'status' => $status,
        'message' => $message,
        'details' => $details
    ];
    
    if (!$status) {
        $overallStatus = false;
    }
    
    $statusClass = $status ? 'success' : 'error';
    $statusIcon = $status ? '✅' : '❌';
    
    echo "<div class='test-section $statusClass'>";
    echo "<h3>$statusIcon $testName</h3>";
    echo "<p><strong>Resultado:</strong> $message</p>";
    if (!empty($details)) {
        echo "<div class='code-block'>$details</div>";
    }
    echo "</div>";
}

// ==================== TESTE 1: VERIFICAÇÃO DE ARQUIVOS ====================
echo "<h2>📁 1. VERIFICAÇÃO DE ARQUIVOS NECESSÁRIOS</h2>";

$requiredFiles = [
    'config/database.php' => 'Configuração do banco de dados',
    'config/constants.php' => 'Constantes do sistema',
    'controllers/StoreController.php' => 'Controlador de lojas',
    'controllers/AuthController.php' => 'Controlador de autenticação',
    'utils/Validator.php' => 'Classe de validação',
    'api/employees.php' => 'API de funcionários',
    'views/stores/employees.php' => 'Página de funcionários',
    'assets/js/stores/employees.js' => 'JavaScript de funcionários'
];

$missingFiles = [];
foreach ($requiredFiles as $file => $description) {
    if (file_exists($file)) {
        logTest("Arquivo: $file", true, "✅ $description encontrado");
    } else {
        logTest("Arquivo: $file", false, "❌ $description não encontrado");
        $missingFiles[] = $file;
    }
}

if (!empty($missingFiles)) {
    logTest("Arquivos Faltantes", false, "Encontrados " . count($missingFiles) . " arquivos faltantes", 
            "Arquivos faltantes:\n" . implode("\n", $missingFiles));
}

// ==================== TESTE 2: CONEXÃO COM BANCO DE DADOS ====================
echo "<h2>🗄️ 2. TESTE DE CONEXÃO COM BANCO DE DADOS</h2>";

try {
    if (file_exists('config/database.php')) {
        require_once 'config/database.php';
        $db = Database::getConnection();
        logTest("Conexão DB", true, "Conexão estabelecida com sucesso");
        
        // Testar uma query simples
        $stmt = $db->query("SELECT VERSION() as version");
        $version = $stmt->fetch(PDO::FETCH_ASSOC);
        logTest("Versão MySQL", true, "MySQL Versão: " . $version['version']);
    } else {
        logTest("Conexão DB", false, "Arquivo database.php não encontrado");
        $db = null;
    }
} catch (Exception $e) {
    logTest("Conexão DB", false, "Erro na conexão: " . $e->getMessage());
    $db = null;
}

// ==================== TESTE 3: VERIFICAÇÃO DE TABELAS ====================
echo "<h2>🗃️ 3. VERIFICAÇÃO DE ESTRUTURA DO BANCO</h2>";

if ($db) {
    $requiredTables = [
        'usuarios' => 'Tabela de usuários',
        'lojas' => 'Tabela de lojas',
        'lojas_endereco' => 'Tabela de endereços de lojas'
    ];
    
    foreach ($requiredTables as $table => $description) {
        try {
            $stmt = $db->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                logTest("Tabela: $table", true, "$description existe");
                
                // Verificar estrutura da tabela usuarios se for a tabela de usuários
                if ($table === 'usuarios') {
                    $stmt = $db->query("DESCRIBE usuarios");
                    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $requiredColumns = ['loja_vinculada_id', 'subtipo_funcionario'];
                    $foundColumns = array_column($columns, 'Field');
                    
                    foreach ($requiredColumns as $col) {
                        if (in_array($col, $foundColumns)) {
                            logTest("Coluna: $col", true, "Coluna '$col' existe na tabela usuarios");
                        } else {
                            logTest("Coluna: $col", false, "Coluna '$col' não existe na tabela usuarios");
                        }
                    }
                }
            } else {
                logTest("Tabela: $table", false, "$description não existe");
            }
        } catch (Exception $e) {
            logTest("Tabela: $table", false, "Erro ao verificar: " . $e->getMessage());
        }
    }
}

// ==================== TESTE 4: CARREGAMENTO DE CLASSES ====================
echo "<h2>🔧 4. TESTE DE CARREGAMENTO DE CLASSES</h2>";

$requiredClasses = [
    'config/constants.php' => 'Constantes',
    'controllers/AuthController.php' => 'AuthController',
    'controllers/StoreController.php' => 'StoreController',
    'utils/Validator.php' => 'Validator'
];

foreach ($requiredClasses as $file => $className) {
    try {
        if (file_exists($file)) {
            require_once $file;
            logTest("Classe: $className", true, "Classe carregada com sucesso");
        } else {
            logTest("Classe: $className", false, "Arquivo não encontrado: $file");
        }
    } catch (Exception $e) {
        logTest("Classe: $className", false, "Erro ao carregar: " . $e->getMessage());
    }
}

// ==================== TESTE 5: VERIFICAÇÃO DE CONSTANTES ====================
echo "<h2>📋 5. VERIFICAÇÃO DE CONSTANTES NECESSÁRIAS</h2>";

$requiredConstants = [
    'USER_TYPE_STORE' => 'Tipo de usuário loja',
    'USER_ACTIVE' => 'Status usuário ativo',
    'USER_INACTIVE' => 'Status usuário inativo',
    'EMPLOYEE_TYPE_FINANCIAL' => 'Tipo funcionário financeiro',
    'EMPLOYEE_TYPE_MANAGER' => 'Tipo funcionário gerente',
    'EMPLOYEE_TYPE_SELLER' => 'Tipo funcionário vendedor',
    'PASSWORD_MIN_LENGTH' => 'Tamanho mínimo da senha'
];

foreach ($requiredConstants as $constant => $description) {
    if (defined($constant)) {
        $value = constant($constant);
        logTest("Constante: $constant", true, "$description = '$value'");
    } else {
        logTest("Constante: $constant", false, "$description não está definida");
    }
}

// ==================== TESTE 6: SIMULAÇÃO DE SESSÃO DE LOJA ====================
echo "<h2>👤 6. SIMULAÇÃO DE SESSÃO DE LOJA</h2>";

// Iniciar sessão para testes
session_start();

// Verificar se existe uma loja de teste no banco
if ($db) {
    try {
        // Buscar primeira loja ativa
        $stmt = $db->query("SELECT l.id as loja_id, l.usuario_id, u.email 
                           FROM lojas l 
                           INNER JOIN usuarios u ON l.usuario_id = u.id 
                           WHERE l.status = 'ativo' 
                           LIMIT 1");
        
        $testStore = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($testStore) {
            // Simular login como loja
            $_SESSION['user_id'] = $testStore['usuario_id'];
            $_SESSION['user_type'] = defined('USER_TYPE_STORE') ? USER_TYPE_STORE : 'loja';
            
            logTest("Sessão de Teste", true, 
                   "Sessão simulada para loja ID: {$testStore['loja_id']}, " .
                   "Usuário: {$testStore['email']}");
        } else {
            logTest("Sessão de Teste", false, "Nenhuma loja ativa encontrada para teste");
        }
    } catch (Exception $e) {
        logTest("Sessão de Teste", false, "Erro ao configurar sessão: " . $e->getMessage());
    }
}

// ==================== TESTE 7: TESTE DOS MÉTODOS DO STORECONTROLLER ====================
echo "<h2>⚙️ 7. TESTE DOS MÉTODOS DO STORECONTROLLER</h2>";

if (class_exists('StoreController')) {
    // Teste 7.1: Método getEmployees
    try {
        $result = StoreController::getEmployees();
        if (is_array($result) && isset($result['status'])) {
            if ($result['status']) {
                $employeeCount = count($result['data']['funcionarios'] ?? []);
                logTest("StoreController::getEmployees()", true, 
                       "Método funcionando - {$employeeCount} funcionários encontrados");
            } else {
                logTest("StoreController::getEmployees()", false, 
                       "Método retornou erro: " . ($result['message'] ?? 'Erro desconhecido'));
            }
        } else {
            logTest("StoreController::getEmployees()", false, 
                   "Método retornou formato inválido");
        }
    } catch (Exception $e) {
        logTest("StoreController::getEmployees()", false, 
               "Exceção no método: " . $e->getMessage());
    }
    
    // Teste 7.2: Método createEmployee (simulação)
    try {
        $testEmployeeData = [
            'nome' => 'Funcionário Teste Debug',
            'email' => 'teste.debug.' . time() . '@exemplo.com',
            'telefone' => '(38) 99999-9999',
            'subtipo_funcionario' => defined('EMPLOYEE_TYPE_SELLER') ? EMPLOYEE_TYPE_SELLER : 'vendedor',
            'senha' => '12345678'
        ];
        
        $result = StoreController::createEmployee($testEmployeeData);
        if (is_array($result) && isset($result['status'])) {
            logTest("StoreController::createEmployee()", $result['status'], 
                   $result['message'] ?? 'Teste realizado');
            
            // Se criou com sucesso, tentar deletar para não poluir o banco
            if ($result['status'] && $db) {
                try {
                    $stmt = $db->prepare("DELETE FROM usuarios WHERE email = ?");
                    $stmt->execute([$testEmployeeData['email']]);
                    logTest("Limpeza de Teste", true, "Funcionário de teste removido");
                } catch (Exception $e) {
                    logTest("Limpeza de Teste", false, "Erro ao remover teste: " . $e->getMessage());
                }
            }
        } else {
            logTest("StoreController::createEmployee()", false, 
                   "Método retornou formato inválido");
        }
    } catch (Exception $e) {
        logTest("StoreController::createEmployee()", false, 
               "Exceção no método: " . $e->getMessage());
    }
}

// ==================== TESTE 8: TESTE DA API DE FUNCIONÁRIOS ====================
echo "<h2>🌐 8. TESTE DA API DE FUNCIONÁRIOS</h2>";

if (file_exists('api/employees.php')) {
    // Simular requisição GET para a API
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = ['page' => 1];
    
    try {
        // Capturar saída da API
        ob_start();
        include 'api/employees.php';
        $apiOutput = ob_get_clean();
        
        // Tentar decodificar JSON
        $apiResult = json_decode($apiOutput, true);
        
        if ($apiResult && isset($apiResult['status'])) {
            logTest("API employees.php (GET)", $apiResult['status'], 
                   $apiResult['message'] ?? 'API respondeu corretamente');
        } else {
            logTest("API employees.php (GET)", false, 
                   "API não retornou JSON válido", 
                   "Saída da API: " . substr($apiOutput, 0, 500));
        }
    } catch (Exception $e) {
        logTest("API employees.php (GET)", false, 
               "Erro ao testar API: " . $e->getMessage());
    }
}

// ==================== TESTE 9: VERIFICAÇÃO DE PERMISSÕES DE ARQUIVO ====================
echo "<h2>🔐 9. VERIFICAÇÃO DE PERMISSÕES</h2>";

$criticalFiles = [
    'api/employees.php',
    'config/database.php',
    'controllers/StoreController.php'
];

foreach ($criticalFiles as $file) {
    if (file_exists($file)) {
        $perms = fileperms($file);
        $octal = substr(sprintf('%o', $perms), -4);
        
        if (is_readable($file)) {
            logTest("Permissão: $file", true, "Arquivo legível (permissões: $octal)");
        } else {
            logTest("Permissão: $file", false, "Arquivo não legível (permissões: $octal)");
        }
    }
}

// ==================== TESTE 10: VERIFICAÇÃO DE ERROS PHP ====================
echo "<h2>🐛 10. VERIFICAÇÃO DE ERROS PHP</h2>";

// Verificar se há erros no log
if (function_exists('error_get_last')) {
    $lastError = error_get_last();
    if ($lastError && $lastError['message']) {
        logTest("Último Erro PHP", false, 
               "Erro encontrado: " . $lastError['message'],
               "Arquivo: " . $lastError['file'] . ", Linha: " . $lastError['line']);
    } else {
        logTest("Último Erro PHP", true, "Nenhum erro PHP recente encontrado");
    }
}

// ==================== RESUMO FINAL ====================
echo "<h2>📊 RESUMO FINAL DO DIAGNÓSTICO</h2>";

$totalTests = count($testResults);
$passedTests = count(array_filter($testResults, function($test) { return $test['status']; }));
$failedTests = $totalTests - $passedTests;

echo "<div class='test-section " . ($overallStatus ? 'success' : 'error') . "'>";
echo "<h3>" . ($overallStatus ? '✅' : '❌') . " RESULTADO GERAL</h3>";
echo "<p><strong>Total de Testes:</strong> $totalTests</p>";
echo "<p><strong>Testes Aprovados:</strong> $passedTests</p>";
echo "<p><strong>Testes Falharam:</strong> $failedTests</p>";
echo "<p><strong>Taxa de Sucesso:</strong> " . round(($passedTests / $totalTests) * 100, 2) . "%</p>";
echo "</div>";

// Lista de testes falhados
if ($failedTests > 0) {
    echo "<h3>❌ TESTES QUE FALHARAM:</h3>";
    echo "<ol>";
    foreach ($testResults as $test) {
        if (!$test['status']) {
            echo "<li><strong>{$test['name']}:</strong> {$test['message']}</li>";
        }
    }
    echo "</ol>";
}

// ==================== RECOMENDAÇÕES ====================
echo "<h2>💡 RECOMENDAÇÕES PARA CORREÇÃO</h2>";

if ($failedTests > 0) {
    echo "<div class='test-section warning'>";
    echo "<h3>🔧 AÇÕES RECOMENDADAS:</h3>";
    echo "<ol>";
    
    if (in_array('config/database.php', $missingFiles)) {
        echo "<li>Criar arquivo config/database.php com configurações do banco</li>";
    }
    
    if (in_array('config/constants.php', $missingFiles)) {
        echo "<li>Adicionar constantes necessárias no arquivo constants.php</li>";
    }
    
    if ($db === null) {
        echo "<li>Verificar configurações de conexão do banco de dados</li>";
    }
    
    echo "<li>Verificar permissões de arquivos (devem ser 644 ou 755)</li>";
    echo "<li>Verificar se todas as tabelas necessárias existem no banco</li>";
    echo "<li>Verificar se a sessão PHP está funcionando corretamente</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div class='test-section success'>";
    echo "<h3>🎉 SISTEMA FUNCIONANDO PERFEITAMENTE!</h3>";
    echo "<p>Todos os testes passaram com sucesso. O sistema de funcionários está pronto para uso.</p>";
    echo "</div>";
}

// ==================== INFORMAÇÕES TÉCNICAS ====================
echo "<h2>📋 INFORMAÇÕES TÉCNICAS</h2>";

echo "<div class='test-section info'>";
echo "<h3>🖥️ Ambiente</h3>";
echo "<p><strong>PHP Versão:</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>Sistema Operacional:</strong> " . PHP_OS . "</p>";
echo "<p><strong>Servidor Web:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Desconhecido') . "</p>";
echo "<p><strong>Diretório Atual:</strong> " . getcwd() . "</p>";
echo "<p><strong>Uso de Memória:</strong> " . number_format(memory_get_usage() / 1024 / 1024, 2) . " MB</p>";
echo "</div>";

// Botões de ação
echo "<div style='text-align: center; margin: 20px 0;'>";
echo "<button class='btn btn-primary' onclick='location.reload()'>🔄 Executar Novamente</button>";
echo "<button class='btn btn-success' onclick='window.print()'>🖨️ Imprimir Relatório</button>";
if (file_exists('views/stores/employees.php')) {
    echo "<button class='btn btn-primary' onclick='window.open(\"views/stores/employees.php\", \"_blank\")'>👥 Ir para Funcionários</button>";
}
echo "</div>";

echo "</div></body></html>";

// Salvar log em arquivo
$logContent = ob_get_contents();
file_put_contents('debug_funcionarios_' . date('Y-m-d_H-i-s') . '.html', $logContent);

echo "<script>
console.log('Debug concluído. Total de testes: $totalTests, Aprovados: $passedTests, Falharam: $failedTests');
</script>";
?>
<?php
// debug-login-trace.php - Rastreamento completo do processo de login
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Diagnóstico Avançado - Rastreamento de Login</h1>";

// Primeiro, vamos verificar se nosso AuthController está carregando corretamente
echo "<h2>Teste 1: Verificação da Classe AuthController</h2>";

try {
    require_once './config/constants.php';
    require_once './controllers/AuthController.php';
    
    if (class_exists('AuthController')) {
        echo "<p>✅ Classe AuthController carregada com sucesso</p>";
        
        // Verificar se o método login existe
        if (method_exists('AuthController', 'login')) {
            echo "<p>✅ Método login encontrado na classe</p>";
        } else {
            echo "<p>❌ Método login não encontrado</p>";
        }
        
        // Usar reflexão para examinar o código do método
        $reflection = new ReflectionMethod('AuthController', 'login');
        $filename = $reflection->getFileName();
        $startLine = $reflection->getStartLine();
        $endLine = $reflection->getEndLine();
        
        echo "<p><strong>Arquivo do método:</strong> {$filename}</p>";
        echo "<p><strong>Linhas:</strong> {$startLine} - {$endLine}</p>";
        
        // Ler o código atual do método para verificar se nossa correção está lá
        $fileContent = file($filename);
        $methodContent = array_slice($fileContent, $startLine - 1, $endLine - $startLine + 1);
        
        // Procurar por nossa correção específica
        $hasCorrection = false;
        foreach ($methodContent as $line) {
            if (strpos($line, '$storeData = null') !== false) {
                $hasCorrection = true;
                break;
            }
        }
        
        if ($hasCorrection) {
            echo "<p>✅ Correção encontrada no código</p>";
        } else {
            echo "<p>❌ Correção NÃO encontrada no código</p>";
            echo "<p><strong>Isso indica que a modificação não foi salva corretamente!</strong></p>";
        }
        
    } else {
        echo "<p>❌ Classe AuthController não encontrada</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Erro ao carregar AuthController: " . $e->getMessage() . "</p>";
}

echo "<h2>Teste 2: Simulação Controlada de Login</h2>";

// Vamos simular o processo de login step-by-step
$test_email = 'financeiro@klubedigital.com';
$test_password = '123456';

echo "<p>Testando login para: {$test_email}</p>";

try {
    // Chamar diretamente o método login que corrigimos
    $result = AuthController::login($test_email, $test_password);
    
    echo "<p><strong>Resultado do método login:</strong></p>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
    if ($result['status']) {
        echo "<p>✅ Login bem-sucedido</p>";
        
        // Agora vamos verificar o que realmente foi definido na sessão
        echo "<h3>Estado da Sessão Após Login Direto:</h3>";
        
        session_start(); // Garantir que a sessão está ativa
        
        echo "<pre>";
        print_r($_SESSION);
        echo "</pre>";
        
        // Verificar especificamente as variáveis que esperamos
        $expectedVars = ['employee_subtype', 'store_id', 'store_name', 'employee_permissions'];
        
        echo "<h3>Verificação das Variáveis Específicas de Funcionário:</h3>";
        foreach ($expectedVars as $var) {
            if (isset($_SESSION[$var])) {
                echo "<p>✅ {$var}: " . (is_array($_SESSION[$var]) ? implode(', ', $_SESSION[$var]) : $_SESSION[$var]) . "</p>";
            } else {
                echo "<p>❌ {$var}: não definido</p>";
            }
        }
        
    } else {
        echo "<p>❌ Login falhou: " . $result['message'] . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Erro durante simulação de login: " . $e->getMessage() . "</p>";
}

echo "<h2>Teste 3: Verificação do Banco de Dados</h2>";

// Vamos verificar os dados no banco mais uma vez
try {
    require_once './config/database.php';
    $db = Database::getConnection();
    
    $stmt = $db->prepare("
        SELECT u.*, l.nome_fantasia as loja_nome
        FROM usuarios u 
        LEFT JOIN lojas l ON u.loja_vinculada_id = l.id
        WHERE u.email = ?
    ");
    $stmt->execute([$test_email]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($userData) {
        echo "<p>✅ Dados encontrados no banco:</p>";
        echo "<pre>";
        print_r($userData);
        echo "</pre>";
    } else {
        echo "<p>❌ Usuário não encontrado no banco</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Erro de banco de dados: " . $e->getMessage() . "</p>";
}

?>
<?php
// debug-login-detalhado.php - Diagnóstico avançado do processo de login
// DELETAR após uso por segurança

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once './config/constants.php';
require_once './config/database.php';

echo "<h1>Diagnóstico Detalhado do Processo de Login</h1>";

// Simular o processo de login passo a passo
$test_email = 'financeiro@klubedigital.com';
$test_password = '123456';

echo "<p><strong>Testando:</strong> {$test_email}</p>";

try {
    $db = Database::getConnection();
    
    echo "<h2>Passo 1: Consulta SQL Completa</h2>";
    $stmt = $db->prepare("
        SELECT id, nome, email, senha_hash, tipo, status, loja_vinculada_id, subtipo_funcionario
        FROM usuarios 
        WHERE email = ? AND tipo IN ('cliente', 'admin', 'loja', 'funcionario')
    ");
    $stmt->execute([$test_email]);
    
    if ($stmt->rowCount() === 0) {
        echo "<p>❌ Usuário não encontrado</p>";
        exit;
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>✅ Usuário encontrado</p>";
    echo "<pre>";
    print_r($user);
    echo "</pre>";
    
    echo "<h2>Passo 2: Verificação de Senha</h2>";
    if (!password_verify($test_password, $user['senha_hash'])) {
        echo "<p>❌ Senha incorreta</p>";
        exit;
    }
    echo "<p>✅ Senha correta</p>";
    
    echo "<h2>Passo 3: Verificação de Status</h2>";
    if ($user['status'] !== 'ativo') {
        echo "<p>❌ Usuário não ativo: {$user['status']}</p>";
        exit;
    }
    echo "<p>✅ Usuário ativo</p>";
    
    echo "<h2>Passo 4: Verificação Específica de Funcionário</h2>";
    $storeData = null;
    if ($user['tipo'] === 'funcionario') {
        echo "<p>✅ Usuário é funcionário</p>";
        echo "<p>Subtipo encontrado: '{$user['subtipo_funcionario']}'</p>";
        echo "<p>Loja vinculada ID: {$user['loja_vinculada_id']}</p>";
        
        $storeStmt = $db->prepare("
            SELECT status, nome_fantasia 
            FROM lojas 
            WHERE id = ? AND status = 'aprovado'
        ");
        $storeStmt->execute([$user['loja_vinculada_id']]);
        
        if ($storeStmt->rowCount() === 0) {
            echo "<p>❌ Loja vinculada não está aprovada</p>";
            exit;
        }
        
        $storeData = $storeStmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>✅ Loja vinculada aprovada: {$storeData['nome_fantasia']}</p>";
    }
    
    echo "<h2>Passo 5: Simulação das Variáveis de Sessão</h2>";
    echo "<p><strong>Variáveis que SERIAM definidas:</strong></p>";
    echo "<ul>";
    echo "<li>user_id: {$user['id']}</li>";
    echo "<li>user_name: {$user['nome']}</li>";
    echo "<li>user_email: {$user['email']}</li>";
    echo "<li>user_type: {$user['tipo']}</li>";
    
    if ($user['tipo'] === 'funcionario') {
        echo "<li>employee_subtype: {$user['subtipo_funcionario']}</li>";
        echo "<li>store_id: {$user['loja_vinculada_id']}</li>";
        echo "<li>store_name: {$storeData['nome_fantasia']}</li>";
        
        // Simular permissões
        $permissions = [];
        switch($user['subtipo_funcionario']) {
            case 'gerente':
                $permissions = ['dashboard', 'transacoes', 'funcionarios', 'relatorios'];
                break;
            case 'financeiro':
                $permissions = ['dashboard', 'comissoes', 'pagamentos', 'relatorios'];
                break;
            case 'vendedor':
                $permissions = ['dashboard', 'transacoes'];
                break;
            default:
                $permissions = ['dashboard'];
        }
        echo "<li>employee_permissions: " . implode(', ', $permissions) . "</li>";
    }
    echo "</ul>";
    
    echo "<h2>Conclusão</h2>";
    echo "<p><strong>🎉 TODOS OS DADOS ESTÃO CORRETOS!</strong></p>";
    echo "<p>O código deveria funcionar perfeitamente em um novo login.</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>";
}
?>
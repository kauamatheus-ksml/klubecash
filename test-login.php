<?php
// test-login.php - Ferramenta de diagnóstico para problemas de login
// IMPORTANTE: Deletar após uso por segurança

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once './config/constants.php';
require_once './config/database.php';

// Dados de teste para verificar
$test_email = 'gerente@klubedigital.com';
$test_password = '123456';

echo "<h1>Diagnóstico de Login - Funcionários</h1>";
echo "<p><strong>Testando login para:</strong> {$test_email}</p>";
echo "<p><strong>Senha sendo testada:</strong> {$test_password}</p>";

try {
    $db = Database::getConnection();
    echo "<p>✅ Conexão com banco estabelecida</p>";
    
    // Passo 1: Verificar se o usuário existe
    echo "<h2>Passo 1: Procurando usuário no banco</h2>";
    $stmt = $db->prepare("
        SELECT id, nome, email, senha_hash, tipo, status, loja_vinculada_id, subtipo_funcionario
        FROM usuarios 
        WHERE email = ? AND tipo = 'funcionario'
    ");
    $stmt->execute([$test_email]);
    
    if ($stmt->rowCount() === 0) {
        echo "<p>❌ PROBLEMA ENCONTRADO: Usuário não encontrado no banco de dados</p>";
        echo "<p>Isso significa que os comandos INSERT não funcionaram ou foram executados em outro banco.</p>";
    } else {
        echo "<p>✅ Usuário encontrado no banco</p>";
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p><strong>Dados encontrados:</strong></p>";
        echo "<ul>";
        echo "<li>ID: {$user['id']}</li>";
        echo "<li>Nome: {$user['nome']}</li>";
        echo "<li>Tipo: {$user['tipo']}</li>";
        echo "<li>Subtipo: {$user['subtipo_funcionario']}</li>";
        echo "<li>Status: {$user['status']}</li>";
        echo "<li>Loja vinculada: {$user['loja_vinculada_id']}</li>";
        echo "<li>Hash da senha: " . substr($user['senha_hash'], 0, 30) . "...</li>";
        echo "</ul>";
        
        // Passo 2: Verificar a senha
        echo "<h2>Passo 2: Verificando senha</h2>";
        if (password_verify($test_password, $user['senha_hash'])) {
            echo "<p>✅ Senha está correta</p>";
            
            // Passo 3: Verificar status
            echo "<h2>Passo 3: Verificando status do usuário</h2>";
            if ($user['status'] !== 'ativo') {
                echo "<p>❌ PROBLEMA: Usuário não está ativo (Status: {$user['status']})</p>";
            } else {
                echo "<p>✅ Usuário está ativo</p>";
                
                // Passo 4: Verificar loja vinculada
                echo "<h2>Passo 4: Verificando loja vinculada</h2>";
                $storeStmt = $db->prepare("
                    SELECT status, nome_fantasia 
                    FROM lojas 
                    WHERE id = ? AND status = 'aprovado'
                ");
                $storeStmt->execute([$user['loja_vinculada_id']]);
                
                if ($storeStmt->rowCount() === 0) {
                    echo "<p>❌ PROBLEMA: Loja vinculada não está aprovada ou não existe</p>";
                } else {
                    $store = $storeStmt->fetch(PDO::FETCH_ASSOC);
                    echo "<p>✅ Loja vinculada está aprovada: {$store['nome_fantasia']}</p>";
                    echo "<p><strong>🎉 TODOS OS TESTES PASSARAM - Login deveria funcionar!</strong></p>";
                }
            }
        } else {
            echo "<p>❌ PROBLEMA ENCONTRADO: Senha não confere</p>";
            echo "<p>O hash armazenado não corresponde à senha fornecida.</p>";
            echo "<p>Hash esperado: {$user['senha_hash']}</p>";
            
            // Vamos gerar um novo hash para comparar
            $novo_hash = password_hash($test_password, PASSWORD_DEFAULT);
            echo "<p>Novo hash gerado agora: {$novo_hash}</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p>❌ Erro durante o teste: " . $e->getMessage() . "</p>";
}

echo "<h2>Testando outros funcionários</h2>";
$outros_emails = ['financeiro@klubedigital.com', 'vendedor@klubedigital.com'];

foreach ($outros_emails as $email) {
    echo "<p><strong>Verificando:</strong> {$email}</p>";
    try {
        $stmt = $db->prepare("SELECT email, tipo, subtipo_funcionario FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p>✅ Encontrado: {$user['subtipo_funcionario']}</p>";
        } else {
            echo "<p>❌ Não encontrado</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Erro: " . $e->getMessage() . "</p>";
    }
}
?>
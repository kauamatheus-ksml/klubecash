<?php
// debug-authcontroller.php - DEBUG ESPECÍFICO DO AUTHCONTROLLER

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/utils/Email.php';

echo "<h1>🔍 Debug Específico do AuthController</h1>";

$testEmail = 'kauamatheus920@gmail.com';

echo "<h2>🧪 Passo 1: Testar Email diretamente</h2>";
$emailResult = Email::sendPasswordRecovery($testEmail, 'Teste Direto', 'token_123');
echo "<p>Email direto: " . ($emailResult ? "✅ SUCESSO" : "❌ FALHA") . "</p>";

echo "<h2>🧪 Passo 2: Simular o que AuthController faz</h2>";

try {
    $db = Database::getConnection();
    echo "<p>✅ Conexão com BD estabelecida</p>";
    
    // Verificar se o email existe (igual ao AuthController)
    $stmt = $db->prepare("SELECT id, nome, status FROM usuarios WHERE email = :email");
    $stmt->bindParam(':email', $testEmail);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "<p>❌ Usuário não encontrado no banco</p>";
        echo "<p>📋 Vamos criar um usuário de teste...</p>";
        
        // Criar usuário de teste
        $insertUser = $db->prepare("INSERT INTO usuarios (nome, email, senha_hash, tipo, status, data_criacao) VALUES (:nome, :email, :senha, :tipo, :status, NOW())");
        $insertUser->execute([
            'nome' => 'Usuário Teste',
            'email' => $testEmail,
            'senha' => password_hash('123456', PASSWORD_DEFAULT),
            'tipo' => USER_TYPE_CLIENT,
            'status' => USER_ACTIVE
        ]);
        
        $user = [
            'id' => $db->lastInsertId(),
            'nome' => 'Usuário Teste',
            'status' => USER_ACTIVE
        ];
        
        echo "<p>✅ Usuário de teste criado</p>";
    } else {
        echo "<p>✅ Usuário encontrado: " . htmlspecialchars($user['nome']) . "</p>";
        echo "<p>📋 Status: " . htmlspecialchars($user['status']) . "</p>";
    }
    
    if ($user['status'] !== USER_ACTIVE) {
        echo "<p>❌ Usuário não está ativo</p>";
    } else {
        echo "<p>✅ Usuário está ativo</p>";
        
        // Gerar token (igual ao AuthController)
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+2 hours'));
        
        echo "<p>✅ Token gerado: " . substr($token, 0, 20) . "...</p>";
        
        // Verificar se tabela existe
        $tableCheck = $db->query("SHOW TABLES LIKE 'recuperacao_senha'");
        if ($tableCheck->rowCount() == 0) {
            echo "<p>❌ Tabela 'recuperacao_senha' não existe</p>";
            echo "<p>📋 Criando tabela...</p>";
            
            $createTable = "
            CREATE TABLE recuperacao_senha (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                token VARCHAR(64) NOT NULL,
                data_expiracao DATETIME NOT NULL,
                usado TINYINT(1) DEFAULT 0,
                data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
            )";
            
            $db->exec($createTable);
            echo "<p>✅ Tabela criada</p>";
        } else {
            echo "<p>✅ Tabela 'recuperacao_senha' existe</p>";
        }
        
        // Excluir tokens antigos
        $deleteStmt = $db->prepare("DELETE FROM recuperacao_senha WHERE usuario_id = :user_id");
        $deleteStmt->bindParam(':user_id', $user['id']);
        $deleteResult = $deleteStmt->execute();
        echo "<p>📋 Tokens antigos removidos: " . ($deleteResult ? "✅" : "❌") . "</p>";
        
        // Inserir novo token
        $insertStmt = $db->prepare("INSERT INTO recuperacao_senha (usuario_id, token, data_expiracao) VALUES (:user_id, :token, :expiry)");
        $insertStmt->bindParam(':user_id', $user['id']);
        $insertStmt->bindParam(':token', $token);
        $insertStmt->bindParam(':expiry', $expiry);
        
        $insertResult = $insertStmt->execute();
        echo "<p>📋 Token inserido no BD: " . ($insertResult ? "✅" : "❌") . "</p>";
        
        if ($insertResult) {
            echo "<p>🚀 Tentando enviar email...</p>";
            
            // Enviar email (igual ao AuthController)
            $emailResult = Email::sendPasswordRecovery($testEmail, $user['nome'], $token);
            
            echo "<p>📧 Resultado do envio: " . ($emailResult ? "✅ SUCESSO" : "❌ FALHA") . "</p>";
            
            if ($emailResult) {
                echo "<p>🎉 <strong>TUDO FUNCIONANDO!</strong></p>";
            } else {
                echo "<p>❌ <strong>FALHA NO ENVIO - Vamos investigar...</strong></p>";
                
                // Testar novamente com debug
                echo "<h3>🔍 Teste com debug detalhado:</h3>";
                
                // Configurar logs temporários
                $originalLogLevel = error_reporting();
                error_reporting(E_ALL);
                
                ob_start();
                $emailResult2 = Email::sendPasswordRecovery($testEmail, $user['nome'], $token);
                $output = ob_get_clean();
                
                echo "<p>Resultado 2: " . ($emailResult2 ? "✅" : "❌") . "</p>";
                if (!empty($output)) {
                    echo "<pre>Output: " . htmlspecialchars($output) . "</pre>";
                }
                
                error_reporting($originalLogLevel);
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>";
    echo "<p>📍 Arquivo: " . $e->getFile() . "</p>";
    echo "<p>📍 Linha: " . $e->getLine() . "</p>";
}

echo "<h2>🧪 Passo 3: Testar AuthController após correções</h2>";
$authResult = AuthController::recoverPassword($testEmail);
echo "<p>AuthController resultado: " . ($authResult['status'] ? "✅" : "❌") . "</p>";
echo "<p>Mensagem: " . htmlspecialchars($authResult['message']) . "</p>";
?>
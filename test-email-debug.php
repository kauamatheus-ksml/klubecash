<?php
// debug-token.php - DEBUG ESPECÍFICO DO TOKEN

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';

echo "<h1>🔍 Debug do Token de Recuperação</h1>";

// 1. Verificar token da URL
$tokenFromUrl = $_GET['token'] ?? '';
echo "<h2>📋 1. Token da URL:</h2>";
echo "<p><strong>Token recebido:</strong> " . htmlspecialchars($tokenFromUrl) . "</p>";
echo "<p><strong>Tamanho:</strong> " . strlen($tokenFromUrl) . " caracteres</p>";

if (empty($tokenFromUrl)) {
    echo "<p>❌ Nenhum token fornecido na URL</p>";
    echo "<p>🔗 Teste com: <a href='/debug-token.php?token=teste123'>debug-token.php?token=teste123</a></p>";
    exit;
}

// 2. Verificar tokens no banco
echo "<h2>📋 2. Tokens no Banco de Dados:</h2>";

try {
    $db = Database::getConnection();
    
    // Listar todos os tokens
    $stmt = $db->prepare("
        SELECT rs.*, u.nome, u.email,
               rs.data_expiracao,
               NOW() as agora,
               (rs.data_expiracao > NOW()) as nao_expirado,
               rs.usado
        FROM recuperacao_senha rs
        JOIN usuarios u ON rs.usuario_id = u.id
        ORDER BY rs.id DESC
        LIMIT 10
    ");
    $stmt->execute();
    $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($tokens)) {
        echo "<p>❌ Nenhum token encontrado no banco</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Usuário</th><th>Token (primeiros 20)</th><th>Expira em</th><th>Agora</th><th>Válido?</th><th>Usado?</th></tr>";
        
        foreach ($tokens as $token) {
            $tokenPreview = substr($token['token'], 0, 20) . '...';
            $validClass = ($token['nao_expirado'] && !$token['usado']) ? 'style="background: #d4edda;"' : 'style="background: #f8d7da;"';
            
            echo "<tr $validClass>";
            echo "<td>" . $token['id'] . "</td>";
            echo "<td>" . htmlspecialchars($token['nome']) . "</td>";
            echo "<td>" . htmlspecialchars($tokenPreview) . "</td>";
            echo "<td>" . $token['data_expiracao'] . "</td>";
            echo "<td>" . $token['agora'] . "</td>";
            echo "<td>" . ($token['nao_expirado'] ? '✅ Sim' : '❌ Expirado') . "</td>";
            echo "<td>" . ($token['usado'] ? '❌ Usado' : '✅ Não') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    // 3. Testar token específico da URL
    echo "<h2>📋 3. Validação do Token Específico:</h2>";
    
    $stmt = $db->prepare("
        SELECT rs.*, u.nome, u.email,
               rs.data_expiracao,
               NOW() as agora,
               (rs.data_expiracao > NOW()) as nao_expirado,
               rs.usado
        FROM recuperacao_senha rs
        JOIN usuarios u ON rs.usuario_id = u.id
        WHERE rs.token = :token
    ");
    $stmt->bindParam(':token', $tokenFromUrl);
    $stmt->execute();
    $tokenInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tokenInfo) {
        echo "<p>❌ <strong>Token não encontrado no banco!</strong></p>";
        echo "<p>🔍 Possíveis causas:</p>";
        echo "<ul>";
        echo "<li>Token foi digitado incorretamente</li>";
        echo "<li>Token foi removido do banco</li>";
        echo "<li>Email contém link incorreto</li>";
        echo "</ul>";
        
        // Verificar se existe token similar
        $similarStmt = $db->prepare("
            SELECT token 
            FROM recuperacao_senha 
            WHERE token LIKE :similar_token
            LIMIT 5
        ");
        $similarToken = substr($tokenFromUrl, 0, 10) . '%';
        $similarStmt->bindParam(':similar_token', $similarToken);
        $similarStmt->execute();
        $similarTokens = $similarStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($similarTokens)) {
            echo "<p>🔍 Tokens similares encontrados:</p>";
            foreach ($similarTokens as $similar) {
                echo "<p>• " . substr($similar['token'], 0, 30) . "...</p>";
            }
        }
        
    } else {
        echo "<p>✅ <strong>Token encontrado!</strong></p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Campo</th><th>Valor</th></tr>";
        echo "<tr><td>ID</td><td>" . $tokenInfo['id'] . "</td></tr>";
        echo "<tr><td>Usuário</td><td>" . htmlspecialchars($tokenInfo['nome']) . " (" . htmlspecialchars($tokenInfo['email']) . ")</td></tr>";
        echo "<tr><td>Expira em</td><td>" . $tokenInfo['data_expiracao'] . "</td></tr>";
        echo "<tr><td>Agora</td><td>" . $tokenInfo['agora'] . "</td></tr>";
        echo "<tr><td>Não expirado?</td><td>" . ($tokenInfo['nao_expirado'] ? '✅ Sim' : '❌ Não') . "</td></tr>";
        echo "<tr><td>Usado?</td><td>" . ($tokenInfo['usado'] ? '❌ Sim' : '✅ Não') . "</td></tr>";
        echo "</table>";
        
        // 4. Verificar lógica de validação
        echo "<h2>📋 4. Validação da Lógica:</h2>";
        
        $isValid = ($tokenInfo['nao_expirado'] && !$tokenInfo['usado']);
        
        if ($isValid) {
            echo "<p>✅ <strong>TOKEN VÁLIDO!</strong> Deveria funcionar.</p>";
            echo "<p>🔗 Teste: <a href='/recuperar-senha?token=" . urlencode($tokenFromUrl) . "'>Ir para página de recuperação</a></p>";
        } else {
            echo "<p>❌ <strong>TOKEN INVÁLIDO!</strong></p>";
            if (!$tokenInfo['nao_expirado']) {
                echo "<p>• Motivo: Token expirado</p>";
            }
            if ($tokenInfo['usado']) {
                echo "<p>• Motivo: Token já foi usado</p>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p>❌ Erro no banco: " . $e->getMessage() . "</p>";
}

// 5. Verificar URL do email
echo "<h2>📋 5. Verificar URL do Email:</h2>";
echo "<p>A URL no email deveria ser:</p>";
echo "<p><strong>" . SITE_URL . "/recuperar-senha?token=SEU_TOKEN_AQUI</strong></p>";

// 6. Teste de token novo
echo "<h2>📋 6. Gerar Token de Teste:</h2>";
$testToken = bin2hex(random_bytes(32));
echo "<p>Token de teste gerado: <strong>$testToken</strong></p>";
echo "<p>🔗 <a href='/debug-token.php?token=$testToken'>Testar este token</a></p>";
?>
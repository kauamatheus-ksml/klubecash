<?php
// gerar-token-valido.php - GERAR TOKEN VÁLIDO PARA TESTE

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';

// Definir fuso horário
date_default_timezone_set('America/Sao_Paulo');

echo "<h1>🔧 Gerar Token Válido para Teste</h1>";

try {
    $db = Database::getConnection();
    
    // Buscar seu usuário
    $stmt = $db->prepare("SELECT id, nome, email FROM usuarios WHERE email = ?");
    $stmt->execute(['kauamatheus920@gmail.com']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "<p>❌ Usuário não encontrado</p>";
        exit;
    }
    
    echo "<p>✅ Usuário encontrado: " . htmlspecialchars($user['nome']) . "</p>";
    
    // Gerar novo token com 24 horas
    $token = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    echo "<p>📋 Novo token: <strong>$token</strong></p>";
    echo "<p>⏰ Expira em: <strong>$expiry</strong></p>";
    
    // Limpar tokens antigos
    $deleteStmt = $db->prepare("DELETE FROM recuperacao_senha WHERE usuario_id = ?");
    $deleteStmt->execute([$user['id']]);
    
    // Inserir novo token
    $insertStmt = $db->prepare("INSERT INTO recuperacao_senha (usuario_id, token, data_expiracao) VALUES (?, ?, ?)");
    $result = $insertStmt->execute([$user['id'], $token, $expiry]);
    
    if ($result) {
        echo "<p>✅ Token inserido no banco com sucesso!</p>";
        echo "<h2>🔗 Links de Teste:</h2>";
        echo "<p><a href='/debug-token.php?token=$token' target='_blank'>🔍 Debug do token</a></p>";
        echo "<p><a href='/recuperar-senha?token=$token' target='_blank'>🔐 Recuperar senha</a></p>";
        
        echo "<h2>📋 Para usar:</h2>";
        echo "<ol>";
        echo "<li>Clique no link 'Recuperar senha' acima</li>";
        echo "<li>Digite uma nova senha</li>";
        echo "<li>Confirme a senha</li>";
        echo "<li>Clique em 'Redefinir senha'</li>";
        echo "</ol>";
    } else {
        echo "<p>❌ Erro ao inserir token no banco</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>";
}
?>
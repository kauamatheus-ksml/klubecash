<?php
// teste-pagina-recuperacao.php

require_once __DIR__ . '/controllers/AuthController.php';

echo "<h1>🧪 Teste AuthController Recuperação</h1>";

$testEmail = 'kauamatheus920@gmail.com';

echo "<p>📧 Testando AuthController::recoverPassword('$testEmail')...</p>";

try {
    $result = AuthController::recoverPassword($testEmail);
    
    echo "<p><strong>Status:</strong> " . ($result['status'] ? "✅ TRUE" : "❌ FALSE") . "</p>";
    echo "<p><strong>Mensagem:</strong> " . htmlspecialchars($result['message']) . "</p>";
    
    if ($result['status']) {
        echo "<p>✅ <strong>AUTHCONTROLLER FUNCIONANDO!</strong></p>";
        echo "<p>📬 Verificar email recebido</p>";
    } else {
        echo "<p>❌ Problema no AuthController</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>";
}
?>
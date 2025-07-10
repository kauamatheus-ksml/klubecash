<?php
// teste-classe-email.php

require_once __DIR__ . '/utils/Email.php';

echo "<h1>🧪 Teste da Classe Email Corrigida</h1>";

try {
    $result = Email::sendPasswordRecovery('kauamatheus920@gmail.com', 'Usuário Teste', 'token_teste_123');
    
    if ($result) {
        echo "<p>✅ <strong>CLASSE EMAIL FUNCIONANDO!</strong></p>";
        echo "<p>📬 Verifique sua caixa de entrada</p>";
    } else {
        echo "<p>❌ Classe Email ainda com problema</p>";
    }
    
    // Teste direto do método send
    $result2 = Email::send('kauamatheus920@gmail.com', 'Teste Método Send', '<h2>Teste do método send()</h2><p>Funcionando!</p>', 'Teste');
    
    echo "<p>Resultado método send(): " . ($result2 ? "✅ OK" : "❌ FALHA") . "</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>";
}
?>
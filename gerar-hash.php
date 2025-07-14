<?php
// gerar-hash-novo.php - Gerando hash com verificação de integridade
$senha = '123456';
$hash = password_hash($senha, PASSWORD_DEFAULT);

echo "Nova tentativa de hash:\n";
echo "Senha: {$senha}\n";
echo "Hash: {$hash}\n";
echo "Comprimento: " . strlen($hash) . " caracteres\n";
echo "Deve ser exatamente 60 caracteres\n";

// Vamos verificar se este hash funciona corretamente
if (password_verify($senha, $hash)) {
    echo "✅ Verificação passou - Este hash está correto\n";
} else {
    echo "❌ Verificação falhou - Problema no hash\n";
}
?>
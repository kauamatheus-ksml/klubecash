<?php
// gerar-hash.php - Arquivo temporário para gerar hash de senha
// DELETAR APÓS O USO por segurança

$senha = '123456';
$hash = password_hash($senha, PASSWORD_DEFAULT);

echo "Senha: {$senha}\n";
echo "Hash gerado: {$hash}\n";
echo "\nCopie o hash acima para usar nos comandos SQL.\n";
?>
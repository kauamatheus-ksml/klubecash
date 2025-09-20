<?php
/**
 * Script de teste para verificar e configurar o campo senat
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();

    echo "=== TESTE DO CAMPO SENAT ===\n\n";

    // 1. Verificar estrutura da tabela
    echo "1. Verificando estrutura da tabela usuarios:\n";
    $stmt = $db->prepare("DESCRIBE usuarios");
    $stmt->execute();
    $columns = $stmt->fetchAll();

    foreach ($columns as $column) {
        if ($column['Field'] === 'senat') {
            echo "✓ Campo 'senat' encontrado: {$column['Type']}, Default: {$column['Default']}\n";
            break;
        }
    }

    // 2. Listar todos os usuários e seus valores de senat
    echo "\n2. Usuários atuais na tabela:\n";
    $stmt = $db->prepare("SELECT id, nome, email, tipo, senat FROM usuarios ORDER BY id");
    $stmt->execute();
    $users = $stmt->fetchAll();

    foreach ($users as $user) {
        echo "ID: {$user['id']}, Nome: {$user['nome']}, Tipo: {$user['tipo']}, Senat: {$user['senat']}\n";
    }

    // 3. Atualizar um usuário para ter senat = 'sim' (para teste)
    echo "\n3. Deseja configurar algum usuário com senat='sim' para teste? (y/n): ";

    // Para automação, vou configurar o primeiro usuário com tipo 'loja' para ter senat='sim'
    $stmt = $db->prepare("SELECT id, nome FROM usuarios WHERE tipo IN ('loja', 'funcionario') LIMIT 1");
    $stmt->execute();
    $testUser = $stmt->fetch();

    if ($testUser) {
        $stmt = $db->prepare("UPDATE usuarios SET senat = 'sim' WHERE id = ?");
        $stmt->execute([$testUser['id']]);
        echo "✓ Usuário '{$testUser['nome']}' (ID: {$testUser['id']}) configurado com senat='sim' para teste\n";

        // Verificar a atualização
        $stmt = $db->prepare("SELECT senat FROM usuarios WHERE id = ?");
        $stmt->execute([$testUser['id']]);
        $result = $stmt->fetch();
        echo "✓ Confirmação: senat = '{$result['senat']}'\n";
    } else {
        echo "⚠ Nenhum usuário tipo 'loja' ou 'funcionario' encontrado para teste\n";
    }

    echo "\n=== TESTE CONCLUÍDO ===\n";
    echo "Agora faça login com o usuário configurado para ver o CSS azul no dashboard.\n";

} catch (PDOException $e) {
    echo "Erro no banco: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>
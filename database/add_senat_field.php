<?php
/**
 * Script para adicionar campo senat à tabela usuarios
 * Execute este arquivo para aplicar a alteração no banco
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();

    echo "Conectado ao banco de dados...\n";

    // Verificar se o campo já existe
    $checkStmt = $db->prepare("SHOW COLUMNS FROM usuarios LIKE 'senat'");
    $checkStmt->execute();

    if ($checkStmt->rowCount() > 0) {
        echo "O campo 'senat' já existe na tabela usuarios.\n";
        exit(0);
    }

    // Executar a alteração da tabela
    $sql = "ALTER TABLE usuarios ADD COLUMN senat ENUM('sim', 'nao') DEFAULT 'nao' NOT NULL AFTER tipo";
    $db->exec($sql);

    echo "Campo 'senat' adicionado com sucesso à tabela usuarios!\n";
    echo "- senat = 'sim': usuário logado como senat, deve usar CSS com sufixo _sest.css\n";
    echo "- senat = 'nao': usuário normal, deve usar CSS padrão\n";

} catch (PDOException $e) {
    echo "Erro ao executar alteração no banco: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    exit(1);
}
?>
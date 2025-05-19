<?php
// create_missing_table.php - Criar tabela transacoes_saldo_usado

require_once 'config/database.php';

echo "<pre>";
echo "=== CRIANDO TABELA TRANSACOES_SALDO_USADO ===\n\n";

try {
    $db = Database::getConnection();
    
    // Verificar se a tabela já existe
    $checkTable = $db->query("SHOW TABLES LIKE 'transacoes_saldo_usado'");
    
    if ($checkTable->rowCount() == 0) {
        echo "Criando tabela transacoes_saldo_usado...\n";
        
        $createTable = "
        CREATE TABLE transacoes_saldo_usado (
            id INT AUTO_INCREMENT PRIMARY KEY,
            transacao_id INT NOT NULL,
            usuario_id INT NOT NULL,
            loja_id INT NOT NULL,
            valor_usado DECIMAL(10,2) NOT NULL,
            data_uso TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_transacao (transacao_id),
            INDEX idx_usuario_loja (usuario_id, loja_id),
            FOREIGN KEY (transacao_id) REFERENCES transacoes_cashback(id) ON DELETE CASCADE
        )";
        
        $db->exec($createTable);
        echo "✓ Tabela criada com sucesso!\n";
    } else {
        echo "✓ Tabela já existe!\n";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
<?php
// Script para aplicar as configurações de cashback personalizado
// Execute este arquivo uma vez para adicionar os novos campos na tabela lojas

require_once '../config/database.php';

try {
    $db = Database::getConnection();
    
    echo "<h2>Aplicando Configurações de Cashback Personalizado</h2>";
    
    // 1. Verificar se os campos já existem
    $checkColumns = $db->query("SHOW COLUMNS FROM lojas LIKE 'porcentagem_cliente'");
    if ($checkColumns->rowCount() == 0) {
        echo "<p>Adicionando novos campos na tabela lojas...</p>";
        
        $alterTable = $db->exec("
            ALTER TABLE lojas 
            ADD COLUMN porcentagem_cliente DECIMAL(5,2) DEFAULT 5.00 COMMENT 'Percentual de cashback para o cliente (%)',
            ADD COLUMN porcentagem_admin DECIMAL(5,2) DEFAULT 5.00 COMMENT 'Percentual de comissão para o admin/plataforma (%)',
            ADD COLUMN cashback_ativo TINYINT(1) DEFAULT 1 COMMENT 'Se a loja oferece cashback (0=inativo, 1=ativo)',
            ADD COLUMN data_config_cashback TIMESTAMP NULL DEFAULT NULL COMMENT 'Data da última configuração de cashback'
        ");
        
        if ($alterTable !== false) {
            echo "<p style='color: green;'>✓ Novos campos adicionados com sucesso!</p>";
        } else {
            throw new Exception("Erro ao adicionar novos campos");
        }
    } else {
        echo "<p style='color: blue;'>→ Campos já existem na tabela</p>";
    }
    
    // 2. Atualizar registros existentes
    echo "<p>Atualizando registros existentes...</p>";
    
    $updateRecords = $db->exec("
        UPDATE lojas 
        SET 
            porcentagem_cliente = CASE 
                WHEN porcentagem_cashback > 0 THEN porcentagem_cashback / 2 
                ELSE 5.00 
            END,
            porcentagem_admin = CASE 
                WHEN porcentagem_cashback > 0 THEN porcentagem_cashback / 2 
                ELSE 5.00 
            END,
            cashback_ativo = 1,
            data_config_cashback = NOW()
        WHERE porcentagem_cliente IS NULL OR data_config_cashback IS NULL
    ");
    
    if ($updateRecords !== false) {
        echo "<p style='color: green;'>✓ {$updateRecords} registros atualizados!</p>";
    }
    
    // 3. Criar índice para performance
    try {
        $db->exec("CREATE INDEX idx_lojas_cashback_config ON lojas (cashback_ativo, porcentagem_cliente, porcentagem_admin)");
        echo "<p style='color: green;'>✓ Índice criado para melhor performance!</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "<p style='color: blue;'>→ Índice já existe</p>";
        } else {
            throw $e;
        }
    }
    
    // 4. Mostrar estatísticas finais
    $stats = $db->query("
        SELECT 
            COUNT(*) as total_lojas,
            AVG(porcentagem_cliente) as media_cliente,
            AVG(porcentagem_admin) as media_admin,
            SUM(CASE WHEN cashback_ativo = 1 THEN 1 ELSE 0 END) as lojas_ativas
        FROM lojas
    ")->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>Estatísticas Finais:</h3>";
    echo "<ul>";
    echo "<li><strong>Total de lojas:</strong> {$stats['total_lojas']}</li>";
    echo "<li><strong>Média cashback cliente:</strong> " . number_format($stats['media_cliente'], 2) . "%</li>";
    echo "<li><strong>Média comissão admin:</strong> " . number_format($stats['media_admin'], 2) . "%</li>";
    echo "<li><strong>Lojas com cashback ativo:</strong> {$stats['lojas_ativas']}</li>";
    echo "</ul>";
    
    echo "<h3 style='color: green;'>🎉 Configuração aplicada com sucesso!</h3>";
    echo "<p><strong>Próximos passos:</strong></p>";
    echo "<ul>";
    echo "<li>Acesse <a href='/admin/cashback-config'>/admin/cashback-config</a> para configurar percentuais personalizados</li>";
    echo "<li>Exemplo: Loja A com 8% cliente + 2% admin</li>";
    echo "<li>Exemplo: Loja B com 3% cliente + 7% admin</li>";
    echo "<li>As transações agora usarão os percentuais configurados por loja</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
    echo "<p>Verifique as permissões do banco de dados e tente novamente.</p>";
}
?>
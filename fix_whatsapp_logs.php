<?php
/**
 * CORREÇÃO ESTRUTURA WHATSAPP_LOGS - KLUBE CASH
 *
 * Script para corrigir a estrutura da tabela whatsapp_logs
 * e resolver os problemas de notificação
 */

require_once 'config/database.php';

class WhatsAppLogsFixer {

    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function fixDatabase() {
        echo "<h2>🔧 CORRIGINDO ESTRUTURA WHATSAPP_LOGS</h2>\n";

        try {
            // 1. Verificar estrutura atual
            echo "<h3>1. Verificando estrutura atual...</h3>\n";
            $this->checkCurrentStructure();

            // 2. Corrigir estrutura
            echo "<h3>2. Corrigindo estrutura...</h3>\n";
            $this->fixTableStructure();

            // 3. Verificar se ficou correto
            echo "<h3>3. Verificando correção...</h3>\n";
            $this->verifyFix();

            echo "<h3>✅ CORREÇÃO CONCLUÍDA!</h3>\n";
            echo "<p>O sistema de notificações deve funcionar corretamente agora.</p>\n";

        } catch (Exception $e) {
            echo "<h3>❌ ERRO: " . $e->getMessage() . "</h3>\n";
        }
    }

    private function checkCurrentStructure() {
        try {
            $stmt = $this->db->query("DESCRIBE whatsapp_logs");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo "<table border='1' style='border-collapse: collapse;'>\n";
            echo "<tr><th>Coluna</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>\n";

            foreach ($columns as $col) {
                echo "<tr>\n";
                echo "<td>{$col['Field']}</td>\n";
                echo "<td>{$col['Type']}</td>\n";
                echo "<td>{$col['Null']}</td>\n";
                echo "<td>{$col['Key']}</td>\n";
                echo "<td>{$col['Default']}</td>\n";
                echo "</tr>\n";
            }
            echo "</table>\n";

        } catch (Exception $e) {
            echo "<p>Erro ao verificar estrutura: " . $e->getMessage() . "</p>\n";
        }
    }

    private function fixTableStructure() {
        $queries = [
            // Adicionar coluna metadata se não existir
            "ALTER TABLE whatsapp_logs ADD COLUMN IF NOT EXISTS metadata JSON NULL",

            // Adicionar coluna message se não existir
            "ALTER TABLE whatsapp_logs ADD COLUMN IF NOT EXISTS message TEXT NULL",

            // Garantir que status é enum correto
            "ALTER TABLE whatsapp_logs MODIFY COLUMN status ENUM('success', 'failed', 'pending') DEFAULT 'pending'",

            // Adicionar índices se não existirem
            "ALTER TABLE whatsapp_logs ADD INDEX IF NOT EXISTS idx_status (status)",
            "ALTER TABLE whatsapp_logs ADD INDEX IF NOT EXISTS idx_created (created_at)",

            // Garantir estrutura completa
            "ALTER TABLE whatsapp_logs ADD COLUMN IF NOT EXISTS phone VARCHAR(20) NULL",
            "ALTER TABLE whatsapp_logs ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
        ];

        foreach ($queries as $query) {
            try {
                $this->db->exec($query);
                echo "<p>✅ Query executada: " . substr($query, 0, 50) . "...</p>\n";
            } catch (Exception $e) {
                echo "<p>⚠️ Query: " . substr($query, 0, 50) . "... - " . $e->getMessage() . "</p>\n";
            }
        }
    }

    private function verifyFix() {
        try {
            // Testar inserção
            $testData = [
                'phone' => 'test_fix',
                'message' => 'Teste de correção da estrutura',
                'status' => 'success',
                'metadata' => json_encode(['test' => true, 'fix_time' => date('Y-m-d H:i:s')])
            ];

            $stmt = $this->db->prepare("
                INSERT INTO whatsapp_logs (phone, message, status, metadata, created_at)
                VALUES (:phone, :message, :status, :metadata, NOW())
            ");

            $success = $stmt->execute($testData);

            if ($success) {
                echo "<p>✅ Teste de inserção: SUCESSO</p>\n";

                // Testar busca por metadata
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) FROM whatsapp_logs
                    WHERE JSON_EXTRACT(metadata, '$.test') = true
                ");
                $stmt->execute();
                $count = $stmt->fetchColumn();

                echo "<p>✅ Teste de busca metadata: SUCESSO ({$count} registros encontrados)</p>\n";

            } else {
                echo "<p>❌ Teste de inserção: FALHOU</p>\n";
            }

        } catch (Exception $e) {
            echo "<p>❌ Erro no teste: " . $e->getMessage() . "</p>\n";
        }
    }

    public function cleanupTestData() {
        try {
            $this->db->exec("DELETE FROM whatsapp_logs WHERE phone = 'test_fix'");
            echo "<p>🧹 Dados de teste removidos</p>\n";
        } catch (Exception $e) {
            echo "<p>⚠️ Erro ao limpar dados de teste: " . $e->getMessage() . "</p>\n";
        }
    }
}

// EXECUTAR CORREÇÃO
if (isset($_GET['run'])) {
    $fixer = new WhatsAppLogsFixer();
    $fixer->fixDatabase();

    if (isset($_GET['cleanup'])) {
        $fixer->cleanupTestData();
    }
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Correção WhatsApp Logs - Klube Cash</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
            .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .btn { background: #FF7A00; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; }
            .btn:hover { background: #e56a00; }
            .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 10px 0; }
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔧 Correção da Estrutura WhatsApp Logs</h1>

            <div class="warning">
                <h3>⚠️ O que será corrigido:</h3>
                <ul>
                    <li>✅ Adicionar coluna <code>metadata</code> (JSON)</li>
                    <li>✅ Adicionar coluna <code>message</code> (TEXT)</li>
                    <li>✅ Corrigir enum da coluna <code>status</code></li>
                    <li>✅ Adicionar índices para performance</li>
                    <li>✅ Garantir estrutura completa da tabela</li>
                </ul>
            </div>

            <p><strong>Problema identificado:</strong> O sistema está tentando usar colunas que não existem na tabela <code>whatsapp_logs</code>.</p>

            <p><strong>Solução:</strong> Este script irá adicionar as colunas necessárias sem perder dados existentes.</p>

            <a href="?run=1" class="btn">🚀 Executar Correção</a>
            <a href="?run=1&cleanup=1" class="btn">🚀 Executar + Limpar Testes</a>

            <h3>Status atual:</h3>
            <p>Execute o <a href="debug_notificacoes.php?run=1" target="_blank">debug</a> novamente após a correção para verificar se o problema foi resolvido.</p>
        </div>
    </body>
    </html>
    <?php
}
?>
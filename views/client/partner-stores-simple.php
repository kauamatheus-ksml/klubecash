<?php
// views/client/partner-stores-simple.php
// Versão simplificada para teste
require_once '../../config/database.php';
require_once '../../config/constants.php';
// DEBUG TEMPORÁRIO - REMOVER DEPOIS
$debug = true; // Mude para false depois de corrigir

if ($debug) {
    echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px; border: 1px solid #ccc;'>";
    echo "<h3>DEBUG - Informações do Sistema</h3>";
    
    try {
        $db = Database::getConnection();
        echo "✓ Conexão com banco OK<br>";
        
        // Verificar tabelas
        $tables = ['lojas', 'usuarios', 'transacoes_cashback', 'cashback_saldos', 'cashback_movimentacoes', 'favorites'];
        foreach ($tables as $table) {
            $result = $db->query("SHOW TABLES LIKE '$table'");
            if ($result->rowCount() > 0) {
                echo "✓ Tabela '$table' existe<br>";
            } else {
                echo "✗ Tabela '$table' NÃO existe<br>";
            }
        }
        
        // Verificar se há lojas
        $result = $db->query("SELECT COUNT(*) as total FROM lojas");
        $count = $result->fetch();
        echo "📊 Total de lojas: " . $count['total'] . "<br>";
        
        if ($count['total'] > 0) {
            $result = $db->query("SELECT COUNT(*) as aprovadas FROM lojas WHERE status = 'aprovado'");
            $aprovadas = $result->fetch();
            echo "📊 Lojas aprovadas: " . $aprovadas['aprovadas'] . "<br>";
        }
        
        // Verificar usuário atual
        echo "👤 User ID: " . $userId . "<br>";
        echo "👤 User Name: " . ($_SESSION['user_name'] ?? 'Não definido') . "<br>";
        
    } catch (Exception $e) {
        echo "❌ ERRO: " . $e->getMessage() . "<br>";
    }
    
    echo "</div>";
}

session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $db = Database::getConnection();
    
    // Query simples para testar
    $query = "SELECT * FROM lojas WHERE status = 'aprovado' ORDER BY nome_fantasia LIMIT 10";
    $stmt = $db->query($query);
    $lojas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h1>Lojas Parceiras (Versão Teste)</h1>";
    echo "<p>Total encontradas: " . count($lojas) . "</p>";
    
    foreach ($lojas as $loja) {
        echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>";
        echo "<h3>" . htmlspecialchars($loja['nome_fantasia']) . "</h3>";
        echo "<p>Categoria: " . htmlspecialchars($loja['categoria']) . "</p>";
        echo "<p>Cashback: " . $loja['porcentagem_cashback'] . "%</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>
<?php
// debug_stores.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

echo "<h1>🔍 Debug Klube Cash - Lojas</h1>";

try {
    echo "<p>✅ Sessão iniciada</p>";
    
    // Testar includes
    require_once 'config/database.php';
    echo "<p>✅ Database config carregado</p>";
    
    require_once 'config/constants.php';
    echo "<p>✅ Constants carregado</p>";
    
    require_once 'controllers/AuthController.php';
    echo "<p>✅ AuthController carregado</p>";
    
    // Testar conexão de banco
    $db = Database::getConnection();
    echo "<p>✅ Conexão com banco estabelecida</p>";
    
    // Testar se tabela lojas existe
    $stmt = $db->query("SHOW TABLES LIKE 'lojas'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Tabela 'lojas' existe</p>";
        
        // Testar contagem básica
        $countStmt = $db->query("SELECT COUNT(*) as total FROM lojas");
        $count = $countStmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>✅ Total de lojas: " . $count['total'] . "</p>";
    } else {
        echo "<p>❌ Tabela 'lojas' não existe!</p>";
    }
    
    // Testar autenticação
    if (isset($_SESSION['user_id'])) {
        echo "<p>✅ Usuário logado: ID " . $_SESSION['user_id'] . "</p>";
        echo "<p>✅ Tipo de usuário: " . ($_SESSION['user_type'] ?? 'não definido') . "</p>";
        
        if ($_SESSION['user_type'] === 'admin') {
            echo "<p>✅ É administrador</p>";
        } else {
            echo "<p>❌ Não é administrador</p>";
        }
    } else {
        echo "<p>❌ Usuário não está logado</p>";
    }
    
    // Testar query simples
    try {
        $simpleQuery = "SELECT id, nome_fantasia, status FROM lojas LIMIT 5";
        $stmt = $db->query($simpleQuery);
        $lojas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>✅ Query simples executada com sucesso</p>";
        echo "<pre>";
        print_r($lojas);
        echo "</pre>";
        
    } catch (Exception $e) {
        echo "<p>❌ Erro na query simples: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ ERRO: " . $e->getMessage() . "</p>";
    echo "<p>📁 Arquivo: " . $e->getFile() . "</p>";
    echo "<p>📍 Linha: " . $e->getLine() . "</p>";
}

echo "<hr>";
echo "<p><a href='/admin/lojas'>← Voltar para lojas</a></p>";
?>
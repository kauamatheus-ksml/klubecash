<?php
// debug.php - Arquivo temporário para diagnóstico
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>Teste de Diagnóstico</h1>";
echo "<p>PHP está funcionando corretamente.</p>";
echo "<p>Versão do PHP: " . phpversion() . "</p>";

// Testar se os arquivos de configuração carregam sem erro
echo "<h2>Testando arquivos de configuração...</h2>";

try {
    require_once './config/constants.php';
    echo "✅ constants.php carregado com sucesso<br>";
    
    // Verificar se as novas constantes foram definidas
    if (defined('USER_TYPE_EMPLOYEE')) {
        echo "✅ USER_TYPE_EMPLOYEE definido: " . USER_TYPE_EMPLOYEE . "<br>";
    } else {
        echo "❌ USER_TYPE_EMPLOYEE não definido<br>";
    }
    
    if (defined('EMPLOYEE_TYPE_MANAGER')) {
        echo "✅ Constantes de subtipos de funcionário definidas<br>";
    } else {
        echo "❌ Constantes de subtipos de funcionário não definidas<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Erro ao carregar constants.php: " . $e->getMessage() . "<br>";
}

try {
    require_once './config/database.php';
    echo "✅ database.php carregado com sucesso<br>";
} catch (Exception $e) {
    echo "❌ Erro ao carregar database.php: " . $e->getMessage() . "<br>";
}

try {
    require_once './controllers/AuthController.php';
    echo "✅ AuthController.php carregado com sucesso<br>";
} catch (Exception $e) {
    echo "❌ Erro ao carregar AuthController.php: " . $e->getMessage() . "<br>";
}

echo "<h2>Teste de conexão com banco de dados...</h2>";
try {
    $db = Database::getConnection();
    echo "✅ Conexão com banco estabelecida<br>";
    
    // Testar se a estrutura da tabela está correta
    $stmt = $db->query("DESCRIBE usuarios");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasSubtipo = false;
    $hasLojaVinculada = false;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'subtipo_funcionario') {
            $hasSubtipo = true;
            echo "✅ Coluna subtipo_funcionario encontrada<br>";
        }
        if ($column['Field'] === 'loja_vinculada_id') {
            $hasLojaVinculada = true;
            echo "✅ Coluna loja_vinculada_id encontrada<br>";
        }
    }
    
    if (!$hasSubtipo) {
        echo "❌ Coluna subtipo_funcionario não encontrada<br>";
    }
    if (!$hasLojaVinculada) {
        echo "❌ Coluna loja_vinculada_id não encontrada<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Erro de banco de dados: " . $e->getMessage() . "<br>";
}

echo "<p><strong>Diagnóstico concluído.</strong></p>";
?>
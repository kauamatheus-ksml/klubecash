<?php
// teste_admin_controller.php - Para testar o AdminController diretamente

session_start();
require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/AdminController.php';

echo "<h2>🧪 Teste AdminController - manageStoresWithBalance</h2>";

// Verificar se está logado como admin
if (!AuthController::isAuthenticated() || !AuthController::isAdmin()) {
    die('❌ Precisa estar logado como admin');
}

try {
    // Testar o método
    $result = AdminController::manageStoresWithBalance();
    
    if ($result['status']) {
        $stores = $result['data']['lojas'];
        $stats = $result['data']['estatisticas'];
        
        echo "<h3>✅ Método funcionou! Resultados:</h3>";
        
        // Estatísticas
        echo "<h4>📊 Estatísticas:</h4>";
        echo "<ul>";
        echo "<li>Total de lojas: <strong>{$stats['total_lojas']}</strong></li>";
        echo "<li>Lojas com saldo: <strong>{$stats['lojas_com_saldo']}</strong></li>";
        echo "<li>Total saldo acumulado: <strong>R$ " . number_format($stats['total_saldo_acumulado'], 2, ',', '.') . "</strong></li>";
        echo "<li>Total saldo usado: <strong>R$ " . number_format($stats['total_saldo_usado'], 2, ',', '.') . "</strong></li>";
        echo "</ul>";
        
        // Lojas
        echo "<h4>🏪 Lojas:</h4>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>Nome</th><th>Status</th><th>Clientes c/ Saldo</th><th>Total Saldo</th><th>Trans. Total</th><th>Trans. c/ Saldo</th></tr>";
        
        foreach ($stores as $store) {
            $cor = $store['total_saldo_clientes'] > 0 ? 'style="background: #e8f5e9;"' : '';
            echo "<tr $cor>";
            echo "<td>{$store['id']}</td>";
            echo "<td>{$store['nome_fantasia']}</td>";
            echo "<td>{$store['status']}</td>";
            echo "<td><strong>{$store['clientes_com_saldo']}</strong></td>";
            echo "<td><strong>R$ " . number_format($store['total_saldo_clientes'], 2, ',', '.') . "</strong></td>";
            echo "<td>{$store['total_transacoes']}</td>";
            echo "<td>{$store['transacoes_com_saldo']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<br><div style='background: #e8f5e9; padding: 15px; border-radius: 5px;'>";
        echo "<strong>✅ SUCESSO:</strong> O AdminController está retornando os dados corretos!<br>";
        echo "Se a tela ainda não mostra os dados certos, o problema está na view stores.php.";
        echo "</div>";
        
    } else {
        echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; color: #c62828;'>";
        echo "<strong>❌ ERRO:</strong> " . $result['message'];
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; color: #c62828;'>";
    echo "<strong>❌ EXCEÇÃO:</strong> " . $e->getMessage();
    echo "</div>";
}
?>

<br><br>
<a href="views/admin/stores.php" style="background: #FF7A00; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">← Testar na Tela de Lojas</a>
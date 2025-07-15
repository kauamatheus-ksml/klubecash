<?php
/**
 * Script de correção para redirecionamento de funcionários
 * USAR APENAS PARA DEBUG - REMOVER EM PRODUÇÃO
 */
session_start();
require_once 'config/database.php';
require_once 'config/constants.php';

echo "<h2>🔧 Verificação de Redirecionamento de Funcionários</h2>";

if (isset($_SESSION['user_id'])) {
    $userType = $_SESSION['user_type'] ?? 'não definido';
    $userId = $_SESSION['user_id'];
    $userName = $_SESSION['user_name'] ?? 'Nome não definido';
    
    echo "<div style='background: #f0f8ff; padding: 15px; margin: 10px 0; border-left: 4px solid #007cba;'>";
    echo "<h3>👤 Usuário Atual</h3>";
    echo "<p><strong>ID:</strong> {$userId}</p>";
    echo "<p><strong>Nome:</strong> {$userName}</p>";
    echo "<p><strong>Tipo:</strong> {$userType}</p>";
    
    if ($userType === USER_TYPE_EMPLOYEE) {
        $employeeSubtype = $_SESSION['employee_subtype'] ?? 'não definido';
        $storeId = $_SESSION['store_id'] ?? 'não definido';
        $storeName = $_SESSION['store_name'] ?? 'não definido';
        
        echo "<p><strong>Subtipo:</strong> {$employeeSubtype}</p>";
        echo "<p><strong>Loja ID:</strong> {$storeId}</p>";
        echo "<p><strong>Loja Nome:</strong> {$storeName}</p>";
        
        // Verificar URL correta
        $correctUrl = STORE_DASHBOARD_URL;
        echo "<p><strong>URL Correta:</strong> <a href='{$correctUrl}' style='color: green; font-weight: bold;'>{$correctUrl}</a></p>";
        
        // Verificar se está na URL errada
        $currentUrl = $_SERVER['REQUEST_URI'];
        if (strpos($currentUrl, '/cliente/') !== false) {
            echo "<div style='background: #ffe6e6; padding: 10px; border-left: 4px solid #dc3545; margin: 10px 0;'>";
            echo "<p style='color: #dc3545; font-weight: bold;'>❌ ERRO: Funcionário na área do cliente!</p>";
            echo "<p>URL atual: {$currentUrl}</p>";
            echo "<p><a href='{$correctUrl}' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ir para área correta</a></p>";
            echo "</div>";
        } else {
            echo "<p style='color: green; font-weight: bold;'>✅ Funcionário na área correta</p>";
        }
    }
    echo "</div>";
    
    // Teste de redirecionamento
    echo "<h3>🧪 Teste de Redirecionamento</h3>";
    echo "<button onclick='testRedirect()' style='background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Testar Redirecionamento</button>";
    
} else {
    echo "<p style='color: #dc3545;'>❌ Nenhum usuário logado. <a href='/login'>Faça login primeiro</a></p>";
}

// Verificar dados no banco
echo "<h3>💾 Verificação no Banco de Dados</h3>";
try {
    $db = Database::getConnection();
    
    // Buscar todos os funcionários
    $stmt = $db->prepare("
        SELECT u.id, u.nome, u.email, u.tipo, u.subtipo_funcionario, u.loja_vinculada_id, u.status,
               l.nome_fantasia, l.status as loja_status
        FROM usuarios u
        LEFT JOIN lojas l ON u.loja_vinculada_id = l.id
        WHERE u.tipo = ?
        ORDER BY u.nome
    ");
    $stmt->execute([USER_TYPE_EMPLOYEE]);
    
    $funcionarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($funcionarios) > 0) {
        echo "<table style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th style='border: 1px solid #ddd; padding: 8px;'>ID</th>";
        echo "<th style='border: 1px solid #ddd; padding: 8px;'>Nome</th>";
        echo "<th style='border: 1px solid #ddd; padding: 8px;'>Email</th>";
        echo "<th style='border: 1px solid #ddd; padding: 8px;'>Subtipo</th>";
        echo "<th style='border: 1px solid #ddd; padding: 8px;'>Loja</th>";
        echo "<th style='border: 1px solid #ddd; padding: 8px;'>Status</th>";
        echo "</tr>";
        
        foreach ($funcionarios as $func) {
            $statusColor = $func['status'] === 'ativo' ? 'green' : 'red';
            $lojaColor = $func['loja_status'] === 'aprovado' ? 'green' : 'orange';
            
            echo "<tr>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$func['id']}</td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$func['nome']}</td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$func['email']}</td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$func['subtipo_funcionario']}</td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px; color: {$lojaColor};'>{$func['nome_fantasia']}</td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px; color: {$statusColor};'>{$func['status']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>Nenhum funcionário encontrado no banco de dados.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro ao verificar banco: " . $e->getMessage() . "</p>";
}
?>

<script>
function testRedirect() {
    // Simular redirecionamento
    const userType = '<?php echo $_SESSION['user_type'] ?? ''; ?>';
    
    if (userType === 'funcionario') {
        const correctUrl = '<?php echo STORE_DASHBOARD_URL; ?>';
        if (confirm('Redirecionar para a área correta da loja?')) {
            window.location.href = correctUrl;
        }
    } else {
        alert('Teste aplicável apenas para funcionários');
    }
}

// Verificar URL atual
window.onload = function() {
    const currentPath = window.location.pathname;
    const userType = '<?php echo $_SESSION['user_type'] ?? ''; ?>';
    
    if (userType === 'funcionario' && currentPath.includes('/cliente/')) {
        const alertDiv = document.createElement('div');
        alertDiv.style.cssText = 'position: fixed; top: 10px; right: 10px; background: #dc3545; color: white; padding: 15px; border-radius: 5px; z-index: 9999;';
        alertDiv.innerHTML = '❌ ERRO: Funcionário na área errada!<br><button onclick="window.location.href=\'/store/dashboard\'" style="background: white; color: #dc3545; border: none; padding: 5px 10px; margin-top: 5px; border-radius: 3px; cursor: pointer;">Corrigir</button>';
        document.body.appendChild(alertDiv);
    }
};
</script>
<?php
session_start();
require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'controllers/AuthController.php';

echo "<h2>🧪 TESTE FINAL - CORREÇÃO DE REDIRECIONAMENTO</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    
    echo "<h3>🚀 Testando redirecionamento...</h3>";
    
    // Fazer logout primeiro
    session_destroy();
    session_start();
    
    // Fazer login
    $result = AuthController::login($email, $senha);
    
    if ($result['status']) {
        $userType = $_SESSION['user_type'] ?? '';
        
        echo "<div style='background: #d4edda; padding: 15px; color: #155724;'>";
        echo "<h4>✅ LOGIN BEM-SUCEDIDO</h4>";
        echo "<p><strong>Tipo de usuário:</strong> {$userType}</p>";
        
        // Determinar URL de redirecionamento
        $redirectUrl = '';
        if ($userType == 'admin') {
            $redirectUrl = ADMIN_DASHBOARD_URL;
        } else if ($userType == 'loja') {
            $redirectUrl = STORE_DASHBOARD_URL;
        } else if ($userType == 'funcionario') {
            $redirectUrl = STORE_DASHBOARD_URL; // CORREÇÃO!
        } else {
            $redirectUrl = CLIENT_DASHBOARD_URL;
        }
        
        echo "<h5>🔄 URL de Redirecionamento:</h5>";
        echo "<p><strong>Deveria ir para:</strong> <code>{$redirectUrl}</code></p>";
        
        if ($userType === 'funcionario') {
            echo "<div style='background: #cce5ff; padding: 10px; margin: 10px 0;'>";
            echo "<h6>✅ CORREÇÃO APLICADA:</h6>";
            echo "<p>Funcionário VAI para <strong>/store/dashboard/</strong> (mesmo que lojista)</p>";
            echo "</div>";
        }
        
        echo "<h5>🧪 Testar agora:</h5>";
        echo "<p><a href='{$redirectUrl}' style='background: #28a745; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px;'>🏠 Ir para Dashboard Correto</a></p>";
        
        echo "</div>";
        
        // Mostrar dados da sessão
        echo "<h4>📋 Dados da Sessão:</h4>";
        echo "<ul>";
        echo "<li><strong>User Type:</strong> {$userType}</li>";
        echo "<li><strong>Store ID:</strong> " . ($_SESSION['store_id'] ?? 'N/A') . "</li>";
        echo "<li><strong>Store Name:</strong> " . ($_SESSION['store_name'] ?? 'N/A') . "</li>";
        if ($userType === 'funcionario') {
            echo "<li><strong>Employee Subtype:</strong> " . ($_SESSION['employee_subtype'] ?? 'N/A') . "</li>";
        }
        echo "</ul>";
        
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; color: #721c24;'>";
        echo "<h4>❌ LOGIN FALHOU</h4>";
        echo "<p>{$result['message']}</p>";
        echo "</div>";
    }
    
} else {
    ?>
    <h3>🔐 Testar Login e Redirecionamento:</h3>
    <form method="POST">
        <p>Email: <input type="email" name="email" placeholder="email@funcionario.com" style="width: 300px; padding: 8px;"></p>
        <p>Senha: <input type="password" name="senha" placeholder="Digite a senha" style="width: 300px; padding: 8px;"></p>
        <p><button type="submit" style="background: #007bff; color: white; padding: 12px 20px; border: none; border-radius: 5px;">🚀 Testar Redirecionamento</button></p>
    </form>
    
    <h3>📋 Constantes de Redirecionamento:</h3>
    <ul>
        <li><strong>ADMIN_DASHBOARD_URL:</strong> <?php echo defined('ADMIN_DASHBOARD_URL') ? ADMIN_DASHBOARD_URL : 'NÃO DEFINIDA'; ?></li>
        <li><strong>STORE_DASHBOARD_URL:</strong> <?php echo defined('STORE_DASHBOARD_URL') ? STORE_DASHBOARD_URL : 'NÃO DEFINIDA'; ?></li>
        <li><strong>CLIENT_DASHBOARD_URL:</strong> <?php echo defined('CLIENT_DASHBOARD_URL') ? CLIENT_DASHBOARD_URL : 'NÃO DEFINIDA'; ?></li>
    </ul>
    <?php
}
?>
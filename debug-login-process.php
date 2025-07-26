<?php
session_start();
require_once 'config/database.php';
require_once 'controllers/AuthController.php';

echo "<h2>🔧 DEBUG DO PROCESSO DE LOGIN</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    
    echo "<h3>🚀 Executando login...</h3>";
    echo "Email: {$email}<br>";
    
    // CHAMAR O LOGIN E CAPTURAR RESULTADO
    $result = AuthController::login($email, $senha);
    
    echo "<h4>Resultado do login:</h4>";
    echo "<pre>" . print_r($result, true) . "</pre>";
    
    echo "<h4>Sessão após login:</h4>";
    echo "<pre>" . print_r($_SESSION, true) . "</pre>";
    
    if ($result['status']) {
        echo "<div style='background: #d4edda; padding: 15px; color: #155724;'>";
        echo "<h4>✅ LOGIN BEM-SUCEDIDO</h4>";
        echo "Store ID na sessão: " . ($_SESSION['store_id'] ?? 'NÃO DEFINIDO') . "<br>";
        echo "</div>";
        
        echo "<h4>🧪 Testes:</h4>";
        echo "<a href='debug-session.php' style='background: #28a745; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>🔍 Debug Sessão</a><br><br>";
        echo "<a href='store/dashboard/' style='background: #007bff; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>🏠 Dashboard</a>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; color: #721c24;'>";
        echo "<h4>❌ LOGIN FALHOU</h4>";
        echo "Erro: {$result['message']}";
        echo "</div>";
    }
} else {
    ?>
    <form method="POST">
        <h3>🔐 Teste de Login:</h3>
        <p>Email: <input type="email" name="email" value="kaua@syncholding.com.br" style="width: 300px; padding: 5px;"></p>
        <p>Senha: <input type="password" name="senha" placeholder="Digite sua senha" style="width: 300px; padding: 5px;"></p>
        <p><button type="submit" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px;">🚀 Testar Login</button></p>
    </form>
    <?php
}
?>
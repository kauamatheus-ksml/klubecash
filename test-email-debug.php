<?php
// debug-recuperacao.php - DEBUG ESPECÍFICO DA PÁGINA

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/AuthController.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    echo "<h1>🔍 Debug Recuperação</h1>";
    echo "<p>Email enviado: " . htmlspecialchars($email) . "</p>";
    
    $result = AuthController::recoverPassword($email);
    
    echo "<p>Resultado AuthController:</p>";
    echo "<pre>" . print_r($result, true) . "</pre>";
    
    exit;
}
?>

<form method="post">
    <h1>🧪 Teste Direto de Recuperação</h1>
    <p>Email: <input type="email" name="email" value="kauamatheus920@gmail.com" required></p>
    <button type="submit">Testar Recuperação</button>
</form>
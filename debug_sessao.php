<?php
/**
 * Debug da sessão para verificar se SEST SENAT está funcionando
 */
session_start();

echo "<h1>Debug da Sessão SEST SENAT</h1>";

// Verificar estado da sessão
echo "<h2>Estado da Sessão:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Verificar AuthController
require_once 'controllers/AuthController.php';

echo "<h2>AuthController:</h2>";
echo "isAuthenticated(): " . (AuthController::isAuthenticated() ? 'SIM' : 'NÃO') . "<br>";

if (AuthController::isAuthenticated()) {
    echo "isSestSenat(): " . (AuthController::isSestSenat() ? 'SIM' : 'NÃO') . "<br>";
    echo "getThemeClass(): '" . AuthController::getThemeClass() . "'<br>";
}

// Verificar usuário no banco
echo "<h2>Usuário no Banco:</h2>";
if (isset($_SESSION['user_id'])) {
    require_once 'config/database.php';
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT id, nome, email, senat FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo "Nome: " . $user['nome'] . "<br>";
        echo "Email: " . $user['email'] . "<br>";
        echo "SENAT: " . $user['senat'] . "<br>";
    } else {
        echo "Usuário não encontrado no banco<br>";
    }
} else {
    echo "Usuário não logado<br>";
}

// Se não estiver logado, simular login
if (!AuthController::isAuthenticated()) {
    echo "<h2>Simulando Login SEST SENAT:</h2>";
    $_SESSION['user_id'] = 55;
    $_SESSION['user_senat'] = 'Sim';
    $_SESSION['user_type'] = 'loja';
    $_SESSION['user_name'] = 'Matheus - SEST SENAT';

    echo "Sessão simulada criada!<br>";
    echo "isSestSenat(): " . (AuthController::isSestSenat() ? 'SIM' : 'NÃO') . "<br>";
    echo "getThemeClass(): '" . AuthController::getThemeClass() . "'<br>";
}

echo "<h2>Teste Visual:</h2>";
$themeClass = AuthController::getThemeClass();
?>

<div class="<?php echo $themeClass; ?>" style="padding: 20px; margin: 20px 0; border: 2px solid #ccc;">
    <p>Esta div tem a classe: "<?php echo $themeClass; ?>"</p>
    <button class="btn-primary" style="background: var(--primary-color, #FF7A00); padding: 10px 20px; border: none; color: white;">
        Botão de Teste
    </button>
</div>

<style>
:root {
    --primary-color: #FF7A00;
}

.sest-senat-theme {
    --primary-color: #1E3A8A !important;
}

.btn-primary {
    background: var(--primary-color) !important;
}
</style>

<link rel="stylesheet" href="assets/css/sest-senat-theme.css">

<p><strong>Instruções:</strong></p>
<ul>
    <li>Se o botão estiver AZUL, o tema está funcionando</li>
    <li>Se aparecer "SEST SENAT THEME ATIVO" no canto superior direito, perfeito!</li>
    <li>Faça login real com: kauamathes123487654@gmail.com</li>
</ul>

<p><a href="views/stores/dashboard.php">Ir para Dashboard da Loja</a></p>
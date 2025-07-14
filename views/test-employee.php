<?php
// views/test-employee.php - Página temporária para testar funcionários
require_once '../config/constants.php';
require_once '../controllers/AuthController.php';

session_start();

if (!AuthController::isAuthenticated()) {
    header('Location: ' . LOGIN_URL);
    exit;
}

if (!AuthController::isEmployee()) {
    echo "<h1>Acesso negado - apenas funcionários</h1>";
    exit;
}

$subtypeDisplay = AuthController::getEmployeeSubtypeDisplay();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste - Funcionário Logado</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .success-message { 
            background: #d4edda; 
            color: #155724; 
            padding: 15px; 
            border-radius: 5px; 
            margin: 20px 0;
            border-left: 5px solid #28a745;
        }
        .info-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <h1>🎉 Teste de Login de Funcionário</h1>
    
    <div class="success-message">
        <strong>Login realizado com sucesso!</strong><br>
        Você logou como funcionário: <strong><?php echo $subtypeDisplay; ?></strong>
    </div>
    
    <div class="info-box">
        <h3>Informações da Sessão:</h3>
        <p><strong>Nome:</strong> <?php echo $_SESSION['user_name']; ?></p>
        <p><strong>Email:</strong> <?php echo $_SESSION['user_email']; ?></p>
        <p><strong>Tipo:</strong> <?php echo $_SESSION['user_type']; ?></p>
        <p><strong>Subtipo:</strong> <?php echo $_SESSION['employee_subtype']; ?></p>
        <?php if (isset($_SESSION['store_name'])): ?>
        <p><strong>Loja:</strong> <?php echo $_SESSION['store_name']; ?></p>
        <?php endif; ?>
    </div>
    
    <div class="info-box">
        <h3>Próximos Passos:</h3>
        <p>✅ Sistema de login para funcionários implementado</p>
        <p>⏳ Dashboard específico para funcionários (em desenvolvimento)</p>
        <p>⏳ Sistema de permissões por subtipo (em desenvolvimento)</p>
    </div>
    
    <p><a href="<?php echo LOGIN_URL; ?>?action=logout">Fazer Logout</a></p>
</body>
</html>
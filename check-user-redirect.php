
<?php
session_start();

// Verificar se é funcionário sendo redirecionado errado
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'funcionario') {
    // FORÇAR redirecionamento correto
    header('Location: /store/dashboard/');
    exit;
}

// Se não for funcionário, continuar normalmente
header('Location: /views/client/dashboard.php');
exit;
?>
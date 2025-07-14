<?php
// fix-sessao.php - Forçar definição das variáveis de sessão
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'funcionario') {
    echo "Você precisa estar logado como funcionário primeiro.";
    exit;
}

// Buscar dados completos do usuário logado
require_once './config/database.php';

try {
    $db = Database::getConnection();
    $stmt = $db->prepare("
        SELECT u.*, l.nome_fantasia as loja_nome
        FROM usuarios u
        LEFT JOIN lojas l ON u.loja_vinculada_id = l.id
        WHERE u.id = ? AND u.tipo = 'funcionario'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Definir variáveis de sessão que estão faltando
        $_SESSION['employee_subtype'] = $user['subtipo_funcionario'];
        $_SESSION['store_id'] = $user['loja_vinculada_id'];
        $_SESSION['store_name'] = $user['loja_nome'];
        
        // Definir permissões
        switch($user['subtipo_funcionario']) {
            case 'gerente':
                $_SESSION['employee_permissions'] = ['dashboard', 'transacoes', 'funcionarios', 'relatorios'];
                break;
            case 'financeiro':
                $_SESSION['employee_permissions'] = ['dashboard', 'comissoes', 'pagamentos', 'relatorios'];
                break;
            case 'vendedor':
                $_SESSION['employee_permissions'] = ['dashboard', 'transacoes'];
                break;
            default:
                $_SESSION['employee_permissions'] = ['dashboard'];
        }
        
        echo "<h1>✅ Sessão Corrigida!</h1>";
        echo "<p>Variáveis de funcionário definidas com sucesso.</p>";
        echo "<p><a href='debug-sessao.php'>Verificar sessão</a></p>";
        echo "<p><a href='index.php'>Ir para página inicial</a></p>";
    } else {
        echo "Erro: Dados do funcionário não encontrados.";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>
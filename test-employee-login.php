<?php
session_start();
require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'controllers/AuthController.php';

echo "<h2>🧪 TESTE DE LOGIN - FUNCIONÁRIO</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    
    echo "<h3>🚀 Testando login de funcionário...</h3>";
    echo "<p><strong>Email:</strong> {$email}</p>";
    
    // Chamar login
    $result = AuthController::login($email, $senha);
    
    echo "<h4>📋 Resultado do Login:</h4>";
    echo "<pre style='background: #f8f9fa; padding: 15px;'>";
    print_r($result);
    echo "</pre>";
    
    echo "<h4>📋 Sessão Após Login:</h4>";
    echo "<pre style='background: #f8f9fa; padding: 15px;'>";
    print_r($_SESSION);
    echo "</pre>";
    
    if ($result['status']) {
        $userType = $_SESSION['user_type'] ?? '';
        
        echo "<div style='background: #d4edda; padding: 15px; color: #155724;'>";
        echo "<h4>✅ LOGIN BEM-SUCEDIDO</h4>";
        echo "<p><strong>Tipo de usuário:</strong> {$userType}</p>";
        
        if ($userType === 'funcionario') {
            echo "<h5>🔍 Verificações para funcionário:</h5>";
            echo "<ul>";
            echo "<li><strong>Store ID:</strong> " . ($_SESSION['store_id'] ?? 'NÃO DEFINIDO') . "</li>";
            echo "<li><strong>Store Name:</strong> " . ($_SESSION['store_name'] ?? 'NÃO DEFINIDO') . "</li>";
            echo "<li><strong>Loja Vinculada ID:</strong> " . ($_SESSION['loja_vinculada_id'] ?? 'NÃO DEFINIDO') . "</li>";
            echo "<li><strong>Subtipo:</strong> " . ($_SESSION['employee_subtype'] ?? 'NÃO DEFINIDO') . "</li>";
            echo "</ul>";
            
            // Teste de redirecionamento
            echo "<h5>🔄 Teste de Redirecionamento:</h5>";
            if (isset($_SESSION['store_id']) && !empty($_SESSION['store_id'])) {
                echo "<p>✅ Funcionário DEVE ir para: <strong>/store/dashboard/</strong></p>";
                echo "<p><a href='store/dashboard/' style='background: #28a745; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>🏠 Ir para Dashboard</a></p>";
            } else {
                echo "<p>❌ Funcionário NÃO pode acessar área da loja (store_id não definido)</p>";
            }
        }
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; color: #721c24;'>";
        echo "<h4>❌ LOGIN FALHOU</h4>";
        echo "<p>{$result['message']}</p>";
        echo "</div>";
    }
    
} else {
    // Listar funcionários para teste
    try {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT u.id, u.nome, u.email, u.tipo, u.loja_vinculada_id, u.subtipo_funcionario,
                   l.nome_fantasia as loja_nome, l.status as loja_status
            FROM usuarios u
            LEFT JOIN lojas l ON u.loja_vinculada_id = l.id
            WHERE u.tipo = 'funcionario'
            ORDER BY u.nome
        ");
        
        echo "<h3>📋 Funcionários no Sistema:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Nome</th><th>Email</th><th>Loja</th><th>Status Loja</th><th>Subtipo</th></tr>";
        
        while ($funcionario = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $lojaStatus = $funcionario['loja_status'] ?? 'Sem loja';
            $statusColor = ($lojaStatus === 'aprovado') ? '#d4edda' : '#f8d7da';
            
            echo "<tr style='background: {$statusColor};'>";
            echo "<td>{$funcionario['id']}</td>";
            echo "<td>{$funcionario['nome']}</td>";
            echo "<td>{$funcionario['email']}</td>";
            echo "<td>" . ($funcionario['loja_nome'] ?? 'Não vinculado') . "</td>";
            echo "<td>{$lojaStatus}</td>";
            echo "<td>" . ($funcionario['subtipo_funcionario'] ?? 'Não definido') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } catch (Exception $e) {
        echo "<p>Erro ao buscar funcionários: {$e->getMessage()}</p>";
    }
    
    ?>
    <form method="POST" style="margin-top: 20px;">
        <h3>🔐 Testar Login de Funcionário:</h3>
        <p>Email: <input type="email" name="email" placeholder="email@funcionario.com" style="width: 300px; padding: 8px;"></p>
        <p>Senha: <input type="password" name="senha" placeholder="Digite a senha" style="width: 300px; padding: 8px;"></p>
        <p><button type="submit" style="background: #007bff; color: white; padding: 12px 20px; border: none; border-radius: 5px;">🚀 Testar Login</button></p>
    </form>
    <?php
}
?>
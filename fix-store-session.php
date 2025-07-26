<?php
session_start();
require_once 'config/database.php';
require_once 'config/constants.php';

echo "<h2>🔧 CORREÇÃO IMEDIATA DE SESSÃO DA LOJA</h2>";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'loja') {
    echo "❌ Usuário não é lojista ou não está logado";
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $db = Database::getConnection();
    
    // Buscar dados da loja
    echo "<p>🔍 Buscando loja para usuário ID: {$userId}</p>";
    
    $stmt = $db->prepare("SELECT * FROM lojas WHERE usuario_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $loja = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($loja) {
        // CORRIGIR SESSÃO IMEDIATAMENTE
        $_SESSION['store_id'] = intval($loja['id']);
        $_SESSION['store_name'] = $loja['nome_fantasia'];
        $_SESSION['loja_vinculada_id'] = intval($loja['id']);
        
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; color: #155724; margin: 10px 0;'>";
        echo "<h3>✅ SESSÃO CORRIGIDA COM SUCESSO!</h3>";
        echo "Store ID: <strong>{$loja['id']}</strong><br>";
        echo "Nome: <strong>{$loja['nome_fantasia']}</strong><br>";
        echo "Status: <strong>{$loja['status']}</strong><br>";
        echo "Email: <strong>{$loja['email']}</strong>";
        echo "</div>";
        
        echo "<h3>🧪 TESTE AGORA:</h3>";
        echo "<a href='debug-final.php' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>🔍 Verificar Debug</a><br><br>";
        echo "<a href='store/dashboard/' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>🏠 Dashboard Loja</a><br><br>";
        echo "<a href='views/stores/funcionarios.php' style='background: #6f42c1; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>👥 Funcionários</a>";
        
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24; margin: 10px 0;'>";
        echo "<h3>❌ PROBLEMA ENCONTRADO</h3>";
        echo "Usuário lojista {$userId} NÃO tem loja associada no banco!";
        echo "</div>";
        
        // Verificar se existem lojas sem usuário associado
        echo "<h4>🔍 Verificando lojas disponíveis:</h4>";
        $allStores = $db->query("SELECT id, nome_fantasia, email, usuario_id FROM lojas ORDER BY id DESC LIMIT 10");
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Nome</th><th>Email</th><th>User ID</th></tr>";
        while($store = $allStores->fetch(PDO::FETCH_ASSOC)) {
            $highlight = ($store['usuario_id'] == $userId) ? "background: #fff3cd;" : "";
            echo "<tr style='{$highlight}'>";
            echo "<td>{$store['id']}</td>";
            echo "<td>{$store['nome_fantasia']}</td>";
            echo "<td>{$store['email']}</td>";
            echo "<td>" . ($store['usuario_id'] ?: 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Buscar dados do usuário
        echo "<h4>🔍 Dados do usuário:</h4>";
        $userStmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
        $userStmt->execute([$userId]);
        $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Email do usuário:</strong> {$userData['email']}</p>";
        
        // Verificar se existe loja com mesmo email
        $emailStmt = $db->prepare("SELECT * FROM lojas WHERE email = ?");
        $emailStmt->execute([$userData['email']]);
        $lojaEmail = $emailStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($lojaEmail) {
            echo "<div style='background: #cce5ff; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h4>💡 SOLUÇÃO ENCONTRADA!</h4>";
            echo "Existe uma loja com o mesmo email: <strong>{$lojaEmail['nome_fantasia']}</strong> (ID: {$lojaEmail['id']})<br>";
            echo "Status: {$lojaEmail['status']}<br><br>";
            
            if ($lojaEmail['usuario_id'] === null || $lojaEmail['usuario_id'] == 0) {
                echo "<p><strong>🔗 Vou associar automaticamente...</strong></p>";
                
                $linkStmt = $db->prepare("UPDATE lojas SET usuario_id = ? WHERE id = ?");
                if ($linkStmt->execute([$userId, $lojaEmail['id']])) {
                    echo "<p style='color: green;'>✅ Loja associada com sucesso!</p>";
                    echo "<a href='fix-store-session.php' style='background: #28a745; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>🔄 Recarregar e Testar</a>";
                } else {
                    echo "<p style='color: red;'>❌ Erro ao associar loja</p>";
                }
            } else {
                echo "<p style='color: orange;'>⚠️ Loja já está associada ao usuário {$lojaEmail['usuario_id']}</p>";
            }
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24;'>";
    echo "<h3>❌ ERRO:</h3>";
    echo $e->getMessage();
    echo "</div>";
}
?>
<?php
session_start();
require_once 'config/database.php';

echo "<h2>🔧 CORREÇÃO FINAL DE SESSÃO</h2>";

if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'loja') {
    $userId = $_SESSION['user_id'];
    
    try {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM lojas WHERE usuario_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $store = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($store) {
            // CORRIGIR SESSÃO DEFINITIVAMENTE
            $_SESSION['store_id'] = $store['id'];
            $_SESSION['store_name'] = $store['nome_fantasia'];
            $_SESSION['loja_vinculada_id'] = $store['id'];
            
            echo "✅ Sessão corrigida!<br>";
            echo "Store ID: {$store['id']}<br>";
            echo "Nome: {$store['nome_fantasia']}<br>";
            echo "Status: {$store['status']}<br><br>";
            
            echo "<strong>🧪 TESTE AGORA:</strong><br>";
            echo "<a href='debug-session.php' style='background: #4CAF50; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>🔍 Verificar Sessão</a><br><br>";
            echo "<a href='store/dashboard/' style='background: #2196F3; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>🏠 Dashboard Loja</a>";
            
        } else {
            echo "❌ ERRO: Loja não encontrada para usuário {$userId}<br>";
            
            // Verificar se existem lojas
            $allStores = $db->query("SELECT id, nome_fantasia, usuario_id, email FROM lojas LIMIT 5");
            echo "<h4>Lojas no sistema:</h4>";
            while($row = $allStores->fetch(PDO::FETCH_ASSOC)) {
                echo "ID: {$row['id']}, Nome: {$row['nome_fantasia']}, User ID: {$row['usuario_id']}<br>";
            }
        }
        
    } catch (Exception $e) {
        echo "❌ Erro: " . $e->getMessage();
    }
} else {
    echo "❌ Usuário não é lojista<br>";
    echo "Tipo atual: " . ($_SESSION['user_type'] ?? 'não definido');
}
?>
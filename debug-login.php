<?php
session_start();
require_once 'config/database.php';

echo "<h2>🔍 DEBUG DETALHADO DO LOGIN</h2>";

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    echo "<h3>1. Verificando usuário no banco:</h3>";
    
    $db = Database::getConnection();
    $userStmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<strong>Dados do usuário:</strong><br>";
    foreach ($user as $key => $value) {
        echo "{$key}: {$value}<br>";
    }
    
    echo "<h3>2. Verificando lojas:</h3>";
    
    // Por usuario_id
    $storeStmt = $db->prepare("SELECT * FROM lojas WHERE usuario_id = ?");
    $storeStmt->execute([$userId]);
    $storeByUser = $storeStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<strong>Loja por usuario_id:</strong><br>";
    if ($storeByUser) {
        foreach ($storeByUser as $key => $value) {
            echo "{$key}: {$value}<br>";
        }
    } else {
        echo "❌ Nenhuma loja encontrada por usuario_id<br>";
    }
    
    // Por email
    $emailStmt = $db->prepare("SELECT * FROM lojas WHERE email = ?");
    $emailStmt->execute([$user['email']]);
    $storeByEmail = $emailStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<br><strong>Loja por email:</strong><br>";
    if ($storeByEmail) {
        foreach ($storeByEmail as $key => $value) {
            echo "{$key}: {$value}<br>";
        }
    } else {
        echo "❌ Nenhuma loja encontrada por email<br>";
    }
    
    echo "<h3>3. Todas as lojas no sistema:</h3>";
    $allStores = $db->query("SELECT id, nome_fantasia, email, usuario_id, status FROM lojas ORDER BY id");
    while($store = $allStores->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$store['id']}, Nome: {$store['nome_fantasia']}, Email: {$store['email']}, User: {$store['usuario_id']}, Status: {$store['status']}<br>";
    }
}
?>
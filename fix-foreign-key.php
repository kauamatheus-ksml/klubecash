<?php
// fix-foreign-key.php
require_once 'config/database.php';

echo "<h1>🔧 Corrigindo Foreign Key - Cliente Visitante</h1>";

try {
    $db = Database::getConnection();
    
    echo "<h2>1. Verificando lojas existentes:</h2>";
    
    $stmt = $db->query("SELECT id, nome_fantasia, usuario_id FROM lojas ORDER BY id");
    $lojas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($lojas)) {
        echo "<div style='color: red;'>❌ PROBLEMA: Nenhuma loja encontrada na tabela 'lojas'!</div>";
        echo "<p>Vamos criar uma loja de teste...</p>";
        
        // Verificar se existe um usuário loja
        $userStmt = $db->query("SELECT id, nome FROM usuarios WHERE tipo = 'loja' LIMIT 1");
        $userLoja = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userLoja) {
            echo "<p>✅ Usuário loja encontrado: " . $userLoja['nome'] . " (ID: " . $userLoja['id'] . ")</p>";
            
            // Criar loja teste
            $insertLoja = $db->prepare("
                INSERT INTO lojas (nome_fantasia, razao_social, usuario_id, porcentagem_cashback, status, data_criacao)
                VALUES ('Loja Teste', 'Loja Teste Ltda', ?, 10.00, 'aprovado', NOW())
            ");
            $insertLoja->execute([$userLoja['id']]);
            $lojaId = $db->lastInsertId();
            
            echo "<div style='color: green;'>✅ Loja teste criada com ID: $lojaId</div>";
            
            // Recarregar lojas
            $stmt = $db->query("SELECT id, nome_fantasia, usuario_id FROM lojas ORDER BY id");
            $lojas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } else {
            echo "<div style='color: red;'>❌ Nenhum usuário tipo 'loja' encontrado!</div>";
            echo "<p>Vamos criar um usuário loja...</p>";
            
            // Criar usuário loja
            $insertUser = $db->prepare("
                INSERT INTO usuarios (nome, email, senha_hash, tipo, status, data_criacao)
                VALUES ('Loja Teste', 'loja.teste@klubecash.com', ?, 'loja', 'ativo', NOW())
            ");
            $insertUser->execute([password_hash('123456', PASSWORD_DEFAULT)]);
            $userId = $db->lastInsertId();
            
            echo "<div style='color: green;'>✅ Usuário loja criado com ID: $userId</div>";
            
            // Criar loja
            $insertLoja = $db->prepare("
                INSERT INTO lojas (nome_fantasia, razao_social, usuario_id, porcentagem_cashback, status, data_criacao)
                VALUES ('Loja Teste', 'Loja Teste Ltda', ?, 10.00, 'aprovado', NOW())
            ");
            $insertLoja->execute([$userId]);
            $lojaId = $db->lastInsertId();
            
            echo "<div style='color: green;'>✅ Loja teste criada com ID: $lojaId</div>";
            
            // Recarregar lojas
            $stmt = $db->query("SELECT id, nome_fantasia, usuario_id FROM lojas ORDER BY id");
            $lojas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
    if (!empty($lojas)) {
        echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID Loja</th><th>Nome</th><th>ID Usuário</th></tr>";
        foreach ($lojas as $loja) {
            echo "<tr>";
            echo "<td>{$loja['id']}</td>";
            echo "<td>{$loja['nome_fantasia']}</td>";
            echo "<td>{$loja['usuario_id']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        $primeiraLoja = $lojas[0];
        echo "<div style='color: green; background: #e8f5e8; padding: 10px; margin: 10px 0;'>";
        echo "✅ <strong>SOLUÇÃO:</strong> Use store_id = <strong>{$primeiraLoja['id']}</strong> nos testes!";
        echo "</div>";
        
        echo "<h2>2. Testando criação com loja válida:</h2>";
        
        // Testar criação com loja válida
        $nome = 'Teste Corrigido ' . date('H:i:s');
        $telefone = '11' . rand(100000000, 999999999);
        $lojaValida = $primeiraLoja['id'];
        
        echo "<p>📤 Testando: nome=$nome, telefone=$telefone, loja_id=$lojaValida</p>";
        
        // Gerar email fictício único
        $emailFicticio = 'visitante_' . $telefone . '_loja_' . $lojaValida . '@klubecash.local';
        
        $insertStmt = $db->prepare("
            INSERT INTO usuarios (nome, email, telefone, tipo, tipo_cliente, loja_criadora_id, status, data_criacao)
            VALUES (?, ?, ?, 'cliente', 'visitante', ?, 'ativo', NOW())
        ");
        
        $result = $insertStmt->execute([
            $nome,
            $emailFicticio,
            $telefone,
            $lojaValida
        ]);
        
        if ($result) {
            $clientId = $db->lastInsertId();
            echo "<div style='color: green;'>✅ SUCESSO! Cliente visitante criado com ID: $clientId</div>";
            
            // Verificar
            $verifyStmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
            $verifyStmt->execute([$clientId]);
            $cliente = $verifyStmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<h3>Cliente criado:</h3>";
            echo "<pre style='background: #f0f0f0; padding: 10px;'>";
            echo "ID: {$cliente['id']}\n";
            echo "Nome: {$cliente['nome']}\n";
            echo "Telefone: {$cliente['telefone']}\n";
            echo "Tipo Cliente: {$cliente['tipo_cliente']}\n";
            echo "Loja Criadora: {$cliente['loja_criadora_id']}\n";
            echo "Status: {$cliente['status']}\n";
            echo "</pre>";
            
        } else {
            echo "<div style='color: red;'>❌ Falha: " . print_r($insertStmt->errorInfo(), true) . "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Erro: " . $e->getMessage() . "</div>";
}

echo "<h2>3. Código corrigido para API:</h2>";
echo "<p>Atualize a API para usar uma loja válida. Veja o código abaixo:</p>";

?>

<div style="background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #007bff;">
<h3>📝 Correção para a API:</h3>
<p>No arquivo <code>api/store-client-search.php</code>, substitua a parte de validação do store_id por:</p>
<pre style="background: #282c34; color: #abb2bf; padding: 15px; border-radius: 5px; overflow-x: auto;">
// ANTES DE USAR O STORE_ID, VERIFICAR SE A LOJA EXISTE
try {
    $checkStoreStmt = $db->prepare("SELECT id FROM lojas WHERE id = ?");
    $checkStoreStmt->execute([$storeId]);
    
    if ($checkStoreStmt->rowCount() == 0) {
        // Se a loja não existe, pegar a primeira loja disponível
        $firstStoreStmt = $db->query("SELECT id FROM lojas WHERE status = 'aprovado' LIMIT 1");
        $firstStore = $firstStoreStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($firstStore) {
            $storeId = $firstStore['id'];
            error_log("API CLIENT SEARCH - Store ID ajustado para: $storeId");
        } else {
            echo json_encode(['status' => false, 'message' => 'Nenhuma loja ativa encontrada']);
            exit;
        }
    }
} catch (Exception $e) {
    error_log("API CLIENT SEARCH - Erro ao verificar loja: " . $e->getMessage());
}
</pre>
</div>